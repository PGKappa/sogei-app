<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Pannello tecnico</title>
    @include('partials.rtp-styles')
    <style>
        /* quadro di stato: un riquadro per sezione, verde se non c'è nulla da fare, ambra se servono interventi */
        .board { display:grid; grid-template-columns:repeat(4, 1fr); background:var(--card); border:1px solid var(--line); border-radius:8px; margin-bottom:24px; overflow:hidden; }
        .board a { display:block; padding:14px 18px 16px; color:var(--text); text-decoration:none; border-left:1px solid var(--line); border-top:4px solid var(--ok); }
        .board a:first-child { border-left:0; }
        .board a:hover { background:var(--head); color:var(--text); }
        .board a.todo { border-top-color:var(--silk); }
        .board .n { display:block; font-size:38px; line-height:1; font-weight:800; font-variation-settings:"wdth" 70; font-variant-numeric:tabular-nums; margin-bottom:6px; }
        .board a:not(.todo) .n { color:var(--ok); }
        .board .l { display:block; font-weight:600; }
        .board .s { display:block; color:var(--muted); font-size:12.5px; }

        .filters { display:flex; flex-wrap:wrap; gap:12px 20px; align-items:flex-end; margin-bottom:20px; }
        .filters form { display:flex; gap:8px; align-items:flex-end; }
        .filters label { display:block; font-size:12.5px; color:var(--muted); margin-bottom:4px; }
        .filters .refresh { margin-left:auto; align-self:center; }

        .card { scroll-margin-top:16px; }
        .card .desc { padding:12px 18px; color:var(--muted); font-size:13px; margin:0; border-bottom:1px solid var(--line); }
        .card .desc > span { display:block; max-width:90ch; }
        .actions { justify-content:space-between; align-items:center; flex-wrap:wrap; }
        .actions .right { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .actions label { display:flex; gap:6px; align-items:center; }
        .empty { padding:16px 18px; color:var(--muted); }
        .badge { display:inline-block; font-size:12px; font-weight:600; border-radius:4px; padding:1px 7px; background:var(--chip); color:var(--accent); }
        .badge.warn { background:var(--warn-soft); color:var(--warn); }
        .badge.err { background:var(--err-soft); color:var(--err); }
        .report { border-left:4px solid var(--accent); }
        .report td.ok { color:var(--ok); font-weight:600; }
        .report td.ko { color:var(--err); font-weight:600; }
        td.msg { white-space:normal; min-width:280px; }
        th.ck, td.ck { width:40px; padding-right:0; }
        dl.detail { display:grid; grid-template-columns:200px 1fr; gap:8px 16px; padding:16px 18px; margin:0; }
        dl.detail dt { color:var(--muted); }
        dl.detail dd { margin:0; font-variant-numeric:tabular-nums; word-break:break-all; }
        .busy { position:fixed; inset:0; background:rgba(238,241,238,.8); display:none; align-items:center; justify-content:center; font-weight:700; font-size:16px; z-index:20; }
        body.is-busy .busy { display:flex; }
        @media (max-width: 900px) { .board { grid-template-columns:repeat(2, 1fr); } .board a:nth-child(3) { border-left:0; } .board a:nth-child(n+3) { border-top-width:4px; } }
        @media (max-width: 600px) { dl.detail { grid-template-columns:1fr; } .filters .refresh { margin-left:0; } }
    </style>
</head>
<body>
@include('partials.rtp-nav', ['active' => 'tecnico'])
<main>
    <h1>Manutenzione eventi</h1>
    <p class="sub">Interventi sugli eventi che i servizi automatici non hanno chiuso: palinsesto non ricevuto, event card non inviate,
        eventi scaduti senza risultato, risultati non trasmessi a Isibet. Ogni operazione usa le stesse funzioni dei servizi e viene registrata nel log.</p>

    @if (session('error'))
        <div class="flash err">{{ session('error') }}</div>
    @endif

    @if ($report = session('report'))
        @php $okCount = collect($report['results'])->filter(fn ($r) => $r[0])->count(); @endphp
        <div class="card report">
            <header>
                <h2>{{ $report['title'] }}</h2>
                <span class="chip">{{ $okCount }} / {{ count($report['results']) }} riusciti</span>
            </header>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Evento</th><th>Esito</th><th>Dettaglio</th></tr></thead>
                    <tbody>
                    @forelse ($report['results'] as $id => [$ok, $msg])
                        <tr>
                            <td class="num"><a href="?evento={{ $id }}">{{ $id }}</a></td>
                            <td class="{{ $ok ? 'ok' : 'ko' }}">{{ $ok ? 'OK' : 'Errore' }}</td>
                            <td class="msg">{{ $msg }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">Nessun evento trovato.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @php
        $board = [
            ['id' => 'palinsesto', 'n' => count($missingPalinsesto), 'l' => 'Palinsesto non ricevuto', 's' => 'codici ADM mancanti'],
            ['id' => 'saltati', 'n' => count($skipped), 'l' => 'Event card non inviate', 's' => 'in partenza a breve'],
            ['id' => 'scaduti', 'n' => count($expired), 'l' => 'Scaduti senza risultato', 's' => 'da esitare o annullare'],
            ['id' => 'risultati', 'n' => count($resultsNotSent), 'l' => 'Risultati non inviati', 's' => 'generati, non trasmessi'],
        ];
    @endphp
    <nav class="board" aria-label="Stato">
        @foreach ($board as $b)
            <a href="#{{ $b['id'] }}" class="{{ $b['n'] ? 'todo' : '' }}">
                <span class="n">{{ $b['n'] }}</span>
                <span class="l">{{ $b['l'] }}</span>
                <span class="s">{{ $b['n'] ? $b['s'] : 'nessun intervento' }}</span>
            </a>
        @endforeach
    </nav>

    <div class="filters">
        <form method="get">
            <div>
                <label for="ore">Periodo controllato</label>
                <select id="ore" name="ore" onchange="this.form.submit()">
                    @foreach ([2, 6, 12, 24, 48, 72, 168] as $h)
                        <option value="{{ $h }}" {{ $h === $hours ? 'selected' : '' }}>{{ $h === 168 ? 'ultimi 7 giorni' : 'ultime ' . $h . ' ore' }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        <form method="get" role="search">
            <input type="hidden" name="ore" value="{{ $hours }}">
            <div>
                <label for="evento">ID evento</label>
                <input type="text" id="evento" name="evento" value="{{ $lookupId }}" inputmode="numeric" style="width:140px" placeholder="es. 862">
            </div>
            <button type="submit">Apri evento</button>
        </form>
        <a class="refresh" href="{{ url()->current() }}?ore={{ $hours }}">Aggiorna dati</a>
    </div>

    {{-- dettaglio evento --}}
    @if ($lookupId !== null && $lookupId !== '')
        <div class="card">
            <header><h2>Evento {{ $lookupId }}</h2></header>
            @if (!$lookup)
                <div class="empty">Evento non trovato.</div>
            @else
                @php $opts = $lookup->getOpts(); @endphp
                <dl class="detail">
                    <dt>Gioco</dt><dd>{{ $channels[$lookup->channel_id] ?? $lookup->channel_id }}</dd>
                    <dt>Interno pal/evento</dt><dd>{{ $lookup->int_pal_id }} / {{ $lookup->int_event_id }}</dd>
                    <dt>ADM pal/evento</dt><dd>{{ trim((string) $lookup->ext_pal_id) !== '' ? $lookup->ext_pal_id : '—' }} / {{ trim((string) $lookup->ext_event_id) !== '' ? $lookup->ext_event_id : '—' }}</dd>
                    <dt>Partenza</dt><dd>{{ $lookup->time }}</dd>
                    <dt>Fine</dt><dd>{{ $lookup->time_finish ?? '—' }}</dd>
                    <dt>Stato</dt><dd>{{ \App\Libraries\MaintenanceOps::statusLabel($lookup->status) }} ({{ $lookup->status }})</dd>
                    <dt>Event card inviata</dt><dd>{{ $lookup->sent_on ?? '—' }}</dd>
                    <dt>Risultato inviato</dt><dd>{{ $lookup->isibet_result ?? '—' }}</dd>
                    <dt>Ordine d'arrivo</dt><dd>{{ !empty($opts['arrivalOrder']) ? implode(' - ', $opts['arrivalOrder']) : '—' }}</dd>
                </dl>
                <div class="actions">
                    @php
                    $st = (int) $lookup->status;
                    $past = \Carbon\Carbon::parse($lookup->time)->isPast();
                    $hasActions = (!$lookup->sent_on && $st === 0 && !$past)
                        || ($lookup->sent_on && !$lookup->ext_pal_id)
                        || (($st === 0 || ($st === 3 && $lookup->sent_on)) && !$lookup->isibet_result && $past)
                        || in_array($st, [2, 5], true);
                @endphp
                    <span class="note">{{ $hasActions ? 'Azioni possibili su questo evento' : 'Nessun intervento necessario su questo evento' }}</span>
                    <div class="right">
                        @if (!$lookup->sent_on && $st === 0 && !$past)
                            <form method="post" action="{{ route('tecnico.run', 'invio-card') }}" data-confirm="Inviare l'event card a Isibet?">
                                @csrf <input type="hidden" name="ids[]" value="{{ $lookup->id }}">
                                <button type="submit">Invia event card</button>
                            </form>
                        @endif
                        @if ($lookup->sent_on && !$lookup->ext_pal_id)
                            <form method="post" action="{{ route('tecnico.run', 'palinsesto') }}">
                                @csrf <input type="hidden" name="ids[]" value="{{ $lookup->id }}">
                                <button type="submit">Recupera palinsesto</button>
                            </form>
                        @endif
                        @if (($st === 0 || ($st === 3 && $lookup->sent_on)) && !$lookup->isibet_result && $past)
                            <form method="post" action="{{ route('tecnico.run', 'esita') }}" data-confirm="Generare il risultato e inviarlo a Isibet?">
                                @csrf <input type="hidden" name="ids[]" value="{{ $lookup->id }}">
                                <input type="hidden" name="invia_subito" value="1">
                                <button type="submit" class="primary">Esita e invia</button>
                            </form>
                        @endif
                        @if ($st === 0 && $past)
                            <form method="post" action="{{ route('tecnico.run', 'annulla') }}" data-confirm="Annullare l'evento? Non verrà esitato.">
                                @csrf <input type="hidden" name="ids[]" value="{{ $lookup->id }}">
                                <button type="submit" class="danger">Annulla</button>
                            </form>
                        @endif
                        @if ($st === 2)
                            <form method="post" action="{{ route('tecnico.run', 'risultati') }}">
                                @csrf <input type="hidden" name="ids[]" value="{{ $lookup->id }}">
                                <button type="submit" class="primary">Invia risultato</button>
                            </form>
                        @endif
                        @if ($st === 5)
                            <form method="post" action="{{ route('tecnico.run', 'risultati') }}" data-confirm="Il risultato è già stato inviato il {{ $lookup->isibet_result }}. Reinviarlo?">
                                @csrf <input type="hidden" name="ids[]" value="{{ $lookup->id }}">
                                <input type="hidden" name="reinvio" value="1">
                                <button type="submit">Reinvia risultato</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- 1. palinsesto non ricevuto --}}
    <form class="card" id="palinsesto" method="post" action="{{ route('tecnico.run', 'palinsesto') }}" data-selection>
        @csrf
        <header>
            <h2>Palinsesto non ricevuto</h2>
            <span class="chip count">{{ count($missingPalinsesto) }}</span>
        </header>
        <p class="desc"><span>Eventi inviati a Isibet per cui non sono ancora arrivati i codici ADM (palinsesto/evento). Senza codici il risultato non viene generato dal worker.</span></p>
        @include('partials.tecnico-events', ['events' => $missingPalinsesto, 'extra' => 'sent'])
        @if (count($missingPalinsesto))
            <div class="actions">
                <span class="note" data-counter></span>
                <div class="right"><button type="submit" class="primary" disabled>Recupera palinsesto</button></div>
            </div>
        @endif
    </form>

    {{-- 2. event card non inviate --}}
    <form class="card" id="saltati" method="post" action="{{ route('tecnico.run', 'invio-card') }}" data-selection data-confirm="Inviare le event card selezionate a Isibet?">
        @csrf
        <header>
            <h2>Event card non inviate</h2>
            <span class="chip count">{{ count($skipped) }}</span>
        </header>
        <p class="desc"><span>Eventi in partenza nei prossimi {{ \App\Libraries\MaintenanceOps::PUBLISH_MIN_MINUTES }} minuti mai inviati a Isibet: il publisher non li invia più da solo. Dopo l'invio usa "Recupera palinsesto".</span></p>
        @include('partials.tecnico-events', ['events' => $skipped, 'extra' => null])
        @if (count($skipped))
            <div class="actions">
                <span class="note" data-counter></span>
                <div class="right"><button type="submit" class="primary" disabled>Invia event card</button></div>
            </div>
        @endif
    </form>

    {{-- 3. eventi scaduti non esitati --}}
    <form class="card" id="scaduti" method="post" action="{{ route('tecnico.run', 'esita') }}" data-selection>
        @csrf
        <header>
            <h2>Eventi scaduti senza risultato</h2>
            <span class="chip count">{{ count($expired) }}</span>
        </header>
        <p class="desc"><span>Eventi partiti da oltre {{ \App\Libraries\MaintenanceOps::EXPIRED_AFTER_SECONDS / 60 }} minuti senza risultato. <b>Esita</b> genera il risultato con l'RNG (come il worker) e lo invia;
            <b>Annulla</b> chiude l'evento senza risultato: usalo solo per eventi mai pubblicati su Isibet.</span></p>
        @include('partials.tecnico-events', ['events' => $expired, 'extra' => 'sent'])
        @if (count($expired))
            <div class="actions">
                <span class="note" data-counter></span>
                <div class="right">
                    <label class="note"><input type="checkbox" name="invia_subito" value="1" checked> Invia subito il risultato a Isibet</label>
                    <button type="submit" class="danger" disabled formaction="{{ route('tecnico.run', 'annulla') }}"
                            data-confirm="Annullare gli eventi selezionati? Non verranno esitati.">Annulla selezionati</button>
                    <button type="submit" class="primary" disabled data-confirm="Generare il risultato degli eventi selezionati?">Esita selezionati</button>
                </div>
            </div>
        @endif
    </form>

    {{-- 4. risultati non inviati --}}
    <form class="card" id="risultati" method="post" action="{{ route('tecnico.run', 'risultati') }}" data-selection>
        @csrf
        <header>
            <h2>Risultati non inviati</h2>
            <span class="chip count">{{ count($resultsNotSent) }}</span>
        </header>
        <p class="desc"><span>Eventi con risultato generato e gara terminata, ma esito non ancora trasmesso a Isibet. Il worker syncResults li riprova ogni 30 secondi: se restano qui il worker è fermo o Isibet rifiuta l'invio.</span></p>
        @include('partials.tecnico-events', ['events' => $resultsNotSent, 'extra' => 'result'])
        @if (count($resultsNotSent))
            <div class="actions">
                <span class="note" data-counter></span>
                <div class="right"><button type="submit" class="primary" disabled>Invia risultati</button></div>
            </div>
        @endif
    </form>

    <p class="note">Massimo {{ $maxSelection }} eventi per operazione. Una sola operazione alla volta.</p>
</main>
<div class="busy" role="status">Operazione in corso: non chiudere la pagina</div>
<script>
    var MAX = {{ $maxSelection }};
    document.querySelectorAll('form[data-selection]').forEach(function (form) {
        var boxes = form.querySelectorAll('input[name="ids[]"]');
        var all = form.querySelector('[data-all]');
        var counter = form.querySelector('[data-counter]');
        var buttons = form.querySelectorAll('.actions button');
        function update() {
            var n = form.querySelectorAll('input[name="ids[]"]:checked').length;
            buttons.forEach(function (b) { b.disabled = n === 0 || n > MAX; });
            if (counter) counter.textContent = n === 0 ? 'Seleziona gli eventi su cui intervenire' : (n > MAX ? n + ' selezionati: massimo ' + MAX + ' per operazione' : n + (n === 1 ? ' evento selezionato' : ' eventi selezionati'));
        }
        boxes.forEach(function (b) { b.addEventListener('change', update); });
        if (all) all.addEventListener('change', function () {
            var i = 0;
            boxes.forEach(function (b) { b.checked = all.checked && i++ < MAX; });
            update();
        });
        update();
    });
    // conferma (sul pulsante o sul form) e blocco doppio invio
    document.querySelectorAll('form').forEach(function (form) {
        if (form.method.toLowerCase() !== 'post') return;
        form.addEventListener('submit', function (e) {
            var btn = e.submitter;
            var msg = (btn && btn.dataset.confirm) || form.dataset.confirm;
            if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
            document.body.classList.add('is-busy');
        });
    });
</script>
</body>
</html>
