<?php

namespace App\Libraries;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PGVirtual\Core\Models\Channel;
use PGVirtual\Core\Models\Palinsesto;

/**
 * Logica del palinsesto (tabella `palinsestos`, letta da EventController::generateNew dei giochi).
 *
 * Le righe si raggruppano per controller Isibet: i canali con lo stesso isibet_controller_id
 * condividono un'unica sequenza di eventi. La durata di un evento è il tempo trascorso
 * dal precedente evento dello STESSO controller (qualunque canale), non dello stesso canale.
 * Il primo evento della giornata (nessun precedente) mantiene la durata impostata a mano.
 *
 * Ordine logico della giornata: inizia dall'orario del primo evento 'P' del controller;
 * gli orari precedenti (es. 00:00) sono dopo mezzanotte e vanno al giorno successivo.
 *
 * Posizione e giorno sono derivati dal pannello, l'operatore sceglie solo il 'P' (inizio giornata):
 *  - 'U' è sempre l'ultimo evento di ogni canale, gli altri sono 'I';
 *  - le librerie dei giochi (sotto hash) leggono le righe del canale in ordine di id e applicano
 *    giorno = 1 in modo cumulativo (anche alle righe successive). Per mandare al giorno dopo tutti
 *    gli eventi dopo mezzanotte si salva quindi giorno = 1 solo sul primo di essi, e le righe
 *    vengono tenute in ordine di id = ordine di orario (vedi plan()).
 */
class PalinsestoSchedule
{
    const POSITIONS = ['P' => 'Primo (inizio giornata)', 'I' => 'Intermedio', 'U' => 'Ultimo (automatico)'];
    const DAY = 86400;
    const MIN_DURATION = 30;
    const MAX_DURATION = 6 * 3600;

    /**
     * Controller Isibet => canali. I canali senza controller formano un gruppo a sé ("ch{id}").
     *
     * @return array<string, Collection>
     */
    public static function groups(): array
    {
        $groups = [];
        foreach (Channel::orderBy('id')->get() as $channel) {
            $groups[self::groupKey($channel)][] = $channel;
        }
        return array_map(fn ($c) => collect($c), $groups);
    }

    public static function groupKey($channel): string
    {
        return $channel->isibet_controller_id !== null && $channel->isibet_controller_id !== ''
            ? (string) $channel->isibet_controller_id
            : 'ch' . $channel->id;
    }

    public static function channelsOf(string $groupKey): Collection
    {
        return self::groups()[$groupKey] ?? collect();
    }

    public static function rowsOf(Collection $channels): Collection
    {
        return Palinsesto::whereIn('channel_id', $channels->pluck('id'))->get();
    }

    public static function normalizeTime(string $raw): ?string
    {
        $raw = trim($raw);
        if (!preg_match('/^([01]?\d|2[0-3])[:.]([0-5]\d)(?:[:.]([0-5]\d))?$/', $raw, $m)) {
            return null;
        }
        return sprintf('%02d:%02d:%02d', $m[1], $m[2], $m[3] ?? 0);
    }

    public static function seconds(string $time): int
    {
        [$h, $m, $s] = array_map('intval', explode(':', $time) + [0, 0, 0]);
        return $h * 3600 + $m * 60 + $s;
    }

    public static function formatDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }
        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    /**
     * Ordina le righe di un controller nella sequenza logica e calcola durata, posizione e giorno di ognuna.
     * Ogni elemento: row, key (secondi dall'inizio giornata), nextDay (dopo mezzanotte), computed (durata
     * dal precedente evento del controller; per il primo la durata salvata), prev, first,
     * posizione e giorno da salvare.
     *
     * @param iterable $rows oggetti/array con id, channel_id, orario, posizione, durata
     */
    public static function compute(iterable $rows): array
    {
        $rows = self::normalizeRows($rows);
        $dayStart = self::dayStart($rows);

        $items = $rows->map(function ($r) use ($dayStart) {
            $sec = self::seconds($r->orario);
            $nextDay = $sec < $dayStart; // dopo la mezzanotte UTC rispetto all'inizio giornata
            return ['row' => $r, 'key' => $dayStart + self::relative($r->orario, $dayStart), 'nextDay' => $nextDay];
        })->sort(function ($a, $b) {
            return [$a['key'], $a['row']->channel_id, $a['row']->id ?? PHP_INT_MAX]
                <=> [$b['key'], $b['row']->channel_id, $b['row']->id ?? PHP_INT_MAX];
        })->values()->all();

        // posizioni e giorno per canale
        $byChannel = [];
        foreach ($items as $i => $item) {
            $byChannel[$item['row']->channel_id][] = $i;
        }
        foreach ($byChannel as $indexes) {
            $pIndex = null;
            foreach ($indexes as $i) {
                if ($items[$i]['row']->posizione === 'P') {
                    $pIndex = $i;
                    break;
                }
            }
            $pIndex = $pIndex ?? $indexes[0]; // canale senza 'P': il primo evento apre la giornata
            $last = end($indexes);
            $nextDaySeen = false;
            foreach ($indexes as $i) {
                $items[$i]['posizione'] = $i === $pIndex ? 'P' : ($i === $last ? 'U' : 'I');
                $items[$i]['giorno'] = ($items[$i]['nextDay'] && !$nextDaySeen) ? '1' : '0';
                $nextDaySeen = $nextDaySeen || $items[$i]['nextDay'];
            }
        }

        $prev = null;
        foreach ($items as $i => $item) {
            $items[$i]['first'] = $prev === null;
            $items[$i]['prev'] = $prev ? $prev['row'] : null;
            $items[$i]['computed'] = $prev === null
                ? ($item['row']->durata !== null ? (int) $item['row']->durata : null)
                : $item['key'] - $prev['key'];
            $prev = $item;
        }
        return $items;
    }

    /**
     * Secondi dell'inizio giornata del controller.
     * Con i 'P': il P da cui partono tutti gli altri P più vicini in avanti (gestisce i P a cavallo della
     * mezzanotte UTC, es. dopo uno spostamento di un'ora). Senza 'P': l'orario dopo la pausa più lunga
     * del palinsesto (la notte), così gli eventi dopo mezzanotte non diventano mai l'inizio della giornata.
     */
    public static function dayStart(iterable $rows): int
    {
        $rows = self::normalizeRows($rows);
        $starts = $rows->where('posizione', 'P')->map(fn ($r) => self::seconds($r->orario))->unique()->values();
        if ($starts->isNotEmpty()) {
            return (int) $starts->sortBy(function ($p) use ($starts) {
                return $starts->map(fn ($q) => ($q - $p + self::DAY) % self::DAY)->max();
            })->first();
        }
        $secs = $rows->map(fn ($r) => self::seconds($r->orario))->unique()->sort()->values()->all();
        if (!$secs) {
            return 0;
        }
        $best = $secs[0];
        $bestGap = -1;
        foreach ($secs as $i => $sec) {
            $next = $secs[($i + 1) % count($secs)];
            $gap = ($next - $sec + self::DAY) % self::DAY ?: self::DAY;
            if ($gap > $bestGap) {
                $bestGap = $gap;
                $best = $next;
            }
        }
        return (int) $best;
    }

    /** Posizione di un orario nella giornata che inizia a $dayStart (0 = inizio giornata). */
    public static function relative(string $time, int $dayStart): int
    {
        return (self::seconds($time) - $dayStart + self::DAY) % self::DAY;
    }

    /**
     * Ai canali rimasti senza 'P' (es. P eliminato o trasformato in evento normale) assegna il 'P' al primo
     * evento nell'ordine della giornata PRIMA della modifica ($refDayStart), così l'ordine non cambia.
     * Restituisce gli id delle righe esistenti da promuovere a 'P' (le righe in $rows sono aggiornate).
     */
    public static function ensureStarts(Collection $rows, int $refDayStart): array
    {
        $promoted = [];
        foreach ($rows->groupBy(fn ($r) => is_array($r) ? $r['channel_id'] : $r->channel_id) as $channelRows) {
            $hasP = $channelRows->contains(fn ($r) => (is_array($r) ? $r['posizione'] : $r->posizione) === 'P');
            if ($hasP) {
                continue;
            }
            $first = $channelRows->sortBy(fn ($r) => self::relative(is_array($r) ? $r['orario'] : $r->orario, $refDayStart))->first();
            $id = is_array($first) ? ($first['id'] ?? null) : $first->id;
            $rows->transform(function ($r) use ($first) {
                if ($r === $first) {
                    is_array($r) ? $r['posizione'] = 'P' : $r->posizione = 'P';
                }
                return $r;
            });
            if ($id) {
                $promoted[] = (int) $id;
            }
        }
        return $promoted;
    }

    /** Come ensureStarts() ma direttamente su DB (dentro una transazione). */
    public static function repairStarts(Collection $channels, int $refDayStart): void
    {
        $rows = self::rowsOf($channels)->map(fn ($r) => (object) $r->getAttributes());
        $ids = self::ensureStarts($rows, $refDayStart);
        if ($ids) {
            Palinsesto::whereIn('id', $ids)->update(['posizione' => 'P']);
        }
    }

    /**
     * Sposta tutto il palinsesto dei canali di $hours ore (rotazione sulle 24 ore UTC):
     * ordine, durate, P e U restano gli stessi, cambia solo quali eventi cadono dopo la mezzanotte UTC.
     */
    public static function shift(Collection $channels, int $hours): int
    {
        $delta = $hours * 3600;
        $count = 0;
        DB::transaction(function () use ($channels, $delta, &$count) {
            $now = now();
            foreach (self::rowsOf($channels) as $row) {
                $sec = (self::seconds($row->orario) + $delta % self::DAY + self::DAY) % self::DAY;
                Palinsesto::where('id', $row->id)->update([
                    'orario' => sprintf('%02d:%02d:%02d', intdiv($sec, 3600), intdiv($sec % 3600, 60), $sec % 60),
                    'updated_at' => $now,
                ]);
                $count++;
            }
            self::normalize($channels);
        });
        return $count;
    }

    /**
     * Valida le righe di un controller così come sarebbero dopo la modifica.
     * Restituisce gli errori (stringhe), vuoto se tutto ok.
     */
    public static function validate(iterable $rows, Collection $channels): array
    {
        $errors = [];
        $names = $channels->pluck('name', 'id');
        $items = self::compute($rows);
        foreach ($items as $item) {
            if (!$item['first'] && $item['computed'] === 0) {
                $a = $item['prev'];
                $b = $item['row'];
                $errors[] = 'Due eventi sullo stesso controller alla stessa ora (' . substr($b->orario, 0, 5) . '): '
                    . ($names[$a->channel_id] ?? $a->channel_id) . ' e ' . ($names[$b->channel_id] ?? $b->channel_id);
            }
        }
        if ($items && $items[0]['computed'] !== null
            && ($items[0]['computed'] < self::MIN_DURATION || $items[0]['computed'] > self::MAX_DURATION)) {
            $errors[] = 'La durata del primo evento deve essere tra ' . self::MIN_DURATION . ' e ' . self::MAX_DURATION . ' secondi';
        }
        return array_values(array_unique($errors));
    }

    /**
     * Cosa va riscritto per allineare il controller: durate calcolate dal precedente evento del controller e
     * id in ordine di orario (le librerie generano gli eventi in ordine di id, numerando int_event_id in quell'ordine).
     * Gli id esistenti vengono riassegnati alle righe in ordine logico, senza crearne di nuovi.
     *
     * @return array{updates: array<int, array>, durations: int, moved: int, fixes: int}
     */
    public static function plan(Collection $channels): array
    {
        $rows = self::rowsOf($channels);
        $current = $rows->keyBy('id');
        $items = self::compute($rows);
        $ids = $rows->pluck('id')->sort()->values()->all();

        $updates = [];
        $durations = 0;
        $moved = 0;
        $fixes = 0;
        foreach ($items as $i => $item) {
            $r = $item['row'];
            $durata = $item['computed'] ?? (int) $r->durata;
            $durations += (int) $r->durata !== $durata ? 1 : 0;
            $moved += (int) $r->id !== (int) $ids[$i] ? 1 : 0;
            $fixes += ($r->posizione !== $item['posizione'] || (string) $r->giorno !== $item['giorno']) ? 1 : 0;
            $target = [
                'channel_id' => (int) $r->channel_id,
                'orario' => $r->orario,
                'giorno' => $item['giorno'],
                'posizione' => $item['posizione'],
                'durata' => $durata,
            ];
            $cur = $current[$ids[$i]];
            $same = (int) $cur->channel_id === $target['channel_id'] && $cur->orario === $target['orario']
                && (string) $cur->giorno === $target['giorno'] && $cur->posizione === $target['posizione']
                && (int) $cur->durata === $target['durata'];
            if (!$same) {
                $updates[(int) $ids[$i]] = $target;
            }
        }
        return ['updates' => $updates, 'durations' => $durations, 'moved' => $moved, 'fixes' => $fixes];
    }

    /**
     * Applica plan(): durate, posizioni (U all'ultimo), giorno dopo mezzanotte e righe in ordine di orario. Restituisce le righe riscritte.
     */
    public static function normalize(Collection $channels): int
    {
        $count = 0;
        DB::transaction(function () use ($channels, &$count) {
            $now = now();
            foreach (self::plan($channels)['updates'] as $id => $attrs) {
                Palinsesto::where('id', $id)->update($attrs + ['updated_at' => $now]);
                $count++;
            }
        });
        return $count;
    }

    private static function normalizeRows(iterable $rows): Collection
    {
        return collect($rows)->map(fn ($r) => is_array($r) ? (object) $r : ($r instanceof Model ? (object) $r->getAttributes() : $r));
    }

    /** Durata di default per un nuovo primo evento. */
    public static function defaultDuration($channel): int
    {
        return (int) ($channel->controller_event_duration ?: 300);
    }
}
