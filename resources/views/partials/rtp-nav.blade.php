{{-- Navigazione laterale del back office. $active: panel | simulator | palinsesto | tecnico --}}
<nav class="side" aria-label="Back office">
    <div class="brand">
        <b>Sogei Virtual</b>
        <span>{{ $active === 'tecnico' ? 'Area tecnica' : 'Back office' }}</span>
    </div>

    @if ($active === 'tecnico')
        {{-- area tecnica: credenziali separate, nessun collegamento alle altre sezioni --}}
        <div class="nav-group">
            <p>Manutenzione</p>
            <a href="{{ route('tecnico.index') }}" class="active" aria-current="page">Eventi e Isibet</a>
        </div>
    @else
        <div class="nav-group">
            <p>Quote</p>
            <a href="{{ route('rtp.index') }}" class="{{ $active === 'panel' ? 'active' : '' }}" @if ($active === 'panel') aria-current="page" @endif>Impostazioni RTP</a>
            <a href="{{ route('rtp.simulator') }}" class="{{ $active === 'simulator' ? 'active' : '' }}" @if ($active === 'simulator') aria-current="page" @endif>Simulatore quote</a>
        </div>
        <div class="nav-group">
            <p>Programmazione</p>
            <a href="{{ route('palinsesto.index') }}" class="{{ $active === 'palinsesto' ? 'active' : '' }}" @if ($active === 'palinsesto') aria-current="page" @endif>Palinsesto</a>
        </div>
    @endif

    <dl class="clock" aria-label="Ora corrente">
        <dt>UTC</dt><dd class="big" data-clock="UTC">--:--</dd>
        <dt>Italia</dt><dd data-clock="Europe/Rome">--:--</dd>
    </dl>
</nav>
<script>
    (function () {
        var els = document.querySelectorAll('[data-clock]');
        function tick() {
            var now = new Date();
            els.forEach(function (el) {
                el.textContent = now.toLocaleTimeString('it-IT', { timeZone: el.dataset.clock, hour: '2-digit', minute: '2-digit', second: el.classList.contains('big') ? '2-digit' : undefined });
            });
        }
        tick();
        setInterval(tick, 1000);
    })();
</script>
