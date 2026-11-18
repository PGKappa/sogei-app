<?php

namespace App\Libraries;

use App\Models\EventRtp;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PGVirtual\Core\Models\Channel;
use PGVirtual\Core\Models\Configuration;
use PGVirtual\Core\Models\Event;
use PGVirtual\Core\Models\EventExt;
use PGVirtual\Core\Models\Game;
use Throwable;

/**
 * Operazioni di manutenzione del pannello tecnico. Riusano le stesse funzioni dei worker
 * (pg:publishEventsToIsibet, pg:generateResults, pg:syncResults) per i casi che i worker
 * non hanno gestito: palinsesto non ricevuto, eventi scaduti non esitati, risultati non inviati.
 *
 * Ogni operazione restituisce [event_id => [bool ok, string messaggio]].
 */
class MaintenanceOps
{
    /** Margine dopo l'orario di partenza oltre il quale un evento ancora "creato" è considerato scaduto */
    public const EXPIRED_AFTER_SECONDS = 120;

    /** Il publisher invia solo eventi che partono tra 20 minuti o più: sotto questa soglia vanno inviati a mano */
    public const PUBLISH_MIN_MINUTES = 20;

    public const STATUS_LABELS = [
        Event::STATUS_CREATED => 'Creato',
        Event::STATUS_SYNCED => 'Sincronizzato',
        Event::STATUS_GENERATED_ARRIVAL => 'Risultato generato',
        Event::STATUS_VOID => 'Annullato',
        Event::PAYMENT_STATUS_DONE => 'Pagato',
        Event::ISIBET_STATUS_DONE => 'Risultato inviato',
        9 => 'Chiusura palinsesto (non inviato a Isibet)',
    ];

    /* ---------------------------------------------------------------- elenchi */

    /** Inviati a Isibet ma senza codici ADM (palinsesto/evento) */
    public static function missingPalinsesto(Carbon $from): Collection
    {
        return Event::whereNotNull('sent_on')->whereNull('ext_pal_id')
            ->where('status', Event::STATUS_CREATED)
            ->where('time', '>=', $from)
            ->orderBy('time')->limit(500)->get();
    }

    /** Mai inviati e ormai sotto la soglia del publisher, ma non ancora partiti */
    public static function skippedNotSent(): Collection
    {
        return Event::whereNull('sent_on')->where('status', Event::STATUS_CREATED)
            ->where('time', '>', Carbon::now()->addMinute())
            ->where('time', '<=', Carbon::now()->addMinutes(self::PUBLISH_MIN_MINUTES))
            ->orderBy('time')->limit(500)->get();
    }

    /**
     * Scaduti senza risultato: ancora "creati" dopo la partenza, oppure annullati dal worker
     * pur essendo stati pubblicati su Isibet (per Isibet restano aperti finché non arriva l'esito).
     */
    public static function expiredUnsettled(Carbon $from): Collection
    {
        return Event::where('time', '>=', $from)
            ->where('time', '<', Carbon::now()->subSeconds(self::EXPIRED_AFTER_SECONDS))
            ->whereNull('isibet_result')
            ->where(function ($q) {
                $q->where('status', Event::STATUS_CREATED)
                    ->orWhere(function ($q) {
                        $q->where('status', Event::STATUS_VOID)->whereNotNull('sent_on');
                    });
            })
            ->orderBy('time')->limit(500)->get();
    }

    /** Risultato generato e gara finita, ma esito non ancora inviato a Isibet */
    public static function resultsNotSent(Carbon $from): Collection
    {
        return Event::where('status', Event::STATUS_GENERATED_ARRIVAL)
            ->whereNull('isibet_result')
            ->where('time', '>=', $from)
            ->where(function ($q) {
                $q->where('time_finish', '<', Carbon::now())
                    ->orWhere(function ($q) {
                        $q->whereNull('time_finish')->where('time', '<', Carbon::now()->subSeconds(self::EXPIRED_AFTER_SECONDS));
                    });
            })
            ->orderBy('time')->limit(500)->get();
    }

    /* ---------------------------------------------------------------- operazioni */

    /** Richiede a Isibet i codici ADM (EventCodes) degli eventi */
    public static function recoverPalinsesto(array $ids): array
    {
        $out = [];
        foreach (self::load($ids) as $event) {
            if (empty($event->sent_on)) {
                $out[$event->id] = [false, 'evento mai inviato a Isibet: prima va inviata l\'event card'];
                continue;
            }
            $channel = Channel::find($event->channel_id);
            self::sandbox(fn () => IsibetLib::getPalinsesto($event->id, $channel->isibet_controller_id));
            $event->refresh();
            $out[$event->id] = $event->ext_pal_id
                ? [true, "codici ricevuti: palinsesto {$event->ext_pal_id}, evento {$event->ext_event_id}"]
                : [false, 'Isibet non ha restituito i codici (vedi api_logs / laravel.log)'];
        }
        return $out;
    }

    /** Invia le event card come fa pg:publishEventsToIsibet (stesso RTP, stesso snapshot) */
    public static function sendEventCards(array $ids): array
    {
        $out = [];
        $isibetLog = optional(Configuration::where('key', 'ISIBET_LOG')->first())->getValue();

        foreach (self::load($ids)->groupBy('channel_id') as $channelId => $events) {
            $channel = Channel::find($channelId);
            $cards = ['virtualEvents' => []];
            $sendable = [];
            foreach ($events as $event) {
                if (!empty($event->sent_on)) {
                    $out[$event->id] = [false, 'già inviato il ' . $event->sent_on];
                    continue;
                }
                if ((int) $event->status !== Event::STATUS_CREATED || Carbon::parse($event->time)->isPast()) {
                    $out[$event->id] = [false, 'evento già partito o non più in stato creato'];
                    continue;
                }
                try {
                    $cards['virtualEvents'][] = self::sandbox(fn () => IsibetLib::create_event_card2($event));
                    $sendable[] = $event;
                } catch (Throwable $e) {
                    $out[$event->id] = [false, 'event card non generata: ' . $e->getMessage()];
                }
            }
            if (!$sendable) {
                continue;
            }

            $message = self::sandbox(fn () => IsibetLib::sendEventsCard($cards, $channel->isibet_controller_id));
            $sentOn = Carbon::now();
            foreach ($sendable as $i => $event) {
                if (!$message) {
                    $out[$event->id] = [false, 'invio a Isibet fallito (vedi laravel.log)'];
                    continue;
                }
                $event->sent_on = $sentOn;
                $event->save();
                try {
                    EventRtp::updateOrCreate(
                        ['event_id' => $event->id],
                        ['game_id' => $channel->game_id, 'rtp' => json_encode(IsibetLib::rtpUsedFor($event->id))]
                    );
                    if ($isibetLog == 'true') {
                        EventExt::create(['event_id' => $event->id, 'isibet_eventcard' => json_encode([
                            'transactionId' => $message['transactionId'],
                            'messageDateTime' => $message['messageDateTime'],
                            'eventcard' => $cards['virtualEvents'][$i],
                        ])]);
                    }
                } catch (Throwable $e) {
                    Log::error('Pannello tecnico: snapshot/log event card non salvato per evento ' . $event->id . ': ' . $e->getMessage());
                }
                $out[$event->id] = [true, 'event card inviata; i codici ADM arrivano col recupero palinsesto'];
            }
        }
        return $out;
    }

    /**
     * Esita un evento scaduto: genera il risultato con l'RNG come pg:generateResults
     * (risultato, video, storico, ritardatari, moltiplicatori) e, se richiesto, lo invia subito.
     */
    public static function settle(array $ids, bool $sendNow): array
    {
        $out = [];
        foreach (self::load($ids) as $event) {
            $status = (int) $event->status;
            $voidButSent = $status === Event::STATUS_VOID && !empty($event->sent_on);
            if (!($status === Event::STATUS_CREATED || $voidButSent) || !empty($event->isibet_result)) {
                $out[$event->id] = [false, 'stato ' . self::statusLabel($status) . ': niente da esitare'];
                continue;
            }
            if (Carbon::parse($event->time)->gt(Carbon::now()->subSeconds(self::EXPIRED_AFTER_SECONDS))) {
                $out[$event->id] = [false, 'evento non ancora scaduto: lo esita il worker'];
                continue;
            }
            [$controller, $game] = self::gameController($event);
            if (!$controller) {
                $out[$event->id] = [false, 'libreria del gioco non trovata'];
                continue;
            }
            try {
                self::sandbox(function () use ($controller, $game, $event) {
                    $controller::generateResult($event);
                    $event->save();
                    $controller::afterGenerateResult($event, $game->id);
                    $event->save();
                });
            } catch (Throwable $e) {
                $out[$event->id] = [false, 'generazione risultato fallita: ' . $e->getMessage()];
                continue;
            }
            $event->refresh();
            $order = implode('-', $event->getOpts()['arrivalOrder'] ?? []);
            $msg = "risultato generato (arrivo {$order})";

            if ($sendNow) {
                [$ok, $sendMsg] = self::sendResults([$event->id], false)[$event->id];
                $out[$event->id] = [$ok, $msg . '; ' . $sendMsg];
            } else {
                $out[$event->id] = [true, $msg . '; lo invierà il worker syncResults'];
            }
        }
        return $out;
    }

    /** Annulla eventi scaduti mai pubblicati (status → annullato), come fa pg:generateResults */
    public static function void(array $ids): array
    {
        $out = [];
        foreach (self::load($ids) as $event) {
            if ((int) $event->status !== Event::STATUS_CREATED) {
                $out[$event->id] = [false, 'stato ' . self::statusLabel($event->status) . ': non annullabile'];
                continue;
            }
            if (Carbon::parse($event->time)->isFuture()) {
                $out[$event->id] = [false, 'evento non ancora partito'];
                continue;
            }
            Event::where('id', $event->id)->where('status', Event::STATUS_CREATED)->update(['status' => Event::STATUS_VOID]);
            $out[$event->id] = [true, !empty($event->sent_on)
                ? 'annullato (attenzione: era stato pubblicato su Isibet)'
                : 'annullato'];
        }
        return $out;
    }

    /** Invia a Isibet (/Results) gli esiti già generati. Con $resend reinvia anche quelli già inviati. */
    public static function sendResults(array $ids, bool $resend): array
    {
        $out = [];
        foreach (self::load($ids) as $event) {
            $allowed = [Event::STATUS_GENERATED_ARRIVAL];
            if ($resend) {
                $allowed[] = Event::ISIBET_STATUS_DONE;
            }
            if (!in_array((int) $event->status, $allowed, true) || empty($event->getOpts()['arrivalOrder'])) {
                $out[$event->id] = [false, 'stato ' . self::statusLabel($event->status) . ': nessun risultato da inviare'];
                continue;
            }
            if (!$resend && !empty($event->isibet_result)) {
                $out[$event->id] = [false, 'già inviato il ' . $event->isibet_result];
                continue;
            }
            [$controller] = self::gameController($event);
            if (!$controller) {
                $out[$event->id] = [false, 'libreria del gioco non trovata'];
                continue;
            }
            // la libreria non segnala l'esito e può fallire dopo l'invio (log su event_ext):
            // l'invio è riuscito se isibet_result è stato aggiornato
            $before = $event->isibet_result;
            $error = null;
            try {
                self::sandbox(fn () => $controller::syncResults($event));
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
            $event->refresh();
            $sent = !empty($event->isibet_result) && $event->isibet_result !== $before;
            $out[$event->id] = $sent
                ? [true, 'risultato inviato a Isibet' . ($error ? " (avviso: {$error})" : '')]
                : [false, $error ? 'invio fallito: ' . $error : 'Isibet non ha confermato l\'invio (vedi laravel.log)'];
        }
        return $out;
    }

    /* ---------------------------------------------------------------- supporto */

    public static function statusLabel($status): string
    {
        return self::STATUS_LABELS[(int) $status] ?? (string) $status;
    }

    /** [classe EventController del gioco, Game] */
    public static function gameController(Event $event): array
    {
        $channel = Channel::find($event->channel_id);
        $game = $channel ? Game::find($channel->game_id) : null;
        if (!$game) {
            return [null, null];
        }
        $class = 'PGVirtual\\Game' . ucfirst(strtolower($game->name)) . '\\Controllers\\EventController';
        return [class_exists($class) ? $class : null, $game];
    }

    private static function load(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        return $ids ? Event::whereIn('id', $ids)->orderBy('time')->get() : collect();
    }

    /**
     * Le librerie scrivono file con percorso relativo (results.json, rng.log) e stampano a video:
     * eseguiamo dentro una cartella scrivibile e scartiamo l'output, come fanno i servizi runit.
     */
    private static function sandbox(callable $fn)
    {
        $dir = storage_path('services/manutenzione');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $cwd = getcwd();
        @set_time_limit(300);
        ob_start();
        try {
            if (is_dir($dir)) {
                chdir($dir);
            }
            return $fn();
        } finally {
            ob_end_clean();
            if ($cwd) {
                chdir($cwd);
            }
        }
    }
}
