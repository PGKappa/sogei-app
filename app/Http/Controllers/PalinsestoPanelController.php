<?php

namespace App\Http\Controllers;

use App\Libraries\PalinsestoSchedule as Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PGVirtual\Core\Models\Channel;
use PGVirtual\Core\Models\Palinsesto;

/**
 * Gestione del palinsesto (tabella palinsestos) per controller Isibet.
 * Dopo ogni modifica le durate del controller vengono ricalcolate dal precedente evento dello stesso controller.
 */
class PalinsestoPanelController extends Controller
{
    public function index(Request $request)
    {
        $groups = Schedule::groups();
        if (empty($groups)) {
            abort(404);
        }
        $groupKey = array_key_exists((string) $request->query('controller'), $groups)
            ? (string) $request->query('controller')
            : array_key_first($groups);
        $channels = $groups[$groupKey];

        $items = Schedule::compute(Schedule::rowsOf($channels));
        $plan = Schedule::plan($channels);
        $diffs = $plan['durations'];

        $perChannel = [];
        foreach ($channels as $ch) {
            $chItems = collect($items)->filter(fn ($i) => $i['row']->channel_id == $ch->id);
            $perChannel[$ch->id] = [
                'count' => $chItems->count(),
                'first' => optional($chItems->first())['row']->orario ?? null,
                'last' => optional($chItems->last())['row']->orario ?? null,
            ];
        }

        return view('palinsesto', [
            'groups' => $groups,
            'groupKey' => $groupKey,
            'channels' => $channels,
            'items' => $items,
            'diffs' => $diffs,
            'moved' => $plan['moved'],
            'fixes' => $plan['fixes'],
            'perChannel' => $perChannel,
            'problems' => Schedule::validate(Schedule::rowsOf($channels), $channels),
        ]);
    }

    /** Aggiunge (id vuoto) o modifica una riga. */
    public function save(Request $request)
    {
        $channel = Channel::find((int) $request->input('channel_id'));
        if (!$channel) {
            return $this->fail($request, null, ['Canale non valido']);
        }
        $groupKey = Schedule::groupKey($channel);
        $channels = Schedule::channelsOf($groupKey);

        $id = (int) $request->input('id', 0);
        $existing = $id ? Palinsesto::find($id) : null;
        if ($id && (!$existing || !$channels->pluck('id')->contains($existing->channel_id))) {
            return $this->fail($request, $groupKey, ['Evento non trovato in questo controller']);
        }

        $errors = [];
        $orario = Schedule::normalizeTime((string) $request->input('orario'));
        if ($orario === null) {
            $errors[] = 'Orario non valido (formato HH:MM o HH:MM:SS)';
        }
        // l'operatore sceglie solo il 'P' (inizio giornata): 'U' e giorno dopo mezzanotte sono automatici
        $posizione = $request->input('posizione') === 'P' ? 'P' : 'I';
        $durataRaw = trim((string) $request->input('durata', ''));
        if ($durataRaw !== '' && !ctype_digit($durataRaw)) {
            $errors[] = 'Durata non valida (secondi interi)';
        }
        if ($errors) {
            return $this->fail($request, $groupKey, $errors);
        }

        $durata = $durataRaw !== '' ? (int) $durataRaw : ($existing->durata ?? Schedule::defaultDuration($channel));
        $new = [
            'id' => $existing->id ?? null,
            'channel_id' => $channel->id,
            'orario' => $orario,
            'giorno' => '0', // ricalcolato da normalize()
            'posizione' => $posizione,
            'durata' => $durata,
        ];

        $refDayStart = Schedule::dayStart(Schedule::rowsOf($channels)); // ordine della giornata prima della modifica

        // un nuovo 'P' sostituisce quello precedente dello stesso canale
        $demote = $posizione === 'P'
            ? Schedule::rowsOf($channels)->where('channel_id', $channel->id)->where('posizione', 'P')
                ->reject(fn ($r) => $existing && $r->id == $existing->id)->pluck('id')->all()
            : [];
        $rows = Schedule::rowsOf($channels)
            ->reject(fn ($r) => $existing && $r->id == $existing->id)
            ->map(fn ($r) => array_merge($r->getAttributes(), in_array($r->id, $demote) ? ['posizione' => 'I'] : []))
            ->push($new);
        Schedule::ensureStarts($rows, $refDayStart);
        if ($problems = Schedule::validate($rows, $channels)) {
            return $this->fail($request, $groupKey, $problems);
        }

        $before = $existing ? $existing->only(['channel_id', 'orario', 'giorno', 'posizione', 'durata']) : null;
        $changed = 0;
        DB::transaction(function () use ($existing, $new, $channels, $demote, $refDayStart, &$changed) {
            if ($demote) {
                Palinsesto::whereIn('id', $demote)->update(['posizione' => 'I']);
            }
            $row = $existing ?? new Palinsesto();
            $row->channel_id = $new['channel_id'];
            $row->orario = $new['orario'];
            $row->giorno = $new['giorno'];
            $row->posizione = $new['posizione'];
            $row->durata = $new['durata'];
            $row->save();
            Schedule::repairStarts($channels, $refDayStart);
            $changed = Schedule::normalize($channels);
        });

        $this->audit($request, $existing ? 'modifica' : 'aggiunta', $groupKey, ['before' => $before, 'after' => $new, 'durate_ricalcolate' => $changed]);

        $msg = ($existing ? 'Evento modificato' : 'Evento aggiunto') . ' (' . $channel->name . ' ' . substr($orario, 0, 5) . ').';
        return $this->done($groupKey, $msg . $this->durationsNote($changed));
    }

    /** Elimina le righe selezionate. */
    public function destroy(Request $request)
    {
        $groupKey = (string) $request->input('controller');
        $channels = Schedule::channelsOf($groupKey);
        $ids = array_map('intval', (array) $request->input('ids', []));
        if ($channels->isEmpty() || !$ids) {
            return $this->fail($request, $groupKey ?: null, ['Nessun evento selezionato']);
        }
        $rows = Palinsesto::whereIn('id', $ids)->whereIn('channel_id', $channels->pluck('id'))->get();
        if ($rows->isEmpty()) {
            return $this->fail($request, $groupKey, ['Nessun evento selezionato']);
        }

        $refDayStart = Schedule::dayStart(Schedule::rowsOf($channels)); // ordine della giornata prima dell'eliminazione
        $remaining = Schedule::rowsOf($channels)->reject(fn ($r) => $rows->pluck('id')->contains($r->id))
            ->map(fn ($r) => $r->getAttributes())->values();
        Schedule::ensureStarts($remaining, $refDayStart);
        if ($problems = Schedule::validate($remaining, $channels)) {
            return $this->fail($request, $groupKey, $problems);
        }

        $changed = 0;
        DB::transaction(function () use ($rows, $channels, $refDayStart, &$changed) {
            Palinsesto::whereIn('id', $rows->pluck('id'))->delete();
            // se è stato eliminato il P, diventa P il primo evento rimasto nell'ordine di prima
            Schedule::repairStarts($channels, $refDayStart);
            $changed = Schedule::normalize($channels);
        });

        $this->audit($request, 'eliminazione', $groupKey, ['deleted' => $rows->map->only(['id', 'channel_id', 'orario', 'giorno', 'posizione', 'durata'])->values()->all(), 'durate_ricalcolate' => $changed]);

        return $this->done($groupKey, 'Eliminati ' . $rows->count() . ' eventi.' . $this->durationsNote($changed));
    }

    /** Crea una sequenza di eventi 'I' a intervallo fisso per un canale. */
    public function generate(Request $request)
    {
        $channel = Channel::find((int) $request->input('channel_id'));
        if (!$channel) {
            return $this->fail($request, null, ['Canale non valido']);
        }
        $groupKey = Schedule::groupKey($channel);
        $channels = Schedule::channelsOf($groupKey);

        $from = Schedule::normalizeTime((string) $request->input('from'));
        $to = Schedule::normalizeTime((string) $request->input('to'));
        $step = (int) $request->input('step');
        $errors = [];
        if ($from === null || $to === null) {
            $errors[] = 'Orari "dalle"/"alle" non validi (HH:MM)';
        }
        if ($step < 1 || $step > 240) {
            $errors[] = 'Intervallo non valido (1–240 minuti)';
        }
        if ($errors) {
            return $this->fail($request, $groupKey, $errors);
        }

        $rows = Schedule::rowsOf($channels)->map(fn ($r) => $r->getAttributes());
        $taken = Schedule::compute($rows);
        $takenKeys = collect($taken)->pluck('key')->flip();
        $dayStart = Schedule::dayStart($rows);

        // "alle" prima di "dalle" = la sequenza passa la mezzanotte (es. 23:00 → 02:00)
        $start = Schedule::seconds($from);
        $end = Schedule::seconds($to) + (Schedule::seconds($to) < $start ? Schedule::DAY : 0);
        $new = [];
        $skipped = [];
        for ($t = $start; $t <= $end; $t += $step * 60) {
            $sec = $t % Schedule::DAY;
            $orario = sprintf('%02d:%02d:%02d', intdiv($sec, 3600), intdiv($sec % 3600, 60), $sec % 60);
            $key = $sec + ($sec < $dayStart ? Schedule::DAY : 0);
            if (isset($takenKeys[$key])) {
                $skipped[] = substr($orario, 0, 5);
                continue;
            }
            $takenKeys[$key] = true;
            $new[] = ['id' => null, 'channel_id' => $channel->id, 'orario' => $orario, 'giorno' => '0', 'posizione' => 'I', 'durata' => Schedule::defaultDuration($channel)];
        }
        if (!$new) {
            return $this->fail($request, $groupKey, ['Nessun evento da creare: tutti gli orari sono già occupati su questo controller']);
        }
        if ($problems = Schedule::validate($rows->concat($new), $channels)) {
            return $this->fail($request, $groupKey, $problems);
        }

        $changed = 0;
        DB::transaction(function () use ($new, $channels, $dayStart, &$changed) {
            $now = now();
            Palinsesto::insert(array_map(fn ($r) => array_merge(array_diff_key($r, ['id' => 0]), ['created_at' => $now, 'updated_at' => $now]), $new));
            Schedule::repairStarts($channels, $dayStart);
            $changed = Schedule::normalize($channels);
        });

        $this->audit($request, 'generazione', $groupKey, ['channel_id' => $channel->id, 'from' => $from, 'to' => $to, 'step' => $step, 'created' => count($new), 'skipped' => $skipped]);

        $msg = 'Creati ' . count($new) . ' eventi per ' . $channel->name . '.';
        if ($skipped) {
            $msg .= ' Saltati ' . count($skipped) . ' orari già occupati sul controller (' . implode(', ', array_slice($skipped, 0, 10)) . (count($skipped) > 10 ? '…' : '') . ').';
        }
        return $this->done($groupKey, $msg . $this->durationsNote($changed));
    }

    /** Sposta di un'ora avanti o indietro tutto il palinsesto del controller (o di tutti i controller). */
    public function shift(Request $request)
    {
        $groupKey = (string) $request->input('controller');
        $hours = (int) $request->input('hours');
        if (!in_array($hours, [-1, 1], true)) {
            return $this->fail($request, $groupKey ?: null, ['Spostamento non valido']);
        }
        $groups = Schedule::groups();
        $keys = $request->input('scope') === 'all' ? array_keys($groups) : [$groupKey];
        if (!array_key_exists($groupKey, $groups)) {
            abort(404);
        }

        $count = 0;
        foreach ($keys as $key) {
            $count += Schedule::shift($groups[$key], $hours);
        }
        $this->audit($request, 'spostamento orario', $groupKey, ['hours' => $hours, 'controllers' => $keys, 'rows' => $count]);

        $label = $hours > 0 ? 'avanti' : 'indietro';
        $where = count($keys) > 1 ? 'di tutti i controller' : 'del controller';
        return $this->done($groupKey, "Palinsesto {$where} spostato di un'ora {$label} ({$count} eventi). Ordine, durate, P e U invariati.");
    }

    /** Allinea le durate salvate a quelle calcolate (dati pregressi). */
    public function recalc(Request $request)
    {
        $groupKey = (string) $request->input('controller');
        $channels = Schedule::channelsOf($groupKey);
        if ($channels->isEmpty()) {
            abort(404);
        }
        $changed = Schedule::normalize($channels);
        $this->audit($request, 'ricalcolo durate', $groupKey, ['durate_ricalcolate' => $changed]);
        return $this->done($groupKey, $changed ? "Riallineate {$changed} righe (durate, U finale, giorno dopo mezzanotte, ordine)." : 'Il palinsesto era già allineato.');
    }

    private function durationsNote(int $changed): string
    {
        return $changed ? " Palinsesto riallineato (durate, U finale, giorno dopo mezzanotte, ordine): {$changed} righe." : '';
    }

    private function done(string $groupKey, string $message)
    {
        return redirect()->route('palinsesto.index', ['controller' => $groupKey])->with('status', $message);
    }

    private function fail(Request $request, ?string $groupKey, array $errors)
    {
        return redirect()->route('palinsesto.index', $groupKey ? ['controller' => $groupKey] : [])
            ->with('form_errors', $errors)
            ->withInput();
    }

    private function audit(Request $request, string $action, string $groupKey, array $context): void
    {
        Log::channel('daily')->info('Palinsesto: ' . $action, array_merge([
            'controller' => $groupKey,
            'user' => $request->getUser(),
            'ip' => $request->ip(),
        ], $context));
    }
}
