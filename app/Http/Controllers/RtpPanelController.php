<?php

namespace App\Http\Controllers;

use App\Libraries\OddsSimulator;
use App\Libraries\RtpConfig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PGVirtual\Core\Models\Channel;
use PGVirtual\Core\Models\Event;

class RtpPanelController extends Controller
{
    public function index()
    {
        $games = [];
        foreach (RtpConfig::games() as $game) {
            $defaults = RtpConfig::defaults($game->name);
            if (empty($defaults)) {
                continue; // libreria del gioco non installata
            }
            $games[] = [
                'id' => $game->id,
                'label' => ucfirst($game->name) . ' ' . $game->racers,
                'defaults' => $defaults,
                'overrides' => RtpConfig::overrides((int) $game->id),
            ];
        }

        $markets = [];
        foreach (array_keys(RtpConfig::MARKET_PROPERTIES) as $market) {
            [$min, $max] = RtpConfig::bounds($market);
            $markets[$market] = ['min' => $min, 'max' => $max];
        }

        return view('rtp', ['games' => $games, 'markets' => $markets]);
    }

    /**
     * Simulatore quote: nessuna scrittura, calcola le quote con la libreria del gioco
     * confrontando RTP di default e RTP simulati (precompilati con quelli salvati).
     */
    public function simulator(Request $request)
    {
        $games = collect(RtpConfig::games())->filter(fn ($g) => !empty(RtpConfig::defaults($g->name)))->values();
        if ($games->isEmpty()) {
            abort(404);
        }
        $game = $games->firstWhere('id', (int) $request->query('game_id')) ?? $games->first();
        $numRacers = (int) $game->racers;
        $defaults = RtpConfig::defaults($game->name);
        $saved = RtpConfig::overrides((int) $game->id);

        // RTP simulati: dalla query se il form è stato inviato, altrimenti quelli salvati
        $submitted = $request->has('rtp');
        $rtpInput = [];
        $rtpValues = [];
        $errors = [];
        foreach (array_keys(RtpConfig::MARKET_PROPERTIES) as $market) {
            $raw = $submitted
                ? (string) ($request->query('rtp')[$market] ?? '')
                : (isset($saved[$market]) ? number_format($saved[$market] / 10000, 2, '.', '') : '');
            $rtpInput[$market] = $raw;
            [$units, $error] = RtpConfig::parsePercent($market, $raw);
            if ($error) {
                $errors["rtp.{$market}"] = $error;
            } elseif ($units !== null) {
                $rtpValues[$market] = $units;
            }
        }

        // eventi vicini all'ora attuale per la sorgente "evento reale"
        $channelIds = Channel::where('game_id', $game->id)->pluck('id');
        $events = Event::whereIn('channel_id', $channelIds)
            ->where('time', '>=', Carbon::now()->subHour())
            ->orderBy('time')->limit(30)->get();
        if ($events->isEmpty()) {
            $events = Event::whereIn('channel_id', $channelIds)->orderBy('time', 'desc')->limit(30)->get();
        }

        $source = in_array($request->query('source'), ['event', 'rng', 'manual'], true) ? $request->query('source') : 'rng';
        $manual = (string) $request->query('manual', '');
        $eventId = (int) $request->query('event_id', 0);
        $probs = null;
        $sourceLabel = '';

        switch ($source) {
            case 'event':
                $event = $events->firstWhere('id', $eventId) ?? Event::whereIn('channel_id', $channelIds)->find($eventId) ?? $events->first();
                if ($event) {
                    $eventId = $event->id;
                    $probs = OddsSimulator::probsFromEvent($event);
                    $sourceLabel = 'Evento ' . $event->int_pal_id . '/' . $event->int_event_id . ' delle ' . Carbon::parse($event->time)->format('d/m H:i');
                } else {
                    $errors['event_id'] = 'Nessun evento disponibile per questo gioco';
                }
                break;
            case 'manual':
                $result = OddsSimulator::probsFromManual($manual, $numRacers);
                if (is_string($result)) {
                    $errors['manual'] = $result;
                } else {
                    $probs = $result;
                    $sourceLabel = 'Probabilità inserite a mano';
                }
                break;
            default:
                $probs = OddsSimulator::probsFromRng($game->name, $numRacers);
                if ($probs === null) {
                    $errors['source'] = 'Generatore RNG della libreria non disponibile in questo ambiente (python/drs)';
                } else {
                    $sourceLabel = 'Corsa generata con l\'RNG della libreria';
                }
        }

        $simulation = null;
        if ($probs && count($probs) === $numRacers && empty(array_filter(array_keys($errors), fn ($k) => str_starts_with($k, 'rtp.')))) {
            $simulation = OddsSimulator::simulate($game->name, $probs, RtpConfig::toOddsOptions($rtpValues));
        }

        $markets = [];
        foreach (array_keys(RtpConfig::MARKET_PROPERTIES) as $market) {
            [$min, $max] = RtpConfig::bounds($market);
            $markets[$market] = ['min' => $min, 'max' => $max, 'default' => $defaults[$market] ?? null];
        }

        return view('rtp-simulator', [
            'games' => $games,
            'game' => $game,
            'markets' => $markets,
            'rtpInput' => $rtpInput,
            'events' => $events,
            'eventId' => $eventId,
            'source' => $source,
            'manual' => $probs ? implode(' ', array_map(fn ($p) => number_format($p / 10000, 2, '.', ''), $probs)) : $manual,
            'sourceLabel' => $sourceLabel,
            'simErrors' => $errors,
            'simulation' => $simulation,
        ]);
    }

    public function update(Request $request, int $gameId)
    {
        $game = collect(RtpConfig::games())->firstWhere('id', $gameId);
        if (!$game || empty(RtpConfig::defaults($game->name))) {
            abort(404);
        }

        $input = $request->input('rtp', []);
        $values = [];
        $errors = [];
        foreach (array_keys(RtpConfig::MARKET_PROPERTIES) as $market) {
            [$units, $error] = RtpConfig::parsePercent($market, $input[$market] ?? '');
            if ($error) {
                $errors[$market] = $error;
                continue;
            }
            $values[$market] = $units; // null = default libreria
        }

        if ($errors) {
            return redirect()->route('rtp.index')
                ->withErrors(array_combine(array_map(fn ($m) => "{$gameId}.{$m}", array_keys($errors)), $errors))
                ->withInput();
        }

        $before = RtpConfig::overrides($gameId);
        RtpConfig::save($gameId, $values);
        $after = RtpConfig::overrides($gameId);

        Log::channel('daily')->info('RTP aggiornato', [
            'game_id' => $gameId,
            'user' => $request->getUser(),
            'ip' => $request->ip(),
            'before' => $before,
            'after' => $after,
        ]);

        return redirect()->route('rtp.index')->with('status', "RTP salvati per {$game->name} {$game->racers}. Valgono per i prossimi eventi inviati a Isibet.");
    }
}
