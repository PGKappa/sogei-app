<?php

namespace App\Http\Controllers;

use App\Libraries\MaintenanceOps;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PGVirtual\Core\Models\Channel;
use PGVirtual\Core\Models\Event;
use PGVirtual\Core\Models\Game;

/**
 * Pannello tecnico (/tecnico): manutenzione eventi su DB e Isibet.
 * Non è collegato ai menu: accesso con TECH_PANEL_USER / TECH_PANEL_PASSWORD.
 */
class MaintenancePanelController extends Controller
{
    public const MAX_SELECTION = 50;

    public function index(Request $request)
    {
        $hours = max(1, min(168, (int) $request->query('ore', 24)));
        $from = Carbon::now()->subHours($hours);

        $lookup = null;
        if ($request->filled('evento')) {
            $lookup = Event::find((int) $request->query('evento'));
        }

        return view('tecnico', [
            'hours' => $hours,
            'missingPalinsesto' => MaintenanceOps::missingPalinsesto($from),
            'skipped' => MaintenanceOps::skippedNotSent(),
            'expired' => MaintenanceOps::expiredUnsettled($from),
            'resultsNotSent' => MaintenanceOps::resultsNotSent($from),
            'lookup' => $lookup,
            'lookupId' => $request->query('evento'),
            'channels' => $this->channelLabels(),
            'maxSelection' => self::MAX_SELECTION,
        ]);
    }

    public function run(Request $request, string $operation)
    {
        $ids = array_slice((array) $request->input('ids', []), 0, self::MAX_SELECTION);
        if (!$ids) {
            return back()->with('error', 'Nessun evento selezionato.');
        }

        // una sola operazione alla volta: evita doppi click e invii doppi
        $lock = Cache::lock('tecnico:operazione', 600);
        if (!$lock->get()) {
            return back()->with('error', 'C\'è già un\'operazione in corso, riprova tra poco.');
        }
        try {
            switch ($operation) {
                case 'palinsesto':
                    $title = 'Recupero palinsesto';
                    $results = MaintenanceOps::recoverPalinsesto($ids);
                    break;
                case 'invio-card':
                    $title = 'Invio event card';
                    $results = MaintenanceOps::sendEventCards($ids);
                    break;
                case 'esita':
                    $title = 'Esito eventi scaduti';
                    $results = MaintenanceOps::settle($ids, $request->boolean('invia_subito'));
                    break;
                case 'annulla':
                    $title = 'Annullamento eventi';
                    $results = MaintenanceOps::void($ids);
                    break;
                case 'risultati':
                    $title = 'Invio risultati';
                    $results = MaintenanceOps::sendResults($ids, $request->boolean('reinvio'));
                    break;
                default:
                    abort(404);
            }
        } finally {
            $lock->release();
        }

        Log::channel('daily')->info('Pannello tecnico: ' . $title, [
            'user' => $request->getUser(),
            'ip' => $request->ip(),
            'results' => $results,
        ]);

        return back()->with('report', ['title' => $title, 'results' => $results]);
    }

    /** channel_id => "Horses 6 (ctrl 84101)" */
    private function channelLabels(): array
    {
        $games = Game::all()->keyBy('id');
        $out = [];
        foreach (Channel::all() as $ch) {
            $g = $games[$ch->game_id] ?? null;
            $out[$ch->id] = ($g ? ucfirst($g->name) . ' ' . $g->racers : 'Canale ' . $ch->id);
        }
        return $out;
    }
}
