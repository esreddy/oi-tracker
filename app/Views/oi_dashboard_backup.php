<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>OI Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
    /* --- Base look --- */
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:24px;background:#0b1220;color:#e8eefc}
    .wrap{max-width:1100px;margin:auto}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .card{background:#121a2b;border-radius:16px;padding:16px;box-shadow:0 8px 24px rgba(0,0,0,.35)}
    .pill{display:inline-block;padding:4px 10px;border-radius:999px;background:#1b2a45;margin-right:8px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:8px;border-bottom:1px solid #24324d;text-align:right}
    th:first-child,td:first-child{text-align:left}
    .up{color:#6ee7a2} .down{color:#f87171}
    .muted{color:#97a6c3}
    .big{font-size:28px;font-weight:700}
    .tag{padding:2px 8px;border-radius:8px;background:#1f2b44;margin-left:8px}
    .badge{padding:2px 8px;border-radius:999px;font-size:12px}
    .b-green{background:#103b2d;color:#a7f3d0}
    .b-red{background:#3b1010;color:#fca5a5}
    .b-blue{background:#0f2e5f;color:#93c5fd}
    .mono{font-family: ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
    .status-bar{background:#0f172a;border:1px solid #24324d;border-radius:10px;padding:6px 10px;margin-bottom:16px;display:flex;justify-content:space-between;gap:10px;align-items:center;position:sticky;top:0;z-index:10}
    .status-left{font-size:12px;color:#97a6c3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .status-right{font-size:12px;color:#97a6c3;white-space:nowrap}
    .ok{color:#6ee7a2} .warn{color:#fde68a} .bad{color:#fca5a5}
    button{cursor:pointer}
    .hscroll{overflow-x:auto;}

    /* --- Net Δ mini bars rendered in a table (Safari-proof) --- */
    table.nb-bars{ width:100%; border-collapse:separate; border-spacing:0 6px; }
    .nb-strike-td{ width:70px; text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; font-size:12px; }
    .nb-bar-td   { width:auto; }
    .nb-val-td   { width:120px; text-align:left; font-variant-numeric:tabular-nums; white-space:nowrap; font-size:12px; }
    .nb-svg{ display:block; width:100%; height:14px; }

    @media (max-width: 900px) { .row{grid-template-columns:1fr} }
  </style>
  <style>
  .meter-badge{padding:2px 10px;border-radius:999px;font-weight:600}
  .m-bull{background:#103b2d;color:#a7f3d0}
  .m-bear{background:#3b1010;color:#fca5a5}
  .m-neutral{background:#0f2e5f;color:#93c5fd}
  .levels span{display:inline-block;margin-right:10px;margin-top:4px}
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
      &nbsp;&nbsp;
      <span id="clockDrift" class="muted"></span>
      &nbsp;&nbsp;
      <strong>Next refresh in:</strong> <span id="refreshTimer">60</span>s
      <button id="refreshNowBtn" style="margin-left:8px">Refresh now</button>
    </div>
  </div>

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

  <!-- Market Meter -->
<div class="card" id="marketMeter" style="margin-bottom:16px;display:none">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
    <div>
      <b>Market Meter</b>
      <div id="meterHeadline" style="margin-top:6px"></div>
      <div id="meterWhy" class="muted" style="margin-top:6px"></div>
    </div>
    <div id="meterBadge"></div>
  </div>
  <div class="levels" style="margin-top:10px">
    <div><b>Support (Put OI):</b> <span id="meterSupport"></span></div>
    <div><b>Resistance (Call OI):</b> <span id="meterResistance"></span></div>
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

    <div class="card">
      <div><span class="pill">Bias: <b id="bias">-</b></span>
           <span class="pill">PCR: <b id="pcr">-</b></span>
           <span class="pill">ATM: <b id="atm">-</b></span>
      </div>
      <table>
        <thead><tr><th>Strike</th><th>Type</th><th>OI</th><th>ΔOI</th></tr></thead>
        <tbody id="topRows"></tbody>
      </table>
      <small class="muted">ΔOI = change vs selected window start.</small>
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

  <!-- Delta by strike -->
  <div class="card" style="margin-top:16px">
    <b>Delta by Strike (last window)</b>
    <table>
      <thead><tr><th>Strike</th><th>Call ΔOI</th><th>Put ΔOI</th></tr></thead>
      <tbody id="deltaTable"></tbody>
    </table>
  </div>

  <!-- Net ΔOI bars & exports -->
  <div class="card" style="margin-top:16px">
    <b>Net ΔOI by Strike (PutΔ − CallΔ)</b>

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
}

function renderTopOI(j){
  const top = document.getElementById('topRows'); top.innerHTML='';
  if (UI.optFilter!=='puts') {
    for (const [k,v] of Object.entries(j.topCalls||{})){
      const d = j.callDelta?.[k] ?? 0;
      top.insertAdjacentHTML('beforeend', `<tr><td>${k}</td><td>CE</td><td>${fmtNum(v)}</td><td class="${d>0?'up':(d<0?'down':'')}">${fmtNum(d)} ${d>0?'▲':(d<0?'▼':'')}</td></tr>`);
    }
  }
  if (UI.optFilter!=='calls') {
    for (const [k,v] of Object.entries(j.topPuts||{})){
      const d = j.putDelta?.[k] ?? 0;
      top.insertAdjacentHTML('beforeend', `<tr><td>${k}</td><td>PE</td><td>${fmtNum(v)}</td><td class="${d>0?'up':(d<0?'down':'')}">${fmtNum(d)} ${d>0?'▲':(d<0?'▼':'')}</td></tr>`);
    }
  }
}

let sortKey = 'absnet'; // 'strike'|'calld'|'putd'|'net'|'absnet'
let sortAsc = false;

function renderDeltaTable(j){
  if(!j) return;
  const atm = j.atm;
  const band = UI.atmBand;
  const around = UI.strikeWin;
  const strikes = new Set([...Object.keys(j.callDelta||{}), ...Object.keys(j.putDelta||{})]);
  let rows = [...strikes].map(s=>{
    const st = parseInt(s,10);
    const cd = j.callDelta?.[s]??0;
    const pd = j.putDelta?.[s]??0;
    const net = (pd - cd);
    const withinATM = Math.abs(st - atm) <= band*50; // 50 step
    const withinWin = (around===999) ? true : Math.abs(st - atm) <= around*50;
    const includeCE = (UI.optFilter!=='puts');
    const includePE = (UI.optFilter!=='calls');
    return { st, cd: includeCE?cd:0, pd: includePE?pd:0, net, withinATM, withinWin };
  }).filter(r=>r.withinWin);

  const cmp = {
    strike: (a,b)=> a.st-b.st,
    calld:  (a,b)=> (a.cd-b.cd)*(sortAsc?1:-1),
    putd:   (a,b)=> (a.pd-b.pd)*(sortAsc?1:-1),
    net:    (a,b)=> (a.net-b.net)*(sortAsc?1:-1),
    absnet: (a,b)=> (Math.abs(a.net)-Math.abs(b.net))*(sortAsc?1:-1),
  }[sortKey] || ((a,b)=> (Math.abs(a.net)-Math.abs(b.net))*(sortAsc?1:-1));

  rows.sort(cmp);
  const html = rows.map(r=>{
    const clsCD = r.cd>0?'up':(r.cd<0?'down':'');
    const clsPD = r.pd>0?'up':(r.pd<0?'down':'');
    const bandHL = r.withinATM ? ' style="background:#10233d"' : '';
    return `<tr${bandHL}>
      <td>${r.st}</td>
      <td class="${clsCD}">${fmtNum(r.cd)} ${r.cd>0?'▲':(r.cd<0?'▼':'')}</td>
      <td class="${clsPD}">${fmtNum(r.pd)} ${r.pd>0?'▲':(r.pd<0?'▼':'')}</td>
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
    const pct = Math.abs(r.net) / maxAbs;     // 0..1
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

    // --- Meter inputs from this snapshot ---
  // Net sum across shown rows
  UI.netDeltaSum = rows.reduce((s,r)=> s + (r.pd - r.cd), 0);

  // Top 3 support/resistance from total OI (not delta): j.topPuts / j.topCalls
  const topN = (obj, n=3) => Object.entries(obj||{})
      .map(([k,v]) => ({strike: parseInt(k,10), oi: Number(v)}))
      .sort((a,b) => b.oi - a.oi)
      .slice(0,n);

  UI.supportList = topN(j.topPuts, 3);
  UI.resistList  = topN(j.topCalls, 3);

  updateMarketMeter(); // refresh the meter UI

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

  // ---- Map classification -> bias + suggested trade ----
  const CLASS_MAP = {
    "Long Build-up":   { bias: "Bullish",             trade: "Buy Futures / CE / Bullish Spread" },
    "Short Build-up":  { bias: "Bearish",             trade: "Sell Futures / PE / Bearish Spread" },
    "Short Covering":  { bias: "Bullish (short-term)",trade: "Quick CE Buy" },
    "Long Unwinding":  { bias: "Bearish (short-term)",trade: "Avoid Longs / Quick Short" }
  };

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

    // color hint for trade text
    const tradeCls = info.bias.startsWith('Bullish') ? 'up'
                    : (info.bias.startsWith('Bearish') ? 'down' : '');

    // If you did NOT add the two new <th>, remove the last two <td> below.
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
  if (!current){ // treat nearest-active as index 0
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
    await loadNextExpiryCard();   // include next expiry refresh
      await loadBreakout5m();  // NEW 5m logic
  }catch(e){}
}
function tick(){
  countdown--;
  if (countdown <= 0){ countdown = AUTO_REFRESH_SEC; doRefresh(); }
  timerEl.textContent = countdown;
  setTimeout(tick, 1000);
}

// ====== Init ======
(async function init(){
  await loadExpiries();
  updateSessionAndDrift();
  await doRefresh();
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
    txt.textContent = 'No 5‑minute breakout detected today.';
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

const CLASS_SCORE = { // price_vs_oi -> score
  "Long Build-up":   +METER_WEIGHTS.classStrong,
  "Short Build-up":  -METER_WEIGHTS.classStrong,
  "Short Covering":  +METER_WEIGHTS.classWeak,
  "Long Unwinding":  -METER_WEIGHTS.classWeak
};

function meterLabel(score){
  // clamp to -6..+6
  score = Math.max(-6, Math.min(6, score));
  if (score >= 4) return {text:'Strong Bullish', cls:'m-bull'};
  if (score >= 1) return {text:'Bullish',        cls:'m-bull'};
  if (score <= -4) return {text:'Strong Bearish',cls:'m-bear'};
  if (score <= -1) return {text:'Bearish',       cls:'m-bear'};
  return {text:'Neutral', cls:'m-neutral'};
}

// Keep a few things in UI state for meter
if (!window.UI) window.UI = {};
UI.breakoutBias = 0;      // +2 bull / -2 bear / 0 none
UI.netDeltaSum  = 0;      // net (PutΔ - CallΔ) within selected window
UI.supportList  = [];     // [{strike,oi}]
UI.resistList   = [];     // [{strike,oi}]


function updateMarketMeter(){
  const card  = document.getElementById('marketMeter');
  if (!card) return;

  const j = lastJson; // from loadSnapshot()
  if (!j || !UI.metricsRows) { card.style.display='none'; return; }

  // ---- Components of score ----
  let score = 0;
  let reasons = [];

  // 1) PCR
  const pcr = Number(j.pcr || 0);
  if (pcr){
    if (pcr >= 1.1){ score += METER_WEIGHTS.pcrStrong; reasons.push(`PCR ${pcr.toFixed(2)} (bullish)`); }
    else if (pcr > 1.0){ score += METER_WEIGHTS.pcrWeak; reasons.push(`PCR ${pcr.toFixed(2)} (mild bull)`); }
    else if (pcr <= 0.9){ score -= METER_WEIGHTS.pcrStrong; reasons.push(`PCR ${pcr.toFixed(2)} (bearish)`); }
    else { score -= METER_WEIGHTS.pcrWeak; reasons.push(`PCR ${pcr.toFixed(2)} (mild bear)`); }
  }

  // 2) Latest classification (from metrics first row)
  const lastRow = UI.metricsRows[0] || {};
  const clsName = lastRow.price_vs_oi || '';
  if (clsName){
    score += (CLASS_SCORE[clsName] || 0);
    reasons.push(`${clsName}`);
  }

  // 3) Breakout (5m)
  if (UI.breakoutBias !== 0){
    score += UI.breakoutBias;
    reasons.push(UI.breakoutBias > 0 ? '5m Breakout' : '5m Breakdown');
  }

  // 4) Net ΔOI (PutΔ - CallΔ) in current window
  if (UI.netDeltaSum){
    score += (UI.netDeltaSum > 0 ? +METER_WEIGHTS.netDelta : -METER_WEIGHTS.netDelta);
    reasons.push(`NetΔOI ${UI.netDeltaSum>0?'+':''}${UI.netDeltaSum.toLocaleString('en-IN')}`);
  }

  // ---- Label & badge ----
  const lab = meterLabel(score);
  const badgeHtml = `<span class="meter-badge ${lab.cls}">${lab.text}</span>`;

  // ---- Levels ----
  const supportHtml = (UI.supportList||[]).map(x=>`<span class="badge b-green">${x.strike}</span>`).join('');
  const resistHtml  = (UI.resistList||[]).map(x=>`<span class="badge b-red">${x.strike}</span>`).join('');

  // ---- Render ----
  document.getElementById('meterBadge').innerHTML = badgeHtml;
  document.getElementById('meterHeadline').innerHTML =
    `${j.symbol} @ <span class="mono">${Number(j.price||0).toFixed(2)}</span> · Exp <span class="mono">${j.expiry}</span>`;
  document.getElementById('meterWhy').innerHTML = `Why: ${reasons.join(' · ')}`;
  document.getElementById('meterSupport').innerHTML = supportHtml || '—';
  document.getElementById('meterResistance').innerHTML = resistHtml || '—';

  card.style.display = 'block';
}

</script>
</body>
</html>
