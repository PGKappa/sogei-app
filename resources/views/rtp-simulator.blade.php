@php
    $labels = \App\Libraries\RtpConfig::MARKET_LABELS;
    $pct = fn ($units, $d = 2) => \App\Libraries\RtpConfig::formatPercent($units, $d);
    $odd = fn ($o) => number_format($o, 2, ',', '');
@endphp
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Simulatore quote</title>
    @include('partials.rtp-styles')
</head>
<body>
<main style="max-width:1240px">
    @include('partials.rtp-nav', ['active' => 'simulator'])
    <h1>Simulatore quote</h1>
    <p class="sub">Calcola le quote con la libreria del gioco e confronta l'RTP di default con quello simulato.
        Non salva nulla: per rendere effettivi i valori usa <a href="{{ route('rtp.index') }}">Impostazioni RTP</a>.</p>

    @if (!empty($simErrors))
        <div class="flash err">
            @foreach ($simErrors as $msg)<div>{{ $msg }}</div>@endforeach
        </div>
    @endif

    <form class="card" method="get" action="{{ route('rtp.simulator') }}">
        <div class="row">
            <div>
                <label for="game_id">Gioco</label>
                <select id="game_id" name="game_id" onchange="this.form.querySelector('[name=event_id]').value=''; this.form.submit()">
                    @foreach ($games as $g)
                        <option value="{{ $g->id }}" {{ ($g->id == $game->id) ? 'selected' : '' }}>{{ ucfirst($g->name) }} {{ $g->racers }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="source">Probabilità da</label>
                <select id="source" name="source">
                    <option value="rng" {{ ($source == 'rng') ? 'selected' : '' }}>Corsa RNG libreria</option>
                    <option value="event" {{ ($source == 'event') ? 'selected' : '' }}>Evento reale</option>
                    <option value="manual" {{ ($source == 'manual') ? 'selected' : '' }}>Inserite a mano</option>
                </select>
            </div>
            <div data-source="event">
                <label for="event_id">Evento</label>
                <select id="event_id" name="event_id">
                    @forelse ($events as $e)
                        <option value="{{ $e->id }}" {{ ($e->id == $eventId) ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($e->time)->format('d/m H:i') }} · {{ $e->int_pal_id }}/{{ $e->int_event_id }}
                        </option>
                    @empty
                        <option value="">Nessun evento</option>
                    @endforelse
                </select>
            </div>
            <div data-source="manual" style="flex:1">
                <label for="manual">Probabilità di vittoria % (n.1 … n.{{ $game->racers }}, somma 100)</label>
                <input type="text" class="wide" id="manual" name="manual" value="{{ $manual }}" placeholder="30 25 18 12 9 6">
            </div>
        </div>

        <div class="section-title">RTP simulati (%) · vuoto = default libreria</div>
        <div class="grid">
            @foreach ($markets as $market => $m)
                <div>
                    <label for="rtp-{{ $market }}">{{ $labels[$market] ?? $market }}</label>
                    <input type="text" inputmode="decimal" id="rtp-{{ $market }}" name="rtp[{{ $market }}]"
                           value="{{ $rtpInput[$market] }}"
                           placeholder="{{ $m['default'] !== null ? number_format($m['default'] / 10000, 2, '.', '') : '' }}"
                           data-min="{{ $m['min'] / 10000 }}" data-max="{{ $m['max'] / 10000 }}"
                           title="Range {{ $pct($m['min']) }} – {{ $pct($m['max']) }}"
                           class="{{ isset($simErrors['rtp.' . $market]) ? 'invalid' : '' }}">
                </div>
            @endforeach
        </div>
        <div class="actions">
            <button type="button" onclick="location.href='{{ route('rtp.simulator', ['game_id' => $game->id]) }}'">Valori salvati</button>
            <button type="submit" class="primary">Simula</button>
        </div>
    </form>

    @if ($simulation)
        <section class="card">
            <header>
                <h2>{{ ucfirst($game->name) }} {{ $game->racers }} · {{ $sourceLabel }}</h2>
                @if ($source !== 'manual')
                    <a href="{{ route('rtp.simulator', array_merge(request()->query(), ['source' => 'manual', 'manual' => $manual])) }}" class="note">Blocca queste probabilità</a>
                @endif
            </header>
            <div class="probs">
                @foreach ($simulation['winnerProbs'] as $i => $p)
                    <span>n.{{ $i + 1 }} · {{ $pct($p) }}</span>
                @endforeach
            </div>

            <div class="tabs" role="tablist">
                <button type="button" role="tab" data-tab="summary" aria-selected="true">Riepilogo</button>
                @foreach ($simulation['markets'] as $market => $data)
                    <button type="button" role="tab" data-tab="{{ $market }}" aria-selected="false">
                        {{ $labels[$market] ?? $market }}
                    </button>
                @endforeach
            </div>

            {{-- Riepilogo --}}
            <div class="tabpanel active" data-panel="summary" role="tabpanel">
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Mercato</th>
                            <th>RTP default</th>
                            <th>RTP simulato</th>
                            <th>Effettivo medio default</th>
                            <th>Effettivo medio simulato</th>
                            <th>Range simulato</th>
                            <th>Quote al limite</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($simulation['markets'] as $market => $data)
                            @php $s = $data['summary']; @endphp
                            <tr>
                                <td><a href="#{{ $market }}" data-goto="{{ $market }}">{{ $labels[$market] ?? $market }}</a></td>
                                <td class="num">{{ $pct($data['rtpDefault']) }}</td>
                                <td class="num">
                                    {{ $pct($data['rtpSimulated']) }}
                                    @if ($data['rtpSimulated'] !== $data['rtpDefault'])
                                        <span class="{{ $data['rtpSimulated'] > $data['rtpDefault'] ? 'up' : 'down' }}">●</span>
                                    @endif
                                </td>
                                <td class="num">{{ $pct($s['Default']['avg']) }}</td>
                                <td class="num">{{ $pct($s['Simulated']['avg']) }}</td>
                                <td class="num">{{ $pct($s['Simulated']['min']) }} – {{ $pct($s['Simulated']['max']) }}</td>
                                <td class="num">
                                    @php $c = $s['Simulated']['cappedMin'] + $s['Simulated']['cappedMax']; @endphp
                                    {{ $c ? $c . ' su ' . $s['count'] : '—' }}
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="note" style="padding:0 16px 12px">
                    RTP effettivo di una selezione = probabilità × quota. Si discosta dall'RTP impostato per l'arrotondamento a 2 decimali
                    e per i limiti di quota della libreria ({{ $odd($simulation['limits']['min']) }} – {{ $odd($simulation['limits']['max']) }}).
                </p>
            </div>

            {{-- Un tab per mercato --}}
            @foreach ($simulation['markets'] as $market => $data)
                @php $s = $data['summary']; $many = count($data['rows']) > 30; @endphp
                <div class="tabpanel" data-panel="{{ $market }}" role="tabpanel">
                    <div class="tab-head">
                        <div class="stats">
                            <span>RTP <b>{{ $pct($data['rtpDefault']) }}</b> → <b>{{ $pct($data['rtpSimulated']) }}</b></span>
                            <span>Effettivo medio <b>{{ $pct($s['Default']['avg']) }}</b> → <b>{{ $pct($s['Simulated']['avg']) }}</b></span>
                            <span>{{ $s['count'] }} selezioni</span>
                        </div>
                        @if ($many)
                            <input type="text" class="filter" placeholder="Filtra (es. 1-2)" data-filter>
                        @endif
                    </div>
                    <div class="table-wrap {{ $many ? 'scroll' : '' }}">
                        <table data-sortable>
                            <thead>
                            <tr>
                                <th class="sortable" data-type="text">Selezione</th>
                                <th class="sortable" data-type="num">Probabilità</th>
                                <th class="sortable" data-type="num">Quota default</th>
                                <th class="sortable" data-type="num">Quota simulata</th>
                                <th class="sortable" data-type="num">Variazione</th>
                                <th class="sortable" data-type="num">RTP sel. default</th>
                                <th class="sortable" data-type="num">RTP sel. simulato</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($data['rows'] as $r)
                                @php
                                    $delta = $r['oddDefault'] > 0 ? ($r['oddSimulated'] - $r['oddDefault']) / $r['oddDefault'] * 100 : 0;
                                    $capMin = $r['oddSimulated'] <= $simulation['limits']['min'];
                                    $capMax = $simulation['limits']['max'] > 0 && $r['oddSimulated'] >= $simulation['limits']['max'];
                                @endphp
                                <tr class="{{ $capMin || $capMax ? 'capped' : '' }}">
                                    <td data-v="{{ $r['label'] }}">
                                        {{ $r['label'] }}
                                        @if ($capMin)<span class="tag">quota min</span>@endif
                                        @if ($capMax)<span class="tag">quota max</span>@endif
                                    </td>
                                    <td class="num" data-v="{{ $r['prob'] }}">{{ $pct($r['prob'], 3) }}</td>
                                    <td class="num" data-v="{{ $r['oddDefault'] }}">{{ $odd($r['oddDefault']) }}</td>
                                    <td class="num" data-v="{{ $r['oddSimulated'] }}"><b>{{ $odd($r['oddSimulated']) }}</b></td>
                                    <td class="num {{ $delta > 0 ? 'up' : ($delta < 0 ? 'down' : '') }}" data-v="{{ $delta }}">
                                        {{ $delta == 0 ? '—' : str_replace('.', ',', sprintf('%+.1f%%', $delta)) }}
                                    </td>
                                    <td class="num" data-v="{{ $r['rtpDefault'] }}">{{ $pct($r['rtpDefault']) }}</td>
                                    <td class="num" data-v="{{ $r['rtpSimulated'] }}">{{ $pct($r['rtpSimulated']) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </section>
    @endif
</main>
<script>
    (function () {
        // sorgente probabilità: mostra solo i campi pertinenti
        var source = document.getElementById('source');
        function syncSource() {
            document.querySelectorAll('[data-source]').forEach(function (el) {
                el.style.display = el.dataset.source === source.value ? '' : 'none';
            });
        }
        source.addEventListener('change', syncSource);
        syncSource();

        // validazione RTP lato client (la verifica vera è sul server)
        document.querySelectorAll('.grid input[type=text]').forEach(function (input) {
            input.addEventListener('input', function () {
                var raw = input.value.trim().replace(',', '.'), n = parseFloat(raw);
                input.classList.toggle('invalid', raw !== '' && (!/^\d{1,2}(\.\d{1,2})?$/.test(raw) || n < +input.dataset.min || n > +input.dataset.max));
            });
        });

        // tab, con la tab attiva nell'hash dell'URL
        var tabs = document.querySelectorAll('[role=tab]');
        function show(name) {
            var found = false;
            tabs.forEach(function (t) {
                var on = t.dataset.tab === name;
                found = found || on;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            if (!found) return;
            document.querySelectorAll('.tabpanel').forEach(function (p) {
                p.classList.toggle('active', p.dataset.panel === name);
            });
            history.replaceState(null, '', name === 'summary' ? location.pathname + location.search : '#' + name);
        }
        tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.tab); }); });
        document.querySelectorAll('[data-goto]').forEach(function (a) {
            a.addEventListener('click', function (ev) { ev.preventDefault(); show(a.dataset.goto); });
        });
        if (location.hash) show(location.hash.slice(1));
        // mantiene la tab attiva quando si rilancia la simulazione
        var form = document.querySelector('form.card');
        form.addEventListener('submit', function () {
            var active = document.querySelector('[role=tab][aria-selected=true]');
            if (active && active.dataset.tab !== 'summary') form.action = form.action.split('#')[0] + '#' + active.dataset.tab;
        });

        // filtro righe
        document.querySelectorAll('[data-filter]').forEach(function (input) {
            var rows = input.closest('.tabpanel').querySelectorAll('tbody tr');
            input.addEventListener('input', function () {
                var q = input.value.trim().replace(/[+\s]/g, '-');
                rows.forEach(function (r) {
                    var label = r.cells[0].dataset.v.replace(/\+/g, '-');
                    r.style.display = q === '' || label.indexOf(q) === 0 ? '' : 'none';
                });
            });
        });

        // ordinamento colonne
        document.querySelectorAll('table[data-sortable]').forEach(function (table) {
            table.querySelectorAll('th.sortable').forEach(function (th, idx) {
                var asc = true;
                th.addEventListener('click', function () {
                    var body = table.tBodies[0], num = th.dataset.type === 'num';
                    var rows = Array.prototype.slice.call(body.rows);
                    rows.sort(function (a, b) {
                        var x = a.cells[idx].dataset.v, y = b.cells[idx].dataset.v;
                        return (num ? (+x - +y) : x.localeCompare(y, undefined, {numeric: true})) * (asc ? 1 : -1);
                    });
                    rows.forEach(function (r) { body.appendChild(r); });
                    asc = !asc;
                });
            });
        });
    })();
</script>
</body>
</html>
