    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..800&display=swap" rel="stylesheet">
    <style>
        /* Back office: verde pista per la struttura, ambra per attenzione/stato attivo, colori semantici solo sugli stati */
        :root {
            --turf:#0F3B2C; --turf-2:#164a38; --turf-text:#d7e5dd; --turf-muted:#8fb0a0;
            --bg:#EEF1EE; --card:#fff; --head:#F6F8F6;
            --text:#16211C; --muted:#5D6B64; --line:#D5DCD7; --line-strong:#B9C4BD;
            --accent:#12684A; --accent-hover:#0d553c; --accent-soft:#E3F0EA; --chip:#E3F0EA;
            --silk:#E8A317;
            --ok:#1B6E42; --ok-soft:#E2F2E8;
            --warn:#7A4E00; --warn-soft:#FFF2D6;
            --err:#B3261E; --err-soft:#FBE9E7;
            --side:236px;
            --font:"Archivo", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        * { box-sizing:border-box; }
        html { -webkit-text-size-adjust:100%; }
        body { margin:0; background:var(--bg); color:var(--text); font:14px/1.5 var(--font); font-variation-settings:"wdth" 100; }
        main { max-width:1280px; margin:0; padding:32px 40px 64px; }
        a { color:var(--accent); text-underline-offset:2px; }
        a:hover { color:var(--accent-hover); }
        code { font-size:.92em; background:var(--head); border:1px solid var(--line); border-radius:4px; padding:0 4px; }
        :focus-visible { outline:2px solid var(--silk); outline-offset:2px; }

        /* titoli: condensati e pesanti, come un programma corse */
        h1 { font-size:32px; line-height:1.1; font-weight:780; font-stretch:72%; font-variation-settings:"wdth" 72; letter-spacing:-.005em; margin:0 0 8px; }
        .sub { color:var(--muted); margin:0 0 24px; max-width:78ch; }

        /* messaggi */
        .flash { padding:12px 16px; border-radius:6px; margin-bottom:16px; border:1px solid transparent; border-left-width:4px; max-width:100%; }
        .flash.ok { background:var(--ok-soft); color:var(--ok); border-color:#c3e2cf; border-left-color:var(--ok); }
        .flash.err { background:var(--err-soft); color:var(--err); border-color:#f1c9c4; border-left-color:var(--err); }
        .flash.warn, .warn { background:var(--warn-soft); color:var(--warn); }
        .flash.warn { border-color:#f2deb0; border-left-color:var(--silk); }

        /* pannelli */
        .card { background:var(--card); border:1px solid var(--line); border-radius:8px; margin-bottom:20px; overflow:hidden; }
        .card header { display:flex; gap:12px; justify-content:space-between; align-items:center; padding:14px 18px; border-bottom:1px solid var(--line); }
        .card header h2 { font-size:18px; line-height:1.2; font-weight:700; font-variation-settings:"wdth" 85; margin:0; }
        .chip { background:var(--chip); color:var(--accent); border-radius:999px; padding:2px 10px; font-size:12px; font-weight:600; font-variant-numeric:tabular-nums; white-space:nowrap; }
        .note { color:var(--muted); font-size:13px; }

        /* tabelle */
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; font-variant-numeric:tabular-nums; }
        th, td { padding:9px 16px; text-align:left; border-bottom:1px solid var(--line); white-space:nowrap; }
        th { font-weight:600; color:var(--muted); font-size:12.5px; background:var(--head); }
        tbody tr:hover td { background:#f8faf8; }
        tr:last-child td { border-bottom:0; }
        td.num { font-variant-numeric:tabular-nums; }

        /* campi */
        input[type=text], input[type=number], input[type=time], select {
            font:inherit; color:var(--text); background:#fff; border:1px solid var(--line-strong); border-radius:5px; padding:6px 9px; min-height:34px;
        }
        input[type=text] { width:90px; font-variant-numeric:tabular-nums; }
        input:focus, select:focus { outline:none; border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }
        input:focus-visible, select:focus-visible { outline:none; }
        input[type=checkbox] { width:16px; height:16px; accent-color:var(--accent); vertical-align:middle; }
        input.changed { border-color:var(--silk); background:var(--warn-soft); }
        input.invalid { border-color:var(--err); background:var(--err-soft); }
        .field-err { color:var(--err); font-size:12px; display:block; margin-top:3px; }

        /* pulsanti */
        .actions { display:flex; gap:8px; justify-content:flex-end; padding:12px 18px; border-top:1px solid var(--line); }
        .card > .actions { background:var(--head); }
        button { font:inherit; font-weight:600; min-height:34px; padding:6px 14px; border-radius:5px; border:1px solid var(--line-strong); background:#fff; color:var(--text); cursor:pointer; }
        button:hover { border-color:var(--muted); }
        button.primary { background:var(--accent); color:#fff; border-color:var(--accent); }
        button.primary:hover { background:var(--accent-hover); border-color:var(--accent-hover); }
        button.danger { color:var(--err); border-color:#e7b9b3; }
        button.danger:hover { background:var(--err-soft); border-color:var(--err); }
        button:disabled, button:disabled:hover { opacity:.45; cursor:not-allowed; background:#fff; border-color:var(--line-strong); }
        button.primary:disabled, button.primary:disabled:hover { background:var(--accent); border-color:var(--accent); }

        /* navigazione laterale */
        nav.side { position:fixed; inset:0 auto 0 0; width:var(--side); background:var(--turf); color:var(--turf-text); display:flex; flex-direction:column; padding:22px 14px 18px; z-index:10; }
        body:has(nav.side) { padding-left:var(--side); }
        .brand { padding:0 10px 22px; }
        .brand b { display:block; color:#fff; font-size:21px; line-height:1.05; font-weight:800; font-variation-settings:"wdth" 68; letter-spacing:.005em; }
        .brand span { color:var(--turf-muted); font-size:12.5px; }
        .nav-group { margin-bottom:18px; }
        .nav-group p { margin:0 0 4px; padding:0 10px; color:var(--turf-muted); font-size:12px; }
        nav.side a { display:block; position:relative; padding:7px 10px; border-radius:5px; color:var(--turf-text); text-decoration:none; }
        nav.side a:hover { background:var(--turf-2); color:#fff; }
        nav.side a.active { background:var(--turf-2); color:#fff; font-weight:600; }
        nav.side a.active::before { content:""; position:absolute; left:-14px; top:6px; bottom:6px; width:4px; border-radius:0 3px 3px 0; background:var(--silk); }
        /* orologio: il palinsesto è in UTC, l'operatore ragiona in ora italiana */
        .clock { margin-top:auto; border-top:1px solid var(--turf-2); padding:14px 10px 0; display:grid; grid-template-columns:auto 1fr; gap:2px 12px; align-items:baseline; }
        .clock dt { color:var(--turf-muted); font-size:12px; }
        .clock dd { margin:0; color:#fff; font-variant-numeric:tabular-nums; font-variation-settings:"wdth" 75; font-weight:700; }
        .clock dd.big { font-size:26px; line-height:1.1; color:var(--silk); }

        /* simulatore e form a griglia */
        .grid { display:grid; gap:12px 16px; grid-template-columns:repeat(auto-fill, minmax(150px, 1fr)); padding:16px 18px; }
        .grid label { display:block; font-size:12.5px; color:var(--muted); margin-bottom:4px; }
        .grid select, .grid input[type=text] { width:100%; }
        .row { display:flex; flex-wrap:wrap; gap:12px 18px; padding:16px 18px; border-bottom:1px solid var(--line); align-items:flex-end; }
        .row > div { min-width:160px; }
        .row label { display:block; font-size:12.5px; color:var(--muted); margin-bottom:4px; }
        .row input[type=text].wide { width:100%; min-width:260px; }
        .section-title { padding:14px 18px 0; font-size:13px; font-weight:600; color:var(--muted); }
        .probs { display:flex; flex-wrap:wrap; gap:6px; padding:12px 18px; }
        .probs span { background:var(--chip); border-radius:5px; padding:3px 8px; font-variant-numeric:tabular-nums; }
        .tabs { display:flex; gap:2px; overflow-x:auto; border-bottom:1px solid var(--line); padding:0 10px; }
        .tabs button { border:0; border-bottom:3px solid transparent; border-radius:0; background:none; min-height:42px; padding:10px 12px; color:var(--muted); font-weight:500; white-space:nowrap; }
        .tabs button:hover { color:var(--text); }
        .tabs button[aria-selected=true] { color:var(--text); border-bottom-color:var(--silk); font-weight:700; }
        .tabpanel { display:none; }
        .tabpanel.active { display:block; }
        .tab-head { display:flex; flex-wrap:wrap; gap:8px 24px; justify-content:space-between; align-items:center; padding:12px 18px; border-bottom:1px solid var(--line); }
        .stats { display:flex; flex-wrap:wrap; gap:8px 20px; font-size:13px; }
        .stats b { font-variant-numeric:tabular-nums; }
        .filter { width:180px; }
        th.sortable { cursor:pointer; user-select:none; }
        th.sortable:hover { color:var(--text); }
        th.sortable::after { content:" ↕"; opacity:.4; }
        .up { color:var(--ok); }
        .down { color:var(--err); }
        .capped td { background:var(--warn-soft); }
        .tag { font-size:11px; font-weight:600; border-radius:4px; padding:1px 5px; background:var(--warn-soft); color:var(--warn); margin-left:4px; }
        .scroll { max-height:560px; overflow:auto; }
        .scroll thead th { position:sticky; top:0; z-index:1; box-shadow:inset 0 -1px 0 var(--line); }

        /* sotto i 900px la barra laterale diventa una fascia in alto */
        @media (max-width: 900px) {
            body:has(nav.side) { padding-left:0; }
            nav.side { position:static; width:auto; flex-direction:row; flex-wrap:wrap; align-items:center; gap:4px 12px; padding:12px 16px; margin:0; }
            main > nav.side { margin:-20px -16px 20px; }
            .brand { padding:0 8px 0 0; }
            .brand span { display:none; }
            .nav-group { margin:0; display:flex; gap:2px; }
            .nav-group p { display:none; }
            nav.side a.active::before { left:10px; right:10px; top:auto; bottom:2px; width:auto; height:3px; border-radius:2px; }
            .clock { margin:0 0 0 auto; border:0; padding:0; grid-template-columns:auto auto auto auto; }
            .clock dd.big { font-size:16px; }
            main { padding:20px 16px 48px; }
            h1 { font-size:26px; }
        }
        @media (prefers-reduced-motion: reduce) { * { transition:none !important; animation:none !important; } }
    </style>
