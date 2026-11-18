<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PGVirtual\Core\Models\Channel;
use PGVirtual\Core\Models\Event;
use PGVirtual\Core\Models\Game;

/**
 * Il viewer avvia il video dell'evento precedente quando il countdown vale current.game_duration,
 * quindi game_duration deve essere il tempo dal PRECEDENTE evento dello stesso controller.
 *
 * Dogs e horses inviano già events.durata (calcolata dal precedente nel pannello palinsesto).
 * La libreria harness (sotto hash, non modificabile) invia invece il tempo fino al SUCCESSIVO evento:
 * questo middleware, solo per harness, sostituisce il valore con events.durata.
 */
class FixViewerGameDuration
{
    public const ROUTES = ['viewer.event.info'];
    public const GAMES = ['harness'];
    public const CACHE_TTL = 1800;

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
            $current = is_object($data) ? ($data->current ?? null) : null;
            if (!is_object($current) || !isset($current->int_pal_id, $current->int_event_id) || !property_exists($current, 'game_duration')) {
                return $response;
            }

            $key = 'viewer:duration:' . $current->int_pal_id . ':' . $current->int_event_id;
            $duration = Cache::remember($key, self::CACHE_TTL, function () use ($current) {
                return $this->durationFor($current->int_pal_id, $current->int_event_id) ?? false;
            });

            if ($duration !== false && (int) $current->game_duration !== $duration) {
                $current->game_duration = $duration;
                $response->setContent(json_encode($data, JSON_PRETTY_PRINT));
            }
        } catch (\Throwable $e) {
            Log::error('FixViewerGameDuration: ' . $e->getMessage());
        }

        return $response;
    }

    /** events.durata per gli eventi dei giochi da correggere, altrimenti null (risposta lasciata com'è). */
    private function durationFor($palimpsestId, $eventId): ?int
    {
        $event = Event::where('int_pal_id', $palimpsestId)->where('int_event_id', $eventId)->first(['channel_id', 'durata']);
        if (!$event || $event->durata === null || (int) $event->durata <= 0) {
            return null;
        }
        $channel = Channel::find($event->channel_id);
        $game = $channel ? Game::find($channel->game_id) : null;
        if (!$game || !in_array(strtolower($game->name), self::GAMES, true)) {
            return null;
        }
        return (int) $event->durata;
    }
}
