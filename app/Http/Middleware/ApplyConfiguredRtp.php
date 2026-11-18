<?php

namespace App\Http\Middleware;

use App\Libraries\RtpConfig;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PGVirtual\Core\Models\Channel;
use PGVirtual\Core\Models\Event;
use PGVirtual\Core\Models\Game;

/**
 * Le API evento della libreria (event/info, event/results) calcolano le quote con
 * l'RTP di default. Questo middleware, senza toccare la libreria, ricalcola le stesse
 * quote con l'RTP dell'evento (snapshot inviato a Isibet o configurazione corrente)
 * e sostituisce i valori nella risposta, così il viewer mostra le quote realmente in vendita.
 */
class ApplyConfiguredRtp
{
    public const ROUTES = ['event.info', 'viewer.event.info', 'event.results'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $route = $request->route();
        if (!$route || !in_array($route->getName(), self::ROUTES, true)) {
            return $response;
        }

        try {
            $content = $response->getContent();
            if (!is_string($content) || $content === '') {
                return $response;
            }
            // decode a oggetti per preservare {} vs [] nella risposta originale
            $data = json_decode($content);
            if (!is_object($data)) {
                return $response;
            }

            $changed = false;
            foreach ([$data, $data->current ?? null, $data->previous ?? null] as $node) {
                if (is_object($node) && isset($node->odds, $node->int_pal_id, $node->int_event_id) && is_object($node->odds)) {
                    $changed = $this->rewriteNode($node) || $changed;
                }
            }

            if ($changed) {
                $response->setContent(json_encode($data, JSON_PRETTY_PRINT));
            }
        } catch (\Throwable $e) {
            Log::error('ApplyConfiguredRtp: ' . $e->getMessage());
        }

        return $response;
    }

    /* Le quote di un evento non cambiano: il viewer le richiede di continuo, quindi le teniamo in cache */
    public const CACHE_TTL = 1800;

    private function rewriteNode(object $node): bool
    {
        // la versione cambia a ogni salvataggio dal pannello, così gli eventi non pubblicati si aggiornano subito
        $key = 'rtp:odds:' . RtpConfig::version() . ':' . $node->int_pal_id . ':' . $node->int_event_id;
        $odds = Cache::remember($key, self::CACHE_TTL, function () use ($node) {
            return $this->resolveOdds($node->int_pal_id, $node->int_event_id) ?? false;
        });
        if ($odds === false) {
            return false; // default: la libreria ha già calcolato le quote giuste
        }
        return $this->replaceLeaves($node->odds, $odds);
    }

    /** Quote ricalcolate con l'RTP dell'evento, oppure null se valgono i default. */
    private function resolveOdds($palimpsestId, $eventId): ?array
    {
        $event = Event::where('int_pal_id', $palimpsestId)->where('int_event_id', $eventId)->first();
        if (!$event) {
            return null;
        }
        $channel = Channel::find($event->channel_id);
        $game = $channel ? Game::find($channel->game_id) : null;
        if (!$game) {
            return null;
        }

        $rtpOptions = RtpConfig::oddsOptionsForEvent($event, (int) $game->id);
        if (empty($rtpOptions)) {
            return null;
        }
        return $this->computeOdds($event, $game->name, $rtpOptions);
    }

    /** Stesso calcolo di EventController::odds() della libreria, con le opzioni rtp* */
    private function computeOdds(Event $event, string $gameName, array $rtpOptions): array
    {
        $opts = $event->getOpts();
        $winnerProbs = array_map(function ($x) {
            return $x['chance'];
        }, $opts['racers']);

        $oddsClass = RtpConfig::oddsControllerClass($gameName);
        $marketsClass = RtpConfig::marketsClass($gameName);
        $controller = new $oddsClass(array_merge(['winnerProbs' => $winnerProbs], $rtpOptions));

        $out = [];
        foreach ($marketsClass::MARKETS as $market) {
            $out[$market] = $controller->{'get' . ucfirst($market) . 'Odds'}();
        }
        return $out;
    }

    /**
     * Sostituisce ogni foglia di $target con il valore allo stesso percorso in $source.
     * Funziona anche sui sottoinsiemi (event/results contiene solo le quote vincenti).
     */
    private function replaceLeaves(&$target, $source): bool
    {
        if (!is_array($source)) {
            return false;
        }
        $changed = false;
        foreach ($target as $key => &$value) {
            if (!array_key_exists($key, $source)) {
                continue;
            }
            if (is_object($value) || is_array($value)) {
                $changed = $this->replaceLeaves($value, $source[$key]) || $changed;
            } elseif (is_scalar($source[$key]) && $value !== $source[$key]) {
                $value = $source[$key];
                $changed = true;
            }
        }
        unset($value);
        return $changed;
    }
}
