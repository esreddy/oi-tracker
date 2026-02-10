<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>OI Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
  /* highlight ATM row everywhere we list strikes */
  .atm-row{
    background:#173057 !important;
    box-shadow: inset 3px 0 0 #3ba3ff;
  }
  /* small badge to show which strike is ATM when needed */
  .atm-badge{
    font-size:11px;
    padding:2px 6px;
    border-radius:8px;
    background:#0f2e5f;
    color:#93c5fd;
    margin-left:6px;
  }
  /* OI Track table scroll */
  .hscroll{overflow-x:auto}
</style>

  <style>
    /* --- Base look --- */
    body{
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
      margin:24px;
      background:
        radial-gradient(circle at top left,#1d283a 0,#020617 40%,#020617 100%);
      color:#e8eefc;
    }
    /* Full-width layout, small side padding so it doesn't touch edges */
    .wrap{
      max-width:100%;
      width:100%;
      margin:0 auto;
      padding:0 8px;
    }

    h2{
      font-size:22px;
      font-weight:600;
      margin:0 0 12px;
      display:flex;
      align-items:center;
      gap:10px;
      letter-spacing:.02em;
    }
    h2::before{
      content:'';
      width:6px;
      height:24px;
      border-radius:999px;
      background:linear-gradient(180deg,#38bdf8,#6366f1);
      box-shadow:0 0 12px rgba(56,189,248,.7);
    }

    .small{font-size:12px;}
    .mono{font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}

    /* Layout grids */
    .row{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:16px;
    }

    @media (min-width: 1400px){
      .row{
        grid-template-columns:1.2fr 1.2fr 1fr;
      }
    }
    @media (max-width: 900px){
      .row{grid-template-columns:1fr}
    }

    .row-2col{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:16px;
    }
    .row-60-40{
      display:grid;
      grid-template-columns:3fr 2fr;
      gap:16px;
    }
    @media (max-width: 900px){
      .row-2col,
      .row-60-40{
        grid-template-columns:1fr;
      }
    }

    /* Cards */
    .card{
      background:radial-gradient(circle at top,#111827 0,#020617 55%,#020617 100%);
      border-radius:16px;
      padding:16px;
      box-shadow:
        0 18px 45px rgba(0,0,0,.65),
        0 0 0 1px rgba(148,163,184,.12);
      border:1px solid rgba(30,64,175,.5);
      backdrop-filter:blur(6px);
      transition:transform .15s ease-out, box-shadow .15s ease-out, border-color .15s ease-out, background .15s ease-out;
    }
    .card:hover{
      transform:translateY(-2px);
      box-shadow:
        0 22px 55px rgba(15,23,42,.9),
        0 0 0 1px rgba(59,130,246,.8);
      border-color:rgba(59,130,246,.8);
      background:radial-gradient(circle at top,#111827 0,#020617 50%,#020617 100%);
    }

    .pill{
      display:inline-flex;
      align-items:center;
      padding:4px 10px;
      border-radius:999px;
      background:#111827;
      border:1px solid rgba(148,163,184,.4);
      margin-right:8px;
      gap:6px;
      font-size:12px;
    }

    table{
      width:100%;
      border-collapse:collapse;
      margin-top:10px;
      font-size:13px;
    }
    th,td{
      padding:8px 6px;
      border-bottom:1px solid #24324d;
      text-align:right;
    }
    th:first-child,td:first-child{text-align:left}
    thead th{
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:.03em;
      background:linear-gradient(180deg,#020617,#020617);
      color:#9ca3af;
      position:sticky;
      top:0;
      z-index:2;
    }
    tbody tr:nth-child(odd){
      background:#020617;
    }
    tbody tr:nth-child(even){
      background:#020617;
    }
    tbody tr:hover{
      background:#111827;
    }

    /* OI Track – spacing between OI/ΔOI and between timeframes */
    #oiTrackTable th.tf-oi,
    #oiTrackTable td.tf-oi{
      padding-right:6px;
      white-space:nowrap;
    }
    #oiTrackTable th.tf-delta,
    #oiTrackTable td.tf-delta{
      padding-right:18px;
      border-right:1px solid #1f2937;
      white-space:nowrap;
    }
    #oiTrackTable th.tf-delta:last-of-type,
    #oiTrackTable td.tf-delta:last-of-type{
      border-right:none;
    }
    /* Current ΔOI emphasis */
    #oiTrackTable td:last-child{
      white-space:nowrap;
    }
    #oiTrackTable .cur-delta{
      font-weight:600;
      font-size:13px;
    }

    .up{color:#6ee7a2}
    .down{color:#f87171}
    .muted{color:#97a6c3}

    .big{font-size:28px;font-weight:700}
    .tag{padding:2px 8px;border-radius:8px;background:#1f2b44;margin-left:8px}

    .badge{
      padding:2px 8px;
      border-radius:999px;
      font-size:12px;
      display:inline-flex;
      align-items:center;
      gap:4px;
    }
    .b-green{background:#103b2d;color:#a7f3d0}
    .b-red{background:#3b1010;color:#fecaca}
    .b-blue{background:#0f2e5f;color:#bfdbfe}

    .status-bar{
      background:linear-gradient(90deg,rgba(15,23,42,.95),rgba(17,24,39,.95));
      border:1px solid rgba(148,163,184,.4);
      border-radius:12px;
      padding:6px 10px;
      margin-bottom:16px;
      display:flex;
      justify-content:space-between;
      gap:10px;
      align-items:center;
      position:sticky;
      top:0;
      z-index:20;
      box-shadow:0 10px 30px rgba(0,0,0,.7);
    }
    .status-left{
      font-size:12px;
      color:#97a6c3;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .status-right{
      font-size:12px;
      color:#97a6c3;
      white-space:nowrap;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .ok{color:#6ee7a2}
    .warn{color:#fde68a}
    .bad{color:#fca5a5}

    button{
      cursor:pointer;
      border:none;
      border-radius:999px;
      padding:4px 10px;
      font-size:12px;
      font-weight:500;
      background:radial-gradient(circle at top,#1d4ed8,#1e293b);
      color:#e5e7eb;
      box-shadow:0 0 0 1px rgba(59,130,246,.7);
      transition:background .15s ease-out, box-shadow .15s ease-out, transform .12s ease-out;
    }
    button:hover{
      background:radial-gradient(circle at top,#2563eb,#020617);
      box-shadow:0 0 18px rgba(59,130,246,.9);
      transform:translateY(-1px);
    }
    button:active{
      transform:translateY(0);
      box-shadow:0 0 0 1px rgba(37,99,235,.9);
    }

    .hscroll{overflow-x:auto;}

    /* --- Net Δ mini bars rendered in a table (Safari-proof) --- */
    table.nb-bars{ width:100%; border-collapse:separate; border-spacing:0 6px; }
    .nb-strike-td{ width:70px; text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; font-size:12px; }
    .nb-bar-td   { width:auto; }
    .nb-val-td   { width:140px; text-align:left; font-variant-numeric:tabular-nums; white-space:nowrap; font-size:12px; }
    .nb-svg{ display:block; width:100%; height:14px; }

    @media (max-width: 900px) {
      .row{grid-template-columns:1fr}
      .status-bar{
        flex-direction:column;
        align-items:flex-start;
      }
      .status-right{
        width:100%;
        justify-content:space-between;
      }
    }

    /* OI Track card & toolbar */
    .card-oi-track{
      margin-top:16px;
    }
    .card-oi-header{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:6px;
    }
    .card-oi-title{
      font-size:16px;
      font-weight:600;
      letter-spacing:.02em;
    }
    .oi-toolbar{
      display:flex;
      align-items:center;
      gap:8px;
      flex-wrap:wrap;
    }
    .oi-toolbar input{
      background:#020617;
      border-radius:999px;
      border:1px solid rgba(148,163,184,.5);
      color:#e5e7eb;
      padding:4px 8px;
      font-size:12px;
    }
    .oi-toolbar input:focus{
      outline:none;
      border-color:#60a5fa;
      box-shadow:0 0 0 1px rgba(59,130,246,.7);
    }

    /* small field styling for selects / inputs row */
    select, input[type="number"], input[type="text"]{
      background:#020617;
      border-radius:8px;
      border:1px solid rgba(148,163,184,.5);
      color:#e5e7eb;
      padding:3px 6px;
      font-size:12px;
    }
    select:focus, input[type="number"]:focus, input[type="text"]:focus{
      outline:none;
      border-color:#60a5fa;
      box-shadow:0 0 0 1px rgba(59,130,246,.7);
    }

    /* meter badges */
    .meter-badge{padding:2px 10px;border-radius:999px;font-weight:600}
    .m-bull{background:#103b2d;color:#a7f3d0}
    .m-bear{background:#3b1010;color:#fca5a5}
    .m-neutral{background:#0f2e5f;color:#93c5fd}
    .levels span{display:inline-block;margin-right:10px;margin-top:4px}

    /* --- Strike OI Heatmap upgraded layout --- */
    .hm-container{
      margin-top:8px;
      display:flex;
      flex-direction:column;
      gap:4px;
    }
    .hm-row{
      display:grid;
      grid-template-columns:64px minmax(0,0.7fr) minmax(0,260px);
      gap:8px;
      align-items:center;
      font-size:12px;
    }

    .hm-strike{
      font-variant-numeric:tabular-nums;
      white-space:nowrap;
    }
    .hm-bar-wrap{
      width:100%;
      height:18px;
      border-radius:999px;
      background:#020617;
      overflow:hidden;
      border:1px solid rgba(30,64,175,.8);
    }
    .hm-bar{
      display:flex;
      width:100%;
      height:100%;
    }
    .hm-call{
      background:linear-gradient(90deg,rgba(248,113,113,.85),rgba(248,113,113,.45));
    }
    .hm-put{
      background:linear-gradient(90deg,rgba(34,197,94,.9),rgba(34,197,94,.5));
    }
    .hm-label{
      font-variant-numeric:tabular-nums;
      font-size:11px;
      line-height:1.2;
      white-space:normal;
      overflow:hidden;
    }

    .hm-row-dominant-call .hm-bar-wrap{
      box-shadow:0 0 0 1px rgba(248,113,113,.7);
    }
    .hm-row-dominant-put .hm-bar-wrap{
      box-shadow:0 0 0 1px rgba(34,197,94,.7);
    }
    .hm-row-balanced .hm-bar-wrap{
      box-shadow:0 0 0 1px rgba(59,130,246,.7);
    }
  </style>

</head>
<body>
<div class="wrap">
  <h2>Intraday OI Levels</h2>

  <!-- STATUS BAR -->
  <?php
    $fresh = 7;   // <= 7m green
    $warn  = 12;  // 8–12m yellow
    $dbAgo    = $health['db']['minsAgo']    ?? null;
    $fetchAgo = $health['fetch']['minsAgo'] ?? null;
    $enrAgo   = $health['enrich']['minsAgo']?? null;
    $cls = function($m) use ($fresh,$warn){
      if ($m===null) return 'bad';
      if ($m <= $fresh) return 'ok';
      if ($m <= $warn)  return 'warn';
      return 'bad';
    };
  ?>
  <div class="status-bar">
    <div class="status-left">
      <strong>DB last insert:</strong>
      <span class="<?= $cls($dbAgo) ?>">
        <?= esc($health['db']['ist'] ?? '—') ?> (<?= $dbAgo!==null ? esc($dbAgo).' min ago' : 'no data' ?>)
      </span>
      &nbsp;|&nbsp;
      <strong>Fetch cron:</strong>
      <span class="<?= $cls($fetchAgo) ?>">
        <?= esc($health['fetch']['ist'] ?? 'No log') ?> (<?= $fetchAgo!==null ? esc($fetchAgo).' min ago' : '—' ?>)
      </span>
      &nbsp;|&nbsp;
      <strong>Enrich cron:</strong>
      <span class="<?= $cls($enrAgo) ?>">
        <?= esc($health['enrich']['ist'] ?? 'No log') ?> (<?= $enrAgo!==null ? esc($enrAgo).' min ago' : '—' ?>)
      </span>
    </div>

    <div class="status-right">
      <span id="sessionTag" class="muted"></span>
      <span id="clockDrift" class="muted"></span>
      <span><strong>Next refresh in:</strong> <span id="refreshTimer">60</span>s</span>
      <button id="refreshNowBtn">Refresh now</button>
    </div>
  </div>

<div class="row"> 
  <div class="card" id="breakout5m" style="display:none; margin-bottom:16px">
    <div id="bo5mText" style="font-weight:600">Loading…</div>
    <div class="muted" style="margin-top:6px">
    Rule: Close > max(High, last 10) with strong body (5m) for Bullish; inverse for Bearish.
    </div>
  </div>

  <!-- Breakout + Swings (today, 15m) -->
  <div class="card" id="signalCard" style="margin-bottom:16px; display:none;">
    <div id="signalText" style="font-weight:600"></div>
    <div id="swingText"  class="muted" style="margin-top:8px"></div>
    <div class="muted" style="margin-top:6px">
      Rules: Breakout = Close &gt; max(High last 10) &amp; body &ge; 1.5× avg body (15m).
      Swings = 5-bar fractals (HH/LL).
    </div>
  </div>
    
    <div class="card" id="trendCard" style="margin-top:16px; display:none">
      <b>Intraday Trend (auto, last <span id="trendLookback">15</span>m)</b>
      <div id="trendSummary" style="margin-top:8px"></div>
      <div id="trendWhy" class="muted" style="margin-top:6px"></div>
    </div>
</div>


<!-- ZigZag + Market Meter in one row -->
<div class="row-2col" style="margin-top:16px;align-items:flex-start;gap:16px">
<div class="card" id="zzCard" style="margin-top:16px; display:none">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
    <b>ZigZag % Swings</b>
    <div class="pill">
      Threshold:
      <input id="zzPct" type="number" step="0.05" value="0.35" style="width:80px">
      Min bars:
      <input id="zzBars" type="number" min="1" value="2" style="width:60px">
      <button id="zzApply">Apply</button>
    </div>
  </div>

  <div id="zzMeta" class="muted" style="margin-top:6px">—</div>

  <svg id="zzSpark" viewBox="0 0 500 120" preserveAspectRatio="none"
       style="width:100%;height:120px;margin-top:8px;background:#0f172a;border-radius:8px"></svg>

  <table style="margin-top:8px">
    <thead><tr>
      <th>Pivot</th><th>Time (IST)</th><th class="mono">Price</th>
      <th class="mono">Δ% from prev</th><th class="mono">Duration</th>
    </tr></thead>
    <tbody id="zzTable"></tbody>
  </table>
</div>

<!-- Market Meter + Key Stats + Advanced -->
<div class="card" id="marketMeter" style="margin-bottom:16px;display:none">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <div>
      <b>Market Meter</b>
      <div id="meterHeadline" style="margin-top:6px"></div>
      <div id="meterWhy" class="muted" style="margin-top:6px"></div>
    </div>
    <div id="meterBadge"></div>
  </div>

  <!-- Support / Resistance -->
  <div class="levels" style="margin-top:10px">
    <span><b>Support (Put OI):</b> <span id="meterSupport"></span></span>
    <span><b>Resistance (Call OI):</b> <span id="meterResistance"></span></span>
  </div>

  <!-- Key Stats -->
  <div class="levels" style="margin-top:8px">
    <span><b>Price:</b> <span id="ksPrice" class="mono">-</span></span>
    <span><b>ATM:</b> <span id="ksAtm" class="mono">-</span></span>
    <span><b>PCR:</b> <span id="ksPcr" class="mono">-</span></span>
    <span><b>Bias:</b> <span id="ksBias">-</span></span>
  </div>

  <!-- ATM zone + Price vs OI -->
  <div class="levels" style="margin-top:6px">
    <span><b>ATM Zone (±100):</b> <span id="ksAtmZone" class="mono">-</span></span>
    <span><b>Price vs OI:</b> <span id="ksPriceOI">-</span></span>
  </div>

  <!-- OI pressure + Trend confidence -->
  <div class="levels" style="margin-top:6px">
    <span><b>OI Pressure:</b> <span id="ksOiPressure">-</span></span>
    <span><b>Trend Confidence:</b> <span id="ksTrendConf">-</span></span>
  </div>

  <!-- Breakout probability -->
  <div class="levels" style="margin-top:6px">
    <span><b>Breakout Prob:</b>
      <span id="ksBoUp" class="mono">-</span> ↑ /
      <span id="ksBoDown" class="mono">-</span> ↓
    </span>
  </div>
  
</div>
</div>


  <!-- TOP ROW: PCR Trend + Classifications -->
  <div class="row">
    <div class="card">
      <b>PCR Trend (latest 12)</b>
      <svg id="pcrSpark" width="120" height="24" style="vertical-align:middle; margin-left:8px"></svg>
      <table>
        <thead>
          <tr><th>Time (IST)</th><th class="mono">Price</th><th>PCR</th><th>Bias</th></tr>
        </thead>
        <tbody id="pcrTrend"></tbody>
      </table>
      <small class="muted">Tip: rising PCR → bullish tilt; falling PCR → bearish tilt.</small>
    </div>

    <div class="card">
      <b>Last 5 Classifications</b>
      <table>
        <thead>
          <tr><th>Time (IST)</th><th>Price vs OI</th><th>Top Put</th><th>Top Call</th><th>Bias</th><th>Suggested Trade</th></tr>
        </thead>
        <tbody id="classTable"></tbody>
      </table>
      <small class="muted">
      Long Build-up: Price↑ &amp; OI↑ (Bullish → Buy Futures / CE / Bullish Spread) ·
      Short Build-up: Price↓ &amp; OI↑ (Bearish → Sell Futures / PE / Bearish Spread) ·
      Short Covering: Price↑ &amp; OI↓ (Bullish short-term → Quick CE Buy) ·
      Long Unwinding: Price↓ &amp; OI↓ (Bearish short-term → Avoid Longs / Quick Short)
      </small>
    </div>
    <div class="card">
      <div><span class="pill">Bias: <b id="bias">-</b></span>
           <span class="pill">PCR: <b id="pcr">-</b></span>
           <span class="pill">ATM: <b id="atm">-</b></span>
      </div>
      <table>
       <thead>
        <tr>
          <th>Strike</th>
          <th>Type</th>
          <th>OI</th>
          <th>ΔOI (Intra)</th>
          <th>ΔOI (Day)</th>
          <th>ΔOI (NSE)</th>
        </tr>
        </thead>
        <tbody id="topRows"></tbody>
      </table>
      
      <small class="muted">
        <b>ΔOI (Intra)</b> = change since selected window start ·
        <b>ΔOI (Day)</b> = latest OI - yesterday's EOD OI (computed) ·
        <b>ΔOI (NSE)</b> = NSE "CHNG IN OI" (from <code>chg_oi</code>)
      </small>
    </div>
  </div>
  <!-- Strike OI Heatmap -->
  <div class="card" style="margin-top:16px">
    <b>Strike OI Heatmap (near ATM)</b>
    <div id="strikeHeatmap" class="hm-container"></div>
    <small class="muted">
      Each row = Strike vs stacked Call / Put OI near ATM. Bar length ≈ total OI ·
      Green = Put-heavy support · Red = Call-heavy resistance · Blue = balanced.
    </small>
  </div>

  <!-- SUMMARY ROW -->
  <div class="row" style="margin-top:16px">
    <div class="card">
      <div id="hdr" class="big muted">Loading…</div>
      <div id="meta" style="margin-top:8px;"></div>
      <div style="margin-top:12px">
        <label>Symbol:</label>
        <select id="symbol">
          <option>NIFTY</option>
          <option>BANKNIFTY</option>
          <option>FINNIFTY</option>
        </select>

        <label style="margin-left:10px">Expiry:</label>
        <select id="expirySel" style="min-width:140px"><option value="">Nearest active</option></select>

        <label style="margin-left:10px">Window:</label>
        <input id="window" type="number" value="10" min="1" style="width:70px">

        <label style="margin-left:10px">Strikes ±</label>
        <select id="strikeWin" style="width:70px">
          <option value="5">5</option>
          <option value="10" selected>10</option>
          <option value="15">15</option>
          <option value="999">All</option>
        </select>

        <label style="margin-left:10px">Band ±</label>
        <select id="atmBand" style="width:70px">
          <option value="3" selected>3</option>
          <option value="5">5</option>
          <option value="10">10</option>
        </select>

        <label style="margin-left:10px">
          <input type="checkbox" id="showNextExp"> Show next expiry
        </label>

        <div style="margin-top:8px">
          <span class="pill">Filter:
            <label style="margin-left:6px"><input type="radio" name="optFilter" value="both" checked> Both</label>
            <label style="margin-left:6px"><input type="radio" name="optFilter" value="calls"> Calls</label>
            <label style="margin-left:6px"><input type="radio" name="optFilter" value="puts"> Puts</label>
          </span>
          <button id="refreshBtn" style="margin-left:8px">Refresh</button>
          <span class="muted" id="lastRef" style="margin-left:8px;"></span>
        </div>
      </div>
    </div>

    
  </div>

  
  <!-- Next Expiry Snapshot -->
  <div class="card" id="nextCard" style="margin-top:16px; display:none">
    <b>Next Expiry Snapshot</b>
    <div id="nextHdr" class="muted" style="margin-top:6px">Loading…</div>

    <div style="margin-top:8px">
      <span class="pill">PCR: <b id="nextPcr">-</b></span>
      <span class="pill">ATM: <b id="nextAtm">-</b></span>
    </div>

    <table style="margin-top:8px">
      <thead><tr><th>Strike</th><th>Type</th><th>OI</th><th>ΔOI</th></tr></thead>
      <tbody id="nextTopRows"></tbody>
    </table>

    <small class="muted" id="nextCompare" style="display:block;margin-top:6px"></small>
  </div>

  <!-- OI TRACK CARD -->
  <div class="card card-oi-track">
    <div class="card-oi-header">
      <div>
        <div class="card-oi-title">OI Track</div>
        <div class="muted small">Multi-timeframe OI &amp; ΔOI by strike (today vs intraday windows)</div>
      </div>
      <div class="oi-toolbar">
        <label class="pill">Timeframes (m):
          <input id="tfInput" value="5,10,15,30" class="mono" style="width:180px">
          <button id="tfApply">Apply</button>
        </label>
      </div>
    </div>

    <div class="hscroll oi-track-scroll" style="overflow-x:visible">
      <table class="table table-sm" id="oiTrackTable">
        <thead><tr id="trackHeadRow"></tr></thead>
        <tbody id="trackTable"></tbody>
      </table>
    </div>
  </div>
  


  <!-- Delta by strike -->
  <div class="card" style="margin-top:16px">
    <b>Delta by Strike (last window)</b>
    <table>
      <thead>
  <tr>
    <th>Strike</th>
    <th>Call ΔOI (Intra)</th>
    <th>Put ΔOI (Intra)</th>
    <th>Call ΔOI (Day)</th>
    <th>Put ΔOI (Day)</th>
  </tr>
</thead>
      <tbody id="deltaTable"></tbody>
    </table>
  </div>

  <!-- Net ΔOI bars & exports -->
  <div class="card" style="margin-top:16px">
    <b>Net ΔOI by Strike (PutΔ − CallΔ)</b>

    <div style="margin-top:6px;margin-bottom:6px">
      <label class="pill" style="cursor:pointer">
      View:
      <select id="netView" style="margin-left:6px">
        <option value="intra" selected>Intra</option>
        <option value="day">Day</option>
      </select>
      </label>
    </div>
    <!-- Big OI Δ vs Strike chart (Sensibull-style) -->
    <div style="margin-top:4px">
      <svg id="oiChangeChart"
            viewBox="0 0 100 60"
            preserveAspectRatio="none"
            style="width:100%;height:220px;background:#020617;border-radius:8px"></svg>
      <div class="muted" style="margin-top:4px;font-size:11px">
        Green = Put ΔOI, Red = Call ΔOI. Above line = increase, below line = decrease.
      </div>
    </div>
    <!-- Mini bars (table rendering) -->
    <table class="nb-bars" style="margin-top:8px">
      <tbody id="netBarsBody"></tbody>
    </table>

    <!-- Real table (scrollable) -->
    <div class="hscroll" style="margin-top:10px">
      <table style="min-width:520px">
        <thead>
          <tr>
            <th>Strike</th>
            <th class="mono">Call ΔOI</th>
            <th class="mono">Put ΔOI</th>
            <th class="mono">Net (Put−Call)</th>
          </tr>
        </thead>
        <tbody id="netTable"></tbody>
      </table>
    </div>

    <div style="margin-top:8px">
      <button id="exportDelta" class="pill">Export ΔOI CSV</button>
      <button id="exportPCR" class="pill">Export PCR CSV</button>
    </div>
  </div>
</div>


<script>
// ====== Config ======
const AUTO_REFRESH_SEC = 60;
const timerEl = document.getElementById('refreshTimer');

// ====== IST helpers ======
function fmtIST(ts){
  if(!ts) return '-';
  const d = new Date(String(ts).replace(' ','T') + 'Z');
  const s = d.toLocaleString('en-IN',{hour12:false,timeZone:'Asia/Kolkata',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit'});
  const [datePart,timePart] = s.split(', '); const [dd,mm,yyyy] = datePart.split('/');
  return `${yyyy}-${mm}-${dd} ${timePart}`;
}
function fmtISTsec(ts){
  if(!ts) return '-';
  const d = new Date(String(ts).replace(' ','T') + 'Z');
  const s = d.toLocaleString('en-IN',{hour12:false,timeZone:'Asia/Kolkata',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit'});
  const [datePart,timePart] = s.split(', '); const [dd,mm,yyyy] = datePart.split('/');
  return `${yyyy}-${mm}-${dd} ${timePart} IST`;
}
function fmtNum(n){ return Number(n).toLocaleString('en-IN'); }

// ====== UI state ======
let UI = {
  optFilter: 'both',
  strikeWin: 10,     // ± strikes around ATM (50-pt step assumed)
  atmBand: 3,        // highlight ± band
  expiry: '',
  lastServerTsUTC: null,
  metricsRows: [],
  expiriesList: []
};

function isMarketOpenIST() {
  const now = new Date();
  const ist = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Kolkata' }));
  const day = ist.getDay(); if (day===0 || day===6) return false;
  const mins = ist.getHours()*60 + ist.getMinutes();
  return mins >= (9*60+15) && mins <= (15*60+30);
}
function updateSessionAndDrift(){
  document.getElementById('sessionTag').textContent = isMarketOpenIST() ? 'Live (Market Hours)' : 'After Hours';
  if (UI.lastServerTsUTC){
    const d = new Date(String(UI.lastServerTsUTC).replace(' ','T')+'Z');
    const istNow = new Date(new Date().toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
    const diffMin = Math.round( Math.abs(istNow - d)/60000 );
    const el = document.getElementById('clockDrift');
    el.textContent = diffMin>2 ? `Clock drift ~${diffMin}m` : '';
  }
}

// ====== Badges ======
function badgeBias(b){ if(b==='Bullish') return `<span class="badge b-green">${b}</span>`;
  if(b==='Bearish') return `<span class="badge b-red">${b}</span>`; return `<span class="badge b-blue">${b??'-'}</span>`; }
function badgeClass(c){ if(c==='Long Build-up'||c==='Short Covering') return `<span class="badge b-green">${c}</span>`;
  if(c==='Short Build-up'||c==='Long Unwinding') return `<span class="badge b-red">${c}</span>`; return `<span class="badge b-blue">${c??'-'}</span>`; }

// ====== Data loaders ======
let lastJson = null;

async function loadExpiries(){
  const symbol = document.getElementById('symbol').value;
  const res = await fetch(`/oi/expiries?symbol=${symbol}`);
  const j = await res.json();
  const sel = document.getElementById('expirySel');
  sel.innerHTML = `<option value="">Nearest active</option>`;
  UI.expiriesList = [];

  if (j.ok && Array.isArray(j.expiries)){
    UI.expiriesList = j.expiries.slice();
    j.expiries.forEach(e=>{
      const opt = document.createElement('option'); opt.value = e; opt.textContent = e; sel.appendChild(opt);
    });
  }
}

async function loadSnapshot(){
  const symbol = document.getElementById('symbol').value;
  const windowM= document.getElementById('window').value;
  const params = new URLSearchParams({ symbol, window: windowM });
  if (UI.expiry) params.append('expiry', UI.expiry);

  const res = await fetch(`/oi/json?${params.toString()}`);
  const j   = await res.json();
  if(!j.ok) throw new Error(j.msg||'No data');
  lastJson = j;

  document.getElementById('hdr').textContent = `${j.symbol} @ ${(+j.price).toFixed(2)} | Exp ${j.expiry}`;
  document.getElementById('meta').innerHTML = `<span class="muted">TS:</span> ${fmtISTsec(j.ts)}  <span class="tag">Window ${j.window}m</span>`;
  document.getElementById('pcr').textContent = j.pcr ?? '-';
  document.getElementById('bias').textContent = j.bias ?? '-';
  document.getElementById('atm').textContent = j.atm ?? '-';
  document.getElementById('lastRef').textContent =
    'Updated: ' + new Date().toLocaleTimeString('en-IN',{hour12:false,timeZone:'Asia/Kolkata'}) + ' IST';

  UI.lastServerTsUTC = j.ts; updateSessionAndDrift();

  renderTopOI(j);
  renderDeltaTable(j);
  renderNetBars(j);
  renderOiChangeChart(j);
  renderStrikeHeatmap(j);
}
function renderTopOI(j){
  const top = document.getElementById('topRows');
  top.innerHTML='';

  const fmt = n => Number(n).toLocaleString('en-IN');
  const cell = (v) => {
    if (v === undefined || v === null) return '<td class="muted">—</td>';
    const cls = v>0 ? 'up' : (v<0 ? 'down' : '');
    const arrow = v>0 ? '▲' : (v<0 ? '▼' : '');
    return `<td class="${cls}">${fmt(v)} ${arrow}</td>`;
  };

  // CE rows
  if (UI.optFilter !== 'puts') {
    for (const [k,v] of Object.entries(j.topCalls||{})){
      const intra = (j.callDelta||{})[k];      // window change
      const day   = (j.callDayDelta||{})[k];   // your computed day change
      const nse   = (j.callNseDelta||{})[k];   // NSE chg_oi (official)
      top.insertAdjacentHTML('beforeend',
        `<tr>
           <td>${k}</td><td>CE</td><td>${fmt(v)}</td>
           ${cell(intra)}${cell(day)}${cell(nse)}
         </tr>`);
    }
  }

  // PE rows
  if (UI.optFilter !== 'calls') {
    for (const [k,v] of Object.entries(j.topPuts||{})){
      const intra = (j.putDelta||{})[k];
      const day   = (j.putDayDelta||{})[k];
      const nse   = (j.putNseDelta||{})[k];
      top.insertAdjacentHTML('beforeend',
        `<tr>
           <td>${k}</td><td>PE</td><td>${fmt(v)}</td>
           ${cell(intra)}${cell(day)}${cell(nse)}
         </tr>`);
    }
  }
}
let sortKey = 'absnet'; // 'strike'|'calld'|'putd'|'net'|'absnet'
let sortAsc = false;

function renderDeltaTable(j){
  if(!j) return;

  const atm    = j.atm;
  const band   = UI.atmBand;
  const around = UI.strikeWin;

  const strikes = new Set([
    ...Object.keys(j.callDelta||{}),
    ...Object.keys(j.putDelta||{}),
    ...Object.keys(j.callDayDelta||{}),
    ...Object.keys(j.putDayDelta||{})
  ]);

  const rows = [...strikes].map(s=>{
    const st = parseInt(s,10);
    const cdIntra = Number((j.callDelta||{})[s]    ?? 0);
    const pdIntra = Number((j.putDelta||{})[s]     ?? 0);
    const cdDay   = Number((j.callDayDelta||{})[s] ?? 0);
    const pdDay   = Number((j.putDayDelta||{})[s]  ?? 0);
    const withinATM = Math.abs(st - atm) <= band*50;
    const withinWin = (around===999) ? true : Math.abs(st - atm) <= around*50;
    return { st, cdIntra, pdIntra, cdDay, pdDay, withinATM, withinWin };
  }).filter(r=>r.withinWin)
    .sort((a,b)=>a.st-b.st);

  const fmt = n => Number(n).toLocaleString('en-IN');
  const col = v => {
    const cls = v>0?'up':(v<0?'down':'');
    const arrow = v>0?'▲':(v<0?'▼':'');
    return `<td class="${cls}">${fmt(v)} ${arrow}</td>`;
  };

  const html = rows.map(r=>{
    const hl = r.withinATM ? ' class="atm-row"' : '';
    return `<tr${hl}>
      <td>${r.st}</td>
      ${col(r.cdIntra)}${col(r.pdIntra)}${col(r.cdDay)}${col(r.pdDay)}
    </tr>`;
  }).join('');

  document.getElementById('deltaTable').innerHTML = html;
}

// Clickable headers for delta table
(function makeDeltaHeadersClickable(){
  const deltaCard = Array.from(document.querySelectorAll('.card')).find(c=>c.querySelector('b')?.textContent.includes('Delta by Strike'));
  const thead = deltaCard ? deltaCard.querySelector('thead') : null;
  if(!thead) return;
  thead.style.cursor='pointer';
  thead.addEventListener('click', (e)=>{
    const ths = Array.from(thead.querySelectorAll('th'));
    const idx = ths.indexOf(e.target.closest('th'));
    if (idx===0){ sortKey='strike'; sortAsc = !sortAsc; }
    if (idx===1){ sortKey='calld';  sortAsc = !sortAsc; }
    if (idx===2){ sortKey='putd';   sortAsc = !sortAsc; }
    renderDeltaTable(lastJson);
  });
})();

// ===== Net Δ bar mini-bars (table + SVG) + numeric table =====
function renderNetBars(j){
  if(!j) return;

  const atm    = j.atm;
  const around = UI.strikeWin;

  const strikes = new Set([
    ...Object.keys(j.callDelta || {}),
    ...Object.keys(j.putDelta  || {})
  ]);

  let rows = [...strikes].map(s=>{
    const st = parseInt(s,10);
    const cd = Number(j.callDelta?.[s] ?? 0);
    const pd = Number(j.putDelta?.[s]  ?? 0);
    const net = pd - cd; // PutΔ − CallΔ
    const withinWin = (around===999) ? true : Math.abs(st - atm) <= around*50;
    return { st, cd, pd, net, withinWin };
  }).filter(r => r.withinWin)
    .sort((a,b)=> a.st - b.st);

  // Mini bars
  const body = document.getElementById('netBarsBody');
  const maxAbs = Math.max(1, ...rows.map(r=>Math.abs(r.net)));
  body.innerHTML = rows.map(r=>{
    const pct = Math.abs(r.net) / maxAbs;
    const up  = r.net >= 0;
    const label = up ? 'PutΔ>' : '<CallΔ';
    const bar = up
      ? `<rect x="50" y="2" width="${Math.max(0.5, 50*pct)}" height="10" fill="#103b2d"></rect>`
      : `<rect x="${50 - Math.max(0.5, 50*pct)}" y="2" width="${Math.max(0.5, 50*pct)}" height="10" fill="#3b1010"></rect>`;
    return `
      <tr>
        <td class="nb-strike-td mono">${r.st}</td>
        <td class="nb-bar-td">
          <svg class="nb-svg" viewBox="0 0 100 14" preserveAspectRatio="none">
            <rect x="49.75" y="0" width="0.5" height="14" fill="#24324d" opacity="0.6"></rect>
            ${bar}
          </svg>
        </td>
        <td class="nb-val-td mono">${label} ${fmtNum(r.net)}</td>
      </tr>`;
  }).join('');

  // Numeric table
  const tbody = document.getElementById('netTable');
  if (tbody){
    tbody.innerHTML = rows.map(r=>{
      const clsCD = r.cd>0?'up':(r.cd<0?'down':'');
      const clsPD = r.pd>0?'up':(r.pd<0?'down':'');
      const clsNT = r.net>0?'up':(r.net<0?'down':'');
      return `<tr>
        <td>${r.st}</td>
        <td class="${clsCD} mono">${fmtNum(r.cd)} ${r.cd>0?'▲':(r.cd<0?'▼':'')}</td>
        <td class="${clsPD} mono">${fmtNum(r.pd)} ${r.pd>0?'▲':(r.pd<0?'▼':'')}</td>
        <td class="${clsNT} mono">${fmtNum(r.net)} ${r.net>0?'▲':(r.net<0?'▼':'')}</td>
      </tr>`;
    }).join('');
  }

  // meter inputs
  UI.netDeltaSum = rows.reduce((s,r)=> s + (r.pd - r.cd), 0);

  const topN = (obj, n=3) => Object.entries(obj||{})
      .map(([k,v]) => ({strike: parseInt(k,10), oi: Number(v)}))
      .sort((a,b) => b.oi - a.oi)
      .slice(0,n);

  UI.supportList = topN(j.topPuts, 3);
  UI.resistList  = topN(j.topCalls, 3);

  updateMarketMeter();
}
// ===== Big OI Δ vs Strike chart (Sensibull-style) =====
function renderOiChangeChart(j){
  if (!j) return;
  const svg = document.getElementById('oiChangeChart');
  if (!svg) return;

  const atm    = j.atm;
  const around = UI.strikeWin;

  const callSrc = j.callDelta || {};
  const putSrc  = j.putDelta  || {};

  const strikes = new Set([
    ...Object.keys(callSrc),
    ...Object.keys(putSrc),
  ]);

  let rows = [...strikes].map(s => {
    const st = parseInt(s, 10);
    const cd = Number(callSrc[s] ?? 0);
    const pd = Number(putSrc[s]  ?? 0);
    const withinWin = (around === 999) ? true : Math.abs(st - atm) <= around * 50;
    return { st, cd, pd, withinWin };
  }).filter(r => r.withinWin)
    .sort((a,b) => a.st - b.st);

  if (!rows.length){
    svg.innerHTML = '';
    return;
  }

  const maxAbs = Math.max(
    1,
    ...rows.map(r => Math.max(Math.abs(r.cd), Math.abs(r.pd)))
  );

  const W = 100;
  const H = 60;
  const baseline = H * 0.5;
  const groupW = W / rows.length;
  const barW   = groupW * 0.35;
  const maxBarH = H * 0.4;

  svg.setAttribute('viewBox', `0 0 ${W} ${H}`);

  const parts = [];

  parts.push(
    `<rect x="0" y="${baseline-0.25}" width="${W}" height="0.5" fill="#1e293b" />`
  );

  const atmIdx = rows.findIndex(r => r.st === atm);
  if (atmIdx >= 0){
    const cx = (atmIdx + 0.5) * groupW;
    parts.push(
      `<rect x="${cx-0.3}" y="2" width="0.6" height="${H-4}" fill="#3b82f6" opacity="0.35" />`
    );
  }

  rows.forEach((r, i) => {
    const cx = (i + 0.5) * groupW;
    const leftX  = cx - barW;
    const rightX = cx;

    const callVal = r.cd;
    const putVal  = r.pd;

    const callH = Math.abs(callVal) / maxAbs * maxBarH;
    const putH  = Math.abs(putVal)  / maxAbs * maxBarH;

    const drawBar = (x, val, h, colorUp, colorDown) => {
      if (h < 0.2) return;
      const up = val >= 0;
      const y  = up ? (baseline - h) : baseline;
      const color = up ? colorUp : colorDown;
      parts.push(
        `<rect x="${x}" y="${y}" width="${barW*0.9}" height="${h}" fill="${color}" />`
      );
    };

    drawBar(leftX, callVal, callH, '#f97373', '#7f1d1d');
    drawBar(rightX, putVal, putH, '#22c55e', '#14532d');
  });

  svg.innerHTML = parts.join('');
}
// ===== Strike OI Heatmap (near ATM) =====
function renderStrikeHeatmap(j){
  if (!j) return;
  const el = document.getElementById('strikeHeatmap');
  if (!el) return;

  const calls = j.topCalls || {};
  const puts  = j.topPuts  || {};
  const atm   = Number(j.atm || 0) || null;

  const map = {};
  for (const [k,v] of Object.entries(calls)) {
    const st = parseInt(k, 10); if (!st) continue;
    if (!map[st]) map[st] = { strike: st, ce: 0, pe: 0 };
    map[st].ce += Number(v) || 0;
  }
  for (const [k,v] of Object.entries(puts)) {
    const st = parseInt(k, 10); if (!st) continue;
    if (!map[st]) map[st] = { strike: st, ce: 0, pe: 0 };
    map[st].pe += Number(v) || 0;
  }

  let rows = Object.values(map);
  if (!rows.length) {
    el.innerHTML = '<span class="muted">No OI data for heatmap.</span>';
    return;
  }

  const totalSorter = (a, b) => (b.ce + b.pe) - (a.ce + a.pe);

  if (atm) {
    rows.sort((a, b) => {
      const da = Math.abs(a.strike - atm), db = Math.abs(b.strike - atm);
      if (da !== db) return da - db;
      return totalSorter(a, b);
    });
  } else {
    rows.sort(totalSorter);
  }

  const top = rows.slice(0, 18);
  const totals = top.map(r => r.ce + r.pe);
  const maxTot = Math.max(...totals, 1);

  el.innerHTML = '';
  top.forEach(r => {
    const tot = r.ce + r.pe;
    const ce  = r.ce;
    const pe  = r.pe;
    if (!tot) return;

    const scale   = tot / maxTot;
    const ceRatio = (ce / tot) * scale;
    const peRatio = (pe / tot) * scale;

    let domClass = 'hm-row-balanced';
    let note = 'Balanced';
    if (pe > ce * 1.1) { domClass = 'hm-row-dominant-put';  note = 'Put-heavy'; }
    else if (ce > pe * 1.1) { domClass = 'hm-row-dominant-call'; note = 'Call-heavy'; }

    const rowDiv = document.createElement('div');
    rowDiv.className = 'hm-row ' + domClass;

    const strikeDiv = document.createElement('div');
    strikeDiv.className = 'hm-strike mono';
    strikeDiv.textContent = r.strike;
    if (atm && r.strike === atm) {
      const chip = document.createElement('span');
      chip.className = 'atm-badge';
      chip.textContent = 'ATM';
      strikeDiv.appendChild(chip);
    }

    const barWrap = document.createElement('div');
    barWrap.className = 'hm-bar-wrap';
    const bar = document.createElement('div');
    bar.className = 'hm-bar';

    const callSeg = document.createElement('div');
    callSeg.className = 'hm-call';
    callSeg.style.flex = ceRatio.toFixed(3);

    const putSeg = document.createElement('div');
    putSeg.className = 'hm-put';
    putSeg.style.flex = peRatio.toFixed(3);

    bar.appendChild(callSeg);
    bar.appendChild(putSeg);
    barWrap.appendChild(bar);

    const label = document.createElement('div');
    label.className = 'hm-label mono';
    label.textContent =
      'CE ' + (ce || 0).toLocaleString('en-IN') +
      ' · PE ' + (pe || 0).toLocaleString('en-IN') +
      ' · ' + note;

    rowDiv.appendChild(strikeDiv);
    rowDiv.appendChild(barWrap);
    rowDiv.appendChild(label);
    el.appendChild(rowDiv);
  });
}


// Day's absolute High/Low (from today's rows)
function zz_dayHL(rows) {
  if (!rows?.length) return { high:null, low:null };
  let high = rows[0], low = rows[0];
  for (const r of rows){
    if (r.price > high.price) high = r;
    if (r.price < low.price)  low  = r;
  }
  return { high, low };
}
// PCR trend + sparkline
function drawPcrSparkline(rows){
  const svg = document.getElementById('pcrSpark'); if(!svg) return;
  const w=120, h=24, pad=2;
  svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
  if (!rows || rows.length===0){ svg.innerHTML=''; return; }
  const vals = rows.map(r=>Number(r.pcr||0)).filter(v=>v>0);
  if (!vals.length){ svg.innerHTML=''; return; }
  const min = Math.min(...vals), max = Math.max(...vals);
  const xs  = (i)=> pad + i*( (w-2*pad)/Math.max(1,vals.length-1) );
  const ys  = (v)=> h-pad - ( (v-min)/(max-min||1) ) * (h-2*pad);
  const pts = vals.map((v,i)=> `${xs(i)},${ys(v)}`).join(' ');
  svg.innerHTML = `<polyline fill="none" stroke="#93c5fd" stroke-width="2" points="${pts}" />`;
}
// 👉 Add here:
const CLASS_MAP = {
  "Long Build-up":   { bias: "Bullish", trade: "Buy Futures / CE / Bullish Spread" },
  "Short Build-up":  { bias: "Bearish", trade: "Sell Futures / PE / Bearish Spread" },
  "Short Covering":  { bias: "Bullish (short-term)", trade: "Quick CE Buy" },
  "Long Unwinding":  { bias: "Bearish (short-term)", trade: "Avoid Longs / Quick Short" }
};

async function loadMetrics(){
  const symbol = document.getElementById('symbol').value;
  const res = await fetch(`/oi/metrics?symbol=${symbol}&limit=12`);
  const j   = await res.json();

  const rowsRaw = (j && j.ok && Array.isArray(j.rows)) ? j.rows : [];
  const rows = rowsRaw
    .filter(r => r && r.ts)
    .sort((a,b)=> Date.parse(b.ts+'Z') - Date.parse(a.ts+'Z'));

  UI.metricsRows = rows;

  // ---- PCR Trend ----
  document.getElementById('pcrTrend').innerHTML = rows.map(r=>{
    const price = Number(r.underlying ?? 0).toFixed(2);
    const pcr   = (r.pcr ?? '-') ;
    const bias  = r.bias ?? '-';
    return `<tr>
      <td>${fmtIST(r.ts)}</td>
      <td class="mono">${price}</td>
      <td>${pcr}</td>
      <td>${badgeBias(bias)}</td>
    </tr>`;
  }).join('');

  // ---- Last 5 Classifications ----
  const last5 = rows.slice(0,5);
  document.getElementById('classTable').innerHTML = last5.map(r=>{
    const topPutStrike  = r.top_put_strike  ?? '';
    const topPutDelta   = Number(r.top_put_delta ?? 0);
    const topCallStrike = r.top_call_strike ?? '';
    const topCallDelta  = Number(r.top_call_delta ?? 0);

    const putCell  = topPutStrike
      ? `${topPutStrike} (${topPutDelta>=0?'<span class="up">+'+topPutDelta+'</span>':'<span class="down">'+topPutDelta+'</span>'})`
      : '-';
    const callCell = topCallStrike
      ? `${topCallStrike} (${topCallDelta>=0?'<span class="up">+'+topCallDelta+'</span>':'<span class="down">'+topCallDelta+'</span>'})`
      : '-';

    const clsName = r.price_vs_oi ?? '-';
    const info    = CLASS_MAP[clsName] || { bias: '—', trade: '—' };

    const tradeCls = info.bias.startsWith('Bullish') ? 'up'
                    : (info.bias.startsWith('Bearish') ? 'down' : '');

    return `<tr>
      <td>${fmtIST(r.ts)}</td>
      <td>${badgeClass(clsName)}</td>
      <td>${putCell}</td>
      <td>${callCell}</td>
      <td>${badgeBias(info.bias.startsWith('Bullish') ? 'Bullish' : (info.bias.startsWith('Bearish') ? 'Bearish' : 'Neutral'))}</td>
      <td class="${tradeCls}">${info.trade}</td>
    </tr>`;
  }).join('');

  // ---- Sparkline ----
  drawPcrSparkline(rows);
  renderTrendCard();
  renderZigZagCard();
}


// Breakout + Swing signals (always show card)
async function loadSignals(){
  const symbol = document.getElementById('symbol').value;
  const card   = document.getElementById('signalCard');
  const text   = document.getElementById('signalText');
  const swing  = document.getElementById('swingText');

  try{
    const res = await fetch(`/oi/signals?symbol=${symbol}`);
    const j   = await res.json();

    card.style.display = 'block';

    if (j?.last){
      text.innerHTML = `🚀 <span class="badge b-green">Breakout</span> `
        + `<span class="mono">${j.last.time_ist}</span> `
        + `| Close: <span class="mono">${Number(j.last.close).toFixed(2)}</span>`;
    } else {
      text.textContent = 'No breakout candle detected today (15m).';
    }

    const sh = j?.swing_high, sl = j?.swing_low;
    const partHigh = sh ? `Swing High: <span class="mono">${sh.time_ist}</span> @ <span class="mono">${sh.price}</span>` : 'Swing High: —';
    const partLow  = sl ? `Swing Low: <span class="mono">${sl.time_ist}</span> @ <span class="mono">${sl.price}</span>`   : 'Swing Low: —';
    swing.innerHTML = `📍 ${partHigh} &nbsp; | &nbsp; ${partLow}`;

  }catch(err){
    card.style.display = 'block';
    text.textContent = 'Signals unavailable (error fetching).';
    swing.textContent = '';
    console.warn('signals error', err);
  }
}

// ===== Next expiry helpers =====
function findNextExpiry(current){
  const arr = UI.expiriesList || [];
  if (!arr.length) return null;
  if (!current){
    return arr.length > 1 ? arr[1] : null;
  }
  const idx = arr.indexOf(current);
  if (idx === -1) return null;
  return (idx+1 < arr.length) ? arr[idx+1] : null;
}
async function loadSnapshotForExpiry(symbol, windowM, expiry){
  const params = new URLSearchParams({ symbol, window: windowM });
  if (expiry) params.append('expiry', expiry);
  const res = await fetch(`/oi/json?${params.toString()}`);
  const j = await res.json();
  if (!j.ok) throw new Error(j.msg||'No data');
  return j;
}
function renderTopRowsInto(j, tbodyId){
  const tbody = document.getElementById(tbodyId);
  tbody.innerHTML = '';
  for (const [k,v] of Object.entries(j.topCalls||{})){
    const d = j.callDelta?.[k] ?? 0;
    tbody.insertAdjacentHTML('beforeend',
      `<tr><td>${k}</td><td>CE</td><td>${fmtNum(v)}</td>
       <td class="${d>0?'up':(d<0?'down':'')}">${fmtNum(d)} ${d>0?'▲':(d<0?'▼':'')}</td></tr>`);
  }
  for (const [k,v] of Object.entries(j.topPuts||{})){
    const d = j.putDelta?.[k] ?? 0;
    tbody.insertAdjacentHTML('beforeend',
      `<tr><td>${k}</td><td>PE</td><td>${fmtNum(v)}</td>
       <td class="${d>0?'up':(d<0?'down':'')}">${fmtNum(d)} ${d>0?'▲':(d<0?'▼':'')}</td></tr>`);
  }
}
async function loadNextExpiryCard(){
  const chk = document.getElementById('showNextExp');
  const card = document.getElementById('nextCard');
  if (!chk || !chk.checked){ card.style.display='none'; return; }

  const symbol = document.getElementById('symbol').value;
  const windowM= document.getElementById('window').value;

  const currentExp = UI.expiry || (UI.expiriesList?.[0] || '');
  const nextExp = findNextExpiry(currentExp);
  if (!nextExp){ card.style.display='none'; return; }

  const j = await loadSnapshotForExpiry(symbol, windowM, nextExp);

  document.getElementById('nextHdr').textContent =
    `${j.symbol} @ ${( +j.price).toFixed(2)} | Exp ${j.expiry} | TS ${fmtIST(j.ts)}`;
  document.getElementById('nextPcr').textContent = j.pcr ?? '-';
  document.getElementById('nextAtm').textContent = j.atm ?? '-';

  renderTopRowsInto(j, 'nextTopRows');

  let cmpText = '';
  if (lastJson && lastJson.pcr!=null && j.pcr!=null){
    const dp = (Number(j.pcr)-Number(lastJson.pcr)).toFixed(2);
    cmpText += `PCR Δ vs current: ${dp>0?'+':''}${dp} `;
  }
  if (lastJson && lastJson.atm!=null && j.atm!=null){
    const da = Number(j.atm)-Number(lastJson.atm);
    cmpText += `| ATM shift: ${da>0?'+':''}${da}`;
  }
  document.getElementById('nextCompare').textContent = cmpText;

  card.style.display='block';
}

// ====== CSV Exports ======
function downloadCSV(filename, text){
  const blob = new Blob([text], {type:'text/csv;charset=utf-8;'});
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob); a.download = filename;
  document.body.appendChild(a); a.click(); a.remove();
}
function exportDeltaCSV(){
  if(!lastJson) return;
  const j = lastJson;
  const strikes = new Set([...Object.keys(j.callDelta||{}), ...Object.keys(j.putDelta||{})]);
  let rows = [...strikes].map(s=>{
    const st = parseInt(s,10);
    const cd = j.callDelta?.[s]??0;
    const pd = j.putDelta?.[s]??0;
    return { st, call_delta: cd, put_delta: pd, net: (pd-cd) };
  }).sort((a,b)=>a.st-b.st);
  let csv = 'Strike,CallDelta,PutDelta,NetDelta\n' + rows.map(r=>[r.st,r.call_delta,r.put_delta,r.net].join(',')).join('\n');
  downloadCSV(`${j.symbol}_${j.expiry}_delta.csv`, csv);
}
function exportPCRCSV(){
  const rows = UI.metricsRows||[];
  let csv = 'TimeIST,Price,PCR,Bias\n' + rows.map(r=>[
    fmtIST(r.ts), Number(r.underlying??0).toFixed(2), r.pcr??'', r.bias??''
  ].join(',')).join('\n');
  const symbol = document.getElementById('symbol').value;
  downloadCSV(`${symbol}_pcr_last12.csv`, csv);
}

// ====== Controls & shortcuts ======
document.getElementById('refreshBtn').addEventListener('click', () => { countdown = AUTO_REFRESH_SEC; doRefresh(); });
document.getElementById('refreshNowBtn').addEventListener('click', () => { countdown = AUTO_REFRESH_SEC; doRefresh(); });

document.getElementById('symbol').addEventListener('change', () => loadExpiries().then(()=>{ countdown = AUTO_REFRESH_SEC; doRefresh(); }));
Array.from(document.querySelectorAll('input[name="optFilter"]')).forEach(r=>{
  r.addEventListener('change', e=>{ UI.optFilter = e.target.value; renderTopOI(lastJson); renderDeltaTable(lastJson); renderNetBars(lastJson); });
});
document.getElementById('strikeWin').addEventListener('change', e=>{ UI.strikeWin = parseInt(e.target.value,10); renderDeltaTable(lastJson); renderNetBars(lastJson); });
document.getElementById('atmBand').addEventListener('change', e=>{ UI.atmBand = parseInt(e.target.value,10); renderDeltaTable(lastJson); });
document.getElementById('expirySel').addEventListener('change', e=>{ UI.expiry = e.target.value; countdown = AUTO_REFRESH_SEC; doRefresh(); });
document.getElementById('exportDelta').addEventListener('click', exportDeltaCSV);
document.getElementById('exportPCR').addEventListener('click', exportPCRCSV);
document.getElementById('showNextExp').addEventListener('change', ()=>{ loadNextExpiryCard().catch(()=>{}); });

window.addEventListener('keydown', (e)=>{
  if (e.key==='r' || e.key==='R'){ countdown = AUTO_REFRESH_SEC; doRefresh(); }
  if (e.key==='1'){ document.getElementById('strikeWin').value='5';  UI.strikeWin=5;  renderDeltaTable(lastJson); renderNetBars(lastJson); }
  if (e.key==='2'){ document.getElementById('strikeWin').value='10'; UI.strikeWin=10; renderDeltaTable(lastJson); renderNetBars(lastJson); }
  if (e.key==='3'){ document.getElementById('strikeWin').value='15'; UI.strikeWin=15; renderDeltaTable(lastJson); renderNetBars(lastJson); }
  if (e.key==='e' || e.key==='E'){ const sel=document.getElementById('expirySel'); sel.selectedIndex = (sel.selectedIndex+1) % sel.options.length; sel.dispatchEvent(new Event('change')); }
});

// ====== Countdown loop ======
let countdown = AUTO_REFRESH_SEC;
async function doRefresh(){
  try{
    await loadSnapshot();
    await loadMetrics();
    await loadSignals();
    await loadNextExpiryCard();
    await loadBreakout5m();
    await loadTrack();
  }catch(e){}
}
function tick(){
  countdown--;
  if (countdown <= 0){ countdown = AUTO_REFRESH_SEC; doRefresh(); }
  timerEl.textContent = countdown;
  setTimeout(tick, 1000);
}
function scrollToOiTrack(){
  const tbl = document.getElementById('oiTrackTable');
  if (!tbl) return;

  const offset = 80;
  const top = tbl.getBoundingClientRect().top + window.scrollY - offset;

  window.scrollTo({
    top,
    behavior: 'smooth'
  });
}

// ====== Init ======
(async function init(){
  await loadExpiries();
  updateSessionAndDrift();
  await doRefresh();
  scrollToOiTrack();
  timerEl.textContent = countdown;
  setTimeout(tick, 1000);
})();
</script>

<script>
async function loadBreakout5m(){
  const symbol = document.getElementById('symbol').value;
  const res = await fetch(`/price/breakouts?symbol=${symbol}&tf=5m&lookback=10`);
  const j   = await res.json();
  const card= document.getElementById('breakout5m');
  const txt = document.getElementById('bo5mText');

  if (!j.ok){ card.style.display='none'; return; }

  if (j.last){
    const t = j.last.time_ist;
    const c = Number(j.last.close).toFixed(2);
    const isBull = j.last.type.includes('Bullish');
    txt.innerHTML = (isBull
      ? `🚀 <span class="badge b-green">Bullish Breakout (5m)</span> at <span class="mono">${t}</span> | Close <span class="mono">${c}</span>`
      : `⚠️  <span class="badge b-red">Bearish Breakdown (5m)</span> at <span class="mono">${t}</span> | Close <span class="mono">${c}</span>`
    );
    card.style.display='block';
  } else {
    txt.textContent = 'No 5-minute breakout detected today.';
    card.style.display='block';
  }
  UI.breakoutBias = j.last ? ((j.last.type||'').toLowerCase().includes('bullish') ? +METER_WEIGHTS.breakout : -METER_WEIGHTS.breakout) : 0;
  updateMarketMeter();
}
</script>
<script>
  // Score weights (tweak later)
const METER_WEIGHTS = {
  pcrStrong: 2, pcrWeak: 1,
  classStrong: 2, classWeak: 1,
  breakout: 2,
  netDelta: 1
};

const CLASS_SCORE = {
  "Long Build-up":   +METER_WEIGHTS.classStrong,
  "Short Build-up":  -METER_WEIGHTS.classStrong,
  "Short Covering":  +METER_WEIGHTS.classWeak,
  "Long Unwinding":  -METER_WEIGHTS.classWeak
};

function meterLabel(score){
  score = Math.max(-6, Math.min(6, score));
  if (score >= 4) return {text:'Strong Bullish', cls:'m-bull'};
  if (score >= 1) return {text:'Bullish',        cls:'m-bull'};
  if (score <= -4) return {text:'Strong Bearish',cls:'m-bear'};
  if (score <= -1) return {text:'Bearish',       cls:'m-bear'};
  return {text:'Neutral', cls:'m-neutral'};
}

if (!window.UI) window.UI = {};
UI.breakoutBias = 0;
UI.netDeltaSum  = 0;
UI.supportList  = [];
UI.resistList   = [];


function updateMarketMeter(){
  const card  = document.getElementById('marketMeter');
  if (!card) return;

  const j = lastJson;
  if (!j || !UI.metricsRows) { card.style.display='none'; return; }

  let score = 0;
  let reasons = [];

  const pcr = Number(j.pcr || 0);
  if (pcr){
    if (pcr >= 1.1){ score += METER_WEIGHTS.pcrStrong; reasons.push(`PCR ${pcr.toFixed(2)} (bullish)`); }
    else if (pcr > 1.0){ score += METER_WEIGHTS.pcrWeak; reasons.push(`PCR ${pcr.toFixed(2)} (mild bull)`); }
    else if (pcr <= 0.9){ score -= METER_WEIGHTS.pcrStrong; reasons.push(`PCR ${pcr.toFixed(2)} (bearish)`); }
    else { score -= METER_WEIGHTS.pcrWeak; reasons.push(`PCR ${pcr.toFixed(2)} (mild bear)`); }
  }

  const lastRow = UI.metricsRows[0] || {};
  const clsName = lastRow.price_vs_oi || '';
  if (clsName){
    score += (CLASS_SCORE[clsName] || 0);
    reasons.push(`${clsName}`);
  }

  if (UI.breakoutBias !== 0){
    score += UI.breakoutBias;
    reasons.push(UI.breakoutBias > 0 ? '5m Breakout' : '5m Breakdown');
  }

  if (UI.netDeltaSum){
    score += (UI.netDeltaSum > 0 ? +METER_WEIGHTS.netDelta : -METER_WEIGHTS.netDelta);
    reasons.push(`NetΔOI ${UI.netDeltaSum>0?'+':''}${UI.netDeltaSum.toLocaleString('en-IN')}`);
  }

  const lab = meterLabel(score);
  const badgeHtml = `<span class="meter-badge ${lab.cls}">${lab.text}</span>`;

  const supportHtml = (UI.supportList||[]).map(x=>`<span class="badge b-green">${x.strike}</span>`).join('');
  const resistHtml  = (UI.resistList||[]).map(x=>`<span class="badge b-red">${x.strike}</span>`).join('');

  document.getElementById('meterBadge').innerHTML = badgeHtml;
  document.getElementById('meterHeadline').innerHTML =
    `${j.symbol} @ <span class="mono">${Number(j.price||0).toFixed(2)}</span> · Exp <span class="mono">${j.expiry}</span>`;
  document.getElementById('meterWhy').innerHTML = reasons.length
    ? `Why: ${reasons.join(' · ')}`
    : 'Why: —';
  document.getElementById('meterSupport').innerHTML = supportHtml || '—';
  document.getElementById('meterResistance').innerHTML = resistHtml || '—';

  const ksPriceEl = document.getElementById('ksPrice');
  if (ksPriceEl){
    ksPriceEl.textContent = Number(j.price || 0).toFixed(2);
    document.getElementById('ksAtm').textContent  = j.atm ?? '-';
    document.getElementById('ksPcr').textContent  =
      j.pcr ? Number(j.pcr).toFixed(2) : '-';
    document.getElementById('ksBias').textContent = j.bias ?? '-';

    const ksAtmZoneEl  = document.getElementById('ksAtmZone');
    const ksPriceOIEl  = document.getElementById('ksPriceOI');
    const ksOiPressEl  = document.getElementById('ksOiPressure');
    const ksTrendEl    = document.getElementById('ksTrendConf');
    const ksBoUpEl     = document.getElementById('ksBoUp');
    const ksBoDownEl   = document.getElementById('ksBoDown');

    const atm = Number(j.atm || 0) || null;
    const topCalls = j.topCalls || {};
    const topPuts  = j.topPuts  || {};
    let ceZone = 0, peZone = 0;
    if (atm){
      const zoneWidthPts = 100;
      for (const [k,v] of Object.entries(topCalls)){
        const st = parseInt(k,10); if (!st) continue;
        if (Math.abs(st - atm) <= zoneWidthPts){ ceZone += Number(v)||0; }
      }
      for (const [k,v] of Object.entries(topPuts)){
        const st = parseInt(k,10); if (!st) continue;
        if (Math.abs(st - atm) <= zoneWidthPts){ peZone += Number(v)||0; }
      }
    }
    if (ksAtmZoneEl){
      if (!atm || (!ceZone && !peZone)){
        ksAtmZoneEl.textContent = '-';
      }else{
        let sideTxt = 'Balanced';
        if (peZone > ceZone*1.1) sideTxt = 'PE>CE (bullish OI)';
        else if (ceZone > peZone*1.1) sideTxt = 'CE>PE (bearish OI)';
        ksAtmZoneEl.textContent =
          `${atm} | CE ${ceZone.toLocaleString('en-IN')} · PE ${peZone.toLocaleString('en-IN')} → ${sideTxt}`;
      }
    }

    if (ksPriceOIEl){
      ksPriceOIEl.textContent = clsName || '-';
    }

    if (ksOiPressEl){
      const nd = UI.netDeltaSum || 0;
      if (!nd){
        ksOiPressEl.textContent = 'Flat / neutral';
      }else if (nd > 0){
        ksOiPressEl.textContent = `PutΔ ${nd.toLocaleString('en-IN')} (bullish support)`;
      }else{
        ksOiPressEl.textContent = `CallΔ ${Math.abs(nd).toLocaleString('en-IN')} (bearish pressure)`;
      }
    }

    const absScore = Math.min(6, Math.abs(score));
    const trendPct = Math.round((absScore / 6) * 100);
    if (ksTrendEl){
      ksTrendEl.textContent = trendPct ? `${trendPct}%` : '—';
    }

    if (ksBoUpEl && ksBoDownEl){
      let up = 50, down = 50;
      if (trendPct){
        const dir = score >= 0 ? 1 : -1;
        let shift = dir * (trendPct * 0.3);
        if (UI.breakoutBias > 0) shift += 10;
        if (UI.breakoutBias < 0) shift -= 10;
        shift = Math.max(-40, Math.min(40, shift));
        up   = Math.round(50 + shift);
        down = 100 - up;
      }
      ksBoUpEl.textContent   = `${up}%`;
      ksBoDownEl.textContent = `${down}%`;
    }
  }

  card.style.display = 'block';
}

</script>
<script>
// ---- Trend on 3-minute cadence (frontend only) ----

const TREND_POINTS = 5;
const PRICE_WEIGHT = 0.5;
const PCR_WEIGHT   = 0.3;
const CLASS_WEIGHT = 0.2;
const NET_WEIGHT   = 0.2;

function linSlope(series){
  const n = series.length; if (n < 2) return 0;
  let sx=0, sy=0, sxy=0, sxx=0;
  for (let i=0;i<n;i++){ const x=i, y=Number(series[i])||0; sx+=x; sy+=y; sxy+=x*y; sxx+=x*x; }
  const num = n*sxy - sx*sy, den = n*sxx - sx*sx;
  return den===0 ? 0 : num/den;
}
function zNormSlope(vals){
  if (!vals || vals.length < 3) return 0;
  const s = linSlope(vals);
  const min = Math.min(...vals), max = Math.max(...vals), rng = Math.max(1, max-min);
  return (s / rng) * vals.length;
}

const CLASS_TILT = {
  "Long Build-up": +1, "Short Covering": +0.5,
  "Short Build-up": -1, "Long Unwinding": -0.5
};

function computeTrendFromMetrics(rows){
  const take = Math.min(TREND_POINTS, rows.length);
  const recent = rows.slice(0, take).reverse();

  const priceSeries = recent.map(r => Number(r.underlying||0));
  const pcrSeries   = recent.map(r => Number(r.pcr||0));
  const classSeries = recent.map(r => CLASS_TILT[r.price_vs_oi||''] || 0);

  const priceZ = zNormSlope(priceSeries);
  const pcrZ   = zNormSlope(pcrSeries);
  const classAvg = classSeries.reduce((a,b)=>a+b,0) / Math.max(1,classSeries.length);

  const netNow = (UI.netDeltaSum || 0);
  const netSign = netNow === 0 ? 0 : (netNow > 0 ? +1 : -1);

  const score = (PRICE_WEIGHT*priceZ) + (PCR_WEIGHT*pcrZ) + (CLASS_WEIGHT*classAvg) + (NET_WEIGHT*netSign*0.6);

  let label = 'Range';
  if (score >  0.15) label = 'Uptrend';
  if (score < -0.15) label = 'Downtrend';
  const conf = Math.min(100, Math.round(Math.abs(score) * 100));

  return {
    label, score, conf,
    parts: {
      price: priceZ, pcr: pcrZ, classTilt: classAvg, netSign,
      lastPrice: priceSeries[priceSeries.length-1],
      lastPCR: pcrSeries[pcrSeries.length-1]
    },
    minutes: (take-1) * 3
  };
}

function renderTrendCard(){
  const card = document.getElementById('trendCard');
  if (!card || !UI.metricsRows || UI.metricsRows.length < 3){ card.style.display='none'; return; }

  const t = computeTrendFromMetrics(UI.metricsRows);
  document.getElementById('trendLookback').textContent = t.minutes;

  const color = t.label==='Uptrend' ? '#16a34a' : (t.label==='Downtrend' ? '#ef4444' : '#93c5fd');
  const bar = `<div style="height:8px;background:#0f2e5f;border-radius:6px;overflow:hidden">
                 <div style="width:${t.conf}%;height:8px;background:${color}"></div>
               </div>`;

  document.getElementById('trendSummary').innerHTML =
    `<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
       <span class="badge" style="background:${color}22;color:${color};font-weight:700">${t.label}</span>
       <span class="muted">Confidence: ${t.conf}%</span>
     </div>${bar}`;

  const why = [];
  why.push(`Price slope: ${t.parts.price>0?'▲':'▼'} ${t.parts.price.toFixed(3)}`);
  why.push(`PCR slope: ${t.parts.pcr>0?'▲':'▼'} ${t.parts.pcr.toFixed(3)}`);
  if (t.parts.classTilt!==0) why.push(`Class tilt: ${t.parts.classTilt>0?'+':''}${t.parts.classTilt.toFixed(2)}`);
  if (t.parts.netSign!==0) why.push(`NetΔOI: ${t.parts.netSign>0?'+':''}${t.parts.netSign}`);

  document.getElementById('trendWhy').textContent = why.join(' · ');
  card.style.display='block';
}
</script>

<script>
async function loadTrack(){
  const symbol = document.getElementById('symbol').value;
  const expiry = document.getElementById('expirySel')?.value || '';

  const lbs = (document.getElementById('tfInput')?.value || '5,10,15,30').replace(/\s+/g,'');
  const strikes = parseInt(document.getElementById('strikeWinServer')?.value || '10', 10) || 10;

  const url = `/oi/track?symbol=${encodeURIComponent(symbol)}`
            + (expiry ? `&expiry=${encodeURIComponent(expiry)}` : '')
            + `&lookbacks=${encodeURIComponent(lbs)}&strikes=${strikes}`;

  const res = await fetch(url);
  const j = await res.json().catch(()=>null);
  if (!j || j.ok===false) return;

  if (Array.isArray(j.lookbacks) && j.lookbacks.length){
    document.getElementById('tfInput').value = j.lookbacks.join(',');
  }
  if (j.strike_window) {
    const el = document.getElementById('strikeWinServer');
    if (el) el.value = j.strike_window;
  }

  const minsDesc = (j.lookbacks || []).slice().sort((a,b)=>b-a);
  const head = document.getElementById('trackHeadRow');
  head.innerHTML = `
    <th>Strike</th><th>Type</th>
    ${minsDesc
      .map(m => `<th class="tf-oi">OI (${m}m)</th><th class="tf-delta">ΔOI (${m}m)</th>`)
      .join('')}
    <th>Current OI</th><th>Cur ΔOI (chg & %)</th>
  `;

  const fmt = n => (n===null||n===undefined) ? '-' : Number(n).toLocaleString('en-IN');
  const body = document.getElementById('trackTable');
  body.innerHTML = '';

  (j.rows || []).forEach(r => {
    ['CE','PE'].forEach(side => {
      const s = r[side];
      if (!s) return;

      const cells = minsDesc.map(m =>
        `<td class="tf-oi">${fmt(s['oi_' + m + 'm'])}</td>` +
        `<td class="tf-delta">${fmt(s['chg_' + m + 'm'])}</td>`
      ).join('');

      const curOi  = s.cur_oi;
      const curChg = s.cur_chg;

      let curPctText = '';
      let cls = '';
      let arrow = '';

      if (curChg !== null && curChg !== undefined &&
          curOi   !== null && curOi   !== undefined) {

        const prevOi = curOi - curChg;
        let pct = 0;
        if (prevOi !== 0) {
          pct = (curChg / prevOi) * 100;
        }

        const sign = pct > 0 ? '+' : (pct < 0 ? '' : '');
        curPctText = ` (${sign}${pct.toFixed(2)}%)`;

        if (curChg > 0) {
          cls = 'up';
          arrow = '▲ ';
        } else if (curChg < 0) {
          cls = 'down';
          arrow = '▼ ';
        } else {
          cls = '';
          arrow = '';
        }
      }

      const curChgCell =
        (curChg === null || curChg === undefined)
          ? '-'
          : `<span class="cur-delta ${cls}">${arrow}${fmt(curChg)}${curPctText}</span>`;

      body.insertAdjacentHTML(
        'beforeend',
        `<tr>
          <td>${r.strike}</td>
          <td>${side}</td>
          ${cells}
          <td>${fmt(curOi)}</td>
          <td>${curChgCell}</td>
        </tr>`
      );
    });
  });

  if (window.enhanceAll) window.enhanceAll();
}

document.getElementById('tfApply')?.addEventListener('click', loadTrack);
document.getElementById('strikeWinServer')?.addEventListener('change', loadTrack);
</script>

<script>
// ---------------- ZigZag-% swings (frontend only) ----------------
(function(){
  const CARD = ()=>document.getElementById('zzCard');
  const PCT  = ()=>document.getElementById('zzPct');
  const BARS = ()=>document.getElementById('zzBars');

  function zz_today(){ return new Date(new Date().toLocaleString('en-US',{timeZone:'Asia/Kolkata'})); }
  function zz_toIST(ts){
    return new Date(new Date(String(ts).replace(' ','T')+'Z')
      .toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
  }
  function zz_sameDay(a,b){ return a.getFullYear()===b.getFullYear() && a.getMonth()===b.getMonth() && a.getDate()===b.getDate(); }
  function zz_fmtT(d){ return d.toLocaleTimeString('en-IN',{hour12:false,timeZone:'Asia/Kolkata',hour:'2-digit',minute:'2-digit'}); }
  const zz_fmtN = n => Number(n).toLocaleString('en-IN');

  function zigzag(rows, pct=0.35, minBars=2){
    if (!rows || rows.length<3) return { pivots:[], legs:[] };

    pct = Math.max(0.05, pct);
    minBars = Math.max(1, Math.floor(minBars));

    let pivots = [];
    let dir = 0;
    let lastIdx = 0;
    let lastPrice = rows[0].price;

    for (let i=1;i<rows.length;i++){
      const p = rows[i].price;

      if (dir >= 0 && p > rows[lastIdx].price) lastIdx = i;
      if (dir <= 0 && p < rows[lastIdx].price) lastIdx = i;

      const moveUp   = (p - rows[lastIdx].price) / rows[lastIdx].price * 100;
      const moveDown = (rows[lastIdx].price - p) / rows[lastIdx].price * 100;

      const barsFromLast = i - lastIdx;

      if (dir === 0){
        const seedMove = (p - lastPrice) / lastPrice * 100;
        if (Math.abs(seedMove) >= pct){
          dir = seedMove > 0 ? +1 : -1;
          lastIdx = i;
        }
        continue;
      }

      if (dir === +1){
        const retr = (rows[lastIdx].price - p) / rows[lastIdx].price * 100;
        if (retr >= pct && (i - lastIdx) >= minBars){
          pivots.push({ type:'H', idx:lastIdx, ist:rows[lastIdx].ist, price:rows[lastIdx].price });
          dir = -1; lastIdx = i;
        }
      }else{
        const retr = (p - rows[lastIdx].price) / rows[lastIdx].price * 100;
        if (retr >= pct && (i - lastIdx) >= minBars){
          pivots.push({ type:'L', idx:lastIdx, ist:rows[lastIdx].ist, price:rows[lastIdx].price });
          dir = +1; lastIdx = i;
        }
      }
    }

    pivots.push({ type: (dir>=0?'H':'L'), idx:lastIdx, ist:rows[lastIdx].ist, price:rows[lastIdx].price, provisional:true });

    const legs = [];
    for (let i=1;i<pivots.length;i++){
      const a = pivots[i-1], b = pivots[i];
      const sign = a.type==='L' && b.type==='H' ? +1 : -1;
      const deltaPct = (b.price - a.price) / a.price * 100;
      const durMin = Math.round((b.ist - a.ist)/60000);
      legs.push({from:a, to:b, pct:deltaPct, durMin, dir:sign});
    }

    return { pivots, legs };
  }

  function drawZZSpark(rows, pivots){
    const svg = document.getElementById('zzSpark'); if(!svg) return;
    svg.innerHTML = '';
    if (!rows || rows.length<2){ return; }

    const W=500, H=120, pad=6;
    const minP = Math.min(...rows.map(r=>r.price)), maxP = Math.max(...rows.map(r=>r.price));
    const x = i => pad + i*( (W-2*pad)/(rows.length-1) );
    const y = v => H-pad - ( (v-minP)/(maxP-minP||1) )*(H-2*pad);

    const pts = rows.map((r,i)=>`${x(i)},${y(r.price)}`).join(' ');
    svg.insertAdjacentHTML('beforeend', `<polyline points="${pts}" fill="none" stroke="#93c5fd" stroke-width="1.5" />`);

    pivots.forEach(p=>{
      const cx = x(p.idx), cy = y(p.price);
      const fill = p.type==='H' ? '#ef4444' : '#16a34a';
      const op = p.provisional ? 0.5 : 1;
      svg.insertAdjacentHTML('beforeend', `<circle cx="${cx}" cy="${cy}" r="3.5" fill="${fill}" opacity="${op}"/>`);
    });

    const { high, low } = zz_dayHL(rows);
    if (high){
      const iHigh = rows.indexOf(high);
      svg.insertAdjacentHTML('beforeend',
        `<rect x="${x(iHigh)-4}" y="${y(high.price)-4}" width="8" height="8"
                fill="#ef4444" stroke="#ef4444" stroke-width="1.2" opacity="0.9"/>`);
    }
    if (low){
      const iLow = rows.indexOf(low);
      svg.insertAdjacentHTML('beforeend',
        `<rect x="${x(iLow)-4}" y="${y(low.price)-4}" width="8" height="8"
                fill="#16a34a" stroke="#16a34a" stroke-width="1.2" opacity="0.9"/>`);
    }
  }

  function renderZigZagCard(){
    const card = CARD(); if(!card) return;
    const rowsRaw = (UI.metricsRows||[])
      .map(r=>({ ist: zz_toIST(r.ts), price: Number(r.underlying||0) }))
      .filter(r=> zz_sameDay(r.ist, zz_today()))
      .sort((a,b)=> a.ist - b.ist);

    if (rowsRaw.length < 6){ card.style.display='none'; return; }

    const pct = Number(PCT()?.value || localStorage.zzPct || 0.35);
    const bars = parseInt(BARS()?.value || localStorage.zzBars || 2, 10);

    if (PCT())  PCT().value  = pct;
    if (BARS()) BARS().value = bars;
    localStorage.zzPct  = pct;
    localStorage.zzBars = bars;

    const zz = zigzag(rowsRaw, pct, bars);
    const piv = zz.pivots;
    const legs = zz.legs;

    let meta = '';
    if (legs.length){
      const last = legs[legs.length-1];
      const legTxt = last.dir>0 ? 'Low → High' : 'High → Low';
      meta = `Current leg: <b>${legTxt}</b> since <span class="mono">${zz_fmtN(last.from.price)}</span> `
           + `at <span class="mono">${zz_fmtT(last.from.ist)}</span> `
           + `→ <span class="mono">${zz_fmtN(last.to.price)}</span> `
           + `(<span class="${last.dir>0?'up':'down'}">${last.pct>0?'+':''}${last.pct.toFixed(2)}%</span>, `
           + `${last.durMin}m)`;
    } else {
      meta = 'Detecting first leg…';
    }
    document.getElementById('zzMeta').innerHTML = meta;

    const tbody = document.getElementById('zzTable');
    const rows = [];
    for (let i = Math.max(0, piv.length-6); i < piv.length; i++){
      const p = piv[i];
      let dp = '—', dur='—';
      if (i>0){
        const prev = piv[i-1];
        const d = (p.price - prev.price) / prev.price * 100;
        dp = (d>0?'+':'') + d.toFixed(2) + '%';
        dur = Math.round((p.ist - prev.ist)/60000) + 'm';
      }
      rows.push(`<tr>
        <td>${p.type==='H'?'High':'Low'}${p.provisional?' (provisional)':''}</td>
        <td>${zz_fmtT(p.ist)}</td>
        <td class="mono">${zz_fmtN(p.price)}</td>
        <td class="mono ${dp.startsWith('+')?'up':(dp.startsWith('-')?'down':'')}">${dp}</td>
        <td class="mono">${dur}</td>
      </tr>`);
    }
    tbody.innerHTML = rows.join('');

    const confirmed = zz.pivots.filter(p => !p.provisional);
    const lastLowPivot = [...confirmed].reverse().find(p => p.type==='L') || null;

    const dayHL = zz_dayHL(rowsRaw);
    const lastPrice = rowsRaw[rowsRaw.length-1]?.price || null;

    function pctChg(from, to){ return from ? ((to - from)/from)*100 : 0; }

    let extra = '';
    if (lastLowPivot){
      const dPct = pctChg(lastLowPivot.price, lastPrice);
      extra += ` · Last pivot low: <span class="mono">${zz_fmtN(lastLowPivot.price)}</span> `
            +  `@ <span class="mono">${zz_fmtT(lastLowPivot.ist)}</span>`
            +  ` (<span class="${dPct>=0?'up':'down'}">${dPct>=0?'+':''}${dPct.toFixed(2)}%</span> from pivot)`;
    }
    if (dayHL.low){
      const dPct2 = pctChg(dayHL.low.price, lastPrice);
      extra += ` · Day Low: <span class="mono">${zz_fmtN(dayHL.low.price)}</span>`
            +  ` @ <span class="mono">${zz_fmtT(dayHL.low.ist)}</span>`
            +  ` (<span class="${dPct2>=0?'up':'down'}">${dPct2>=0?'+':''}${dPct2.toFixed(2)}%</span> from low)`;
    }

    const metaEl = document.getElementById('zzMeta');
    metaEl.innerHTML = metaEl.innerHTML + extra;

    drawZZSpark(rowsRaw, piv);

    card.style.display='block';
  }

  window.renderZigZagCard = renderZigZagCard;
  document.getElementById('zzApply')?.addEventListener('click', renderZigZagCard);
})();
</script>



<script src="<?= base_url('assets/js/oi_track_enhancer.js?v=2025-12-05f') ?>"></script>
</body>
</html>
