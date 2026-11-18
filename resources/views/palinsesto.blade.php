@php
    use App\Libraries\PalinsestoSchedule as Schedule;
    $names = $channels->pluck('name', 'id');
    $hhmm = fn ($t) => $t ? substr($t, 0, 5) : '—';
    $isController = !str_starts_with($groupKey, 'ch');
    $formErrors = session('form_errors', []);
    $editing = old('id');
    // orari del palinsesto in UTC (config app.timezone); conversione con l'offset italiano di oggi
    $itNow = \Carbon\Carbon::now('Europe/Rome');
    $itOffset = intdiv($itNow->getOffset(), 60); // minuti: 60 ora solare, 120 ora legale
    $itLabel = $itOffset === 120 ? 'ora legale, UTC+2' : 'ora solare, UTC+1';
    $toIt = function ($t) use ($itOffset) {
        if (!$t) { return '—'; }
        $m = (Schedule::seconds($t) / 60 + $itOffset) % 1440;
        return sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
    };
@endphp
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Palinsesto</title>
    @include('partials.rtp-styles')
    <style>
        .groups { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:16px; }
        .groups a { padding:6px 12px; border:1px solid var(--line); border-radius:8px; background:var(--card); color:var(--text); text-decoration:none; }
        .groups a.active { border-color:var(--accent); color:var(--accent); font-weight:600; background:var(--accent-soft); }
        .channels { display:flex; flex-wrap:wrap; gap:8px 24px; padding:12px 16px; font-size:13px; }
        .channels b { font-variant-numeric:tabular-nums; }
        .pos { display:inline-block; min-width:22px; text-align:center; border-radius:4px; padding:1px 6px; font-size:12px; font-weight:600; }
        .pos-P { background:var(--ok-soft); color:var(--ok); }
        .pos-U { background:var(--err-soft); color:var(--err); }
        .pos-I { background:var(--chip); color:var(--accent); }
        .muted { color:var(--muted); }
        tr.diff td.dur { background:var(--warn-soft); }
        tr.editing td { background:var(--accent-soft); }
        td.ck, th.ck { width:32px; padding-right:0; }
        
        .flash.utc { background:var(--accent-soft); color:var(--turf); border-color:#c7ddd2; border-left-color:var(--accent); line-height:1.6; }
        .it { color:var(--muted); font-size:12px; margin-left:6px; }
        form.shift { display:flex; flex-wrap:wrap; gap:8px 12px; align-items:center; padding:12px 16px; border-bottom:1px solid var(--line); font-size:13px; }
        .row .hint { font-size:12px; color:var(--muted); margin-top:4px; max-width:260px; white-space:normal; }
        .row input[type=text] { width:100px; }
        .toolbar { display:flex; flex-wrap:wrap; gap:12px; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid var(--line); }
        .toolbar label { font-size:13px; color:var(--muted); }
        
        
        .inline { display:inline; }
    </style>
</head>
<body>
<main style="max-width:1100px">
    @include('partials.rtp-nav', ['active' => 'palinsesto'])
    <h1>Palinsesto</h1>
    <p class="sub">Orari degli eventi per canale, usati da <code>pg:generateEventsNew</code> per creare i giorni non ancora generati.
        I canali con lo stesso controller Isibet condividono la stessa sequenza: la <b>durata</b> di ogni evento è il tempo
        dal precedente evento <b>dello stesso controller</b> (anche se di un altro canale) e viene ricalcolata a ogni modifica.
        Solo il primo evento della giornata ha la durata impostata a mano.<br>
        Scegli tu solo l'evento <b>P</b> (inizio giornata). Il resto è automatico: l'ultimo evento di ogni canale diventa <b>U</b>,
        e gli orari prima dell'inizio giornata (dopo mezzanotte) vanno al <b>giorno dopo</b> <span class="tag">+1</span>.</p>

    <div class="flash utc">
        <b>Gli orari vanno inseriti in UTC, non in ora italiana.</b>
        Oggi in Italia è {{ $itLabel }}: per un evento alle <b>06:00 italiane</b> inserisci
        <b>{{ $itOffset === 120 ? '04:00' : '05:00' }}</b>.
        In ora solare (fine ottobre → fine marzo) si toglie 1 ora, in ora legale (fine marzo → fine ottobre) 2 ore:
        le 06:00 italiane sono 05:00 UTC d'inverno e 04:00 UTC d'estate.
        Anche la mezzanotte del "giorno dopo" è quella UTC (01:00 italiane d'inverno, 02:00 d'estate).
        Poiché il palinsesto è fisso in UTC, al cambio dell'ora gli eventi si spostano di un'ora in ora italiana.
    </div>

    <div class="groups">
        @foreach ($groups as $key => $chs)
            <a href="{{ route('palinsesto.index', ['controller' => $key]) }}" class="{{ $key === (string) $groupKey ? 'active' : '' }}">
                {{ str_starts_with($key, 'ch') ? 'Senza controller' : 'Controller ' . $key }}
                <span class="muted">· {{ $chs->pluck('name')->implode(', ') }}</span>
            </a>
        @endforeach
    </div>

    @if (session('status'))
        <div class="flash ok">{{ session('status') }}</div>
    @endif
    @if ($formErrors)
        <div class="flash err">
            <div><b>Nessuna modifica salvata:</b></div>
            @foreach ($formErrors as $msg)<div>{{ $msg }}</div>@endforeach
        </div>
    @endif
    @if ($problems)
        <div class="flash warn">
            <div><b>Il palinsesto attuale ha dei problemi</b> (vanno risolti prima di altre modifiche):</div>
            @foreach ($problems as $msg)<div>{{ $msg }}</div>@endforeach
        </div>
    @endif
    @if ($diffs || $moved || $fixes)
        <div class="flash warn" style="display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap">
            <span>
                @if ($diffs){{ $diffs }} {{ $diffs == 1 ? 'durata salvata non corrisponde' : 'durate salvate non corrispondono' }} al calcolo dal precedente evento del controller (evidenziate in giallo).@endif
                @if ($fixes){{ $fixes }} righe hanno posizione (U finale) o giorno dopo mezzanotte da aggiornare.@endif
                @if ($moved){{ $moved }} righe non sono memorizzate in ordine di orario: la generazione le numererebbe fuori sequenza.@endif
            </span>
            <form method="post" action="{{ route('palinsesto.recalc') }}" class="inline">
                @csrf
                <input type="hidden" name="controller" value="{{ $groupKey }}">
                <button type="submit" class="primary">Allinea palinsesto</button>
            </form>
        </div>
    @endif

    <div class="card">
        <header>
            <h2>{{ $isController ? 'Controller ' . $groupKey : 'Canale senza controller' }}</h2>
            <span class="chip">{{ count($items) }} eventi</span>
        </header>
        <form method="post" action="{{ route('palinsesto.shift') }}" class="shift" id="shift-form">
            @csrf
            <input type="hidden" name="controller" value="{{ $groupKey }}">
            <span>Sposta tutto il palinsesto (es. per il cambio dell'ora):</span>
            <button type="submit" name="hours" value="-1">− 1 ora</button>
            <button type="submit" name="hours" value="1">+ 1 ora</button>
            @if (count($groups) > 1)
                <label><input type="checkbox" name="scope" value="all"> tutti i controller</label>
            @endif
            <span class="muted">Ordine, durate, P e U restano uguali.</span>
        </form>
        <div class="channels">
            @foreach ($channels as $ch)
                <span>{{ $ch->name }} <span class="muted">({{ $ch->track_name }})</span>:
                    <b>{{ $perChannel[$ch->id]['count'] }}</b> eventi,
                    {{ $hhmm($perChannel[$ch->id]['first']) }} → {{ $hhmm($perChannel[$ch->id]['last']) }} UTC
                    <span class="muted">({{ $toIt($perChannel[$ch->id]['first']) }} → {{ $toIt($perChannel[$ch->id]['last']) }} in Italia)</span></span>
            @endforeach
        </div>
    </div>

    <form class="card" method="post" action="{{ route('palinsesto.save') }}" id="edit-form">
        @csrf
        <header>
            <h2 id="edit-title">{{ $editing ? 'Modifica evento' : 'Aggiungi evento' }}</h2>
        </header>
        <input type="hidden" name="id" value="{{ old('id') }}">
        <div class="row" style="border-bottom:0">
            <div>
                <label for="f-channel">Canale</label>
                <select id="f-channel" name="channel_id">
                    @foreach ($channels as $ch)
                        <option value="{{ $ch->id }}" {{ old('channel_id') == $ch->id ? 'selected' : '' }}>{{ $ch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-orario">Orario (UTC)</label>
                <input type="text" id="f-orario" name="orario" value="{{ old('orario') }}" placeholder="HH:MM" inputmode="numeric" required data-it>
                <div class="hint" data-it-out></div>
            </div>
            <div>
                <label for="f-posizione">Tipo</label>
                <select id="f-posizione" name="posizione">
                    <option value="I" {{ old('posizione', 'I') !== 'P' ? 'selected' : '' }}>Evento normale</option>
                    <option value="P" {{ old('posizione') === 'P' ? 'selected' : '' }}>P · Inizio giornata</option>
                </select>
                <div class="hint">La U e il giorno dopo mezzanotte sono automatici.</div>
            </div>
            <div>
                <label for="f-durata">Durata (s)</label>
                <input type="text" id="f-durata" name="durata" value="{{ old('durata') }}" placeholder="auto" inputmode="numeric">
                <div class="hint">Usata solo se è il primo evento del controller; per gli altri è calcolata.</div>
            </div>
            <div class="actions" style="border:0;padding:0;margin-left:auto">
                <button type="button" id="edit-cancel" {{ $editing ? '' : 'hidden' }}>Annulla</button>
                <button type="submit" class="primary" id="edit-submit">{{ $editing ? 'Salva modifica' : 'Aggiungi' }}</button>
            </div>
        </div>
    </form>

    <details class="card" {{ old('step') ? 'open' : '' }}>
        <summary style="padding:12px 16px;cursor:pointer;font-weight:600">Genera sequenza di eventi</summary>
        <form method="post" action="{{ route('palinsesto.generate') }}">
            @csrf
            <div class="row" style="border-top:1px solid var(--line)">
                <div>
                    <label for="g-channel">Canale</label>
                    <select id="g-channel" name="channel_id">
                        @foreach ($channels as $ch)
                            <option value="{{ $ch->id }}" {{ old('channel_id') == $ch->id ? 'selected' : '' }}>{{ $ch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="g-from">Dalle (UTC)</label>
                    <input type="text" id="g-from" name="from" value="{{ old('from') }}" placeholder="05:00" required data-it>
                    <div class="hint" data-it-out></div>
                </div>
                <div>
                    <label for="g-to">Alle, inclusa (UTC)</label>
                    <input type="text" id="g-to" name="to" value="{{ old('to') }}" placeholder="22:55" required data-it>
                    <div class="hint" data-it-out></div>
                </div>
                <div>
                    <label for="g-step">Ogni (minuti)</label>
                    <input type="text" id="g-step" name="step" value="{{ old('step', 5) }}" inputmode="numeric" required>
                </div>
                <div class="actions" style="border:0;padding:0;margin-left:auto">
                    <button type="submit" class="primary">Genera</button>
                </div>
            </div>
            <p class="note" style="padding:0 16px 12px;margin:0">Può passare la mezzanotte (es. dalle 23:00 alle 02:00). Gli orari già occupati sul controller vengono saltati;
                la U si sposta da sola sull'ultimo evento.</p>
        </form>
    </details>

    <form class="card" method="post" action="{{ route('palinsesto.destroy') }}" id="list-form">
        @csrf
        <input type="hidden" name="controller" value="{{ $groupKey }}">
        <div class="toolbar">
            <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
                <label>Canale
                    <select id="flt-channel">
                        <option value="">Tutti</option>
                        @foreach ($channels as $ch)
                            <option value="{{ $ch->id }}">{{ $ch->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label><input type="checkbox" id="flt-diff"> Solo righe da allineare</label>
                <span class="muted" id="flt-count"></span>
            </div>
            <button type="submit" class="danger" id="delete-btn" disabled>Elimina selezionati</button>
        </div>
        <div class="scroll">
            <table>
                <thead>
                <tr>
                    <th class="ck"><input type="checkbox" id="ck-all" aria-label="Seleziona tutti"></th>
                    <th>#</th>
                    <th>Orario UTC <span class="it">(Italia oggi)</span></th>
                    <th>Canale</th>
                    <th>Pos.</th>
                    <th>Durata</th>
                    <th>Calcolata da</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse ($items as $n => $item)
                    @php
                        $r = $item['row'];
                        $diff = ($item['computed'] !== null && (int) $r->durata !== $item['computed'])
                            || $r->posizione !== $item['posizione'] || (string) $r->giorno !== $item['giorno'];
                    @endphp
                    <tr data-channel="{{ $r->channel_id }}" data-diff="{{ $diff ? 1 : 0 }}" data-id="{{ $r->id }}" class="{{ $diff ? 'diff' : '' }} {{ $editing == $r->id ? 'editing' : '' }}">
                        <td class="ck"><input type="checkbox" name="ids[]" value="{{ $r->id }}" aria-label="Seleziona"></td>
                        <td class="num muted">{{ $n + 1 }}</td>
                        <td class="num">{{ $hhmm($r->orario) }}@if ($item['nextDay'])<span class="tag" title="Giorno dopo">+1</span>@endif<span class="it">{{ $toIt($r->orario) }}</span></td>
                        <td>{{ $names[$r->channel_id] ?? $r->channel_id }}</td>
                        <td><span class="pos pos-{{ $item['posizione'] }}" title="{{ Schedule::POSITIONS[$item['posizione']] ?? '' }}">{{ $item['posizione'] }}</span></td>
                        <td class="num dur">
                            {{ Schedule::formatDuration($item['computed']) }}
                            @if ($diff)<span class="muted">(salvata {{ Schedule::formatDuration((int) $r->durata) }})</span>@endif
                        </td>
                        <td class="muted">
                            @if ($item['first'])
                                primo evento · manuale
                            @else
                                {{ $names[$item['prev']->channel_id] ?? $item['prev']->channel_id }} {{ $hhmm($item['prev']->orario) }}
                            @endif
                        </td>
                        <td style="text-align:right">
                            <button type="button" data-edit
                                    data-id="{{ $r->id }}" data-channel="{{ $r->channel_id }}" data-orario="{{ $hhmm($r->orario) . (substr($r->orario, 6, 2) !== '00' ? substr($r->orario, 5) : '') }}"
                                    data-posizione="{{ $item['posizione'] === 'P' ? 'P' : 'I' }}" data-durata="{{ $item['first'] ? $r->durata : '' }}">Modifica</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="note">Nessun evento nel palinsesto di questo controller.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </form>
</main>
<script>
(function () {
    // conversione UTC -> ora italiana di oggi mentre si scrive
    var itOffset = {{ $itOffset }};
    document.querySelectorAll('[data-it]').forEach(function (input) {
        var out = input.parentNode.querySelector('[data-it-out]');
        function show() {
            var m = /^([01]?\d|2[0-3])[:.]([0-5]\d)/.exec(input.value.trim());
            if (!m) { out.textContent = 'Ora italiana: —'; return; }
            var t = (+m[1] * 60 + +m[2] + itOffset) % 1440;
            out.textContent = 'Ora italiana: ' + String(Math.floor(t / 60)).padStart(2, '0') + ':' + String(t % 60).padStart(2, '0');
        }
        input.addEventListener('input', show);
        show();
        input._showIt = show;
    });
    // conferma dello spostamento di un'ora
    var shiftForm = document.getElementById('shift-form');
    shiftForm.addEventListener('submit', function (e) {
        var btn = e.submitter;
        var all = shiftForm.querySelector('[name=scope]');
        var msg = 'Spostare di un\'ora ' + (btn && btn.value === '1' ? 'AVANTI' : 'INDIETRO') + ' tutto il palinsesto '
            + (all && all.checked ? 'di TUTTI i controller' : 'di questo controller') + '?';
        if (!window.confirm(msg)) { e.preventDefault(); }
    });
    var form = document.getElementById('edit-form');
    var f = function (n) { return form.querySelector('[name=' + n + ']'); };
    var title = document.getElementById('edit-title');
    var submit = document.getElementById('edit-submit');
    var cancel = document.getElementById('edit-cancel');

    function markEditing(id) {
        document.querySelectorAll('tr.editing').forEach(function (tr) { tr.classList.remove('editing'); });
        if (id) { var tr = document.querySelector('tr[data-id="' + id + '"]'); if (tr) tr.classList.add('editing'); }
    }

    document.querySelectorAll('[data-edit]').forEach(function (b) {
        b.addEventListener('click', function () {
            f('id').value = b.dataset.id;
            f('channel_id').value = b.dataset.channel;
            f('orario').value = b.dataset.orario;
            if (f('orario')._showIt) f('orario')._showIt();
            f('posizione').value = b.dataset.posizione;
            f('durata').value = b.dataset.durata;
            title.textContent = 'Modifica evento';
            submit.textContent = 'Salva modifica';
            cancel.hidden = false;
            markEditing(b.dataset.id);
            form.scrollIntoView({behavior: 'smooth', block: 'start'});
            f('orario').focus({preventScroll: true});
        });
    });
    cancel.addEventListener('click', function () {
        ['id', 'orario', 'durata'].forEach(function (n) { f(n).value = ''; });
        if (f('orario')._showIt) f('orario')._showIt();
        f('posizione').value = 'I';
        title.textContent = 'Aggiungi evento';
        submit.textContent = 'Aggiungi';
        cancel.hidden = true;
        markEditing(null);
    });

    // filtri e selezione
    var list = document.getElementById('list-form');
    var rows = Array.prototype.slice.call(list.querySelectorAll('tbody tr[data-id]'));
    var fCh = document.getElementById('flt-channel');
    var fDiff = document.getElementById('flt-diff');
    var count = document.getElementById('flt-count');
    var del = document.getElementById('delete-btn');
    var all = document.getElementById('ck-all');

    function visible() { return rows.filter(function (r) { return !r.hidden; }); }
    function syncSelection() {
        var n = list.querySelectorAll('tbody input[type=checkbox]:checked').length;
        del.disabled = n === 0;
        del.textContent = n ? 'Elimina selezionati (' + n + ')' : 'Elimina selezionati';
    }
    function applyFilter() {
        rows.forEach(function (r) {
            var show = (!fCh.value || r.dataset.channel === fCh.value) && (!fDiff.checked || r.dataset.diff === '1');
            r.hidden = !show;
            if (!show) r.querySelector('input[type=checkbox]').checked = false;
        });
        count.textContent = visible().length + ' di ' + rows.length;
        all.checked = false;
        syncSelection();
    }
    fCh.addEventListener('change', applyFilter);
    fDiff.addEventListener('change', applyFilter);
    all.addEventListener('change', function () {
        visible().forEach(function (r) { r.querySelector('input[type=checkbox]').checked = all.checked; });
        syncSelection();
    });
    list.addEventListener('change', function (e) { if (e.target.name === 'ids[]') syncSelection(); });
    list.addEventListener('submit', function (e) {
        var n = list.querySelectorAll('tbody input[type=checkbox]:checked').length;
        if (!n || !window.confirm('Eliminare ' + n + (n === 1 ? ' evento' : ' eventi') + ' dal palinsesto?')) { e.preventDefault(); return; }
        del.disabled = true;
        del.textContent = 'Eliminazione…';
    });
    applyFilter();
})();
</script>
</body>
</html>
