<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pannello RTP</title>
    @include('partials.rtp-styles')
</head>
<body>
<main>
    @include('partials.rtp-nav', ['active' => 'panel'])
    <h1>Pannello RTP</h1>
    <p class="sub">RTP per mercato e per gioco, in percentuale. Se lasci il campo vuoto si usa il default della libreria.
        I nuovi valori valgono per gli eventi inviati a Isibet da ora in poi. Gli eventi già pubblicati mantengono l'RTP con cui sono stati inviati.</p>

    @if (session('status'))
        <div class="flash ok">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="flash err">Alcuni valori non sono validi: nessuna modifica salvata.</div>
    @endif

    @forelse ($games as $game)
        <form class="card" method="post" action="{{ route('rtp.update', ['gameId' => $game['id']]) }}">
            @csrf
            <header>
                <h2>{{ $game['label'] }}</h2>
                <span class="chip">{{ count($game['overrides']) }} personalizzati</span>
            </header>
            <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Mercato</th><th>Default</th><th>Range ammesso</th><th>RTP impostato (%)</th></tr>
                </thead>
                <tbody>
                @foreach ($markets as $market => $bounds)
                    @php
                        $default = $game['defaults'][$market] ?? null;
                        $current = $game['overrides'][$market] ?? null;
                        $errKey = $game['id'] . '.' . $market;
                        $value = old('rtp.' . $market) !== null && old('_game') == $game['id']
                            ? old('rtp.' . $market)
                            : ($current !== null ? number_format($current / 10000, 2, '.', '') : '');
                    @endphp
                    <tr>
                        <td>{{ \App\Libraries\RtpConfig::MARKET_LABELS[$market] ?? ucfirst($market) }}</td>
                        <td class="num">{{ $default !== null ? number_format($default / 10000, 2, ',', '') . '%' : '—' }}</td>
                        <td class="num">{{ number_format($bounds['min'] / 10000, 2, ',', '') }}% – {{ number_format($bounds['max'] / 10000, 2, ',', '') }}%</td>
                        <td>
                            <input type="text" inputmode="decimal" name="rtp[{{ $market }}]" value="{{ $value }}"
                                   placeholder="{{ $default !== null ? number_format($default / 10000, 2, '.', '') : '' }}"
                                   data-min="{{ $bounds['min'] / 10000 }}" data-max="{{ $bounds['max'] / 10000 }}"
                                   data-initial="{{ $value }}"
                                   class="{{ $errors->has($errKey) ? 'invalid' : '' }}">
                            @if ($errors->has($errKey))
                                <span class="field-err">{{ $errors->first($errKey) }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            <input type="hidden" name="_game" value="{{ $game['id'] }}">
            <div class="actions">
                <button type="button" data-reset>Ripristina default</button>
                <button type="submit" class="primary">Salva {{ $game['label'] }}</button>
            </div>
        </form>
    @empty
        <p class="note">Nessun gioco configurato.</p>
    @endforelse
</main>
<script>
    document.querySelectorAll('form.card').forEach(function (form) {
        var inputs = form.querySelectorAll('input[type=text]');
        function check(input) {
            var raw = input.value.trim().replace(',', '.');
            var n = parseFloat(raw);
            var bad = raw !== '' && (!/^\d{1,2}(\.\d{1,2})?$/.test(raw) || n < +input.dataset.min || n > +input.dataset.max);
            input.classList.toggle('invalid', bad);
            input.classList.toggle('changed', !bad && input.value !== input.dataset.initial);
        }
        inputs.forEach(function (i) { i.addEventListener('input', function () { check(i); }); });
        form.querySelector('[data-reset]').addEventListener('click', function () {
            inputs.forEach(function (i) { i.value = ''; check(i); });
        });
    });
</script>
</body>
</html>
