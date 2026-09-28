<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>OI Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
/* === SECTION LABELS + SEMANTICS === */
.secTitle{display:flex; align-items:center; gap:8px;}
.secTitle .secIcon{font-size:14px; line-height:1; opacity:.95}
.semLegend{display:inline-flex; align-items:center; gap:8px; margin-left:10px; font-size:11px; opacity:.92; flex-wrap:wrap}
.semLegend .sem{display:inline-flex; align-items:center; gap:4px; padding:1px 7px; border-radius:999px; border:1px solid rgba(255,255,255,.14); background:rgba(255,255,255,.04)}
.semLegend .acc{border-color:rgba(34,197,94,.35); background:rgba(34,197,94,.10)}
.semLegend .dist{border-color:rgba(239,68,68,.35); background:rgba(239,68,68,.10)}
.semLegend .bal{border-color:rgba(250,204,21,.35); background:rgba(250,204,21,.10)}

</style>

  <style>
/* ===== Core OI visual separation ===== */

/* Thin separator between 1m and Change OI */
.sep-left {
  border-left: 1px solid rgba(255,255,255,0.28);
}
.sep-right {
  border-right: 1px solid rgba(255,255,255,0.28);
}

/* Slightly darker background for OI + Change OI */
.core-oi {
  background: rgba(255,255,255,0.04);
}

/* Keep ATM stronger */
tr.atm-row .core-oi {
  background: rgba(59,130,246,0.18);
}

/* =====================================================
   NSE OC – ATM highlight (match OI Track style)
   ===================================================== */

/* NOTE: ::before on <tr> is unreliable in many browsers.
   So we paint ATM using TD backgrounds + inset borders. */
#nseOcWrap tbody tr.atm-row td{
  background: rgba(59,130,246,.10) !important;
  font-weight: 700;
  /* top/bottom edge like OI Track row band */
  box-shadow:
    inset 0 1px 0 rgba(59,130,246,.28),
    inset 0 -1px 0 rgba(59,130,246,.28);
}

/* left accent bar */
#nseOcWrap tbody tr.atm-row td:first-child{
  box-shadow:
    inset 4px 0 0 rgba(59,130,246,.60),
    inset 0 1px 0 rgba(59,130,246,.28),
    inset 0 -1px 0 rgba(59,130,246,.28);
}

/* optional right edge to “frame” the band */
#nseOcWrap tbody tr.atm-row td:last-child{
  box-shadow:
    inset -2px 0 0 rgba(59,130,246,.22),
    inset 0 1px 0 rgba(59,130,246,.28),
    inset 0 -1px 0 rgba(59,130,246,.28);
}
/* stronger strike cell like OI Track */
#nseOcWrap tbody tr.atm-row td.strike{
  background: rgba(59,130,246,.18) !important;
  color: #fff !important;
  font-weight: 800;
}

/* --- Make ATM band visible even if cells have inline backgrounds --- */
#nseOcWrap tbody tr.atm-row{
  outline: 2px solid rgba(59,130,246,.35);
  outline-offset: -1px;
}

/* ATM “center marker” line like NSE: disabled (user request) */

/* ===== NSE OC: core columns + separators + ATM pulse (only NSE table) ===== */
#nseOcWrap th.core-oi, #nseOcWrap td.core-oi{
  background: rgba(255,255,255,0.045);
}
#nseOcWrap th.core-chg, #nseOcWrap td.core-chg{
  background: rgba(255,255,255,0.055);
}

	/* FIX: ΔOI (DAY) / ΔVOL (DAY) values touching borders (give extra room) */
	#nseOcWrap th.col-delta-day{
	  min-width: 124px;
	  padding-left: 12px;
	  padding-right: 12px;
	}
	#nseOcWrap td.col-delta-day{
	  min-width: 124px;
	  padding-left: 12px;
	  padding-right: 12px;
	  line-height: 1.25;
	}
	#nseOcWrap td.col-delta-day small,
	#nseOcWrap td.col-delta-day .pct{
	  font-size: 11px;
	  opacity: 0.85;
	}

/* Animate ONLY ATM row inside NSE style table */
#nseOcWrap tbody tr.atm-row{
  animation: nseAtmPulse 1.35s ease-in-out infinite;
}

/* Keep wide tables from breaking layout */
.oi-track-scroll{max-width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch;}
#oiTrackTable{min-width:1400px;}

/* ===== ΔOI % intensity shading + dominance badge ===== */
.tf-delta .delta-wrap{ display:inline-flex; align-items:center; gap:4px; padding:1px 6px; border-radius:8px; }
.tf-delta .delta-wrap .muted{ opacity:.85; }
.tf-delta .delta-wrap.pos.i1{ background: rgba(34,197,94,.08); }
.tf-delta .delta-wrap.pos.i2{ background: rgba(34,197,94,.12); }
.tf-delta .delta-wrap.pos.i3{ background: rgba(34,197,94,.18); }
.tf-delta .delta-wrap.pos.i4{ background: rgba(34,197,94,.26); }
.tf-delta .delta-wrap.pos.i5{ background: rgba(34,197,94,.34); }

.tf-delta .delta-wrap.neg.i1{ background: rgba(239,68,68,.08); }
.tf-delta .delta-wrap.neg.i2{ background: rgba(239,68,68,.12); }
.tf-delta .delta-wrap.neg.i3{ background: rgba(239,68,68,.18); }
.tf-delta .delta-wrap.neg.i4{ background: rgba(239,68,68,.26); }
.tf-delta .delta-wrap.neg.i5{ background: rgba(239,68,68,.34); }

.dom-badge{ display:inline-block; margin-left:6px; padding:1px 6px; border-radius:999px; font-size:10px; line-height:1.4; vertical-align:middle; }
.dom-ce{ background: rgba(59,130,246,.18); }  /* blue-ish */
.dom-pe{ background: rgba(168,85,247,.18); } /* purple-ish */

/* ==== UI FIXES (Jump highlight, TF dropdown, Condensed effect, Top PCR chips) ==== */
.jump-nse-btn{
  animation: jumpPulse 1.8s ease-in-out infinite;
}
@keyframes jumpPulse{
  0%,100%{ transform: translateY(0); box-shadow: 0 0 0 1px rgba(37,99,235,0.22), 0 8px 20px rgba(0,0,0,0.35); }
  50%{ transform: translateY(-1px); box-shadow: 0 0 0 2px rgba(96,165,250,0.22), 0 10px 26px rgba(0,0,0,0.45); }
}

/* Ensure NSE timeframe dropdown is not clipped */
#nseOcWrap, .nse-oc-wrap{
  overflow: visible !important;
}
.nse-tf-pop{
  z-index: 99999 !important;
}

/* Make "Condensed" visibly different */
#nseOcWrap.condensed table{
  font-size: 10px;
}
#nseOcWrap.condensed th,
#nseOcWrap.condensed td{
  padding-top: 3px !important;
  padding-bottom: 3px !important;
}
#nseOcWrap.condensed .dcell .num{
  letter-spacing: 0.1px;
}

</style>



  <style>

  /* ===== Form controls visibility (dark mode) ===== */
  select, input[type="text"], input[type="number"], button {
    outline: none;
  }
  select option {
    background: #0b1220;
    color: #e5e7eb;
  }
  label { color: rgba(229,231,235,.92); }

.mvLbl {
  cursor: pointer;
  text-decoration: underline dotted;
  text-underline-offset: 2px;
}
/* ===== Global tooltip UX =====
   Any element with a tooltip should look hoverable (hand cursor)
*/
[title]:not([title=""]) {
  cursor: default;
  text-decoration: none !important;
  text-underline-offset: 0;
}

/* Only arrow marks look hoverable */
.ltp-arrow[title] { cursor: help; }

/* ===== FAST SMART TOOLTIP ===== */
[data-tip]{ cursor: default; text-decoration: none !important; text-underline-offset: 0; position: relative; }
.arr.tip[data-tip]{ cursor: help; }

/* Disable pseudo-tooltips (we use fixed floating tooltip box) */
[data-tip]::after,
[data-tip]::before{
  display:none !important;
}

/* Floating tooltip box */
#miniTipBox{
  position: fixed;
  z-index: 2147483647;
  max-width: 360px;
  background: linear-gradient(135deg, #0b1220, #020617);
  color: #e5e7eb;
  font-size: 12px;
  line-height: 1.45;
  padding: 8px 10px;
  border-radius: 10px;
  box-shadow: 0 12px 30px rgba(0,0,0,.45), inset 0 0 0 1px rgba(255,255,255,.07);
  pointer-events: none;
  opacity: 0;
  transform: translateY(6px);
  transition: opacity .08s ease, transform .08s ease;
}
#miniTipBox.show{
  opacity: 1;
  transform: translateY(0);
}

</style>
<style>
  .hint{ font-size:11.5px; margin-top:6px; opacity:.9; }
</style>
<style>
.tip-pct{ border-bottom:none !important; text-decoration:none !important; }

.tip-i {
  font-size: 11px;
  line-height: 1;
  opacity: .65;
  margin-left: 6px;
  vertical-align: middle;
}

.tip-i:hover {
  opacity: .9;
}
</style>

  <style>
    
    /* ===== Alert sound helper (hidden) ===== */
    #audioCronAlert{ display:none; }

    /* ===== Market badge variants ===== */
    .market-preopen{ background:rgba(234,179,8,.15); color:#eab308; }
    .market-post{ background:rgba(59,130,246,.15); color:#3b82f6; }

    /* ===== Live / Frozen ===== */
    .data-live{ color:#22c55e; font-weight:600; }
    .data-frozen{ color:#ef4444; font-weight:700; }

    /* ===== Blink for delayed cron ===== */
    @keyframes blinkDanger {
      0%,100% { box-shadow: 0 0 0 rgba(239,68,68,0); }
      50%     { box-shadow: 0 0 0 4px rgba(239,68,68,.45); }
    }
    .status-dot.blink{
      animation: blinkDanger 1s ease-in-out infinite;
    }

    /* ===== Market badge ===== */
    .market-badge{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:2px 8px;
      border-radius:999px;
      font-size:12px;
      font-weight:600;
    }
    .market-open{
      background:rgba(34,197,94,.15);
      color:#22c55e;
    }
    .market-closed{
      background:rgba(239,68,68,.15);
      color:#ef4444;
    }

    /* ----------------- GENERIC / COMMON ----------------- */
    /* hide mid-page headline (moved info into Key Stats) */
    #hdr, #meta{ display:none !important; }

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

    /* Main summary filter pill layout */
    #mainFilterPill{
      display:flex;
      align-items:center;
      flex-wrap:wrap;
      gap:6px;
    }
    #mainFilterPill > label{
      display:flex;
      align-items:center;
      gap:4px;
    }
    #mainFilterPill select,
    #mainFilterPill input[type="number"]{
      font-size:12px;
    }

    /* ----------------- BASE LOOK ----------------- */

    body{
      font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;
      margin:24px;
      background:
        radial-gradient(circle at top left,#1d283a 0,#020617 40%,#020617 100%);
      color:#e8eefc;
    }
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
    .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}

    /* Layout grids */
    .row,
    .row-2col{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:10px;  /* less space between cards */
    }
    @media (min-width:1400px){
      .row{
        grid-template-columns:1.2fr 1.2fr 1fr;
      }
    }

    /* Market Meter (Compact 2-column sections) */
    .mm-grid{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:10px;
      margin-top:0;
    }
    .mm-col{display:flex; flex-direction:column; gap:10px;}
    .mm-sec{
      background: rgba(255,255,255,.03);
      border: 1px solid rgba(255,255,255,.07);
      border-radius: 14px;
      padding:10px 12px;
    }
    .mm-sec-h{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      margin-bottom:6px;
    }
    .mm-sec-title{
      font-weight:700;
      font-size:13px;
      opacity:.92;
      letter-spacing:.02em;
    }
    .mm-why{margin-top:6px;}
    .mm-levels{margin-top:6px;}
    @media (max-width: 980px){
      .mm-grid{grid-template-columns:1fr;}
    }
    @media (max-width:900px){
      .row,
      .row-2col{
        grid-template-columns:1fr;
      }
    }

    /* Cards */
    .card{
      background:radial-gradient(circle at top,#111827 0,#020617 55%,#020617 100%);
      border-radius:14px;
      padding:10px 12px 8px; /* was 16px */
      box-shadow:
        0 14px 35px rgba(0,0,0,.65),
        0 0 0 1px rgba(148,163,184,.12);
      border:1px solid rgba(30,64,175,.5);
      backdrop-filter:blur(4px);
      transition:transform .15s ease-out, box-shadow .15s ease-out,
                border-color .15s ease-out, background .15s ease-out;
    }
    .card + .card{
      margin-top:8px !important; /* tighten vertical gap */
    }
    .card:hover{
      transform:translateY(-2px);
      box-shadow:
        0 22px 55px rgba(15,23,42,.9),
        0 0 0 1px rgba(59,130,246,.8);
      border-color:rgba(59,130,246,.8);
      background:radial-gradient(circle at top,#111827 0,#020617 50%,#020617 100%);
    }

    /* =====================================================
       Collapsible cards (PCR Trend / Classifications / Next)
       - Click header to collapse/expand
       - Remembers state using localStorage
       ===================================================== */
    .card.collapsible{ padding:0; }
    .card.collapsible .card-head{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      padding:10px 12px 8px;
      cursor:pointer;
      user-select:none;
    }
    .card.collapsible .card-head .head-left{
      display:flex;
      align-items:center;
      gap:8px;
      min-width:0;
    }
    .card.collapsible .card-head b{
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .card.collapsible .card-toggle{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      width:28px;
      height:28px;
      border-radius:10px;
      border:1px solid rgba(148,163,184,.28);
      background:rgba(255,255,255,.06);
      color:rgba(226,232,240,.9);
      font-weight:800;
      line-height:1;
      transition:transform .15s ease-out, background .15s ease-out;
      flex:0 0 auto;
    }
    .card.collapsible .card-body{ padding:0 12px 10px; }
    .card.collapsible.collapsed .card-body{ display:none; }
    .card.collapsible.collapsed .card-toggle{ transform:rotate(-90deg); }
    .card.collapsible .card-head:hover .card-toggle{ background:rgba(59,130,246,.12); }

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

    /* Pill severity helpers */
    .pill.good{ border-color:rgba(34,197,94,.85); box-shadow:0 0 10px rgba(34,197,94,.18); }
    .pill.warn{ border-color:rgba(234,179,8,.85); box-shadow:0 0 10px rgba(234,179,8,.14); }
    .pill.bad{  border-color:rgba(248,113,113,.85); box-shadow:0 0 10px rgba(248,113,113,.14); }
    .pill.neu{  border-color:rgba(148,163,184,.45); }

    /* Readiness mini bar inside badge */
    .readybar{ width:90px; height:8px; border-radius:999px; background:rgba(148,163,184,.18); overflow:hidden; display:inline-block; vertical-align:middle; margin-left:6px; }
    .readybar > i{ display:block; height:100%; width:0%; background:rgba(59,130,246,.85); }

    /* Tables */
    table{
      width:100%;
      border-collapse:collapse;
      margin-top:10px;
      font-size:13px;
    }
    th,td{
      padding:5px 4px;
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
    tbody tr:nth-child(odd){background:#020617;}
    tbody tr:nth-child(even){background:#020617;}
    tbody tr:hover{background:#111827;}

    /* OI Track – spacing between OI/ΔOI and timeframes */
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

    /* ===== Fixed status bar (always top) ===== */
    /* ===== STATUS BAR – fixed top ===== */
    /* ===== STATUS BAR LAYOUT TUNING ===== */
.status-bar{
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 99999;
  background: linear-gradient(90deg, rgba(15,23,42,.98), rgba(2,6,23,.98));
  border-bottom: 1px solid rgba(148,163,184,.35);
  box-shadow: 0 8px 22px rgba(0,0,0,.6);
  font-size: 13px;
}

/* pills & buttons */
.status-top-right .pill,
.status-top-right button{
  height:26px;
  display:inline-flex;
  align-items:center;
  gap:4px;
  padding:0 8px;
  border-radius:999px;
}

/* ===== ROW 2: FILTER BAR (single line, scroll if needed) ===== */
.status-filter-row{
  align-items:center;
  gap:8px;
  padding:6px 10px;
  border-top:1px solid rgba(148,163,184,.15);
  overflow-x:auto;
  white-space:nowrap;
}

.status-filter-row::-webkit-scrollbar{
  height:4px;
}
.status-filter-row::-webkit-scrollbar-thumb{
  background:#334155;
  border-radius:4px;
}

/* filter pills compact */
.status-filter-row .pill{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:3px 8px;
  border-radius:999px;
}

/* inputs smaller */
.status-filter-row select,
.status-filter-row input[type="number"]{
  height:24px;
  font-size:12px;
}

/* make selects clearly visible on dark background */
.status-filter-row select,
.status-filter-row input[type="number"],
.status-filter-row input[type="text"]{
  background: rgba(2,6,23,.85);
  color: #e8eefc;
  border: 1px solid rgba(148,163,184,.35);
  border-radius: 8px;
  padding: 0 6px;
}
/* ===== spacing below fixed bar ===== */
:root{ --statusbar-h: 78px; }   /* compact height */

/* ===== sticky table headers BELOW bar ===== */
table thead th{
  position: sticky;
  top: var(--statusbar-h);
  z-index: 50;
  background: rgba(2,6,23,.98);
}

    /* Push page content BELOW the fixed bar */
    .wrap{
      padding-top: 110px;   /* adjust if bar height changes */
    }

    .status-top-right{
      font-size:12px;
      color:#97a6c3;
      display:flex;
      align-items:center;
      gap:8px;
      flex-wrap:wrap;
      margin-left:auto;
    }

    /* Row 2: summary filters → right aligned on desktop */
    .status-filter-row{
      display:flex;
      justify-content:flex-end;
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

    /* ----------------- NET Δ MINI BARS ----------------- */

    /* ----------------- OI TRACK CARD & TOOLBAR ----------------- */

    .card-oi-track{
      margin-top:10px;
      grid-column:1 / -1;
    }
    .card-oi-header{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:6px;
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

    select,
    input[type="number"],
    input[type="text"]{
      background:#020617;
      border-radius:8px;
      border:1px solid rgba(148,163,184,.5);
      color:#e5e7eb;
      padding:3px 6px;
      font-size:12px;
    }
    select:focus,
    input[type="number"]:focus,
    input[type="text"]:focus{
      outline:none;
      border-color:#60a5fa;
      box-shadow:0 0 0 1px rgba(59,130,246,.7);
    }

    /* Meter badges & extra bits */
    .meter-badge{padding:2px 10px;border-radius:999px;font-weight:600}
    .m-bull{background:#103b2d;color:#a7f3d0}
    .m-bear{background:#3b1010;color:#fca5a5}
    .m-neutral{background:#0f2e5f;color:#93c5fd}
    .tip{cursor:help;opacity:.85;margin-left:6px;font-size:12px}
    .levels span{display:inline-block;margin-right:10px;margin-top:4px}

    #meterAlerts{
      margin-top:6px;
      display:flex;
      flex-wrap:wrap;
      gap:4px;
    }
    #meterAlerts .badge{margin-right:0;}

    /* small time-of-day OI profile table */
    #ksOiBuckets{
      margin-top:4px;
      font-size:11px;
    }
    #ksOiBuckets th,
    #ksOiBuckets td{
      padding:3px 4px;
    }

    /* ----------------- STRIKE OI HEATMAP ----------------- */

    .hm-container{
      margin-top:8px;
      display:flex;
      flex-direction:column;
      gap:4px;
    }
    .hm-row{
      display:grid;
      grid-template-columns:64px 1fr 260px;
      gap:8px;
      align-items:center;
      font-size:12px;
    }
    @media (max-width:900px){
      .hm-row{
        grid-template-columns:64px 1fr;
      }
      .hm-label{
        grid-column:1 / -1;
      }
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

    /* ----------------- LIGHT MODE OVERRIDES ----------------- */

    body.light-mode{
      background:#f3f4f6;
      color:#111827;
    }
    body.light-mode .muted{color:#4b5563;}
    body.light-mode .card{
      background:#ffffff;
      border-color:#d1d5db;
      box-shadow:0 8px 18px rgba(15,23,42,.08);
    }
    body.light-mode .card:hover{
      background:#ffffff;
      border-color:#9ca3af;
      box-shadow:0 10px 24px rgba(15,23,42,.15);
    }
    body.light-mode .status-bar{
      background:#e5e7eb;
      border-color:#cbd5f5;
      color:#111827;
      box-shadow:0 8px 20px rgba(0,0,0,.12);
    }
    body.light-mode .status-top-right{
      color:#4b5563;
    }
    body.light-mode table thead th{
      background:#e5e7eb;
      color:#4b5563;
      border-bottom:1px solid #cbd5f5;
    }
    body.light-mode tbody tr:nth-child(odd),
    body.light-mode tbody tr:nth-child(even){
      background:#ffffff;
    }
    body.light-mode tbody tr:hover{
      background:#f9fafb;
    }
    body.light-mode .pill{
      background:#f3f4f6;
      border-color:#cbd5f5;
    }
    body.light-mode select,
    body.light-mode input[type="number"],
    body.light-mode input[type="text"]{
      background:#ffffff;
      color:#111827;
      border-color:#d1d5db;
    }
    body.light-mode .oi-toolbar input{
      background:#ffffff;
      color:#111827;
      border-color:#d1d5db;
    }
    body.light-mode .hm-bar-wrap{
      background:#f9fafb;
      border-color:#d1d5db;
    }
    body.light-mode button{
      background:radial-gradient(circle at top,#2563eb,#1e3a8a);
      color:#f9fafb;
      box-shadow:0 0 0 1px rgba(37,99,235,.6);
    }

    /* ----------------- COMPACT MODE ----------------- */

    body.compact{
      margin:12px;
    }
    body.compact .wrap{
      padding:0 4px;
    }
    body.compact .card{
      padding:8px 10px 6px;
      border-radius:12px;
    }
    body.compact table{
      font-size:11px;
    }
    body.compact th,
    body.compact td{
      padding:4px 3px;
    }
    body.compact .pill{
      padding:2px 6px;
      font-size:11px;
    }
    body.compact .status-bar{
      padding:4px 8px;
      margin-bottom:10px;
    }
    body.compact h2{
      font-size:18px;
      margin-bottom:8px;
    }
    body.compact .big{font-size:22px;}
    body.compact .row,
    body.compact .row-2col{
      gap:8px;
    }

    /* tighten some tall sections */
    #trendCard,
    #zzCard,
    #nextCard{
      margin-top:8px !important;
    }
    .wrap > .card:first-of-type{
      margin-top:4px;
    }

    /* ----------------- MOBILE RESPONSIVE ----------------- */

    @media (max-width:900px){
      .status-filter-row{
        justify-content:flex-start;
      }
    }

    /* ===== Confidence meter coloring ===== */
    .conf { font-weight:700; letter-spacing:1px; padding:2px 6px; border-radius:8px; display:inline-block; }
    .conf-3 { background:rgba(34,197,94,.14); color:#22c55e; }
    .conf-2 { background:rgba(234,179,8,.14); color:#eab308; }
    .conf-1 { background:rgba(239,68,68,.14); color:#ef4444; }
    .conf-0 { background:rgba(148,163,184,.14); color:#94a3b8; }

    /* ===== Daywise Δ arrows (match OI-track vibe) ===== */
    .dw-arrow{
      display:inline-flex;
      align-items:center;
      gap:6px;
      font-weight:700;
      white-space:nowrap;
    }
    .dw-up{ color:#22c55e; }     /* green */
    .dw-down{ color:#ef4444; }   /* red */
    .dw-flat{ color:#94a3b8; }   /* gray */
    .dw-arrow .tri{
      font-size:12px;
      line-height:1;
      opacity:.95;
    }

/* ===== Auto-resume toast ===== */
#resumeToast{
  position:fixed;
  right:18px;
  bottom:18px;
  z-index:9999;
  padding:10px 14px;
  border-radius:12px;
  font-size:14px;
  font-weight:700;
  background:rgba(34,197,94,.95);
  color:#052e16;
  box-shadow:0 10px 30px rgba(0,0,0,.25);
  display:none;
}
#resumeToast.show{ display:block; }

    /* ===== Delta table heat bars ===== */
    .delta-cell{ position:relative; white-space:nowrap; }
    .delta-cell .val{ position:relative; z-index:2; }
    .delta-cell .bar{
      position:absolute; left:6px; right:6px; top:50%;
      height:10px; transform:translateY(-50%);
      border-radius:6px; opacity:.22; z-index:1;
      background:rgba(148,163,184,.4);
      overflow:hidden;
    }
    .delta-cell .fill{
      height:100%;
      border-radius:6px;
      background:rgba(34,197,94,.95); /* default, overridden by classes */
      width:0%;
    }
    .delta-cell.neg .fill{ background:rgba(239,68,68,.95); }
    .delta-cell.pos .fill{ background:rgba(34,197,94,.95); }
    .delta-cell.zero .fill{ background:rgba(148,163,184,.75); }
    tr.atm-row td{ font-weight:800; }
    tr.atm-row{ outline:2px solid rgba(59,130,246,.25); background:rgba(59,130,246,.06); }

    /* ===== Safety toggle ===== */
    .toggle{ position:relative; display:inline-block; width:44px; height:22px; vertical-align:middle; margin:0 6px; }
    .toggle input{ display:none; }
    .slider{ position:absolute; cursor:pointer; inset:0; background:#e5e7eb; transition:.2s; border-radius:999px; }
    .slider:before{ position:absolute; content:""; height:18px; width:18px; left:2px; top:2px; background:white; transition:.2s; border-radius:999px; box-shadow:0 1px 2px rgba(0,0,0,.25); }
    .toggle input:checked + .slider{ background:#22c55e; }
    .toggle input:checked + .slider:before{ transform:translateX(22px); }
    #safetyHint{ font-size:12px; }

</style>

  

<style>
/* ===== NSE STYLE OPTION CHAIN (added) ===== */
.nse-oc-wrap{
  margin-top: 14px;
  background: rgba(10,16,30,.85);
  border:1px solid rgba(255,255,255,.10);
  border-radius:12px;
}
.nse-oc-hdr{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  padding:10px 12px;
  background: rgba(17,24,39,.9);
  border-bottom:1px solid rgba(255,255,255,.10);
}
.nse-oc-hdr .rhs{ display:flex; align-items:center; gap:8px; }

/* NSE OC summary bar (timeframe click) */
.nse-oc-sum{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  padding:10px 12px;
  background: rgba(2,6,23,.55);
  border-bottom:1px solid rgba(255,255,255,.08);
}
.nse-oc-sum .sum-left{ display:flex; align-items:center; gap:18px; flex-wrap:wrap; }
.nse-oc-sum .sum-item{ font-size:13px; opacity:.92; }
.nse-oc-sum .sum-item b{ font-variant-numeric: tabular-nums; }
.nse-oc-sum .sum-right{ font-size:13px; opacity:.95; white-space:nowrap; }
.nse-oc-sum .pill{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:6px 12px;
  border-radius:10px;
  background: rgba(255,255,255,.06);
  border:1px solid rgba(255,255,255,.10);
  font-weight:700;
  white-space:nowrap;
}

/* ===== Absorption badge (OI absorption detector) ===== */
.abs-badge{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  margin-left:6px;
  padding:2px 6px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,.14);
  background: rgba(0,0,0,.22);
  font-size:11px;
  line-height:1.1;
  font-weight:800;
  letter-spacing:.02em;
  vertical-align:middle;
}
.abs-badge.strong{ border-color:rgba(34,197,94,.55); background:rgba(34,197,94,.14); color:rgba(134,239,172,1); }
.abs-badge.med{ border-color:rgba(253,224,71,.55); background:rgba(253,224,71,.12); color:rgba(253,224,71,1); }

/* NSE OC timeframe buttons */
.nse-oc-tf{
  margin-left:10px;
}

  .nse-oc-metric{ display:flex; align-items:center; gap:6px; margin-left:10px; }
  .nse-oc-metric .tflbl{ margin-right:4px; opacity:.8; font-size:12px; }

/* NSE OC multi-timeframe column highlight */
.nse-oc .th-hi{ box-shadow: inset 0 -2px 0 rgba(253,224,71,.65); }
.nse-oc td.col-hi{ background: rgba(253,224,71,.06); }
.nse-oc-tf{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.tfbtn.mini{ padding:2px 7px; font-size:11px; border-radius:999px; opacity:.9; }

.nse-oc-tf .tfbtn{
  font-size:11px;
  padding:3px 8px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,.14);
  background:rgba(255,255,255,.04);
  color:#cbd5e1;
  cursor:pointer;
  user-select:none;
}
.nse-oc-tf .tfbtn:hover{ background:rgba(255,255,255,.07); }
.nse-oc-tf .tfbtn.active{
  background:rgba(59,130,246,.18);
  border-color:rgba(59,130,246,.35);
  color:#bfdbfe;
  font-weight:700;
}
.nse-oc-tf .tflbl{
  font-size:11px;
  opacity:.75;
  margin-right:2px;
}

/* Timeframe picker (show/hide 1,3,5,10,15,30) */
.nse-tf-menu{ position:relative; display:inline-flex; align-items:center; }
.nse-tf-menu .tfbtn.picker{ padding:3px 8px; font-size:11px; }
.nse-tf-pop{
  position:absolute;
  top:28px;
  left:0;
  min-width:190px;
  padding:8px 10px;
  border-radius:12px;
  border:1px solid rgba(255,255,255,.14);
  background: rgba(2,6,23,.98);
  box-shadow: 0 10px 26px rgba(0,0,0,.45);
  display:none;
}
.nse-tf-menu.open .nse-tf-pop{ display:block; }
.nse-tf-pop .row{ display:flex; align-items:center; justify-content:space-between; gap:10px; padding:6px 0; }
.nse-tf-pop label{ display:flex; align-items:center; gap:8px; font-size:12px; cursor:pointer; }
.nse-tf-pop input[type="checkbox"]{ accent-color:#3b82f6; }
.nse-tf-pop .actions{ display:flex; gap:8px; margin-top:8px; }
.nse-tf-pop .actions .tfbtn{ padding:3px 10px; }
.nse-tf-pop .hint{ font-size:11px; opacity:.7; margin-top:6px; }
.nse-oc-hdr .chip{
  font-size:12px;
  padding:4px 8px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,.14);
  background: rgba(0,0,0,.25);
  white-space:nowrap;
}
.nse-oc-hdr .chip b{ font-variant-numeric: tabular-nums; }
.nse-oc-scroll{
  overflow-y:auto;
  overflow-x:auto; /* allow horizontal scroll when needed */
  max-height: 360px;
}
/* extra safety: prevent tiny 1-2px overflow */
#nseOcWrap table{ width:100%; max-width:100%; table-layout:auto; box-sizing:border-box; }
/* ================================
  NSE STYLE OPTION CHAIN – SIZE / ROW HEIGHT / NUMBER SPACING
  Change only the vars below for future tuning
   ================================ */
#nseOcWrap{
  --nse-oc-font: 13.5px;     /* Normal (match OI Track) */
  --nse-oc-pad-y: 7px;       /* Row height control */
  --nse-oc-pad-x: 8px;
  --nse-oc-line: 1.25;
  --nse-oc-spike-thresh: .85; /* 0.85 = top 15% of max volume */
}
#nseOcWrap.size-compact{
  --nse-oc-font: 12px;
  --nse-oc-pad-y: 5px;
  --nse-oc-pad-x: 7px;
  --nse-oc-line: 1.15;
}
#nseOcWrap.size-normal{ /* explicit (optional) */ }
#nseOcWrap.size-large{
  --nse-oc-font: 14.5px;
  --nse-oc-pad-y: 8px;
  --nse-oc-pad-x: 9px;
  --nse-oc-line: 1.35;
}

/* ================================
    OI TRACK TABLE – SIZE / ROW HEIGHT / NUMBER SPACING
    (Synced with NSE OC size toggle)
    Change only these vars for future tuning
   ================================ */
#oiTrackTable{
  --ot-font: 13.5px;
  --ot-pad-y: 6px;
  --ot-pad-x: 8px;
  --ot-line: 1.25;
}
#oiTrackTable.size-compact{
  --ot-font: 12px;
  --ot-pad-y: 4.5px;
  --ot-pad-x: 7px;
  --ot-line: 1.15;
}
#oiTrackTable.size-normal{ /* explicit */ }
#oiTrackTable.size-large{
  --ot-font: 14.5px;
  --ot-pad-y: 7.5px;
  --ot-pad-x: 9px;
  --ot-line: 1.35;
}

/* size toggle buttons in NSE OC header */
.nse-oc-size{ display:flex; align-items:center; gap:6px; margin-left:8px; }
.nse-oc-size .tfbtn{ font-size:11px; padding:3px 8px; border-radius:999px; border:1px solid rgba(255,255,255,.14); background:rgba(255,255,255,.04); color:#cbd5e1; cursor:pointer; user-select:none; }
.nse-oc-size .tfbtn:hover{ background:rgba(255,255,255,.07); }
.nse-oc-size .tfbtn.active{ background:rgba(59,130,246,.18); border-color:rgba(59,130,246,.35); color:#bfdbfe; font-weight:800; }

/* Bold volume spikes like NSE */
.nse-oc td.vol-spike{
  font-weight: 900;
  background: rgba(253,224,71,.08);
}
.nse-oc td.vol-max{
  font-weight: 900;
  outline:2px solid rgba(253,224,71,.55);
  outline-offset:-2px;
  background: rgba(253,224,71,.10);
}

.nse-oc{
  width:100%;
  border-collapse:collapse;
  font-size: var(--nse-oc-font, 13.5px);
  line-height: var(--nse-oc-line, 1.25);
  min-width: 980px;
}
.nse-oc th,.nse-oc td{
  padding: var(--nse-oc-pad-y, 7px) var(--nse-oc-pad-x, 8px);
  border:1px solid rgba(255,255,255,.08);
  white-space:nowrap;
  text-align:right;
  font-variant-numeric: tabular-nums;
  font-feature-settings: "tnum" 1, "lnum" 1;
}
.nse-oc thead th{ position:sticky; top:0; z-index:5; }
.nse-oc .call-head{ background:rgba(19,47,76,.95); color:#c7ecff; text-align:center; }
.nse-oc .put-head{ background:rgba(58,31,31,.95); color:#ffd0d0; text-align:center; }
.nse-oc .strike-head{ background:rgba(31,41,55,.95); text-align:center; }
.nse-oc .ltp-h{ background:rgba(17,24,39,.95); text-align:right; }

.nse-oc .strike, .nse-oc .strike-head2{
  text-align:center;
  font-weight:800;
  background:rgba(17,24,39,.95);
}
.nse-oc .strike.atm{
  background: rgba(250,204,21,.95);
  color:#000;
}

.nse-oc tbody tr.atm-row{
  outline: 2px solid rgba(59,130,246,.35);
  background: rgba(59,130,246,.08);
}
.nse-oc tbody tr.atm-row td{
  background: rgba(59,130,246,.08) !important;
  font-weight:700;
}
.nse-oc tbody tr.atm-row td.strike{
  background: rgba(59,130,246,.16) !important;
  color:#fff;
  font-weight:800;
}
.nse-oc td.itm-call{ background: rgba(34,197,94,.025); }
.nse-oc td.itm-put{ background: rgba(239,68,68,.025); }

.nse-oc .dcell .num{ color: inherit; }
.nse-oc .dcell .arr.up{ color:#22c55e; }
.nse-oc .dcell .arr.down{ color:#ef4444; }
.nse-oc .dcell .arr.flat{ opacity:.65; }
.nse-oc td.pos, .nse-oc span.pos{ color:#22c55e; font-weight:700; }
.nse-oc td.neg, .nse-oc span.neg{ color:#ef4444; font-weight:700; }

/* LTP column (with change) */
.nse-oc td.ltp-col{ text-align:right; font-weight:700; }

/* LTP arrow behavior (safe, no layout changes) */
.nse-oc .ltp-arrow{ cursor: help; user-select:none; }
.nse-oc .ltp-up{ color:#22c55e; }
.nse-oc .ltp-down{ color:#ef4444; }
.nse-oc .ltp-arrow.faded{ opacity:.35; }
.nse-oc .ltp-arrow.blink{ animation:ltpBlink .9s ease-in-out 3; }

.nse-oc .max-oi{
  outline:2px solid rgba(34,197,94,.55);
  outline-offset:-2px;
  background: rgba(34,197,94,.08);
}

.nse-oc .max-chg{
  outline:2px solid rgba(250,204,21,.55);
  outline-offset:-2px;
  background: rgba(250,204,21,.08);
}

/* ΔOI arrow + pct */
.nse-oc .dcell{
  display:flex;
  justify-content:flex-end;
  align-items:baseline;
  gap:6px;
}
.nse-oc .dcell .arr{
  font-size:11px;
  opacity:.9;
}
.nse-oc .dcell .pct{
  font-size:11px;
  opacity:.7;
}

/* ATM full-row highlight */
.nse-oc tbody tr.atm-row td.max-oi,
.nse-oc tbody tr.atm-row td.max-chg{
  background: rgba(253,224,71,.10);
}

/* sticky strike column */
.nse-oc .sticky-strike{
  position: sticky;
  left: 0;
  z-index: 4;
  box-shadow: 6px 0 10px rgba(0,0,0,.25);
}
.nse-oc thead .sticky-strike{ z-index: 6; }

/* ===== NSE-style light alternate rows ===== */
.nse-oc tbody tr:nth-child(even) {
  background: rgba(255,255,255,0.035);   /* very light */
}

.nse-oc tbody tr:nth-child(odd) {
  background: transparent;
}

/* Keep hover slightly stronger */
.nse-oc tbody tr:hover {
  background: rgba(59,130,246,0.10) !important;
}
</style>


<style>
/* ===== Beautify: OI Total Chips ===== */
.oiTotChip{
  display:inline-flex; align-items:center; gap:10px;
  padding:7px 10px;
  border-radius:12px;
  background:rgba(255,255,255,.045);
  border:1px solid rgba(255,255,255,.12);
  backdrop-filter: blur(6px);
  font-size:12px;
  line-height:1;
  white-space:nowrap;
}
.oiTotChip .k{ opacity:.82; font-weight:700; }
.oiTotChip .v{ font-weight:800; letter-spacing:.2px; }
.oiTotChip .ce{ color:#60a5fa; }
.oiTotChip .pe{ color:#f472b6; }
.oiTotChip .pcrPill{
  padding:3px 8px; border-radius:999px;
  font-weight:900; background:rgba(0,0,0,.35);
  border:1px solid rgba(255,255,255,.10);
}
.oiDomBar{
  width:78px; height:8px; border-radius:999px;
  background:rgba(255,255,255,.08);
  border:1px solid rgba(255,255,255,.10);
  overflow:hidden;
}
.oiDomBar > i{ display:block; height:100%; width:50%; background:rgba(255,255,255,.22); }
@keyframes pcrPulse { 0%,100%{ box-shadow:0 0 0 rgba(0,0,0,0);} 50%{ box-shadow:0 0 18px rgba(255,255,255,.20);} }
.oiPcrBull{ box-shadow: inset 0 0 0 1px rgba(34,197,94,.35); }
.oiPcrBear{ box-shadow: inset 0 0 0 1px rgba(239,68,68,.35); }
.oiPcrBull.oiPcrGlow{ animation:pcrPulse 1.2s ease-in-out infinite; box-shadow:0 0 18px rgba(34,197,94,.25); }
.oiPcrBear.oiPcrGlow{ animation:pcrPulse 1.2s ease-in-out infinite; box-shadow:0 0 18px rgba(239,68,68,.25); }

/* ===== Expiry chip top dock (full-page sticky) ===== */
#oiExpiryDock{
  position: fixed;
  top: var(--oi-dock-top, 54px);
  left: 50%;
  transform: translateX(-50%);
  z-index: 999999;
  pointer-events: none;
  max-width: calc(100vw - 18px);
}
#oiExpiryDock .oiTotChip{
  pointer-events: auto;
}

#oiExpiryDock .oiDockRow{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  flex-wrap:wrap;
}
#oiExpiryDock .oiDockChip{ margin-left:0; }
@media (max-width: 900px){
  #oiExpiryDock{
    left: 10px;
    right: 10px;
    transform: none;
  }
}

</style>

<style id="tableSizeSyncV2">
/* =====================================================
  TABLE SIZE SYNC V2 (OI Track + NSE OC)
  - Same font size / row height
  - Tabular number spacing
  - Per-table weight: OI bold, NSE normal
  - NSE condensed mode
  - Print-friendly export
   ===================================================== */

:root{
  --tbl-font: 12px;
  --tbl-font-lg: 13px;
  --tbl-pad-y: 7px;
  --tbl-pad-x: 8px;
  --tbl-line: 1.3;

  --tbl-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;

  --oi-weight: 600;   /* OI table bold */
  --nse-weight: 400;  /* NSE normal */
}

/* Compact / Normal / Large (shared) */
.size-compact{
  --tbl-font: 10px;
  --tbl-font-lg: 11px;
  --tbl-pad-y: 5px;
  --tbl-line: 1.2;
}
.size-normal{ /* defaults from :root */ }
.size-large{
  --tbl-font: 15px;
  --tbl-font-lg: 16px;
  --tbl-pad-y: 9px;
  --tbl-line: 1.35;
}

/* OI TRACK TABLE sizing */
#oiTrackTable{
  font-size: var(--tbl-font);
  line-height: var(--tbl-line);
}
#oiTrackTable th,
#oiTrackTable td{
  padding: var(--tbl-pad-y) var(--tbl-pad-x);
  font-variant-numeric: tabular-nums;
  font-feature-settings: "tnum" 1, "lnum" 1;
  font-weight: var(--oi-weight);
  font-family: var(--tbl-family);
}

/* NSE OC sizing */
#nseOcWrap table{
  font-size: var(--tbl-font);
  line-height: var(--tbl-line);
}
#nseOcWrap th,
#nseOcWrap td{
  padding: var(--tbl-pad-y) var(--tbl-pad-x);
  font-variant-numeric: tabular-nums;
  font-feature-settings: "tnum" 1, "lnum" 1;
  font-weight: var(--nse-weight);
  font-family: var(--tbl-family);
}

/* NSE condensed mode (optional) */
#nseOcWrap.condensed table,
#nseOcWrap.condensed th,
#nseOcWrap.condensed td{
  font-family: var(--tbl-family);
  font-stretch: condensed;
  letter-spacing: -0.15px;
}

/* Strike / ATM emphasis (use shared lg font but keep NSE normal weight unless you want bold) */
#nseOcWrap td.strike,
#nseOcWrap tr.atm-row td{
  font-size: var(--tbl-font-lg);
  font-weight: 600;
}

/* Volume spikes (NSE-like) */
#nseOcWrap .vol-spike{
  font-weight: 800;
}
#nseOcWrap .vol-spike.up{
  text-shadow: 0 0 0 rgba(0,0,0,0); /* keep stable */
}
#nseOcWrap .vol-spike.down{
  text-shadow: 0 0 0 rgba(0,0,0,0);
}

/* Print / Export friendly */
@media print{
  /* hide controls / sticky bars */
  .nse-oc-hdr .rhs, .nse-oc-hdr .nse-oc-tf, .nse-oc-hdr .nse-oc-metric, .nse-oc-hdr .nse-oc-size,
  button, input, select { display:none !important; }

  body{
    background:#fff !important;
    color:#000 !important;
  }

  /* remove shadows/blur */
  *{
    box-shadow:none !important;
    text-shadow:none !important;
    backdrop-filter:none !important;
  }

  /* force readable print size */
  :root{
    --tbl-font: 11px;
    --tbl-font-lg: 12px;
    --tbl-pad-y: 3px;
    --tbl-pad-x: 5px;
    --tbl-line: 1.15;

  --tbl-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
    --oi-weight: 600;
    --nse-weight: 400;
  }

  table{
    page-break-inside:auto;
    border-collapse: collapse !important;
  }
  tr{ page-break-inside:avoid; page-break-after:auto; }
  th, td{ border: 1px solid #ddd !important; }
}

/* =====================================================
   NSE OC – FULL ROW ATM + NEAR ATM + MAX OI + MARKER + ANIMATION
   (Works with dynamically rebuilt tables + inline cell BG)
   ===================================================== */
#nseOcWrap #nseOcBody tr.atm-row td.atm-td{
  background: rgba(59,130,246,.14) !important;
  font-weight: 700;
  box-shadow:
    inset 0 1px 0 rgba(59,130,246,.45),
    inset 0 -1px 0 rgba(59,130,246,.45),
    /* subtle divider lines for ATM row */
    inset 0 2px 0 rgba(250,204,21,.14),
    inset 0 -2px 0 rgba(250,204,21,.14);
}
#nseOcWrap #nseOcBody tr.atm-row td.atm-td:first-child{
  box-shadow:
    inset 4px 0 0 rgba(59,130,246,.85),
    inset 0 1px 0 rgba(59,130,246,.45),
    inset 0 -1px 0 rgba(59,130,246,.45);
}
#nseOcWrap #nseOcBody tr.atm-row td.strike.atm-td{
  background: rgba(59,130,246,.22) !important;
  font-weight: 800;
  position: relative;
}
/* ATM center marker line like NSE (on STRIKE cell) */
#nseOcWrap #nseOcBody tr.atm-row td.strike.atm-td::after{
  content:'';
  position:absolute;
  top: 0;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 2px;
  background: rgba(250,204,21,.95);
  border-radius: 2px;
}

/* Reduce visual weight for non-ATM strikes (keeps ATM/near-ATM prominent) */
#nseOcWrap #nseOcBody td.strike{
  font-weight: 500;
  opacity: .92;
}
#nseOcWrap #nseOcBody tr.atm-row td.strike,
#nseOcWrap #nseOcBody tr.near-atm-1 td.strike,
#nseOcWrap #nseOcBody tr.near-atm-2 td.strike{
  opacity: 1;
}

/* ±1 / ±2 strike light bands */
#nseOcWrap #nseOcBody tr.near-atm-1 td.near-td{
  background: rgba(59,130,246,.07) !important;
}
#nseOcWrap #nseOcBody tr.near-atm-2 td.near-td{
  background: rgba(59,130,246,.04) !important;
}

/* Max OI (left-most CALL, right-most PUT) */
#nseOcWrap #nseOcBody td.max-call-oi,
#nseOcWrap #nseOcBody td.max-put-oi{
  outline: 1px solid rgba(250,204,21,.55);
  box-shadow: inset 0 0 0 1px rgba(250,204,21,.22);
  border-radius: 6px;
  font-weight: 800;
}

/* Animate ATM row when it changes */
@keyframes nseAtmPulse{
  0%   { box-shadow: 0 0 0 rgba(59,130,246,.75); }
  100% { box-shadow: 0 0 20px rgba(59,130,246,0); }
}
#nseOcWrap #nseOcBody tr.atm-row.atm-flash td.atm-td{
  animation: nseAtmPulse .75s ease-out;
}

</style>


<style> 

  /* Make faded arrows still visible */
.ltp-arrow.faded { opacity: 0.65 !important; }

/* On hover, always show arrow clearly */
.ltp-arrow:hover { opacity: 1 !important; }

/* Blink should not fade too much */
@keyframes ltpBlink {
  0%   { opacity: 1; }
  50%  { opacity: 0.65; }
  100% { opacity: 1; }
}

/* Keep arrow from “jumping” into next cell */
.ltp-arrow{ cursor: help; user-select:none; }

/* Ensure Change OI cells never spill out */
td.core-chg, .core-chg {
  white-space: nowrap;
  overflow: hidden;
}

/* Make the pill auto-size to its content */
td.core-chg .pill, td.core-chg .badge, td.core-chg .chip,
.core-chg .pill, .core-chg .badge, .core-chg .chip {
  width: auto !important;
  min-width: 0 !important;
  max-width: none !important;
  display: inline-flex !important;
  align-items: center;
  justify-content: center;
  padding: 2px 8px !important;   /* grows with number length */
  box-sizing: border-box !important;
}

</style>
<style> 
  #nseOcWrap th.ltp-col,
#nseOcWrap td.ltp-col {
  width: 62px !important;
  min-width: 62px !important;
  max-width: 62px !important;
  padding-left: 4px !important;
  padding-right: 4px !important;
  text-align: right;
  white-space: nowrap;
  overflow: hidden;
  font-size: 11px;
}

</style>
<style>
/* Tooltip cells: no dotted underline (clean look) */
.tip{ border-bottom:none !important; }
</style>





<style id="jumpToNseBtnStyle">
  .jump-nse-btn{
    position: fixed;
    right: 18px;
    bottom: 86px; /* above bottom dock */
    z-index: 9999;
    padding: 8px 12px;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.18);
    background: rgba(10,14,24,.78);
    color: rgba(255,255,255,.92);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .2px;
    cursor: pointer;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 8px 24px rgba(0,0,0,.35);
  }
  .jump-nse-btn:hover{ background: rgba(10,14,24,.9); }
</style>

<style>
/* Day-wise collapse helpers */
.collapse-hidden{display:none !important;}
[data-collapse-toggle].is-open{transform: rotate(180deg);}
</style>


<style>
/* Align collapse icon to right like other cards */
#daywiseOiCard .card-hd{display:flex;align-items:center;justify-content:space-between}
#daywiseOiCard .card-actions{margin-left:auto}
</style>




<style>
#top5OiCard .head-right{margin-left:auto; display:flex; align-items:center}
#top5ExpiryBadge{
  font-size:11px;
  padding:3px 8px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.04);
  color:rgba(255,255,255,.85);
}
</style>




<!-- FORCE VISIBILITY: Daily OI Bias History Enhancements -->
<style>

#oi-bias-history-enhancements .oi-verdict{
    display:block!important;
    background:#0b1220;
    border-left:4px solid #3b82f6;
    padding:8px 10px;
    margin-top:8px;
    font-size:13px
}

.delta-ce-up{color:#ff6b6b;font-weight:700}
.delta-pe-up{color:#2ecc71;font-weight:700}
.delta-cover{color:#f59e0b;font-weight:700}
</style>


<!-- FINAL FIX: Daily OI Bias History card height/scroll + sticky header (scoped) -->
<style>
/* Make the Daily OI Bias History block reliably visible even inside height-clipped cards */
.oi-bias-history-section {
  min-height: 260px !important;
  max-height: 340px !important;
  overflow-y: auto !important;
  padding-bottom: 6px !important;
}

/* Sticky header only for this table */
#daywiseOiTbl thead th{
  background: #0b1220 !important;
}

</style>


<!-- FINAL FIX v8: Collapse should shrink the Daily OI Bias box (border stays, content hides) -->
<style>
/* When collapsed: keep border/card chrome, but remove the tall empty space */
.oi-bias-history-section.is-collapsed{
  min-height: 0 !important;
  max-height: none !important;
  height: auto !important;
  overflow: hidden !important;
  padding-bottom: 0 !important;
}

/* If your card wrapper uses padding, reduce it when collapsed (safe + scoped) */
.oi-bias-history-section.is-collapsed .oi-verdict{
  display: none !important;
}
</style>


<!-- FINAL FIX v9: Ensure Bias Trend / Icons / Verdict UI is visible and styled (scoped) -->
<style>
/* Enhancements container */
#oi-bias-history-enhancements{
  display:block !important;
  visibility:visible !important;
  opacity:1 !important;
  margin:6px 0 8px 0;
}
#oi-bias-history-enhancements .row{
  display:flex; align-items:center; gap:10px; flex-wrap:wrap;
}
#oi-bias-history-enhancements .chip{
  display:inline-flex; align-items:center; gap:6px;
  padding:4px 8px; border-radius:999px;
  border:1px solid rgba(255,255,255,0.12);
  background:rgba(255,255,255,0.04);
  font-size:12px; font-weight:600;
}
#oi-bias-history-enhancements .label{
  font-size:12px; opacity:.85; margin-right:4px;
}
#oi-bias-history-enhancements .trendbar{
  display:flex; gap:6px; align-items:center;
}
#oi-bias-history-enhancements .dot{
  width:18px; height:18px; border-radius:50%;
  display:inline-flex; align-items:center; justify-content:center;
  font-size:11px; font-weight:800;
  border:1px solid rgba(255,255,255,0.18);
}
#oi-bias-history-enhancements .bull{ background: rgba(46,204,113,0.9); color:#07130b; }
#oi-bias-history-enhancements .bear{ background: rgba(231,76,60,0.9);  color:#190606; }
#oi-bias-history-enhancements .neu { background: rgba(160,174,192,0.9); color:#0b1220; }

#oi-bias-history-enhancements .flip-alert{
  display:none;
  width:100%;
  padding:6px 10px;
  border-radius:8px;
  border:1px solid rgba(245,158,11,0.55);
  background: rgba(245,158,11,0.12);
  color: #fcd34d;
  font-size:12px;
  font-weight:700;
}
#oi-bias-history-enhancements .flip-alert.show{ display:block; }

/* Delta coloring in table */
#daywiseOiTbl td.delta-ce-up{ color:#ff6b6b !important; font-weight:800 !important; }
#daywiseOiTbl td.delta-ce-down{ color:#a7f3d0 !important; font-weight:800 !important; }
#daywiseOiTbl td.delta-pe-up{ color:#34d399 !important; font-weight:800 !important; }
#daywiseOiTbl td.delta-pe-down{ color:#fb7185 !important; font-weight:800 !important; }
#daywiseOiTbl td.delta-cover{ color:#f59e0b !important; font-weight:800 !important; }

/* Verdict strip */
.oi-verdict{
  margin-top:8px;
  padding:8px 10px;
  border-left:4px solid rgba(59,130,246,0.9);
  background: rgba(59,130,246,0.10);
  border-radius:8px;
  font-size:13px;
}
</style>

</head>



<body>




  
<div class="wrap">
  <?php
  // SPECIAL TRADING SESSION banner: computed in IST by OiController::specialSessionInfo()
  // from writable/cache/special_trading_days.conf (EXEMPT_DATES / EXEMPT_SCHEDULE)
  $special = $special ?? ['active' => false, 'meta' => [], 'next' => null, 'alert' => false];
  ?>

  <?php if (!empty($special['active'])): ?>
    <div style="
      margin:12px 0;
      padding:12px 14px;
      border-radius:10px;
      border:1px solid rgba(255,204,0,0.45);
      background:rgba(255,204,0,0.12);
      color:#ffe8a3;
      font-weight:700;
      font-size:14px;
    ">
      ⚡ Special Trading Session Active
      <span style="font-weight:600; color:#fff1c9;">
        — <?= htmlspecialchars($special['meta']['date'] ?? '') ?>
        <?php if (!empty($special['meta']['start'])): ?>
          (<?= htmlspecialchars($special['meta']['start']) ?>–<?= htmlspecialchars($special['meta']['end']) ?>)
        <?php endif; ?>
      </span>
    </div>
  <?php endif; ?>

  <?php if (!empty($special['next'])): ?>
    <div style="
      margin:0 0 12px 0;
      padding:10px 14px;
      border-radius:10px;
      border:1px solid rgba(0,153,255,0.35);
      background:rgba(0,153,255,0.10);
      color:#cfe9ff;
      font-weight:700;
      font-size:13px;
    ">
      📊 Next Special Session:
      <span style="font-weight:600; color:#e9f4ff;">
        <?= htmlspecialchars($special['next']['date']) ?>
        <?php if (!empty($special['next']['label'])): ?>
          — <?= htmlspecialchars($special['next']['label']) ?>
        <?php endif; ?>
        <?php if (!empty($special['next']['start'])): ?>
          (<?= htmlspecialchars($special['next']['start']) ?>–<?= htmlspecialchars($special['next']['end']) ?>)
        <?php endif; ?>
      </span>
    </div>
  <?php endif; ?>

  <?php if (!empty($special['alert'])): ?>
    <script>
      (function(){
        try {
          var key = 'special_session_alert_' + new Date().toISOString().slice(0,10);
          if (!sessionStorage.getItem(key)) {
            alert('⚡ Special Trading Session today. Dashboard running in special mode.');
            sessionStorage.setItem(key, '1');
          }
        } catch(e) {}
      })();
    </script>
  <?php endif; ?>

  <h2>
    Intraday OI Levels
    <?php if (!empty($special['next'])): ?>
      <span
        title="Next Special Session: <?= htmlspecialchars($special['next']['date']) ?> <?= htmlspecialchars($special['next']['label'] ?? '') ?>"
        style="margin-left:6px; font-size:18px; cursor:pointer; color:#cfe9ff;"
      >📅</span>
    <?php endif; ?>
  </h2>

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
    <!-- ROW 1: DB / Fetch / Enrich  +  toggles & timer -->
    <strong>DB last insert:</strong>
    <span class="status-dot" id="dotDb"></span>
    <span id="tsDb" class="<?= $cls($dbAgo) ?>">
      <?= esc($health['db']['ist'] ?? '—') ?>
    </span>
    <span id="agoDb">(<?= $dbAgo!==null ? esc($dbAgo).' min ago' : 'no data' ?>)</span>

    &nbsp;|&nbsp;

    <strong>Fetch cron:</strong>
    <span class="status-dot" id="dotFetch"></span>
    <span id="tsFetch" class="<?= $cls($fetchAgo) ?>">
      <?= esc($health['fetch']['ist'] ?? 'No log') ?>
    </span>
    <span id="agoFetch">(<?= $fetchAgo!==null ? esc($fetchAgo).' min ago' : '—' ?>)</span>

    &nbsp;|&nbsp;

    <strong>Enrich cron:</strong>
    <span class="status-dot" id="dotEnrich"></span>
    <span id="tsEnrich" class="<?= $cls($enrAgo) ?>">
      <?= esc($health['enrich']['ist'] ?? 'No log') ?>
    </span>
    <span id="agoEnrich">(<?= $enrAgo!==null ? esc($enrAgo).' min ago' : '—' ?>)</span>
    <span class="pill" id="daywiseBiasNowBadge" style="margin-left:8px;">Daywise Bias: —</span>
    <span class="pill" id="trendAlignBadge" style="margin-left:6px;">Align: —</span>
    <span class="pill" id="strikeShiftBadge" style="margin-left:6px;">Strike Shift: —</span>
    <span class="pill" id="falseBreakBadge" style="margin-left:6px;" title="Day move vs a points threshold (NIFTY 80, BANKNIFTY 220), checked for OI follow-through">Big move: —</span>
    <span class="pill" id="readinessBadge" style="margin-left:6px;">Ready: —</span>
    <span class="pill" id="dataGapBadge" style="margin-left:6px; display:none;">Data Gap: —</span>
    <span class="pill" id="signalsPausedBadge" style="margin-left:6px; display:none;">Signals: PAUSED</span>
    <div id="resumeToast">LIVE again — signals resumed</div>


    


    <!-- ROW 2: SUMMARY FILTER BAR (always visible) -->
  <div class="status-filter-row">
    
    <span class="pill" id="mainFilterPill">
      <label>
        Symbol:
        <!-- Symbol (NIFTY / BANKNIFTY) -->
        <select id="symbol" style="min-width:110px">
          <option value="NIFTY">NIFTY</option>
          <option value="BANKNIFTY">BANKNIFTY</option>
        </select>
      </label>

      <label>
        Expiry:
        <select id="expirySel" style="min-width:140px">
          <option value="">Nearest active</option>
        </select>
      </label>

      <label>
        Window:
        <input id="window" type="number" value="10" min="1" style="width:60px">
      </label>

      <label>
        Strikes ±
        <select id="strikeWin" style="width:70px">
          <option value="3">3</option>
          <option value="5" selected>5</option>
          <option value="6">6</option>
          <option value="7">7</option>
          <option value="8">8</option>
          <option value="9">9</option>
          <option value="10">10</option>
          <option value="all">All</option>
        </select>
      </label>
      


      <label>
        Band ±
        <select id="atmBand" style="width:70px">
          <option value="3" selected>3</option>
          <option value="5">5</option>
          <option value="10">10</option>
        </select>
      </label>

      <label style="display:flex;align-items:center;gap:4px;">
        <input type="checkbox" id="showNextExp">
        <span>Next exp</span>
      </label>

      <span class="pill" style="padding:2px 6px;">
        Filter:
        <label style="margin-left:4px"><input type="radio" name="optFilter" value="both" checked> Both</label>
        <label style="margin-left:4px"><input type="radio" name="optFilter" value="calls"> Calls</label>
        <label style="margin-left:4px"><input type="radio" name="optFilter" value="puts"> Puts</label>
      </span>

      <button id="toggleAllBtn" style="margin-left:4px">Toggle All</button>
      <span class="muted" id="lastRef" style="margin-left:4px;"></span>
    </span>

          <label class="lbl">

        <span class="toggle">
          <input type="checkbox" id="safetyToggle">
          <span class="slider"></span>
        </span>
        <span class="muted" id="safetyHint" title="When ON, dashboard will keep showing last available snapshots even on holidays/after-hours (data gap).">Show last day</span>
    </label>
    
    <div class="status-top-right"><span class="pill">
        <label style="display:flex;align-items:center;gap:4px;cursor:pointer;">
          <input type="checkbox" id="darkToggle" checked>
          <span>Dark</span>
        </label>
      </span>


    
    
<span class="pill">
        <label style="display:flex;align-items:center;gap:4px;cursor:pointer;">
          <input type="checkbox" id="compactToggle">
          <span>Compact</span>
        </label>
      </span>
      <!-- ✅ ADD THIS LINE -->
      <span id="marketBadge" class="market-badge"></span>
      <?php
        $nhDate = $nextHolidayFO['date'] ?? null;
        $nhDays = $nextHolidayFO['days'] ?? null;
        if ($nhDate):
      ?>
        <span id="nextHolidayTag" class="tag" style="margin-left:6px;opacity:.95;">Next Holiday: <?= esc($nhDate) ?> (<?= (int)$nhDays ?>d)</span>
      <?php endif; ?>

      <audio id="audioCronAlert">
        <source src="/assets/sounds/alert.mp3" type="audio/mpeg">
      </audio>

      
      <span id="clockDrift" class="muted"></span>

      <span class="pill">
        ⏱ <span id="refreshTimer">60</span>s
      </span>
      <button id="refreshNowBtn" class="pill">Refresh</button>
    </div>
  </div>
  </div>

  
</div>
<!-- ===== Intraday panels: always rendered (they used to sit inside the EOD if-blocks below,
     so an empty/failed Index EOD query hid them) ===== -->
<div class="row"> 
  <!-- Breakouts: 5m + 15m price breakouts and today's swings, in one card -->
  <div class="card" id="breakoutsCard" style="display:none; margin-bottom:16px">
    <b>Breakouts</b>
    <div id="breakout5m" style="display:none; margin-top:6px">
      <div id="bo5mText" style="font-weight:600">Loading…</div>
      <div class="muted" style="margin-top:4px">
      Rule (5m): Close > max(High, last 10) with strong body for Bullish; inverse for Bearish.
      </div>
    </div>
    <div id="signalCard" style="display:none; margin-top:10px">
      <div id="signalText" style="font-weight:600"></div>
      <div id="swingText"  class="muted" style="margin-top:6px"></div>
      <div class="muted" style="margin-top:4px">
        Rule (15m): Close &gt; max(High last 10) &amp; body &ge; 1.5× avg body.
        Swings = 5-bar fractals (HH/LL).
      </div>
    </div>
  </div>
    
    <div class="card" id="trendCard" style="margin-top:16px; display:none">
      <b>Intraday Trend (auto, last <span id="trendLookback">15</span>m)</b>
      <div id="trendSummary" style="margin-top:8px"></div>
      <div id="trendWhy" class="muted" style="margin-top:6px"></div>
    </div>
</div>




<!-- Market Meter + Heatmap + ZigZag in one row -->
  <div class="row-2col" style="margin-top:10px;align-items:flex-start;">
    <!-- LEFT COLUMN: Market Meter -->
  
<!-- =========================
  START: MARKET BIAS METER
========================= -->
<div class="card collapsible collapsed" id="marketMeter" style="margin-bottom:16px;display:none" data-panel="marketMeterCard">
    <div class="card-head" title="Click to expand/collapse">
      <div class="head-left"><b><span class="secTitle"><span class="secIcon">🎯</span>Market Bias Meter</span></b></div>
<span class="card-toggle" aria-hidden="true">▼</span>
    </div>
    <div class="card-body">

  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px">
    <b>Market Meter</b>
    <span class="muted small">Compact view</span>
  </div>
  <div class="mm-grid">
    <!-- LEFT COLUMN -->
    <div class="mm-col">
      <!-- Market State -->
      <div class="mm-sec">
        <div class="mm-sec-h">
          <div class="mm-sec-title">Market State</div>
          <div id="meterBadge"></div>
        </div>
        <div id="meterWhy" class="muted mm-why"></div>
        <div id="meterAlerts"></div>
      </div>

      <!-- Price Context -->
      <div class="mm-sec">
        <div class="mm-sec-h">
          <div class="mm-sec-title">Price Context</div>
        </div>

        <div class="levels mm-levels">
          <span><b>Exp:</b> <span id="ksExp" class="mono">-</span></span>
        </div>

        <div class="levels mm-levels">
          <span><b>Day Range:</b> <span id="ksDayRange" class="mono">-</span></span>
          <span><b>From Low / High:</b> <span id="ksFromHL" class="mono">-</span></span>
        </div>
      
      </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="mm-col">
      <!-- Derivatives -->
      <div class="mm-sec">
        <div class="mm-sec-h">
          <div class="mm-sec-title">Derivatives</div>
        </div>

        <div class="levels mm-levels">
          <span><b>PCR:</b> <span id="ksPcr" class="mono">-</span></span>
          <span><b>ATM Zone (±100):</b> <span id="ksAtmZone" class="mono">-</span></span>
        </div>

        <div class="levels mm-levels">
          <span><b>Price vs OI:</b> <span id="ksPriceOI">-</span></span>
          <span><b>OI Pressure:</b> <span id="ksOiPressure">-</span></span>
          <span><b>Trend Confidence:</b> <span id="ksTrendConf">-</span></span>
        </div>


        
      </div>

      
    </div>

    
  </div>
  <!-- OI Shift -->
      <div class="mm-sec">
        <div class="mm-sec-h">
          <div class="mm-sec-title">OI Shift</div>
        </div>
        <div class="levels mm-levels">
          <span><span id="ksOiShift" class="mono">-</span></span>
        </div>

        <div style="margin-top:8px">
          <b class="small">Time-of-day OI (top strikes)</b>
          <table id="ksOiBuckets">
            <thead>
              <tr>
                <th>Session</th>
                <th>Call ΔOI</th>
                <th>Put ΔOI</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        
      </div>


</div>
</div>
<!-- =========================
  END: MARKET BIAS METER
========================= -->


    <!-- RIGHT COLUMN: Heatmap + ZigZag stacked -->
    <div>
      <!-- Heatmap (top of right column) -->
        <div class="card collapsible collapsed" style="margin-top:16px" data-panel="heatmapCard">
          <div class="card-head" title="Click to expand/collapse">
            <div class="head-left"><b>Strike OI Heatmap (near ATM)</b></div>
            <span class="card-toggle" aria-hidden="true">▼</span>
          </div>
          <div class="card-body">
          <div id="strikeHeatmap" class="hm-container"></div>
          <small class="muted">
            Each row = Strike vs stacked Call / Put OI near ATM. Bar length ≈ total OI ·
            Green = Put-heavy support · Red = Call-heavy resistance · Blue = balanced.
          </small>
        
          </div>
        </div>
        <!-- ZigZag directly under Heatmap -->
        <div class="card collapsible collapsed" id="zzCard" style="margin-top:10px; display:none" data-panel="zzCard">
          <div class="card-head" title="Click to expand/collapse">
            <div class="head-left"><b>🔀 Price Swing Structure</b></div>
            <span class="card-toggle" aria-hidden="true">▼</span>
          </div>
          <div class="card-body">

          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap">
            
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
        </div>

        <!-- =======================
            DAY-WISE OI (Last 5 Days)
            ======================= -->

        <!-- =========================
          START: DAILY OI BIAS HISTORY
        ========================= -->
        <div class="card mb-10" id="daywiseOiCard">
          <div class="card-hd">
            <div class="card-title">🗓️ Daily OI Bias History (Last 5 Days) <span class="muted small">Bias context</span></div>
            <div class="card-actions">
              <button class="icon-btn" type="button" data-collapse-toggle="#daywiseOiBody" title="Collapse/Expand">▼</button>
            </div>
          </div>

          <div class="card-bd" id="daywiseOiBody">
            <div id="expiryRangeBox" class="expiry-box mb-6"></div>

            
            <style>
              /* ===== FIX: Daywise OI table header + sizing (scoped) ===== */
              #daywiseOiCard .daywise-legend{
                font-size:11px;
                opacity:.9;
                margin: 0 0 6px 0;
                line-height:1.25;
              }
              #daywiseOiCard .daywise-table-wrap{
                max-height: 220px;           /* increase visible area */
                overflow: auto;              /* enable internal scroll */
                border-radius: 10px;
              }
              #daywiseOiTbl{
                min-width: 760px;            /* prevent header squeeze */
                margin-top: 0 !important;
              }
              /* IMPORTANT: disable global sticky for this table (it causes header misplacement in some layouts) */
              #daywiseOiTbl thead th{
                position: static !important;
                top: auto !important;
                z-index: auto !important;
                white-space: nowrap;
              }
            
/* Info tooltip icons for Daywise OI table headers */
#daywiseOiTbl .oiHdr{display:inline-flex; align-items:center; gap:6px;}
#daywiseOiTbl .oiInfo{font-size:11px; opacity:.75; cursor:help; user-select:none;}
#daywiseOiTbl .oiInfo:hover{opacity:1;}

</style>

            <div class="daywise-legend">
              <b>How to read:</b>
              🟢 ΔPE ↑ = support building · 🔴 ΔCE ↑ = resistance building · 🟠 (−) = covering (weakening) · Δ = Today OI − Yesterday OI (D-1)
            </div>
            <div class="table-wrap daywise-table-wrap">
              <table class="tbl tbl-compact" id="daywiseOiTbl">
                <thead>
                  <tr>
                    <th style="text-align:left"><span class="oiHdr">DATE<span class="oiInfo" title="Trading date for this row." aria-label="Trading date for this row.">ⓘ</span></span></th>
                    <th style="text-align:left"><span class="oiHdr">BIAS<span class="oiInfo" title="Overall OI-based directional bias for the day (derived from PCR + CE/PE dominance + ΔOI alignment)." aria-label="Overall OI-based directional bias for the day (derived from PCR + CE/PE dominance + ΔOI alignment).">ⓘ</span></span></th>
                    <th style="text-align:right"><span class="oiHdr">PCR (ATM±5)<span class="oiInfo" title="Put–Call Ratio using strikes from ATM−5 to ATM+5. PCR = total PE OI / total CE OI in that window." aria-label="Put–Call Ratio using strikes from ATM−5 to ATM+5. PCR = total PE OI / total CE OI in that window.">ⓘ</span></span></th>
                    <th style="text-align:right"><span class="oiHdr">WIN CE OI<span class="oiInfo" title="Total Call (CE) OI in the ATM±5 strike window at the day’s last snapshot. Higher = more call writing near the money (resistance)." aria-label="Total Call (CE) OI in the ATM±5 strike window at the day’s last snapshot. Higher = more call writing near the money (resistance).">ⓘ</span></span></th>
                    <th style="text-align:right"><span class="oiHdr">WIN PE OI<span class="oiInfo" title="Total Put (PE) OI in the ATM±5 strike window at the day’s last snapshot. Higher = more put writing near the money (support)." aria-label="Total Put (PE) OI in the ATM±5 strike window at the day’s last snapshot. Higher = more put writing near the money (support).">ⓘ</span></span></th>
                    <th style="text-align:right"><span class="oiHdr">ΔCE (VS D-1)<span class="oiInfo" title="Change in Call OI vs previous day. ΔCE = Today CE OI − Yesterday CE OI (D−1). + = call writing (resistance building), − = call covering (resistance weakening)." aria-label="Change in Call OI vs previous day. ΔCE = Today CE OI − Yesterday CE OI (D−1). + = call writing (resistance building), − = call covering (resistance weakening).">ⓘ</span></span></th>
                    <th style="text-align:right"><span class="oiHdr">ΔPE (VS D-1)<span class="oiInfo" title="Change in Put OI vs previous day. ΔPE = Today PE OI − Yesterday PE OI (D−1). + = put writing (support building), − = put covering (support weakening)." aria-label="Change in Put OI vs previous day. ΔPE = Today PE OI − Yesterday PE OI (D−1). + = put writing (support building), − = put covering (support weakening).">ⓘ</span></span></th>
                    <th style="text-align:right"><span class="oiHdr">STRONG CE<span class="oiInfo" title="Top Call-wall strike(s) for the day (highest CE OI/ΔOI near price). Often acts as resistance." aria-label="Top Call-wall strike(s) for the day (highest CE OI/ΔOI near price). Often acts as resistance.">ⓘ</span></span></th>
                    <th style="text-align:right"><span class="oiHdr">STRONG PE<span class="oiInfo" title="Top Put-wall strike(s) for the day (highest PE OI/ΔOI near price). Often acts as support." aria-label="Top Put-wall strike(s) for the day (highest PE OI/ΔOI near price). Often acts as support.">ⓘ</span></span></th>
                  </tr>
                </thead>
                <tbody id="daywiseBody">
                  <tr><td colspan="9" class="muted">Waiting…</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <!-- =========================
          END: DAILY OI BIAS HISTORY
        ========================= -->

    </div>
    
    </div>


<!-- =======================
     TOP 5 OI (All Strikes) - Backend computed (stable)
     ======================= -->


  <!-- TOP ROW: PCR Trend + Classifications -->
  <div class="row">
    <!-- Collapsible: 📈 Put–Call Pressure Trend (latest 12) -->
    
<div class="card collapsible collapsed" data-panel="pcrTrendCard">
      <div class="card-head" title="Click to expand/collapse">
        <div class="head-left">
          <b>📈 Put–Call Pressure Trend (latest 12)</b>
          <svg id="pcrSpark" width="120" height="24" style="vertical-align:middle;"></svg>
        </div>
        <span class="card-toggle" aria-hidden="true">▼</span>
      </div>
      <div class="card-body">
        <table>
          <thead>
            <tr><th>Time (IST)</th><th class="mono">Price</th><th>PCR</th><th>Bias</th></tr>
          </thead>
          <tbody id="pcrTrend"></tbody>
        </table>
        <small class="muted">Tip: rising PCR → bullish tilt; falling PCR → bearish tilt.</small>
      </div>
    </div>

    <!-- Collapsible: Last 8 Classifications -->
    <div class="card collapsible collapsed" data-panel="last8ClassCard">
      <div class="card-head" title="Click to expand/collapse">
        <div class="head-left">
          <b>Last 8 Classifications</b>
        </div>
        <span class="card-toggle" aria-hidden="true">▼</span>
      </div>
      <div class="card-body">
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

    <!-- Collapsible: Next box (Bias/PCR/ATM + Top rows) -->
    <div class="card collapsible collapsed" data-panel="nextBoxCard">
      <div class="card-head" title="Click to expand/collapse">
        <div class="head-left">
          <b>Top Strikes Snapshot</b>
        </div>
        <span class="card-toggle" aria-hidden="true">▼</span>
      </div>
      <div class="card-body">
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
  </div>




<!-- SUMMARY TEXT ONLY (controls moved to top toolbar) -->
<div style="margin-top:16px;margin-bottom:8px">
  <div id="hdr" class="big muted">Loading…</div>
  <div id="meta" style="margin-top:4px;"></div>
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


<?php if (!empty($indexMoves)): ?>
  <?php
    $fmt = fn($v) => ($v === null || $v === '') ? '—' : number_format((float)$v, 2);
    $arrow = function($d){
      if ($d > 0) return '<span style="color:#22c55e;font-weight:900;">▲</span>';
      if ($d < 0) return '<span style="color:#ef4444;font-weight:900;">▼</span>';
      return '<span style="opacity:.6;">•</span>';
    };

    // intensity helper: stronger background for bigger abs %
    $intensityBg = function($pct){
      if ($pct === null) return 'rgba(255,255,255,.04)';
      $a = min(0.35, max(0.06, abs((float)$pct) / 3.0 * 0.18)); // ~0–3% mapped
      // green if +, red if -
      return ((float)$pct >= 0)
        ? "rgba(34,197,94,$a)"
        : "rgba(239,68,68,$a)";
    };

    $lu = $indexLastUpdate ?? null;
  ?>

  <div style="margin:10px 0 0 0; padding:10px 12px; border-radius:12px;
              border:1px solid rgba(255,255,255,.14); background:rgba(0,0,0,.22);">

    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
      <div style="font-weight:900;">Index Moves</div>

      <?php if (!empty($lu)): ?>
        <?php
          $st = $lu['status'] ?? 'UNKNOWN';
          $badgeColor = ($st === 'OK') ? 'rgba(34,197,94,.18)' : (($st==='STALE') ? 'rgba(239,68,68,.18)' : 'rgba(148,163,184,.18)');
          $badgeTxt   = ($st === 'OK') ? '#22c55e' : (($st==='STALE') ? '#ef4444' : '#94a3b8');
        ?>
        <div style="font-size:12px; padding:4px 8px; border-radius:999px; background:<?= $badgeColor ?>; color:<?= $badgeTxt ?>; border:1px solid rgba(255,255,255,.12);">
          Index EOD: <?= esc($st) ?>
          · <?= esc($lu['maxDate'] ?? '—') ?>
          · Updated: <?= esc($lu['maxFetched'] ?? '—') ?>
          <?php if (is_array($lu) && isset($lu['minsAgo']) && $lu['minsAgo'] !== null): ?>
            (<?= (int)$lu['minsAgo'] ?>m ago)
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

      <?php
    // ===== Tooltip date helpers for Index Moves =====
    // Uses weekday-based trading-day logic (skips Sat/Sun). (Does not know NSE holidays.)

    if (!function_exists('oi_isWeekend')) {
      function oi_isWeekend(\DateTimeInterface $dt): bool {
        $w = (int)$dt->format('N'); // 6=Sat,7=Sun
        return ($w >= 6);
      }
    }
    if (!function_exists('oi_adjustToWeekday')) {
      function oi_adjustToWeekday(?string $ymd): ?string {
        if (!$ymd) return null;
        $dt = new \DateTime($ymd);
        while (oi_isWeekend($dt)) { $dt->modify('-1 day'); } // roll back to Fri if weekend
        return $dt->format('Y-m-d');
      }
    }
    if (!function_exists('oi_backTradingDays')) {
      function oi_backTradingDays(?string $ymd, int $tradingDaysBack): ?string {
        if (!$ymd) return null;
        $dt = new \DateTime($ymd);
        $dt->setTime(0,0,0);
        while (oi_isWeekend($dt)) { $dt->modify('-1 day'); }

        $count = 0;
        while ($count < $tradingDaysBack) {
          $dt->modify('-1 day');
          if (!oi_isWeekend($dt)) $count++;
        }
        return $dt->format('Y-m-d');
      }
    }
    if (!function_exists('oi_fmtRange')) {
      function oi_fmtRange(?string $startYmd, ?string $endYmd): string {
        $s = $startYmd ? (new \DateTime($startYmd))->format('d M Y') : '—';
        $e = $endYmd   ? (new \DateTime($endYmd))->format('d M Y')   : '—';
        return $s . ' → ' . $e;
      }
    }
    if (!function_exists('oi_indexMoveTooltips')) {
      function oi_indexMoveTooltips(?string $endYmd): array {
        $end = oi_adjustToWeekday($endYmd);

        // Day: prev trading day → end
        $dayStart  = oi_backTradingDays($end, 1);

        // 30D: 30 calendar days back (then adjust to weekday)
        $d30Start = null;
        if ($end) {
          $dt = new \DateTime($end);
          $dt->modify('-30 day');
          $d30Start = oi_adjustToWeekday($dt->format('Y-m-d'));
        }

        // MTD: first of month (adjust to weekday)
        $mtdStart = null;
        if ($end) {
          $dt = new \DateTime($end);
          $dt->modify('first day of this month');
          $mtdStart = oi_adjustToWeekday($dt->format('Y-m-d'));
        }

        // YTD: Jan 1 (adjust to weekday)
        $ytdStart = null;
        if ($end) {
          $dt = new \DateTime($end);
          $dt->setDate((int)$dt->format('Y'), 1, 1);
          $ytdStart = oi_adjustToWeekday($dt->format('Y-m-d'));
        }

        return [
          'day'  => 'Day change vs previous trading day close (' . oi_fmtRange($dayStart,  $end) . ')',
          'd30'  => '30D change over last 30 calendar days (' . oi_fmtRange($d30Start,  $end) . ')',
          'mtd'  => 'MTD (Month-to-date) (' . oi_fmtRange($mtdStart,  $end) . ')',
          'year' => 'YTD (Year-to-date) (' . oi_fmtRange($ytdStart,  $end) . ')',
        ];
      }
    }
    ?>



    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:10px; margin-top:10px;">
      <?php foreach ($indexMoves as $sym => $m): ?>
        <?php if (empty($m['ok'])) continue; ?>
        <div style="padding:10px; border-radius:12px; border:1px solid rgba(255,255,255,.12); background:rgba(255,255,255,.04);">
          <div style="display:flex; justify-content:space-between; gap:10px; align-items:baseline;">
            <div style="font-weight:900;"><?= esc($sym) ?></div>
            <div class="muted" style="font-size:12px;"><?= esc($m['date'] ?? '') ?></div>
          </div>

          <div style="font-size:16px; font-weight:900; margin-top:6px;">
            Close: <?= $fmt($m['close'] ?? null) ?>
          </div>

          <div style="display:grid; grid-template-columns:70px 1fr; gap:6px 10px; margin-top:8px; font-size:13px;">
            

            <?php
              // IMPORTANT: use the correct "as-of" date key from your $m array
              // Common keys: date, trade_date, asof, fetched_date
              $asOf = $m['date'] ?? $m['trade_date'] ?? $m['asof'] ?? null;
              $tips = oi_indexMoveTooltips($asOf);
            ?>

            <?php foreach (['day'=>'Day','wtd'=>'WTD','d7'=>'7D','d30'=>'30D','mtd'=>'MTD','year'=>'YTD'] as $k=>$label): ?>
              <div class="mvLbl" title="<?= esc($m[$k]['tip'] ?? ($tips[$k] ?? '')) ?>"><?= esc($label) ?></div>

              <?php $mv = $m[$k] ?? ['pts'=>null,'pct'=>null,'dir'=>0]; ?>
              <div style="padding:2px 6px; border-radius:8px; background:<?= $intensityBg($mv['pct'] ?? null) ?>;">
                <?= $arrow($mv['dir'] ?? 0) ?>
                <?= $fmt($mv['pts'] ?? null) ?> (<?= $fmt($mv['pct'] ?? null) ?>%)
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<a href="/oi/index-eod/export/<?= date('Y') ?>" class="btn">Export <?= date('Y') ?> CSV</a>

<?php $purged = !empty($cache_purged); ?>

<?php if (!empty($indexMY_cache)): ?>
  <?php
    $hit = $indexMY_cache['hit'] ?? null;
    $age = $indexMY_cache['age'] ?? null;
    $ms  = $indexMY_cache['ms']  ?? null;

    if ($hit === true) {
        $bg = 'rgba(34,197,94,.16)'; $fg = '#22c55e'; $txt = 'CACHE HIT';
    } elseif ($hit === false) {
        $bg = 'rgba(59,130,246,.16)'; $fg = '#60a5fa'; $txt = 'CACHE MISS';
    } else {
        $bg = 'rgba(148,163,184,.16)'; $fg = '#94a3b8'; $txt = 'CACHE';
    }

    $ageTxt = ($age === null) ? '—' : round($age / 60) . 'm';
    $msTxt  = ($ms  === null) ? '—' : ((int)$ms) . 'ms';
  ?>

  <span
    title="Month/YTD cache status. Hit = served from cache. Age = time since cached."
    style="margin-left:8px;font-size:12px;padding:4px 8px;border-radius:999px;
           background:<?= $bg ?>;color:<?= $fg ?>;border:1px solid rgba(255,255,255,.15);">
    <?= $txt ?> · <?= $ageTxt ?> · <?= $msTxt ?>
  </span>
<?php endif; ?>

<a href="<?= site_url('oi/cache/purge') ?>"
   title="Clear Month/YTD cache and recompute"
   style="margin-left:8px;font-size:12px;padding:4px 8px;border-radius:10px;
          border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);text-decoration:none;">
  Purge cache
</a>

<?php if ($purged): ?>
  <span id="purgeBadge"
        style="margin-left:6px;font-size:12px;opacity:.85;transition:opacity .4s ease;">
    ✅ Purged
  </span>
<?php endif; ?>

<?php if (!empty($indexMY) && is_array($indexMY)): ?>
  <?php
    $fmt2 = fn($v) => ($v === null) ? '—' : number_format((float)$v, 2);
    $fmtPct = fn($v) => ($v === null) ? '—' : number_format((float)$v, 2) . '%';

    $arrow = function($dir){
      if ($dir > 0) return '<span style="color:#22c55e;font-weight:900;">▲</span>';
      if ($dir < 0) return '<span style="color:#ef4444;font-weight:900;">▼</span>';
      return '<span style="opacity:.65;">•</span>';
    };

    // Range position color intensity (0–100)
    $posBg = function($pos){
      if ($pos === null) return 'rgba(255,255,255,.04)';
      $p = (float)$pos;
      // near highs -> warmer; near lows -> greener; middle neutral
      if ($p >= 80) return 'rgba(239,68,68,.12)';
      if ($p <= 20) return 'rgba(34,197,94,.12)';
      return 'rgba(59,130,246,.08)';
    };

    $volChip = function($vol){
      if ($vol === 'HIGH')   return ['bg'=>'rgba(239,68,68,.16)','fg'=>'#ef4444'];
      if ($vol === 'LOW')    return ['bg'=>'rgba(148,163,184,.16)','fg'=>'#94a3b8'];
      if ($vol === 'NORMAL') return ['bg'=>'rgba(59,130,246,.16)','fg'=>'#60a5fa'];
      return ['bg'=>'rgba(148,163,184,.10)','fg'=>'#94a3b8'];
    };
  ?>




  <!-- ========================================================= -->
  <!-- INDEX MOVEMENT (EOD) : NIFTY & BANKNIFTY                  -->
  <!-- Daily / Weekly / Monthly close-to-close movement         -->
  <!-- ========================================================= -->

  <div class="card collapsible collapsed" id="monthYtdCard" data-panel="monthYtdContext" style="margin-top:10px;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(0,0,0,.22);">
    <div class="card-head" title="Click to expand/collapse">
      <div class="head-left">
        <b>🧭 <!-- =========================
  START: HIGHER TIMEFRAME CONTEXT
========================= -->
Higher-Timeframe Context
<!-- =========================
  END: HIGHER TIMEFRAME CONTEXT
========================= --></b>
        <span class="tip tip-i" title="Move = Latest Close − Period start Close (close→close).
O→C = Latest Close − Period start Open (opening bias).
If O→C diverges a lot from Move, it often signals gap acceptance/rejection (gap trap)."
      style="margin-left:8px; font-size:12px; opacity:.75; cursor:help;">
  ⓘ
</span>
      </div>
      <div class="muted" style="font-size:12px;">EOD-derived • fast cached</div>
      <span class="card-toggle" aria-hidden="true">▼</span>
    </div>
    <div class="card-body">

    <div style="display:grid;grid-template-columns:repeat(2,minmax(320px,1fr));gap:12px;margin-top:10px;">
      <?php foreach ($indexMY as $sym => $m): ?>
        <?php if (empty($m['ok'])) continue; ?>
        <div style="padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);">
          <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px;">
            <div style="font-weight:900;"><?= esc($sym) ?></div>
            <div class="muted" style="font-size:12px;"><?= esc($m['date']) ?></div>
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:10px;margin-top:10px;">
            <?php foreach (['month'=>'MTD','ytd'=>'YTD'] as $k => $label): ?>
              <?php $p = $m[$k] ?? []; $mv = $p['move'] ?? []; 
              $ocPts = (isset($p['o'], $p['c']) && $p['o'] !== null && $p['c'] !== null) ? ((float)$p['c'] - (float)$p['o']) : null;
              $ocPct = ($ocPts !== null && $p['o']) ? ($ocPts / (float)$p['o']) * 100.0 : null;

              $chip = $volChip($p['vol'] ?? '—'); ?>
              <div style="padding:8px;border-radius:12px;background:<?= $posBg($p['pos'] ?? null) ?>;border:1px solid rgba(255,255,255,.10);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                  <div style="font-weight:900;"><?= $label ?></div>

                  <span style="font-size:12px;padding:3px 8px;border-radius:999px;background:<?= $chip['bg'] ?>;color:<?= $chip['fg'] ?>;border:1px solid rgba(255,255,255,.12);">
                    Vol: <?= esc($p['vol'] ?? '—') ?>
                  </span>
                </div>

                
                <div style="display:grid;grid-template-columns:95px 1fr;gap:5px 10px;margin-top:8px;font-size:13px;">

                  <div>
                    <span class="tip tip-i"
                          title="Opening bias for the period.
Calculated as: Latest Close − First trading day Open. (Close→close moves are in Index Moves.)">
                      O→C
                    </span>
                  </div>
                  <div>
                    <?= $arrow($ocPts === null ? 0 : ($ocPts <=> 0)) ?>
                    <?= $fmt2($ocPts) ?> (<?= $fmtPct($ocPct) ?>)
                  </div>

                  <div>
                    <span class="tip tip-i"
                            title="OHLC structure for the period.
                      O = first trading day Open
                      H/L = highest & lowest price during the period
                      C = latest available Close">
                        OHLC
                      </span>

                  </div>
                  <div>
                    O <?= $fmt2($p['o'] ?? null) ?>
                    <span class="muted">|</span> H <?= $fmt2($p['h'] ?? null) ?>
                    <span class="muted">|</span> L <?= $fmt2($p['l'] ?? null) ?>
                    <span class="muted">|</span> C <?= $fmt2($p['c'] ?? null) ?>
                  </div>
                  <div>
                   <span class="tip tip-i"
                          title="Range Position shows where price is inside the period range.
                    Near 0–15% = near lows (possible bounce zone)
                    Near 85–100% = near highs (possible rejection zone)">
                      Range Pos
                    </span>

                  </div>
                  <div><?= $fmt2($p['pos'] ?? null) ?>%</div>

                  <div>From High</div>
                  <div><?= $fmtPct($p['fromHigh'] ?? null) ?> <span class="muted">|</span> From Low: <?= $fmtPct($p['fromLow'] ?? null) ?></div>

                  <div>Trend</div>
                  <div><?= esc($p['trend'] ?? '—') ?></div>

                  <div>Avg Day</div>
                  <div><?= $fmtPct($p['adm'] ?? null) ?></div>
                </div>

                <?php
                  $pos = $p['pos'] ?? null;
                  if ($pos !== null) {
                    if ($pos >= 85) { $hint = 'Near highs → rejection risk; downside probability increases.'; $cls = 'hint bad'; }
                    elseif ($pos <= 15) { $hint = 'Near lows → bounce zone; shorts risky.'; $cls = 'hint good'; }
                    else { $hint = 'Mid-range → wait for breakout/breakdown confirmation.'; $cls = 'hint warn'; }
                  } else { $hint = '—'; $cls = 'hint'; }
                ?>
                <div class="<?= $cls ?>"><?= esc($hint) ?></div>
              </div>
            <?php endforeach; ?>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  
    </div>
  </div>
<?php endif; ?>
<?php endif; ?>






<div class="card collapsible collapsed" id="daywiseCard" data-panel="daywiseCard">
      <div class="card-head" title="Click to expand/collapse">
        <div class="head-left">
          <b>Last Few Days — OI &amp; Price Behavior</b> <span class="muted" style="font-size:12px;" title="VOL is summed from NSE option-chain totalTradedVolume for CE+PE across strikes at the snapshot timestamp.">(VOL = NSE totalTradedVolume (CE+PE sum) at snapshot time)</span>
        </div>
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
          <label class="muted" style="font-size:12px;">Mode</label>
          <select id="daywiseMode" class="sel" style="padding:6px 8px;">
            <option value="front">Front expiry</option>
            <option value="all">All expiries</option>
          </select>
          <label class="muted" style="font-size:12px;">Days</label>
          <select id="daywiseDays" class="sel" style="padding:6px 8px;">
            <option value="5">5</option>
            <option value="7" selected>7</option>
            <option value="10">10</option>
            <option value="15">15</option>
        <option value="30">30</option>
          </select>
          <button id="daywiseReload" class="btn" type="button">Reload</button>
        </div>
        <span class="card-toggle" aria-hidden="true">▼</span>
      </div>
      <div class="card-body">

      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:6px;">
        <span class="pill" id="daywiseDominant">Dominant: —</span>
        <span class="pill" id="daywiseOverall">Overall: —</span>
        <span class="pill" id="daywiseLast5">Last 5: —</span>
        <span class="pill" id="daywiseModePill">Mode: —</span>
      </div>
      <div class="muted" id="daywiseSummary" style="margin-top:6px; display:none;">—</div>

      <table style="margin-top:8px;">
        <thead>
          <tr>
            <th>Date (IST)</th>
            <th class="mono">Close</th>
            <th class="mono">ΔPrice</th>
            <th class="mono">Total OI</th>
            <th class="mono">ΔOI</th>
            <th class="mono">PCR</th>
            <th class="mono">Vol</th>
            <th class="mono">ΔVol</th>
            <th class="mono">Vol% (5D)</th>
            <th class="mono">Warn</th>
            <th class="mono">Strength</th>
            <th class="mono">Confidence</th>
            <th>Behavior</th>
            <th>Signal</th>
          </tr>
        </thead>
        <tbody id="daywiseTable"></tbody>
      </table>
      <small class="muted">Logic: ΔPrice vs ΔOI → Long Build-up / Short Build-up / Short Covering / Long Unwinding. Range labels when ΔPrice is small.</small>
    
      </div>
    </div>

    

  <!-- OI TRACK CARD -->
  <div class="card card-oi-track collapsible" data-panel="oiTrackCard">
    <div class="card-head" title="Click to expand/collapse">
      <div class="head-left"><b>OI Track</b><span class="muted" style="font-size:12px;">Multi-timeframe OI & ΔOI</span></div>
      <span class="card-toggle" aria-hidden="true">▼</span>
    </div>
    <div class="card-body">

    <div class="card-oi-header">
      <div class="oi-toolbar">
        <label class="pill">Timeframes:
          <!-- OI Track TF picker (NSE-style). Selected values are stored in #tfInput for loadTrack(). -->
          <span class="nse-tf-menu oi-tf-menu" id="oiTfMenu"></span>
          <!-- default = the columns shown so far (1m/2m used to be hidden by a second, client-side TF filter) -->
          <input id="tfInput" value="3,5,10,15,30,60,120,180" class="mono" style="display:none">
          <button id="tfApply" class="btn" type="button">Apply</button>
        </label>

          <label class="pill" style="margin-left:8px">ΔOI style
            <select id="oiDeltaStyle" style="width:96px;margin-left:6px">
              <option value="arrows">Arrows</option>
              <option value="pm">+/-</option>
            </select>
          </label>

      </div>
    </div>

    <div class="muted small" style="margin-top:8px; display:flex; gap:14px; flex-wrap:wrap">
      <span>
        <b>Quick Read (Intra <span id="qrIntraWin2" class="mono">-</span>):</b>
        <span id="qrIntra2">—</span>
        <span class="tip tip-i" title="Intraday flow: Uses ΔOI since the selected window start (e.g., 3m/5m/10m). Helps identify fast support/resistance forming.">ⓘ</span>
      </span>
      <span>
        <b><span id="qrDayLbl2">Quick Read (Day)</span>:</b>
        <span id="qrDay2">—</span> <span id="qrDayMeta2" class="muted"></span>
        <span class="tip tip-i" id="qrDayTip2" title="Day Quick Read uses ΔOI since market open (09:15 IST baseline) when available. If baseline is after 09:20 (late start), it switches to NSE official CHNG IN OI for day-level support/resistance to avoid wrong bias.">ⓘ</span>
      </span>
    </div>


    <div class="hscroll oi-track-scroll" style="overflow-x:auto">
      <table class="table table-sm" id="oiTrackTable">
        <thead><tr id="trackHeadRow"></tr></thead>
        <tbody id="trackTable"></tbody>
      </table>
    </div>
  
    </div>
</div>
  




  <!-- ===== FYERS-style Insights Panel (local) ===== -->
  
<!-- Market Meter moved here (between OI Track and NSE Style table) -->


<div id="betweenOiAndNseMarketMeter"></div>

      <!-- =======================
          LIVE OPTION CHAIN
      ======================= -->
      <!-- ===== 📋 <!-- =========================
      START: LIVE OPTION CHAIN
    ========================= -->

  <div class="card collapsible" data-panel="nseOcCard" style="margin-top:16px">
    <div class="card-head" title="Click to expand/collapse">
      <div class="head-left"><b>📋 Live Option Chain (OI Synced)</b><span class="muted" style="font-size:12px;">Synced from OI Track</span></div>
      <span class="card-toggle" aria-hidden="true">▼</span>
    </div>
    <div class="card-body">
    <div class="nse-oc-wrap size-normal" id="nseOcWrap">
    <div class="nse-oc-hdr">
      <div class="nse-oc-tf" id="nseOcTf"></div>
      <div class="nse-oc-metric" id="nseOcMetric"></div>
      <div class="nse-oc-size" id="nseOcSize"></div>
      <div class="rhs">
        <span class="chip" id="nseOcStatus">Waiting…</span>
        <span class="chip">Max Pain: <b id="nseMaxPain">—</b></span>
        <label class="chip" style="display:inline-flex;align-items:center;gap:6px;cursor:pointer">
          <input type="checkbox" id="nseShowPct" style="accent-color:#3b82f6"> %
        </label>
              <span class="chip">MAX Δ CE: <b id="nseMaxDeltaCe">—</b></span>
        <span class="chip">MAX Δ PE: <b id="nseMaxDeltaPe">—</b></span>
      </div>
    </div>

    <div class="nse-oc-sum" id="nseOcSum" style="display:none">
      <div class="sum-left">
        <span class="pill" id="nseSumLabel">Change</span>
        <span class="sum-item"><span id="nseSumCallLbl">Call OI change</span> <b id="nseSumCall">—</b></span>
        <span class="sum-item"><span id="nseSumPutLbl">Put OI change</span> <b id="nseSumPut">—</b></span>
        <span class="sum-item">Imbalance <b id="nseSumImb">—</b></span>
	        <span class="sum-item">Gamma Zone <b id="nseGammaZone" class="mono tip" data-tip="">—</b></span>
	        <span class="sum-item">Gamma Hint <b id="nseGammaHint" class="mono tip" data-tip="">—</b></span>
      </div>
      <div class="sum-right">
        <span id="nseSumPrice">—</span>
      </div>
    </div>


      
    </div>
    <div class="nse-oc-scroll">
      <table class="nse-oc" aria-label="NSE option chain table">
        <thead id="nseOcHead">
          <!-- built dynamically in JS based on available lookbacks -->
        </thead>
        <tbody id="nseOcBody">
          <tr><td colspan="7" class="muted" style="text-align:center;padding:14px">Waiting…</td></tr>
        </tbody>
        <tfoot id="nseOcFoot"></tfoot>
      </table>
    </div>
  </div>


  
    </div>
  </div>

<!-- =========================
  END: LIVE OPTION CHAIN
=========================  (OI Synced) (synced from OI Track /oi/track) ===== -->
    
<!-- =========================
  START: OI CONCENTRATION ZONES
========================= -->
<div class="card collapsible collapsed" id="top5OiCard" style="margin-top:10px; display:none" data-panel="top5OiCard">
  <div class="card-head" title="Click to expand/collapse">
    <div class="head-left"><b><span class="secTitle"><span class="secIcon">🧲</span>OI Concentration Zones</span></b><span class="semLegend" title="Semantics guide"><span class="sem acc">🟢 Accum</span><span class="sem dist">🔴 Dist</span><span class="sem bal">⚖ Balance</span></span> <span class="muted small">All strikes + Near ATM</span></div><div class="head-right" style="gap:8px"><span class="badge" id="top5ExpiryBadge" title="Expiry used for these levels">Expiry: --</span><span class="badge" id="wallBadgeCE" style="display:none" title="Largest CE wall within ±2 strikes">CE Wall</span><span class="badge" id="wallBadgePE" style="display:none" title="Largest PE wall within ±2 strikes">PE Wall</span><span class="badge" id="wallBadgeTag" style="display:none" title="Magnet vs Rejection classification">⚖ Magnet</span></div>
    <span class="card-toggle" aria-hidden="true">▼</span>
  </div>
  <div class="card-body">

    <div class="oiMiniGrid">
      <div class="oiMiniCard">
        <div class="oiMiniHead">
          <div class="oiMiniTitle">Top Current OI</div>
          <div class="oiMiniMeta muted small">All strikes</div>
        </div>
        <div id="miniTopAllMix" class="oiMiniList"></div>
      </div>

      <div class="oiMiniCard">
        <div class="oiMiniHead">
          <div class="oiMiniTitle">Top Calls (CE)</div>
          <div class="oiMiniMeta muted small">All strikes</div>
        </div>
        <div id="miniTopAllCE" class="oiMiniList"></div>
      </div>

      <div class="oiMiniCard">
        <div class="oiMiniHead">
          <div class="oiMiniTitle">Top Puts (PE)</div>
          <div class="oiMiniMeta muted small">All strikes</div>
        </div>
        <div id="miniTopAllPE" class="oiMiniList"></div>
      </div>

      <div class="oiMiniCard">
        <div class="oiMiniHead">
          <div class="oiMiniTitle">Near ATM Top OI</div>
          <div class="oiMiniMeta muted small" id="miniNearMeta">±5 strikes</div>
        </div>
        <div class="oiMiniTwo">
          <div>
            <div class="oiMiniSub">CE</div>
            <div id="miniNearCE" class="oiMiniList"></div>
          </div>
          <div>
            <div class="oiMiniSub">PE</div>
            <div id="miniNearPE" class="oiMiniList"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="muted small" style="margin-top:8px">Tip: This panel is independent of the “Strikes” filter. Distances are from spot.</div>
  </div>
</div>
<!-- =========================
  END: OI CONCENTRATION ZONES
========================= -->


<style>
/* OI mini cards (ATM-summary style) */
.oiMiniGrid{display:grid; grid-template-columns:repeat(5, minmax(200px, 1fr)); gap:12px}
@media (max-width:1200px){.oiMiniGrid{grid-template-columns:repeat(2, minmax(220px, 1fr));}}
@media (max-width:700px){.oiMiniGrid{grid-template-columns:1fr;}}

.oiMiniCard{
  border:1px solid rgba(255,255,255,.08);
  border-radius:14px;
  padding:10px 10px 8px 10px;
  background:rgba(255,255,255,.02);
}

.oiMiniHead{display:flex; align-items:baseline; justify-content:space-between; gap:10px; margin-bottom:6px}
.oiMiniTitle{font-weight:700; font-size:12px; letter-spacing:.2px}
.oiMiniMeta{white-space:nowrap}

.oiMiniRow{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  padding:5px 7px;
  border-top:1px dashed rgba(255,255,255,.10);
}
.oiMiniRow:first-child{border-top:none}

.oiTag{
  display:inline-flex; align-items:center; justify-content:center;
  min-width:34px;
  padding:2px 8px;
  border-radius:999px;
  border:1px solid rgba(255,255,255,.10);
  font-weight:800;
  font-size:10px;
  letter-spacing:.4px;
}
.oiTag.ce{color:#8ef; background:rgba(80,140,255,.10)}
.oiTag.pe{color:#f8a; background:rgba(255,80,140,.10)}

.oiLeft{display:flex; align-items:center; gap:8px}
.oiStrike{font-weight:700; font-size:11px}
.oiVal{font-variant-numeric:tabular-nums; font-weight:700; font-size:11px}
.oiDist{opacity:.75; font-size:10px; font-variant-numeric:tabular-nums}

.oiMiniTwo{display:grid; grid-template-columns:1fr 1fr; gap:10px}
.oiMiniSub{font-size:10px; opacity:.75; margin:2px 0 4px 0; font-weight:700; letter-spacing:.4px}
</style>


<!-- Advanced (strike deltas) -->
  <div class="card collapsible collapsed" id="advancedStrikeDeltasCard" data-panel="advancedStrikeDeltasCard" style="margin-top:16px">
    <div class="card-head" title="Click to expand/collapse">
      <div class="head-left">
        <b>Advanced: Strike Deltas (Delta by Strike + Net ΔOI)</b>
        <span class="muted" style="font-size:12px;margin-left:8px;">Use this for breakout confirmation &amp; trap detection.</span>
      </div>
      <span class="card-toggle" aria-hidden="true">▼</span>
    </div>
    <div class="card-body">
<div class="small muted" style="margin:6px 0 10px">
      Use this for breakout confirmation & trap detection. Optional if you trade only daywise.
    </div>
<!-- Delta by strike -->
  <div class="card" style="margin-top:16px">
    <b>Delta by Strike (last window)</b> <span id="atmFreshFlowBadge" class="pill neu" style="margin-left:8px;display:none"></span> <label class="pill gray" style="margin-left:8px;gap:6px;cursor:pointer"><input type="checkbox" id="deltaAtmOnly" style="accent-color:#3b82f6"> ATM±2 only</label>
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
    </div>
  </div>
<div class="card collapsible collapsed" id="holidaysCard" data-panel="holidaysCard" style="margin-top:10px;">
  <div class="card-head" title="Click to expand/collapse">
    <div class="head-left">
      <b>NSE Holidays (F&amp;O)</b>
      <span class="muted" style="font-size:12px;margin-left:8px;">— used to ignore holidays in “Last Few Days — OI &amp; Price Behavior”</span>
    </div>
    <span class="card-toggle" aria-hidden="true">▼</span>
  </div>
  <div class="card-body">
<div style="padding:10px 12px;">
    <div class="muted" style="margin-bottom:8px;">
      Segment: <span class="tag">FO</span> &nbsp;|&nbsp; Total: <?= isset($holidayRowsFO) ? count($holidayRowsFO) : 0 ?>
    </div>

    <div style="overflow:auto;max-height:260px;border:1px solid rgba(255,255,255,.10);border-radius:10px;">
      <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead>
          <tr style="background:rgba(0,0,0,.35);backdrop-filter:blur(6px);">
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(255,255,255,.10);position:sticky;top:0;z-index:2;">Date</th>
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(255,255,255,.10);position:sticky;top:0;z-index:2;">Weekday</th>
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(255,255,255,.10);position:sticky;top:0;z-index:2;">Description</th>
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(255,255,255,.10);position:sticky;top:0;z-index:2;">Morning</th>
            <th style="text-align:left;padding:8px 10px;border-bottom:1px solid rgba(255,255,255,.10);position:sticky;top:0;z-index:2;">Evening</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($holidayRowsFO)): ?>
            <?php foreach ($holidayRowsFO as $r): ?>
              <?php
                $dRaw = $r['holiday_date'] ?? ($r['trading_date_str'] ?? ($r['trading_date'] ?? ''));
                $wk   = $r['weekday_name'] ?? '';
                if (empty($wk) && !empty($dRaw)) { $wk = date('l', strtotime($dRaw)); }

                $d = $dRaw;
                if (!empty($dRaw) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dRaw)) {
                  $d = date('d-M-Y', strtotime($dRaw));
                }

                $desc = $r['description'] ?? '';
                $ms = $r['morning_session'] ?? '';
                $es = $r['evening_session'] ?? '';
              ?>
              <tr>
                <td style="padding:7px 10px;border-bottom:1px solid rgba(255,255,255,.06);white-space:nowrap;"><?= esc($d) ?></td>
                <td style="padding:7px 10px;border-bottom:1px solid rgba(255,255,255,.06);white-space:nowrap;"><?= esc($wk) ?></td>
                <td style="padding:7px 10px;border-bottom:1px solid rgba(255,255,255,.06);"><?= esc($desc) ?></td>
                <td style="padding:7px 10px;border-bottom:1px solid rgba(255,255,255,.06);white-space:nowrap;"><?= $ms!=='' ? esc($ms) : '-' ?></td>
                <td style="padding:7px 10px;border-bottom:1px solid rgba(255,255,255,.06);white-space:nowrap;"><?= $es!=='' ? esc($es) : '-' ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5" style="padding:10px;" class="muted">No holidays found in DB for segment FO.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  </div>
</div>


<script>
// ====== Config ======
const AUTO_REFRESH_SEC = 60;
const timerEl = document.getElementById('refreshTimer');

// Market calendar (IST): FO holidays + special sessions, from OiController::marketCalendarForUi()
window.OI_CAL = <?= json_encode($marketCalendar ?? ['holidays' => new \stdClass(), 'special' => new \stdClass()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

// ====== Theme / Compact toggles ======
function initModeToggles(){
  const body = document.body;
  const darkInput = document.getElementById('darkToggle');
  const compactInput = document.getElementById('compactToggle');

  let darkPref = localStorage.getItem('oiDarkMode');
  let compactPref = localStorage.getItem('oiCompactMode');

  let isDark = darkPref === null ? true : darkPref === '1';
  let isCompact = compactPref === '1';

  function apply(){
    // dark = default (no light-mode class)
    body.classList.toggle('light-mode', !isDark);
    body.classList.toggle('compact', isCompact);
    if (darkInput) darkInput.checked = isDark;
    if (compactInput) compactInput.checked = isCompact;
  }

  apply();

  if (darkInput){
    darkInput.addEventListener('change', ()=>{
      isDark = !!darkInput.checked;
      localStorage.setItem('oiDarkMode', isDark ? '1':'0');
      apply();
    });
  }
  if (compactInput){
    compactInput.addEventListener('change', ()=>{
      isCompact = !!compactInput.checked;
      localStorage.setItem('oiCompactMode', isCompact ? '1':'0');
      apply();
    });
  }
}

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

function confidenceMeter(score){
  const s = Number(score || 0);
  if (s >= 75) return "✅✅✅";
  if (s >= 50) return "✅✅";
  if (s >= 25) return "✅";
  return "—";
}


function fmtArrow(val, fmtFn){
  if (val === null || val === undefined || val === "") return "—";
  const n = Number(val);
  if (!Number.isFinite(n)) return "—";
  const txt = fmtFn ? fmtFn(n) : (n > 0 ? `+${n}` : `${n}`);
  if (n > 0)  return `<span class="dw-arrow dw-up"><span class="tri">▲</span>${txt}</span>`;
  if (n < 0)  return `<span class="dw-arrow dw-down"><span class="tri">▼</span>${txt}</span>`;
  return `<span class="dw-arrow dw-flat"><span class="tri">•</span>${txt}</span>`;
}

function confidenceClass(score){
  const s = Number(score || 0);
  if (s >= 75) return "conf-3";
  if (s >= 50) return "conf-2";
  if (s >= 25) return "conf-1";
  return "conf-0";
}

// Re-run the OI Track enhancer (ATM highlights, ATM card) once after tables are re-rendered;
// it is no longer polled. oi_track_enhancer.js defines window.__oiScheduleEnhance.
function requestEnhance(){
  if (window.__oiScheduleEnhance) window.__oiScheduleEnhance();
}

// Strike interval: from the payload (/oi/json and /oi/track send `step`), else by symbol
function strikeStepOf(j){
  const s = Number(j && j.step);
  if (Number.isFinite(s) && s > 0) return s;
  const sym = String((j && j.symbol) || document.getElementById('symbol')?.value || '').toUpperCase();
  return sym === 'BANKNIFTY' ? 100 : 50;
}
// "All" in the Strikes ± selector (UI.strikeWin = 'all') turns the ±window filter off
function isAllStrikes(v){ return v === 'all'; }

// ====== UI state ======
let UI = {
  optFilter: 'both',
  strikeWin: 10,     // ± strikes around ATM (× the symbol's strike step)
  atmBand: 3,        // highlight ± band
  expiry: '',
  lastServerTsUTC: null,
  metricsRows: [],
  expiriesList: []
};

// ====== Market status (IST): the one source for the market badge, stale-data alarms and auto-refresh ======
// Same rules as scripts/oi_cron_guard.sh: Mon–Fri 09:15–15:30 IST except NSE F&O holidays; special
// sessions (window.OI_CAL.special) open the market on their dates, full-day ones 09:15–15:30.
const MKT_OPEN_MIN  = 9*60 + 15;
const MKT_CLOSE_MIN = 15*60 + 30;
const IST_PARTS_FMT = new Intl.DateTimeFormat('en-US', {
  timeZone:'Asia/Kolkata', year:'numeric', month:'2-digit', day:'2-digit',
  hour:'2-digit', minute:'2-digit', hourCycle:'h23', weekday:'short'
});

// IST calendar date, minutes since midnight and weekday ('Mon'..'Sun') of a Date (default: now)
function istNowParts(d){
  const p = {};
  IST_PARTS_FMT.formatToParts(d || new Date()).forEach(x => { p[x.type] = x.value; });
  return { ymd: `${p.year}-${p.month}-${p.day}`, mins: (parseInt(p.hour,10) % 24)*60 + parseInt(p.minute,10), weekday: p.weekday };
}

function hmToMin(hm){
  const m = /^(\d{1,2}):?(\d{2})$/.exec(String(hm || ''));
  return m ? (+m[1])*60 + (+m[2]) : null;
}

// Trading sessions of an IST date in minutes since midnight, sorted: [{from, to, endInclusive, label}]
function marketSessionsOn(ymd, weekday){
  const cal = window.OI_CAL || {};
  const specials = (cal.special && cal.special[ymd]) || [];
  const isHoliday = !!cal.holidays && Object.prototype.hasOwnProperty.call(cal.holidays, ymd);
  const isWeekend = (weekday === 'Sat' || weekday === 'Sun');
  const fullDay = specials.some(s => s.full);
  const out = [];

  if (fullDay || (!isWeekend && !isHoliday)){
    out.push({ from: MKT_OPEN_MIN, to: MKT_CLOSE_MIN, endInclusive: true, label: fullDay ? 'Special session 09:15–15:30' : 'Market Hours' });
  }
  specials.forEach(s => {
    const from = hmToMin(s.start), to = hmToMin(s.end);
    if (!s.full && from !== null && to !== null && to > from){
      out.push({ from, to, endInclusive: false, label: `Special session ${s.start}–${s.end}` });
    }
  });
  return out.sort((a, b) => a.from - b.from);
}

// { open, state: 'OPEN'|'PREOPEN'|'POST'|'CLOSED', reason }
function getMarketStatusIST(now){
  const t = istNowParts(now);
  const sessions = marketSessionsOn(t.ymd, t.weekday);
  const cur = sessions.find(s => t.mins >= s.from && (s.endInclusive ? t.mins <= s.to : t.mins < s.to));
  if (cur) return { open:true, state:'OPEN', reason: cur.label };

  if (!sessions.length){
    if (t.weekday === 'Sat') return { open:false, state:'CLOSED', reason:'Saturday' };
    if (t.weekday === 'Sun') return { open:false, state:'CLOSED', reason:'Sunday' };
    const desc = ((window.OI_CAL || {}).holidays || {})[t.ymd];
    return { open:false, state:'CLOSED', reason: 'NSE holiday' + (desc ? ': ' + desc : '') };
  }
  if (sessions.some(s => t.mins < s.from)) return { open:false, state:'PREOPEN', reason:'Pre-open' };

  const lastEnd = Math.max(...sessions.map(s => s.to));
  return { open:false, state: (t.mins <= lastEnd + 60 ? 'POST' : 'CLOSED'), reason:'After-hours' };
}

// Single writer of #marketBadge (called every second from tick() and after each refresh)
function updateMarketBadge(){
  const el = document.getElementById('marketBadge');
  if (!el) return;

  const st = getMarketStatusIST();
  const [cls, text] = st.open ? ['market-open', 'Market OPEN']
    : (st.state === 'PREOPEN' ? ['market-preopen', 'Pre-open'] : ['market-closed', 'Market CLOSED']);

  if (el.textContent === text && el.dataset.tip === st.reason && el.classList.contains(cls)) return;
  el.classList.remove('market-open','market-closed','market-preopen','market-post');
  el.classList.add(cls);
  el.textContent = text;
  el.dataset.tip = st.reason;   // the page's tooltip (titles are converted to data-tip)
  el.removeAttribute('title');
}

function updateSessionAndDrift(){
  updateMarketBadge();

  // Age of the snapshot on screen (was labelled "Clock drift")
  if (UI.lastServerTsUTC){
    const d = new Date(String(UI.lastServerTsUTC).replace(' ','T')+'Z');
    const ageMin = Math.round((Date.now() - d.getTime())/60000);
    const el = document.getElementById('clockDrift');
    el.textContent = ageMin>2 ? `Data age ~${ageMin}m` : '';
  }
}
// Safety mode ("Show last day"): ON keeps loading everything on stale/gapped data (holidays,
// after hours); OFF pauses the signal loaders and badges (refreshOnce, updateFreshness)
function initSafetyToggle(){
  const t = document.getElementById('safetyToggle');
  if (!t) return;

  const apply = (on)=>{
    window.__oiSafetyMode = on;
    const hint = document.getElementById('safetyHint');
    if (hint) hint.textContent = on ? 'Show last day' : 'Auto-pause on gap';
  };

  let saved = null;
  try{ saved = localStorage.getItem('oiSafetyMode'); }catch(e){}
  t.checked = (saved === '1');
  apply(t.checked);

  t.addEventListener('change', ()=>{
    try{ localStorage.setItem('oiSafetyMode', t.checked ? '1' : '0'); }catch(e){}
    apply(t.checked);

    // refresh immediately so you feel the effect
    countdown = AUTO_REFRESH_SEC;
    doRefresh();
  });
}


// ====== Badges ======
function badgeBias(b){ if(b==='Bullish') return `<span class="badge b-green">${b}</span>`;
  if(b==='Bearish') return `<span class="badge b-red">${b}</span>`; return `<span class="badge b-blue">${b??'-'}</span>`; }
function badgeClass(c){ if(c==='Long Build-up'||c==='Short Covering') return `<span class="badge b-green">${c}</span>`;
  if(c==='Short Build-up'||c==='Long Unwinding') return `<span class="badge b-red">${c}</span>`; return `<span class="badge b-blue">${c??'-'}</span>`; }

// ====== Data loaders ======
var lastJson = null;



// ---- Symbol from URL (?symbol=...) ----
(function initSymbolFromUrl(){
  try{
    const u = new URL(location.href);
    const sym = (u.searchParams.get('symbol')||'').toUpperCase();
    if(sym){
      const sel = document.getElementById('symbol');
      if(sel && [...sel.options].some(o=>o.value===sym)) sel.value = sym;
    }
  }catch(e){}
})();

document.getElementById('symbol')?.addEventListener('change', async ()=>{
  // Keep symbol in URL so refresh/share keeps selection
  const sym = document.getElementById('symbol').value;
  const u = new URL(location.href);
  u.searchParams.set('symbol', sym);
  // expiry becomes symbol-specific; drop it and reload clean
  u.searchParams.delete('expiry');
  location.href = u.toString();
});

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

// Status bar (DB last insert / fetch cron / enrich cron): refreshed every cycle from /oi/healthz.
// Thresholds match the server-rendered classes (<= 7m ok, <= 12m warn, else bad).
function healthClass(mins){
  if (mins === null || mins === undefined) return 'bad';
  if (mins <= 7) return 'ok';
  if (mins <= 12) return 'warn';
  return 'bad';
}

async function loadHealth(){
  const res = await fetch('/oi/healthz', { cache:'no-store' });
  if (!res.ok) throw new Error('healthz HTTP ' + res.status);
  const h = (await res.json()).health;
  if (!h) return;

  [['db', 'tsDb', 'agoDb', '—', 'no data'], ['fetch', 'tsFetch', 'agoFetch', 'No log', '—'], ['enrich', 'tsEnrich', 'agoEnrich', 'No log', '—']]
    .forEach(([key, tsId, agoId, noTs, noAgo]) => {
      const x = h[key] || {};
      const hasAge = (x.minsAgo !== null && x.minsAgo !== undefined);
      const tsEl = document.getElementById(tsId), agoEl = document.getElementById(agoId);
      if (tsEl){ tsEl.textContent = x.ist || noTs; tsEl.className = healthClass(x.minsAgo); }
      if (agoEl) agoEl.textContent = `(${hasAge ? x.minsAgo + ' min ago' : noAgo})`;
    });

  // Re-evaluate dots, sound, data gap and the stale-data pause with the new timestamps
  if (typeof window.__oiUpdateFreshness === 'function') window.__oiUpdateFreshness();
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

  //document.getElementById('hdr').textContent = `${j.symbol} @ ${(+j.price).toFixed(2)} | Exp ${j.expiry} | `;
  document.getElementById('hdr').innerHTML =
  `${j.symbol} @ ${(+j.price).toFixed(2)} | Exp ${j.expiry} |
    <span class="muted">TS:</span> ${fmtISTsec(j.ts)}
    <span class="tag">Window ${j.window}m</span>`;
  document.getElementById('meta').textContent = '';
  //document.getElementById('meta').innerHTML = `<span class="muted">TS:</span> ${fmtISTsec(j.ts)}  <span class="tag">Window ${j.window}m</span>`;
  // --- Fix stuck ATM: derive from underlying if needed ---
  let atmVal = Number(j.atm);

  // ✅ use underlying if present, else fallback to price
  const spot = Number.isFinite(Number(j.underlying))
    ? Number(j.underlying)
    : Number(j.price);

  // NIFTY 50, BANKNIFTY 100
  const step = strikeStepOf(j);

  if (Number.isFinite(spot)) {
    const derived = Math.round(spot / step) * step;

    // override if missing OR clearly wrong
    if (!Number.isFinite(atmVal) || Math.abs(atmVal - derived) >= step) {
      atmVal = derived;
    }
  }

  j.atm = atmVal;   // the enhancer reads window.lastJson.atm


  document.getElementById('lastRef').textContent =
    'Updated: ' + new Date().toLocaleTimeString('en-IN',{hour12:false,timeZone:'Asia/Kolkata'}) + ' IST';

  UI.lastServerTsUTC = j.ts; updateSessionAndDrift();

  renderTopOI(j);
  renderDeltaTable(j);
  renderNetBars(j);
  renderOiChangeChart(j);
  renderStrikeHeatmap(j);
  computeQuickReads(j);
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
  requestEnhance();
}
function renderDeltaTable(j){
  if(!j) return;
  window.__lastDeltaJson = j;

  const atm    = j.atm;
  const band   = UI.atmBand;
  const around = UI.strikeWin;
  const step   = strikeStepOf(j);

  const showCalls = UI.optFilter !== 'puts';
  const showPuts  = UI.optFilter !== 'calls';
  // ATM±2 only toggle (persist)
  const cb = document.getElementById('deltaAtmOnly');
  if (cb && cb.dataset.bound !== '1') {
    cb.dataset.bound = '1';
    const saved = localStorage.getItem('deltaAtmOnly');
    if (saved !== null) cb.checked = (saved === '1');
    cb.addEventListener('change', () => {
      try { localStorage.setItem('deltaAtmOnly', cb.checked ? '1' : '0'); } catch(e) {}
      if (window.__lastDeltaJson) renderDeltaTable(window.__lastDeltaJson);
    });
  }
  const atmOnly = cb ? cb.checked : false;


  const strikes = new Set([
    ...Object.keys(j.callDelta||{}),
    ...Object.keys(j.putDelta||{}),
    ...Object.keys(j.callDayDelta||{}),
    ...Object.keys(j.putDayDelta||{})
  ]);

  const rowsAll = [...strikes].map(s=>{
    const st = parseInt(s,10);
    return {
      st,
      cdIntra: Number(j.callDelta?.[s] ?? 0),
      pdIntra: Number(j.putDelta?.[s] ?? 0),
      cdDay:   Number(j.callDayDelta?.[s] ?? 0),
      pdDay:   Number(j.putDayDelta?.[s] ?? 0),
      withinATM: Math.abs(st - atm) <= band*step,
      withinWin: isAllStrikes(around) ? true : Math.abs(st - atm) <= around*step
    };
  }).filter(r=>r.withinWin)
    .sort((a,b)=>a.st-b.st);
  const rows = atmOnly ? rowsAll.filter(r=>r.withinATM) : rowsAll;


  const fmt = n=>Number(n).toLocaleString('en-IN');

// max abs per column for heat bars
const maxCEi = Math.max(1, ...rows.map(r=>Math.abs(r.cdIntra)));
const maxPEi = Math.max(1, ...rows.map(r=>Math.abs(r.pdIntra)));
const maxCEd = Math.max(1, ...rows.map(r=>Math.abs(r.cdDay)));
const maxPEd = Math.max(1, ...rows.map(r=>Math.abs(r.pdDay)));

const cell = (v, maxAbs)=>{
  const n = Number(v||0);
  const cls = n>0 ? 'pos' : (n<0 ? 'neg' : 'zero');
  const arrow = n>0 ? '▲' : (n<0 ? '▼' : '•');
  const w = Math.max(0, Math.min(100, Math.round(Math.abs(n)/maxAbs*100)));
  return `<td class="delta-cell ${cls}"><div class="bar"><div class="fill" style="width:${w}%"></div></div><span class="val">${fmt(n)} ${arrow}</span></td>`;
};
const blank = `<td class="muted">—</td>`;

// Fresh flow badge at ATM±2
(function(){
  const badge = document.getElementById('atmFreshFlowBadge');
  if(!badge) return;
  const atmRows = rows.filter(r=>r.withinATM);
  const totAtm = atmRows.reduce((a,r)=>a+Math.abs(r.cdIntra)+Math.abs(r.pdIntra),0);
  const totAll = rows.reduce((a,r)=>a+Math.abs(r.cdIntra)+Math.abs(r.pdIntra),0);
  const share = totAll>0 ? (totAtm/totAll) : 0;
  const netAtm = atmRows.reduce((a,r)=>a + (r.pdIntra - r.cdIntra), 0);

  // trigger when ATM flow dominates and has meaningful size
  const trigger = (totAtm >= 200000 && share >= 0.35);
  if(!trigger){
    badge.style.display='none';
    return;
  }
  badge.style.display='';
  badge.classList.remove('good','warn','bad','neu');
  badge.classList.add('good');
  badge.textContent = 'Fresh flow at ATM → Breakout likely';
  badge.title = `ATM±2 flow share: ${(share*100).toFixed(0)}% | ATM net (PE−CE): ${fmt(netAtm)} | ATM flow: ${fmt(totAtm)}`;
})();

document.getElementById("deltaTable").innerHTML = rows.map(r=>{
  const hl = r.withinATM ? ' class="atm-row"' : '';
  return `<tr ${hl}>
    <td>${r.st}</td>
    ${showCalls ? cell(r.cdIntra, maxCEi) : blank}
    ${showPuts  ? cell(r.pdIntra, maxPEi) : blank}
    ${showCalls ? cell(r.cdDay,   maxCEd) : blank}
    ${showPuts  ? cell(r.pdDay,   maxPEd) : blank}
  </tr>`;
}).join('');
  requestEnhance();
}

// ===== Net Δ bar mini-bars (table + SVG) + numeric table =====
// Net ΔOI card "View" (#netView): Intra = window change, Day = change since the day's first snapshot
function netDeltaMaps(j){
  const day = (document.getElementById('netView')?.value === 'day');
  return day ? { calls: j.callDayDelta || {}, puts: j.putDayDelta || {} }
             : { calls: j.callDelta    || {}, puts: j.putDelta    || {} };
}

function renderNetBars(j){
  if(!j) return;

  const atm    = j.atm;
  const around = UI.strikeWin;
  const step   = strikeStepOf(j);
  const { calls: netCalls, puts: netPuts } = netDeltaMaps(j);

  const showCalls = UI.optFilter !== 'puts';
  const showPuts  = UI.optFilter !== 'calls';

  const strikes = new Set([
    ...Object.keys(netCalls),
    ...Object.keys(netPuts)
  ]);

  let rows = [...strikes].map(s=>{
    const st = parseInt(s,10);
    const rawCE = Number(netCalls[s] ?? 0);
    const rawPE = Number(netPuts[s] ?? 0);

    return {
      st,
      cd: showCalls ? rawCE : 0,
      pd: showPuts  ? rawPE : 0,
      cdRaw: rawCE,
      pdRaw: rawPE,
      net: (showPuts ? rawPE : 0) - (showCalls ? rawCE : 0),
      withinWin: isAllStrikes(around) ? true : Math.abs(st - atm) <= around*step
    };
  }).filter(r => r.withinWin)
    .sort((a,b)=>a.st-b.st);

  const fmt = n => Number(n).toLocaleString('en-IN');

  // Table (the chart above draws the same rows; the separate mini-bar list was a third copy)
  const tbody = document.getElementById("netTable");
  tbody.innerHTML = rows.map(r=>{
    const cdCell = showCalls ? fmt(r.cdRaw) : "—";
    const pdCell = showPuts  ? fmt(r.pdRaw) : "—";
    const ntCls  = r.net>0?'up':(r.net<0?'down':'');
    return `<tr>
      <td>${r.st}</td>
      <td>${cdCell}</td>
      <td>${pdCell}</td>
      <td class="${ntCls}">${fmt(r.net)}</td>
    </tr>`;
  }).join('');
  requestEnhance();

  // The meter's "OI Pressure" / "Why: NetΔOI" always use the intraday window net,
  // whichever View (Intra/Day) this card shows
  const intraCalls = j.callDelta || {}, intraPuts = j.putDelta || {};
  UI.netDeltaSum = [...new Set([...Object.keys(intraCalls), ...Object.keys(intraPuts)])].reduce((sum, k)=>{
    const st = parseInt(k,10);
    if (!(isAllStrikes(around) || Math.abs(st - atm) <= around*step)) return sum;
    return sum + (showPuts ? Number(intraPuts[k] ?? 0) : 0) - (showCalls ? Number(intraCalls[k] ?? 0) : 0);
  }, 0);
}

// ===== Big OI Δ vs Strike chart (Sensibull-style) =====
function renderOiChangeChart(j){
  if (!j) return;
  const svg = document.getElementById('oiChangeChart');
  if (!svg) return;

  const atm = j.atm;
  const around = UI.strikeWin;
  const step = strikeStepOf(j);

  const showCalls = UI.optFilter !== 'puts';
  const showPuts  = UI.optFilter !== 'calls';

  const { calls, puts } = netDeltaMaps(j);   // Intra or Day, per the card's View selector

  const strikes = new Set([...Object.keys(calls), ...Object.keys(puts)]);

  const rows = [...strikes].map(st=>{
    st = Number(st);
    const cd = showCalls ? Number(calls[st]||0) : 0;
    const pd = showPuts  ? Number(puts[st] ||0) : 0;
    return {
      st,
      cd,
      pd,
      within: isAllStrikes(around) ? true : Math.abs(st-atm)<=around*step
    };
  }).filter(r=>r.within)
    .sort((a,b)=>a.st-b.st);

  // KEEP YOUR DRAWING LOGIC SAME

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
// ===== Strike OI Heatmap (near ATM) =====
// Always show BOTH CE + PE values (ignore UI.optFilter for heatmap)
function renderStrikeHeatmap(j){
  const el = document.getElementById("strikeHeatmap");
  if (!el) return;

  try{
    if (!j){
      el.innerHTML = '<span class="muted">No snapshot.</span>';
      return;
    }

    const atm = Number(j.atm || 0) || null;

    // ---- 1) Detect data sources (supports multiple payload styles) ----
    // Priority: full OI maps (if present) -> top maps fallback
    const callsFull =
      j.callOi || j.call_oi || j.callsOi || j.calls_oi || null;
    const putsFull  =
      j.putOi  || j.put_oi  || j.putsOi  || j.puts_oi  || null;

    const callsTop = j.topCalls || {};
    const putsTop  = j.topPuts  || {};

    // helper to normalize an object map with strike keys
    function normalizeMap(obj){
      if (!obj || typeof obj !== 'object') return {};
      const out = {};
      for (const [k,v] of Object.entries(obj)){
        const st = Number(k);
        if (!st || !isFinite(st)) continue;
        out[st] = Number(v) || 0;
      }
      return out;
    }

    const callsFullMap = normalizeMap(callsFull);
    const putsFullMap  = normalizeMap(putsFull);
    const callsTopMap  = normalizeMap(callsTop);
    const putsTopMap   = normalizeMap(putsTop);

    // choose “best available” maps
    const useFull = Object.keys(callsFullMap).length || Object.keys(putsFullMap).length;
    const calls = useFull ? callsFullMap : callsTopMap;
    const puts  = useFull ? putsFullMap  : putsTopMap;

    // ---- 2) Build strike list ----
    const strikesSet = new Set([...Object.keys(calls), ...Object.keys(puts)]);
    let strikes = [...strikesSet].map(s=>Number(s)).filter(Boolean).sort((a,b)=>a-b);

    if (!strikes.length){
      el.innerHTML = `<span class="muted">No OI data for heatmap (missing maps).</span>`;
      return;
    }

    // ---- 3) Prefer near-ATM strikes if possible ----
    // UI.strikeWin is “± strikes”, so range = strikeWin × the strike step (NIFTY 50, BANKNIFTY 100)
    const strikeWinRaw = (window.UI && UI.strikeWin!=null) ? UI.strikeWin : 5;
    const strikeWin = (strikeWinRaw === 'all') ? 'all' : (Number(strikeWinRaw) || 5);
    const step = strikeStepOf(j);

    if (atm && strikeWin !== 'all' && strikeWin !== 999){
      const maxDiff = strikeWin * step;
      const near = strikes.filter(st => Math.abs(st - atm) <= maxDiff);
      if (near.length) strikes = near;
    }

    // limit rows to keep card tight
    if (strikes.length > 24){
      // take closest 24 to ATM if ATM exists, else take middle slice
      if (atm){
        strikes.sort((a,b)=>Math.abs(a-atm)-Math.abs(b-atm));
        strikes = strikes.slice(0,24).sort((a,b)=>a-b);
      }else{
        strikes = strikes.slice(0,24);
      }
    }

    // ---- 4) Compute totals and max for scaling ----
    const rows = strikes.map(st=>{
      const ce = Number(calls[st] || 0);
      const pe = Number(puts[st]  || 0);
      return { strike: st, ce, pe, tot: ce+pe };
    }).filter(r => r.tot > 0);

    if (!rows.length){
      el.innerHTML = `<span class="muted">Heatmap has no non-zero strikes.</span>`;
      return;
    }

    const maxTot = Math.max(1, ...rows.map(r=>r.tot));

    // ---- 5) Render ----
    el.innerHTML = '';

    rows.forEach(r=>{
      const scale = r.tot / maxTot;

      // bar uses scaled width for "total OI" AND split for CE/PE share
      const cePct = (r.ce / r.tot) * 100 * scale;
      const pePct = (r.pe / r.tot) * 100 * scale;

      let domClass = 'hm-row-balanced';
      let note = 'Balanced';
      if (r.pe > r.ce * 1.1){ domClass = 'hm-row-dominant-put'; note='Put-heavy'; }
      else if (r.ce > r.pe * 1.1){ domClass = 'hm-row-dominant-call'; note='Call-heavy'; }

      const isAtm = atm && r.strike === atm;

      const rowDiv = document.createElement('div');
      rowDiv.className = 'hm-row ' + domClass;

      const strikeDiv = document.createElement('div');
      strikeDiv.className = 'hm-strike mono';
      strikeDiv.textContent = r.strike;
      if (isAtm){
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
      callSeg.style.width = cePct.toFixed(2) + '%';

      const putSeg = document.createElement('div');
      putSeg.className = 'hm-put';
      putSeg.style.width = pePct.toFixed(2) + '%';

      bar.appendChild(callSeg);
      bar.appendChild(putSeg);
      barWrap.appendChild(bar);

      const label = document.createElement('div');
      label.className = 'hm-label mono';
      label.textContent =
        `CE ${r.ce.toLocaleString('en-IN')} · PE ${r.pe.toLocaleString('en-IN')} · ${note}`;

      rowDiv.appendChild(strikeDiv);
      rowDiv.appendChild(barWrap);
      rowDiv.appendChild(label);

      el.appendChild(rowDiv);
    });

    // small footer debug (optional but helpful)
    // remove next 2 lines if you don't want it
    // el.insertAdjacentHTML('beforeend', `<div class="muted small">Source: ${useFull?'full OI maps':'top OI maps'} · Rows: ${rows.length}</div>`);

  }catch(err){
    console.error('Heatmap error:', err);
    el.innerHTML = `<span class="bad">Heatmap error:</span> <span class="mono">${String(err.message || err)}</span>`;
  }
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

  // 👉 ask backend for more rows for the whole day
  const res = await fetch(`/oi/metrics?symbol=${symbol}&limit=80`);
  const j   = await res.json();

  const rowsRaw = (j && j.ok && Array.isArray(j.rows)) ? j.rows : [];
  const rows = rowsRaw
    .filter(r => r && r.ts)
    .sort((a,b)=> Date.parse(b.ts+'Z') - Date.parse(a.ts+'Z')); // newest first

  // keep ALL rows for Trend / ZigZag / Market Meter / Time-of-day OI
  UI.metricsRows = rows;

  // latest 12 only for table + sparkline
  const latest12 = rows.slice(0, 12);

  // ---- PCR Trend table (12 rows) ----
  document.getElementById('pcrTrend').innerHTML = latest12.map(r=>{
    const price = Number(r.underlying ?? 0).toFixed(2);
    const pcr   = (r.pcr ?? '-');
    const bias  = r.bias ?? '-';
    return `<tr>
      <td>${fmtIST(r.ts)}</td>
      <td class="mono">${price}</td>
      <td>${pcr}</td>
      <td>${badgeBias(bias)}</td>
    </tr>`;
  }).join('');

  // ---- Last 8 Classifications (you asked for 8) ----
  const last8 = rows.slice(0, 8);
  document.getElementById('classTable').innerHTML = last8.map(r=>{
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

  // ---- sparkline only uses those 12 latest points ----
  drawPcrSparkline(latest12);

  // ---- rest of dashboard still uses ALL metrics (UI.metricsRows) ----
  renderTrendCard();
  renderZigZagCard();
  updateMarketMeter();
}

async function loadDaywise(){
  const symbol = document.getElementById('symbol').value;
  const days = (document.getElementById('daywiseDays')?.value) || '7';
  const mode = (document.getElementById('daywiseMode')?.value) || 'front';

  const res = await fetch(`/oi/daywise?symbol=${encodeURIComponent(symbol)}&days=${encodeURIComponent(days)}&segment=FO&mode=${encodeURIComponent(mode)}`);
  const j = await res.json();

  window.__daywiseDebug = { url: res.url, resStatus: res.status, json: j };

  const tbody = document.getElementById('daywiseTable');
  const sumEl = document.getElementById('daywiseSummary');
  if(!tbody || !sumEl) return;

  if(!j || !j.ok || !Array.isArray(j.rows)){
    tbody.innerHTML = `<tr><td colspan="14" class="muted">No data</td></tr>`;
    sumEl.textContent = '—';
    return;
  }

  const rows = j.rows;

  // Vol% vs 5-day avg (older days): compares total_vol to avg(total_vol) of next 5 older rows
  function avgOlder(i, field, need=5){
    let sum=0, cnt=0;
    for(let k=i+1; k<rows.length && cnt<need; k++){
      const v = Number(rows[k]?.[field]);
      if(Number.isFinite(v) && v>0){ sum += v; cnt++; }
    }
    return cnt ? (sum/cnt) : null;
  }

  function fmtPctArrow(pct){
    if(pct === null || pct === undefined) return '—';
    const n = Number(pct);
    if(!Number.isFinite(n)) return '—';
    const txt = (n>0?`+${n.toFixed(0)}%`:`${n.toFixed(0)}%`);
    if(n>0) return `<span class="dw-arrow dw-up"><span class="tri">▲</span>${txt}</span>`;
    if(n<0) return `<span class="dw-arrow dw-down"><span class="tri">▼</span>${txt}</span>`;
    return `<span class="dw-arrow dw-flat"><span class="tri">•</span>${txt}</span>`;
  }

  function fmtVol5d(r, allRows){
    // Find index of r in allRows by day match (safe)
    const i = allRows.indexOf(r);
    const base = avgOlder(i, 'total_vol', 5);
    if(!base) return '—';
    const cur = Number(r.total_vol);
    if(!Number.isFinite(cur) || cur<=0) return '—';
    const pct = ((cur/base)-1)*100;
    return `<span title="Vol vs avg(older 5 days): ${cur.toLocaleString()} vs ${Math.round(base).toLocaleString()}">${fmtPctArrow(pct)}</span>`;
  }

  function fmtDivergence(r){
    const oiR = Number(r.oi_ratio ?? r.oiRatio ?? NaN);
    const vR  = Number(r.vol_ratio ?? r.volRatio ?? NaN);
    const dOi = Number(r.d_oi ?? 0);
    const dV  = Number(r.d_vol ?? 0);
    // Divergence: OI move strong but volume weak, or OI build while volume falls.
    const div = (Number.isFinite(oiR) && Number.isFinite(vR) && oiR>=1.1 && vR<0.7) || (dOi>0 && dV<0);
    if(!div) return '';
    const msg = [];
    if(Number.isFinite(oiR) && Number.isFinite(vR)) msg.push(`ΔOI ratio ${oiR.toFixed(2)}x vs ΔVol ratio ${vR.toFixed(2)}x`);
    if(dOi>0 && dV<0) msg.push('ΔOI↑ while ΔVol↓');
    msg.push('Possible trap / low participation — be cautious');
    return `<span class="pill warn" style="background:rgba(239,68,68,.12);color:#ef4444;font-weight:700" title="${msg.join(' · ')}">⚠️</span>`;
  }

  // Summary: counts + dominant behavior
  const counts = {};
  let bull=0, bear=0, side=0;
  rows.forEach(r=>{
    const b = (r.behavior || '—');
    counts[b] = (counts[b]||0) + 1;
    const sig = (r.signal||'Neutral').toLowerCase();
    if(sig.includes('bull')) bull++;
    else if(sig.includes('bear')) bear++;
    else side++;
  });
    const dom = Object.entries(counts).sort((a,b)=>b[1]-a[1])[0];
  const domTxt = dom ? `${dom[0]} (${dom[1]}/${rows.length})` : '—';

  // Pills
  const domEl  = document.getElementById('daywiseDominant');
  const ovEl   = document.getElementById('daywiseOverall');
  const l5El   = document.getElementById('daywiseLast5');
  const modeEl = document.getElementById('daywiseModePill');

  if(domEl)  domEl.textContent  = `Dominant: ${domTxt}`;
  if(ovEl)   ovEl.textContent   = `Overall: ${bull} Bull · ${bear} Bear · ${side} Side`;

  // Last 5 summary (skip newest row if it has no previous day comparison)
  const rowsForL5 = rows.filter(r => r && r.d_price !== null && r.d_price !== undefined && r.d_oi !== null && r.d_oi !== undefined).slice(0,5);
  let b5=0, be5=0, s5=0;
  rowsForL5.forEach(r=>{
    const sig = (r.signal||'Neutral').toLowerCase();
    if(sig.includes('bull')) b5++;
    else if(sig.includes('bear')) be5++;
    else s5++;
  });
  if(l5El) l5El.textContent = `Last 5: ${b5} / ${be5} / ${s5}`;
  if(modeEl) modeEl.textContent = `Mode: ${mode.toUpperCase()}`;
  // Daywise Bias Now badge (last 3 comparable days)
  const biasBadge = document.getElementById('daywiseBiasNowBadge');
  const last3 = rows.filter(r => r && r.d_oi !== null && r.d_oi !== undefined && r.d_price !== null && r.d_price !== undefined).slice(0,3);
  let b3=0, be3=0, s3=0;
  last3.forEach(r=>{
    const sig = (r.signal||'Neutral').toLowerCase();
    if(sig.includes('bull')) b3++;
    else if(sig.includes('bear')) be3++;
    else s3++;
  });
  let biasNow = 'Sideways';
  let biasCls = 'muted';
  if (b3 >= 2) { biasNow = 'Bullish'; biasCls = 'data-live'; }
  else if (be3 >= 2) { biasNow = 'Bearish'; biasCls = 'data-frozen'; }
  else biasNow = 'Sideways';
  // Confidence meter for badge (avg of last 3 comparable days)
  const avgScore = last3.length ? (last3.reduce((a,r)=>a + Number(r.strength_score||0),0) / last3.length) : 0;
  const meter = confidenceMeter(avgScore);

  if (biasBadge) {
    biasBadge.textContent = `Daywise Bias: ${biasNow} ${meter} (${b3}/${be3}/${s3})`;
    biasBadge.className = 'pill ' + biasCls + ' conf ' + confidenceClass(avgScore);
  }

  // expose for addons widgets
  window.__daywiseState = {
    biasNow,
    avgScore,
    b3, be3, s3,
    last3,
    mode,
    rowsCount: rows.length
  };


  // Keep old summary (hidden) for debugging
  sumEl.textContent = `Mode: ${mode} · Days: ${rows.length} · Dominant: ${domTxt} · Bull/Bear/Side: ${bull}/${bear}/${side}`;

  const deltaWithArrow = (v, fmtFn) => fmtArrow(v, fmtFn);

  function fmtDeltaWithPct(delta, base, decimals=2){
    const d = Number(delta);
    const b = Number(base);
    if (!Number.isFinite(d)) return '—';

    const dTxt = (d > 0 ? '+' : '') + d.toFixed(decimals);

    if (!Number.isFinite(b) || b === 0) return dTxt; // no base => only delta

    const pct = (d / b) * 100;
    const pTxt = (pct > 0 ? '+' : '') + pct.toFixed(2) + '%';
    return `${dTxt} <span class="muted">(${pTxt})</span>`;
  }

  tbody.innerHTML = rows.map(r=>{
    //const dP = deltaWithArrow(r.d_price, n => (n>0?`+${n.toFixed(2)}`:`${n.toFixed(2)}`));
    const prevClose = Number(r.prev_close ?? r.prevClose ?? (Number(r.close) - Number(r.d_price)));
    const dP = fmtArrow(r.d_price, n => fmtDeltaWithPct(n, prevClose, 2));
    const dO = deltaWithArrow(r.d_oi, n => (n>0?`+${Math.round(n)}`:`${Math.round(n)}`));
    const dV = deltaWithArrow(r.d_vol, n => (n>0?`+${Math.round(n)}`:`${Math.round(n)}`));
    const sig = (r.signal || '—');
    const sigCls = sig.toLowerCase().includes('bull') ? 'data-live' :
                   sig.toLowerCase().includes('bear') ? 'data-frozen' : 'muted';
    return `<tr>
      <td class="mono">${r.day || '-'}</td>
      <td class="mono">${r.close ?? '-'}</td>
      <td class="mono">${dP}</td>
      <td class="mono">${r.total_oi ?? '-'}</td>
      <td class="mono">${dO}</td>
      <td class="mono">${r.pcr ?? '-'}</td>
      <td class="mono">${r.total_vol ?? '-'}</td>
      <td class="mono">${dV}</td>
      <td class="mono">${fmtVol5d(r, rows)}</td>
      <td>${fmtDivergence(r)}</td>
      <td class="mono" title="${(() => {
        const s = Number(r.strength_score ?? 0);
        const oiR = (r.oi_ratio ?? r.oiRatio ?? null);
        const vR  = (r.vol_ratio ?? r.volRatio ?? null);
        const parts = [];
        if (oiR !== null && oiR !== undefined && oiR !== '') parts.push(`ΔOI ratio: ${Number(oiR).toFixed(2)}x`);
        if (vR  !== null && vR  !== undefined && vR  !== '') parts.push(`ΔVol ratio: ${Number(vR).toFixed(2)}x`);
        parts.push(`Score: ${Math.round(s)}`);
        return parts.join(' · ');
      })()}">${r.strength ?? '—'}</td>
      <td class="mono">
        <span class="conf ${confidenceClass(r.strength_score)}"
              title="${(() => {
                const s = Number(r.strength_score ?? 0);
                const oiR = (r.oi_ratio ?? r.oiRatio ?? null);
                const vR  = (r.vol_ratio ?? r.volRatio ?? null);
                const parts = [];
                if (oiR !== null && oiR !== undefined && oiR !== '') parts.push(`ΔOI ratio: ${Number(oiR).toFixed(2)}x`);
                if (vR  !== null && vR  !== undefined && vR  !== '') parts.push(`ΔVol ratio: ${Number(vR).toFixed(2)}x`);
                parts.push(`Score: ${Math.round(s)}`);
                return parts.join(' · ');
              })()}">${confidenceMeter(r.strength_score)}</span>
      </td>
      <td>${r.behavior || '-'}</td>
      <td class="${sigCls}">${sig}</td>
    </tr>`;
  }).join('');
}

// ====== Addons for trend & trade decisions (Alignment / Strike Shift / False Breakout / Readiness) ======
function _bias3(b){
  const s = String(b||'').toLowerCase();
  if (s.includes('bull')) return 'Bullish';
  if (s.includes('bear')) return 'Bearish';
  if (s.includes('range') || s.includes('side') || s.includes('neutral')) return 'Sideways';
  return s ? (s.charAt(0).toUpperCase()+s.slice(1)) : 'Sideways';
}
function _pill(el, text, cls, title){
  if (!el) return;
  el.textContent = text;
  el.classList.remove('good','warn','bad','neu');
  el.classList.add(cls || 'neu');
  if (title) el.title = title; else el.removeAttribute('title');
}
function _clamp(n,a,b){ return Math.max(a, Math.min(b, n)); }

async function loadAddons(){
  const symbol = document.getElementById('symbol')?.value || 'NIFTY';
  const j = await fetch(`/oi/intraday?symbol=${encodeURIComponent(symbol)}&mode=all`).then(r=>r.json());
  window.__intradayDebug = j;

  const day = window.__daywiseState || { biasNow:'Sideways', avgScore:0 };
  const alignEl = document.getElementById('trendAlignBadge');
  const shiftEl = document.getElementById('strikeShiftBadge');
  const brkEl   = document.getElementById('falseBreakBadge');
  const readyEl = document.getElementById('readinessBadge');

  if (!j || !j.ok || !j.current) {
    _pill(alignEl, 'Align: —', 'neu');
    _pill(shiftEl, 'Strike Shift: —', 'neu');
    _pill(brkEl,   'Big move: —', 'neu');
    _pill(readyEl, 'Ready: —', 'neu');
    return;
  }

  // --- Intraday bias (prefer oi_metrics.bias; fallback to PCR)
  const intraBias = _bias3(j?.strike_shift?.bias || '');

  // --- Alignment
  const dayBias = _bias3(day.biasNow);
  let align = 'Divergence';
  let alignCls = 'warn';
  if (dayBias === 'Sideways' || intraBias === 'Sideways') {
    align = 'Mixed';
    alignCls = 'neu';
  } else if (dayBias === intraBias) {
    align = 'Aligned';
    alignCls = 'good';
  }
  _pill(alignEl, `Align: ${align} (${dayBias} ↔ ${intraBias})`, alignCls);

  // --- Strike shift (latest vs ~60m ago)
  const sh = j.strike_shift || {};
  const cNow = sh.call_now, cPrev = sh.call_prev;
  const pNow = sh.put_now,  pPrev = sh.put_prev;
  const arrow = (a,b)=> (a==null||b==null) ? '·' : (a>b?'↑':(a<b?'↓':'→'));
  const fmtS = (n)=> (n==null?'—':String(n));
  const ceTxt = `CE ${fmtS(cPrev)}→${fmtS(cNow)} ${arrow(cNow,cPrev)}`;
  const peTxt = `PE ${fmtS(pPrev)}→${fmtS(pNow)} ${arrow(pNow,pPrev)}`;
  // classify shift
  let shiftCls = 'neu';
  if (cNow!=null && cPrev!=null && pNow!=null && pPrev!=null) {
    const ceUp = cNow>cPrev, ceDn=cNow<cPrev;
    const peUp = pNow>pPrev, peDn=pNow<pPrev;
    if (ceUp && peUp) shiftCls='good';
    else if (ceDn && peDn) shiftCls='bad';
    else shiftCls='warn';
  }
  _pill(shiftEl, `Strike Shift: ${ceTxt} | ${peTxt}`, shiftCls, `from ${sh.ts_prev||'—'} to ${sh.ts_now||'—'}`);

  // --- Big move (today's move vs a points threshold) and whether OI follows through.
  //     Labelled "Big move": the price breakout signals are in the Breakouts card.
  const dP = Number(j.current.d_price || 0);
  const dOi = Number(j.current.d_oi || 0);
  const baseOi = Number(j?.baseline?.avg_abs_doi || 0);
  const baseVol = Number(j?.baseline?.avg_abs_dvol || 0);
  const pxThresh = (symbol==='BANKNIFTY') ? 220 : (symbol==='FINNIFTY'?120:80);
  const breakout = Math.abs(dP) >= pxThresh;
  const oiWeak = baseOi>0 ? (Math.abs(dOi) < 0.45*baseOi) : (Math.abs(dOi) < 250000);
  const brkWarn = breakout && oiWeak;
  if (!breakout) {
    _pill(brkEl, `Big move: No (${dP>=0?'+':''}${dP.toFixed(0)} pts)`, 'neu', `Day move ${dP.toFixed(2)} pts; threshold ±${pxThresh} pts`);
  } else if (brkWarn) {
    _pill(brkEl, `Big move: ⚠︎ Not confirmed`, 'warn', `ΔP=${dP.toFixed(2)} pts, ΔOI=${dOi.toLocaleString('en-IN')} vs baseline≈${Math.round(baseOi).toLocaleString('en-IN')}`);
  } else {
    _pill(brkEl, `Big move: Confirmed`, (dP>0?'good':'bad'), `ΔP=${dP.toFixed(2)} pts, ΔOI=${dOi.toLocaleString('en-IN')}`);
  }

  // --- Readiness score (0..100)
  const daywisePart = _clamp((Number(day.avgScore||0) / 100) * 40, 0, 40);
  const alignPart = (align==='Aligned') ? 30 : (align==='Mixed' ? 15 : 10);

  const oiRatio = baseOi>0 ? (Math.abs(dOi) / baseOi) : 0;
  const dVol = Number(j.current.d_vol || 0);
  const volRatio = baseVol>0 ? (Math.abs(dVol) / baseVol) : 0;
  let pressurePart = 0;
  const meanR = (oiRatio + volRatio) / 2;
  if (meanR >= 1.4) pressurePart = 20;
  else if (meanR >= 1.1) pressurePart = 14;
  else if (meanR >= 0.8) pressurePart = 8;
  else pressurePart = 3;

  // penalties
  let penalty = 0;
  if (brkWarn) penalty += 10;
  // If DB looks stale on UI, penalize
  const dbEl = document.getElementById('tsDb');
  if (dbEl && dbEl.classList.contains('bad')) penalty += 10;
  if (dbEl && dbEl.classList.contains('warn')) penalty += 5;

  const score = Math.round(_clamp(daywisePart + alignPart + pressurePart - penalty, 0, 100));
  const meter = confidenceMeter(score);
  const cls = score>=70 ? 'good' : (score>=45 ? 'warn' : 'bad');
  if (readyEl) {
    readyEl.classList.remove('good','warn','bad','neu');
    readyEl.classList.add(cls);
    readyEl.innerHTML = `Ready: ${score}/100 ${meter}<span class="readybar"><i style="width:${score}%"></i></span>`;
    readyEl.title = `Daywise=${dayBias} (avg ${Math.round(day.avgScore||0)}) · Intraday=${intraBias} · ΔOI ratio=${oiRatio.toFixed(2)}x · ΔVol ratio=${volRatio.toFixed(2)}x`;
  }
}

(function(){
  const btn = document.getElementById('daywiseReload');
  if(btn){

    btn.addEventListener('click', ()=>{ loadDaywise().catch(()=>{}); });
  }
  const md = document.getElementById('daywiseMode');
  const ds = document.getElementById('daywiseDays');
  if(md) md.addEventListener('change', ()=>{ loadDaywise().catch(()=>{}); });
  if(ds) ds.addEventListener('change', ()=>{ loadDaywise().catch(()=>{}); });
})();



// Breakout + Swing signals (always show card)
// The 5m and 15m breakout sections share one card: show it while either section is shown
function syncBreakoutsCard(){
  const card = document.getElementById('breakoutsCard');
  if (!card) return;
  const any = ['breakout5m','signalCard'].some(id => {
    const el = document.getElementById(id);
    return el && el.style.display !== 'none';
  });
  card.style.display = any ? 'block' : 'none';
}

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
  syncBreakoutsCard();
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
  requestEnhance();
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
document.getElementById('refreshNowBtn').addEventListener('click', () => { countdown = AUTO_REFRESH_SEC; doRefresh(); });

// (#symbol change reloads the page with ?symbol=..., see initSymbolFromUrl)
// Global CE/PE filter – drives all sections
Array.from(document.querySelectorAll('input[name="optFilter"]')).forEach(r=>{
  r.addEventListener('change', e=>{
    UI.optFilter = e.target.value || 'both';

    // Re-render everything that depends on CE/PE side
    renderTopOI(lastJson);
    renderDeltaTable(lastJson);
    renderNetBars(lastJson);
    renderOiChangeChart(lastJson);
    renderStrikeHeatmap(lastJson);
    loadTrack();
    updateMarketMeter();
  });
});

// Initial state on load
UI.optFilter = 'both';


// Strikes ± (status bar): the one strike-window control, for OI Track / option chain (server-side)
// and the Delta / Net ΔOI tables
(function(){
  const sw1 = document.getElementById('strikeWin');
  function applyVal(v){
    // keep UI as number or 'all'
    UI.strikeWin = (v==='all') ? 'all' : (parseInt(v,10) || 5);
    if(sw1 && sw1.value !== v) sw1.value = v;
  }
  if(sw1){
    sw1.addEventListener('change', e=>{
      const v = e.target.value;
      applyVal(v);
      try{ renderDeltaTable(lastJson); renderNetBars(lastJson); }catch(_){}
      loadTrack();
    });
  }
})();
document.getElementById('atmBand').addEventListener('change', e=>{ UI.atmBand = parseInt(e.target.value,10); renderDeltaTable(lastJson); });
document.getElementById('netView')?.addEventListener('change', ()=>{ renderNetBars(lastJson); renderOiChangeChart(lastJson); });
document.getElementById('expirySel').addEventListener('change', e=>{ UI.expiry = e.target.value; countdown = AUTO_REFRESH_SEC; doRefresh(); });
document.getElementById('exportDelta').addEventListener('click', exportDeltaCSV);
document.getElementById('exportPCR').addEventListener('click', exportPCRCSV);
document.getElementById('showNextExp').addEventListener('change', ()=>{ loadNextExpiryCard().catch(()=>{}); });

window.addEventListener('keydown', (e)=>{
  if (e.ctrlKey || e.metaKey || e.altKey) return;
  const tgt = e.target;
  if (tgt && (tgt.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(tgt.tagName))) return;
  if (e.key==='r' || e.key==='R'){ countdown = AUTO_REFRESH_SEC; doRefresh(); }
  if (e.key==='1'){ document.getElementById('strikeWin').value='3';  UI.strikeWin=3;  renderDeltaTable(lastJson); renderNetBars(lastJson); }
  if (e.key==='2'){ document.getElementById('strikeWin').value='5'; UI.strikeWin=5; renderDeltaTable(lastJson); renderNetBars(lastJson); }
  if (e.key==='3'){ document.getElementById('strikeWin').value='10'; UI.strikeWin=10; renderDeltaTable(lastJson); renderNetBars(lastJson); }
  if (e.key==='e' || e.key==='E'){ const sel=document.getElementById('expirySel'); sel.selectedIndex = (sel.selectedIndex+1) % sel.options.length; sel.dispatchEvent(new Event('change')); }
});

// ====== Countdown loop ======
let countdown = AUTO_REFRESH_SEC;

// Auto refresh from 15 min before the first session to 30 min after the last one
// (09:00–16:00 IST on a normal trading day); paused on weekends and NSE holidays.
// Manual Refresh always works.
function inAutoRefreshWindow(){
  const t = istNowParts();
  return marketSessionsOn(t.ymd, t.weekday).some(s => t.mins >= s.from - 15 && t.mins < s.to + 30);
}

// One loader failing must not skip the others (e.g. /oi/track after a /oi/signals error)
async function runLoader(name, fn){
  try{ await fn(); }
  catch(e){ console.warn(`[OI] ${name} failed:`, (e && e.message) ? e.message : e); }
}

async function refreshOnce(){
  await runLoader('health', loadHealth);   // status bar + stale/gap pause state first
  await runLoader('snapshot', loadSnapshot);

  // If signals are paused due to stale/gap:
  // ✅ Safety mode ON -> still load everything (view last day data)
  // ✅ Safety mode OFF -> stop addons/signals decisions
  const safetyOn = !!window.__oiSafetyMode;

  if (window.__signalsPaused && !safetyOn){
    // keep UI visible but avoid acting on stale/gapped data
    return;
  }

  await runLoader('metrics', loadMetrics);
  await runLoader('daywise', loadDaywise);
  await runLoader('addons', loadAddons);
  await runLoader('signals', loadSignals);
  await runLoader('next expiry', loadNextExpiryCard);
  await runLoader('breakout 5m', loadBreakout5m);
  await runLoader('track', loadTrack);
}

// Timer, buttons and filter changes can overlap: run one cycle at a time and
// coalesce requests made meanwhile into a single follow-up cycle.
let refreshRunning = false, refreshQueued = false;
async function doRefresh(){
  if (refreshRunning){ refreshQueued = true; return; }
  refreshRunning = true;
  try{
    do {
      refreshQueued = false;
      await refreshOnce();
      try{ updateSessionAndDrift(); }catch(e){}   // also updates the market badge
      try{ window.__oiBeautifyNow && window.__oiBeautifyNow(); }catch(e){}
      requestEnhance();   // also when this cycle stopped early (signals paused) or /oi/track failed
    } while (refreshQueued);
  } finally {
    refreshRunning = false;
  }
}

function tick(){
  updateMarketBadge();

  // Pause auto refresh outside the trading-day window (manual Refresh still works)
  if(!inAutoRefreshWindow()){
    countdown = AUTO_REFRESH_SEC;
    timerEl.textContent = 'PAUSED';
    setTimeout(tick, 1000);
    return;
  }

  countdown--;
  if (countdown <= 0){
    countdown = AUTO_REFRESH_SEC;
    doRefresh();
  }
  timerEl.textContent = countdown;
  setTimeout(tick, 1000);
}


// ====== Init ======
(async function init(){
  initModeToggles();
  initSafetyToggle();
  await loadExpiries();
  updateSessionAndDrift();
  await doRefresh();
  timerEl.textContent = countdown;

  updateMarketBadge();

  setTimeout(tick, 1000);   // tick() also keeps the market badge current
})();

</script>

<script>
async function loadBreakout5m(){
  const symbol = document.getElementById('symbol').value;
  const res = await fetch(`/price/breakouts?symbol=${symbol}&tf=5m&lookback=10`);
  const j   = await res.json();
  const card= document.getElementById('breakout5m');
  const txt = document.getElementById('bo5mText');

  if (!j.ok){ card.style.display='none'; syncBreakoutsCard(); return; }

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
  syncBreakoutsCard();
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
UI.maxPainData  = null;
UI.sessionStats = null;

// ---------- Helpers for value-added info ----------

// Compute Quick Read (Intra window vs Day) + Support/Resistance (by OI)
function computeQuickReads(j){
  if (!j) return;

  // ----- Support/Resistance by CURRENT OI (for Market Meter chips) -----
  const topCalls = j.topCalls || {};
  const topPuts  = j.topPuts  || {};

  function maxKV(obj){
    let bestK = null, bestV = -Infinity;
    for (const k in obj){
      const v = Number(obj[k]);
      if (!Number.isFinite(v)) continue;
      if (v > bestV){ bestV = v; bestK = k; }
    }
    return bestK==null ? null : { strike: Number(bestK), val: bestV };
  }

  const maxCallOI = maxKV(topCalls);
  const maxPutOI  = maxKV(topPuts);

  UI.supportList = maxPutOI  ? [{ strike: maxPutOI.strike,  oi: maxPutOI.val }] : [];
  UI.resistList  = maxCallOI ? [{ strike: maxCallOI.strike, oi: maxCallOI.val }] : [];

  // ----- Quick Read by ΔOI -----
  function maxDeltaKV(obj){
    let bestK=null, bestV=-Infinity;
    for (const k in (obj||{})){
      const v = Number(obj[k]);
      if (!Number.isFinite(v)) continue;
      if (v > bestV){ bestV = v; bestK = k; }
    }
    return bestK==null ? null : { strike: Number(bestK), val: bestV };
  }
  function sum(obj){
    let s=0;
    for (const k in (obj||{})){
      const v = Number(obj[k]);
      if (Number.isFinite(v)) s += v;
    }
    return s;
  }
  function fmt(n){
    if (!Number.isFinite(n)) return '—';
    return Number(n).toLocaleString('en-IN');
  }
  function biasFrom(net){
    // Simple, transparent bias based on net PutΔ - CallΔ
    if (!Number.isFinite(net) || net === 0) return 'Neutral';
    return net > 0 ? 'Bullish' : 'Bearish';
  }

  const callDeltaIntra = j.callDelta || {};
  const putDeltaIntra  = j.putDelta  || {};

  // Day quick read source:
  // - Prefer true market-open delta (09:15 IST baseline) when available
  // - If baseline is missing or late (>09:20 IST), fall back to NSE official CHNG IN OI to avoid wrong day bias
  const baselineIst = (j.dayBaselineIst || '').toString();
  let useNseDay = false;
  if (!baselineIst) {
    useNseDay = true;
  } else {
    const m = /\b(\d{2}):(\d{2}):(\d{2})\b/.exec(baselineIst);
    if (m) {
      const hh = parseInt(m[1], 10), mm = parseInt(m[2], 10);
      const mins = hh*60 + mm;
      if (Number.isFinite(mins) && mins > (9*60 + 20)) useNseDay = true; // after 09:20 IST
    }
  }

  const callDeltaDay   = useNseDay ? (j.callNseDelta || {}) : (j.callDayDelta || {});
  const putDeltaDay    = useNseDay ? (j.putNseDelta  || {}) : (j.putDayDelta  || {});

  const intraRes = maxDeltaKV(callDeltaIntra);
  const intraSup = maxDeltaKV(putDeltaIntra);
  const dayRes   = maxDeltaKV(callDeltaDay);
  const daySup   = maxDeltaKV(putDeltaDay);

  const netIntra = sum(putDeltaIntra) - sum(callDeltaIntra);
  const netDay   = sum(putDeltaDay)  - sum(callDeltaDay);

  const intraTxt = (intraSup || intraRes)
    ? `Sup <span class="badge b-green">${intraSup?intraSup.strike:'—'}</span> (PutΔ ${intraSup?fmt(intraSup.val):'—'}) · ` +
      `Res <span class="badge b-red">${intraRes?intraRes.strike:'—'}</span> (CallΔ ${intraRes?fmt(intraRes.val):'—'}) · ` +
      `Bias <b>${biasFrom(netIntra)}</b>`
    : '—';

  const dayTxt = (daySup || dayRes)
    ? `Sup <span class="badge b-green">${daySup?daySup.strike:'—'}</span> (PutΔ ${daySup?fmt(daySup.val):'—'}) · ` +
      `Res <span class="badge b-red">${dayRes?dayRes.strike:'—'}</span> (CallΔ ${dayRes?fmt(dayRes.val):'—'}) · ` +
      `Bias <b>${biasFrom(netDay)}</b>`
    : '—';


  // Day label + meta (baseline visibility)
  const dayLblText = useNseDay ? 'Quick Read (Day • NSE)' : 'Quick Read (Day • from open)';
  const dayMetaText = useNseDay
    ? (baselineIst ? `(using NSE CHNG IN OI; baseline ${baselineIst})` : `(using NSE CHNG IN OI; baseline missing)`)
    : (baselineIst ? `(baseline ${baselineIst})` : '');
  const lbl2 = document.getElementById('qrDayLbl2');
  if (lbl2) lbl2.textContent = dayLblText;
  const meta2 = document.getElementById('qrDayMeta2');
  if (meta2) meta2.textContent = dayMetaText;
  // Rendered once, in the OI Track header
  const wEl2 = document.getElementById('qrIntraWin2');
  if (wEl2) wEl2.textContent = (j.window ? `${j.window}m` : '-');
  const iEl2 = document.getElementById('qrIntra2');
  if (iEl2) iEl2.innerHTML = intraTxt;
  const dEl2 = document.getElementById('qrDay2');
  if (dEl2) dEl2.innerHTML = dayTxt;
}


// Approximate Max Pain & Gamma zone using visible strikes
function computeMaxPainAndGamma(j){
  // Prefer precomputed strike->OI maps if present, else derive from /oi/track rows
  let calls = (j && j.topCalls) ? (j.topCalls || {}) : {};
  let puts  = (j && j.topPuts)  ? (j.topPuts  || {}) : {};

  // Fallback: build maps from rows (CE.cur_oi / PE.cur_oi)
  if ((Object.keys(calls).length === 0 && Object.keys(puts).length === 0) && j && Array.isArray(j.rows)){
    const ceMap = {};
    const peMap = {};
    j.rows.forEach(r=>{
      const st = parseInt(r?.strike, 10);
      if(!Number.isFinite(st)) return;
      const ce = Number(r?.CE?.cur_oi ?? 0);
      const pe = Number(r?.PE?.cur_oi ?? 0);
      if(Number.isFinite(ce) && ce) ceMap[String(st)] = ce;
      if(Number.isFinite(pe) && pe) peMap[String(st)] = pe;
    });
    calls = ceMap;
    puts  = peMap;
  }
  const strikesSet = new Set([
    ...Object.keys(calls || {}),
    ...Object.keys(puts  || {})
  ]);
  const strikes = [...strikesSet]
    .map(s => parseInt(s,10))
    .filter(s => !isNaN(s))
    .sort((a,b) => a-b);

  if (!strikes.length) return null;

  function oiAt(K){
    const ce = Number(calls[K] || calls[String(K)] || 0);
    const pe = Number(puts[K]  || puts[String(K)]  || 0);
    return {ce, pe};
  }

  function painAt(settle){
    let pain = 0;
    strikes.forEach(K => {
      const {ce, pe} = oiAt(K);
      const callPayoff = Math.max(0, settle - K);
      const putPayoff  = Math.max(0, K - settle);
      pain += ce*callPayoff + pe*putPayoff;
    });
    return pain;
  }

  let bestStrike = strikes[0];
  let bestPain   = painAt(bestStrike);
  strikes.forEach(S => {
    const p = painAt(S);
    if (p < bestPain){
      bestPain   = p;
      bestStrike = S;
    }
  });

  const combined = strikes.map(K => {
    const {ce, pe} = oiAt(K);
    return {strike:K, tot:ce+pe};
  }).sort((a,b) => b.tot - a.tot);

  let zone = null;
  if (combined.length){
    const primary   = combined[0];
    const secondary = combined[1] || combined[0];
    const lo = Math.min(primary.strike, secondary.strike);
    const hi = Math.max(primary.strike, secondary.strike);
    zone = {lo, hi};
  }

  return {maxPain: bestStrike, gammaZone: zone};
}

// Session stats (day high/low and distance from them)
function computeSessionStats(){
  const rows = UI.metricsRows || [];
  if (!rows.length || !lastJson) return null;

  const todayIST = new Date(new Date().toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
  function toIST(ts){
    return new Date(new Date(String(ts).replace(' ','T')+'Z')
      .toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
  }
  function sameDay(a,b){
    return a.getFullYear()===b.getFullYear() &&
           a.getMonth()===b.getMonth() &&
           a.getDate()===b.getDate();
  }

  const todays = rows.map(r => {
    const d = toIST(r.ts);
    return {d, price:Number(r.underlying||0)};
  }).filter(r => sameDay(r.d, todayIST));

  if (!todays.length) return null;

  let high = todays[0], low = todays[0];
  todays.forEach(r => {
    if (r.price > high.price) high = r;
    if (r.price < low.price)  low  = r;
  });

  const lastPrice   = Number(lastJson.price || 0);
  const fromLowPts  = lastPrice - low.price;
  const fromHighPts = lastPrice - high.price;
  const fromLowPct  = low.price  ? (fromLowPts  / low.price)  * 100 : 0;
  const fromHighPct = high.price ? (fromHighPts / high.price) * 100 : 0;

  return {high, low, lastPrice, fromLowPts, fromHighPts, fromLowPct, fromHighPct};
}

// Plain-English intraday OI shift summary
function buildOiShiftSummary(j){
  const net = UI.netDeltaSum || 0;
  const sup = (UI.supportList || [])[0];
  const res = (UI.resistList  || [])[0];
  const lastRow = (UI.metricsRows || [])[0] || {};
  const clsName = lastRow.price_vs_oi || '';
  const pcr = Number(j.pcr || 0);

  const parts = [];
  if (sup) parts.push(`Put writing at ${sup.strike}`);
  if (res) parts.push(`Call writing near ${res.strike}`);
  if (net){
    parts.push(`NetΔOI ${net>0?'+':''}${net.toLocaleString('en-IN')} (${net>0?'Put>Call':'Call>Put'})`);
  }
  if (pcr) parts.push(`PCR ${pcr.toFixed(2)}`);
  if (clsName) parts.push(clsName);

  return parts.length ? parts.join(' · ') : '-';
}

// Build alert chips (PCR extremes, near day high/low, heavy ΔOI, far from max pain, breakouts)
function buildAlerts(j){
  const alerts = [];
  const pcr   = Number(j.pcr || 0);
  const price = Number(j.price || 0);
  const mp    = UI.maxPainData  || null;
  const ss    = UI.sessionStats || null;
  const nd    = UI.netDeltaSum  || 0;

  function chip(text, tone){
    const cls = tone==='bull' ? 'b-green' : (tone==='bear' ? 'b-red' : 'b-blue');
    return `<span class="badge ${cls}">${text}</span>`;
  }

  if (pcr && pcr < 0.80) alerts.push(chip(`PCR ${pcr.toFixed(2)} (Call-heavy)`, 'bear'));
  if (pcr && pcr > 1.30) alerts.push(chip(`PCR ${pcr.toFixed(2)} (Put-heavy)`, 'bull'));

  if (nd > 0){
    alerts.push(chip(`PutΔ +${nd.toLocaleString('en-IN')}`, 'bull'));
  }else if (nd < 0){
    alerts.push(chip(`CallΔ ${nd.toLocaleString('en-IN')}`, 'bear'));
  }

  if (ss){
    if (Math.abs(ss.fromLowPct) < 0.40){
      alerts.push(chip('Near Day Low', 'bear'));
    }else if (Math.abs(ss.fromHighPct) < 0.40){
      alerts.push(chip('Near Day High', 'bull'));
    }
  }

  if (mp && mp.maxPain && price && Math.abs(price - mp.maxPain) > 200){
    alerts.push(chip(`Price far from Max Pain ${mp.maxPain}`, 'blue'));
  }

  if (UI.breakoutBias > 0) alerts.push(chip('Recent Bullish Breakout', 'bull'));
  if (UI.breakoutBias < 0) alerts.push(chip('Recent Bearish Breakdown', 'bear'));

  return alerts;
}

// Time-of-day OI buckets using metrics top_call_delta / top_put_delta
function renderOiBuckets(){
  const table = document.getElementById('ksOiBuckets');
  if (!table) return;
  const tbody = table.querySelector('tbody');
  if (!tbody) return;

  const rows = UI.metricsRows || [];
  if (!rows.length){
    tbody.innerHTML = '';
    return;
  }

  const todayIST = new Date(new Date().toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
  function toIST(ts){
    return new Date(new Date(String(ts).replace(' ','T')+'Z')
      .toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
  }
  function sameDay(a,b){
    return a.getFullYear()===b.getFullYear() &&
           a.getMonth()===b.getMonth() &&
           a.getDate()===b.getDate();
  }

  const buckets = [
    { label:'Open–11:00',  start:9*60+15, end:11*60,      call:0, put:0 },
    { label:'11:00–13:00', start:11*60,   end:13*60,      call:0, put:0 },
    { label:'13:00–Close', start:13*60,   end:15*60+30,   call:0, put:0 }
  ];

  rows.forEach(r=>{
    const d = toIST(r.ts);
    if (!sameDay(d, todayIST)) return;
    const mins = d.getHours()*60 + d.getMinutes();
    const b = buckets.find(bk => mins >= bk.start && mins < bk.end);
    if (!b) return;
    b.call += Number(r.top_call_delta || 0);
    b.put  += Number(r.top_put_delta  || 0);
  });

  const fmtCell = (v)=>{
    if (!v) return '<td class="muted">—</td>';
    const cls   = v>0 ? 'up' : 'down';
    const arrow = v>0 ? '▲'  : '▼';
    return `<td class="${cls} mono">${arrow} ${Math.round(v).toLocaleString('en-IN')}</td>`;
  };

  tbody.innerHTML = buckets.map(b =>
    `<tr>
      <td>${b.label}</td>
      ${fmtCell(b.call)}
      ${fmtCell(b.put)}
    </tr>`
  ).join('');
}

// ---------- Market Meter main renderer ----------

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

  // (Spot / ATM / bias / max pain / gamma / support-resistance / quick read are shown once elsewhere:
  //  header line, ATM card, option chain header, OI Track — not repeated in the meter)
  document.getElementById('meterBadge').innerHTML   = badgeHtml;
  document.getElementById('meterWhy').innerHTML = reasons.length
    ? `Why: ${reasons.join(' · ')}`
    : 'Why: —';

  const alertsEl = document.getElementById('meterAlerts');
  if (alertsEl){
    const chips = buildAlerts(j);
    alertsEl.innerHTML = chips.join(' ');
  }

  {
    const ksExpEl = document.getElementById('ksExp');
    if (ksExpEl) ksExpEl.textContent = (j.expiry ?? '-');
    const ksPcrEl = document.getElementById('ksPcr');
    if (ksPcrEl) ksPcrEl.textContent = j.pcr ? Number(j.pcr).toFixed(2) : '-';

    const ksAtmZoneEl  = document.getElementById('ksAtmZone');
    const ksPriceOIEl  = document.getElementById('ksPriceOI');
    const ksOiPressEl  = document.getElementById('ksOiPressure');
    const ksTrendEl    = document.getElementById('ksTrendConf');
    const nseGammaEl    = document.getElementById('nseGammaZone');
    const nseGammaHintEl= document.getElementById('nseGammaHint');
    const ksDayRangeEl = document.getElementById('ksDayRange');
    const ksFromHLEl   = document.getElementById('ksFromHL');
    const ksOiShiftEl  = document.getElementById('ksOiShift');

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

    // Max pain + gamma. Max pain comes from the server (full chain, same value as the option
    // chain header); the local estimate below only sees the top-6 strikes of /oi/json.
    const mp = computeMaxPainAndGamma(j) || { maxPain: null, gammaZone: null };
    if (j.max_pain != null && Number.isFinite(Number(j.max_pain))) mp.maxPain = Number(j.max_pain);
    UI.maxPainData = mp;
    // Also mirror Gamma Zone/Hint into NSE table header chips
    const __applyGammaTo = (zoneEl, hintEl)=>{
      if(zoneEl){
        zoneEl.textContent = mp?.gammaZone ? `${mp.gammaZone.lo}–${mp.gammaZone.hi}` : '—';
        const spot0 = Number(j.price || j.spot || j.underlying || j.ltp || j.index || NaN);
        if (mp?.gammaZone && Number.isFinite(spot0)){
          const lo0 = Number(mp.gammaZone.lo), hi0 = Number(mp.gammaZone.hi);
          if(Number.isFinite(lo0) && Number.isFinite(hi0) && hi0>lo0){
            let pos0 = 'Inside';
            if(spot0 < lo0) pos0='Below'; else if(spot0>hi0) pos0='Above';
            zoneEl.classList.add('tip');
            zoneEl.dataset.tip = `Gamma Zone (approx)
Spot: ${spot0.toFixed(2)}
Zone: ${lo0}–${hi0}
Position: ${pos0}

Idea: Inside zone often behaves range/mean-revert; outside can trend until it returns.`;
          } else { zoneEl.dataset.tip=''; }
        } else { zoneEl.dataset.tip=''; }
      }
      if(hintEl){
        const spot = Number(j.price || j.spot || j.underlying || j.ltp || j.index || NaN);
        const gz = mp?.gammaZone || null;
        if(!gz || !Number.isFinite(gz.lo) || !Number.isFinite(gz.hi) || !Number.isFinite(spot)){
          hintEl.textContent = '—';
          hintEl.dataset.tip = '';
        } else {
          const lo = Number(gz.lo), hi = Number(gz.hi);
          const mid = (lo+hi)/2;
          let pos='', hint='';
          if(spot < lo){ pos='Below'; hint = `Below zone • ${(lo-spot).toFixed(0)} pts to enter`; }
          else if(spot > hi){ pos='Above'; hint = `Above zone • ${(spot-hi).toFixed(0)} pts above`; }
          else { pos='Inside'; hint = `Inside zone • ${Math.min(spot-lo, hi-spot).toFixed(0)} pts to edge`; }
          let sug='';
          if(pos==='Inside') sug='Usually mean-reversion / range behavior; watch zone edges for breakout.';
          else if(pos==='Above') sug='Upside extension possible, but snap-back risk to zone is common; use tight risk.';
          else sug='Downside pressure possible, but rebound into zone can happen; watch for reclaim.';
          hintEl.textContent = hint;
          hintEl.classList.add('tip');
          hintEl.dataset.tip = `Gamma Zone (approx)
Spot: ${spot.toFixed(2)}
Zone: ${lo}–${hi} (mid ${mid.toFixed(0)})
Position: ${pos}

${sug}

Note: Proxy from OI distribution (not true dealer gamma).`;
        }
      }
    };
    __applyGammaTo(nseGammaEl, nseGammaHintEl);
    // Mirror Gamma Zone into NSE header
    if (nseGammaEl){
      nseGammaEl.textContent = (mp && mp.gammaZone)
        ? (String(mp.gammaZone.lo) + '–' + String(mp.gammaZone.hi))
        : '—';

      const spot0 = Number(j.price || j.spot || j.underlying || j.ltp || j.index || NaN);
      if (mp && mp.gammaZone && Number.isFinite(spot0)){
        const lo0 = Number(mp.gammaZone.lo);
        const hi0 = Number(mp.gammaZone.hi);
        if (Number.isFinite(lo0) && Number.isFinite(hi0) && hi0 > lo0){
          let pos0 = 'Inside';
          if (spot0 < lo0) pos0 = 'Below';
          else if (spot0 > hi0) pos0 = 'Above';
          nseGammaEl.classList.add('tip');
          nseGammaEl.dataset.tip = `Gamma Zone (approx)
Spot: ${spot0.toFixed(2)}
Zone: ${lo0}–${hi0}
Position: ${pos0}

Idea: Inside zone often behaves range/mean-revert; outside can trend until it returns.`;
        }else{
          nseGammaEl.dataset.tip = '';
        }
      }else{
        nseGammaEl.dataset.tip = '';
      }
    }

    // Mirror Gamma Hint into NSE header
    if (nseGammaHintEl){
      const spot = Number(j.price || j.spot || j.underlying || j.ltp || j.index || NaN);
      const gz = (mp && mp.gammaZone) ? mp.gammaZone : null;
      if (!gz || !Number.isFinite(gz.lo) || !Number.isFinite(gz.hi) || !Number.isFinite(spot)){
        nseGammaHintEl.textContent = '—';
        nseGammaHintEl.dataset.tip = '';
      }else{
        const lo = Number(gz.lo), hi = Number(gz.hi);
        const mid = (lo + hi) / 2;
        let pos = '';
        let hint = '';
        if (spot < lo){
          pos = 'Below';
          const d = lo - spot;
          hint = `Below zone • ${d.toFixed(0)} pts to enter`;
        }else if (spot > hi){
          pos = 'Above';
          const d = spot - hi;
          hint = `Above zone • ${d.toFixed(0)} pts above`;
        }else{
          pos = 'Inside';
          const dL = spot - lo;
          const dH = hi - spot;
          hint = `Inside zone • ${Math.min(dL,dH).toFixed(0)} pts to edge`;
        }

        let sug = '';
        if (pos === 'Inside'){
          sug = 'Usually mean-reversion / range behavior; watch zone edges for breakout.';
        }else if (pos === 'Above'){
          sug = 'Upside extension possible, but snap-back risk to zone is common; use tight risk.';
        }else{
          sug = 'Downside pressure possible, but rebound into zone can happen; watch for reclaim.';
        }

        nseGammaHintEl.textContent = hint;
        nseGammaHintEl.dataset.tip =
          `Gamma Zone (approx)
`+
          `Spot: ${spot.toFixed(2)}
`+
          `Zone: ${lo}–${hi} (mid ${mid.toFixed(0)})
`+
          `Position: ${pos}

`+
          `${sug}

`+
          `Note: This is a proxy from OI distribution (not true dealer gamma). Use with price action + levels.`;
        nseGammaHintEl.classList.add('tip');
      }
    }


    // Session stats
    const ss = computeSessionStats();
    UI.sessionStats = ss;
    if (ss){
      if (ksDayRangeEl){
        const rangePts = ss.high.price - ss.low.price;
        ksDayRangeEl.textContent =
          `${ss.low.price.toFixed(2)} – ${ss.high.price.toFixed(2)} (${rangePts.toFixed(2)} pts)`;
      }
      if (ksFromHLEl){
        const lowPart  = `${ss.fromLowPts>=0?'+':''}${ss.fromLowPts.toFixed(0)} pts (${ss.fromLowPct>=0?'+':''}${ss.fromLowPct.toFixed(2)}%) from Low`;
        const highPart = `${ss.fromHighPts>=0?'+':''}${ss.fromHighPts.toFixed(0)} pts (${ss.fromHighPct>=0?'+':''}${ss.fromHighPct.toFixed(2)}%) from High`;
        ksFromHLEl.textContent = `${lowPart} • ${highPart}`;
      }
    }else{
      if (ksDayRangeEl) ksDayRangeEl.textContent = '-';
      if (ksFromHLEl)   ksFromHLEl.textContent   = '-';
    }

    // OI shift summary
    if (ksOiShiftEl){
      ksOiShiftEl.textContent = buildOiShiftSummary(j);
    }
  }

  // Time-of-day OI profile
  renderOiBuckets();

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

function applyAtmRowHighlights(atmStrike){
  const atm = Number(atmStrike);
  if(!Number.isFinite(atm)) return;

  function markInTable(tableSel, strikeColIndex){
    const tb = document.querySelector(tableSel);
    if(!tb) return;
    const rows = tb.querySelectorAll('tr');
    rows.forEach(tr=>tr.classList.remove('atm-row'));
    rows.forEach(tr=>{
      const tds = tr.querySelectorAll('td');
      if(!tds || tds.length<=strikeColIndex) return;
      const raw = (tds[strikeColIndex].textContent||'').replace(/[, ]/g,'').trim();
      const val = parseInt(raw,10);
      if(Number.isFinite(val) && val===atm){
        tr.classList.add('atm-row');
      }
    });
  }

  // OI Track table body (#trackTable) -> strike is td[0]
  markInTable('#trackTable', 0);
  // NSE table body -> strike cell has class 'strike' but easier: strike column is middle; we mark by .strike cell
  const nseBody = document.getElementById('nseOcBody');
  if(nseBody){
    [...nseBody.querySelectorAll('tr')].forEach(tr=>tr.classList.remove('atm-row'));
    [...nseBody.querySelectorAll('tr')].forEach(tr=>{
      const sc = tr.querySelector('td.strike');
      if(!sc) return;
      const raw = (sc.textContent||'').replace(/[, ]/g,'').trim();
      const val = parseInt(raw,10);
      if(Number.isFinite(val) && val===atm){
        tr.classList.add('atm-row');
      }
    });
  }
}




// ---- NSE OC render placeholder (prevents 'Waiting…' when loadTrack runs before renderer is defined) ----
window.__nseOcPending = window.__nseOcPending || null;
if (typeof window.renderNseOcFromTrack !== 'function') {
  window.renderNseOcFromTrack = function(j){ window.__nseOcPending = j; };
}

// ---- Shared timeframe label helper (used by BOTH OI Track + NSE-style OC) ----
// NOTE: loadTrack() runs before the NSE OC block below is defined.
// If this helper is missing, a ReferenceError stops rendering and the UI stays on "Waiting…".
if (typeof window.nseTfLabel !== 'function') {
  window.nseTfLabel = function nseTfLabel(mins){
    const m = parseInt(mins, 10);
    if (!Number.isFinite(m) || m <= 0) return String(mins ?? '');
    if (m % 60 === 0) return (m/60) + 'hr';
    return m + 'm';
  };
}

// ---- OI Track: NSE-style TF dropdown (writes selection into #tfInput) ----
function buildOiTfMenu(){
  const host = document.getElementById('oiTfMenu');
  const tfInput = document.getElementById('tfInput');
  if(!host || !tfInput) return;

  const ALL = [1,2,3,5,10,15,30,60,120,180];
  const readSel = ()=>{
    const raw = (tfInput.value || '').trim();
    const a = raw.split(',').map(x=>parseInt(x,10)).filter(n=>Number.isFinite(n) && n>0);
    const uniq = Array.from(new Set(a)).filter(n=>ALL.includes(n)).sort((p,q)=>p-q);
    return uniq.length ? uniq : [5];
  };
  const writeSel = (arr)=>{
    const clean = Array.from(new Set((arr||[]).map(n=>parseInt(n,10)).filter(Number.isFinite)))
      .filter(n=>ALL.includes(n)).sort((p,q)=>p-q);
    tfInput.value = (clean.length ? clean : [5]).join(',');
  };

  const menu = document.createElement('span');
  menu.className = 'nse-tf-menu oi-tf-menu';

  const btn = document.createElement('button');
  btn.type='button';
  btn.className='tfbtn picker';
  btn.textContent='TFs ▾';
  btn.addEventListener('click', (e)=>{ e.preventDefault(); menu.classList.toggle('open'); });

  const pop = document.createElement('div');
  pop.className='nse-tf-pop';

  const cur = new Set(readSel());
  ALL.forEach(m=>{
    const row = document.createElement('div');
    row.className='row';
    const lab = document.createElement('label');
    const cb = document.createElement('input');
    cb.type='checkbox';
    cb.checked = cur.has(m);
    cb.addEventListener('change', ()=>{
      const next = new Set(readSel());
      if(cb.checked) next.add(m); else next.delete(m);
      if(next.size===0){ cb.checked=true; return; }
      writeSel(Array.from(next));
    });
    const t = document.createElement('span');
    t.textContent = window.nseTfLabel(m);
    lab.appendChild(cb);
    lab.appendChild(t);
    row.appendChild(lab);
    pop.appendChild(row);
  });

  const actions = document.createElement('div');
  actions.className='actions';
  const allBtn = document.createElement('button');
  allBtn.type='button';
  allBtn.className='tfbtn mini';
  allBtn.textContent='All';
  allBtn.addEventListener('click', ()=>{ writeSel(ALL); });
  const doneBtn = document.createElement('button');
  doneBtn.type='button';
  doneBtn.className='tfbtn mini active';
  doneBtn.textContent='Done';
  doneBtn.addEventListener('click', ()=> menu.classList.remove('open'));
  actions.appendChild(allBtn);
  actions.appendChild(doneBtn);
  pop.appendChild(actions);

  document.addEventListener('click', (ev)=>{ if(!menu.contains(ev.target)) menu.classList.remove('open'); });
  menu.appendChild(btn);
  menu.appendChild(pop);
  host.innerHTML='';
  host.appendChild(menu);
}


// ===== Shared formatter for OI Track ΔOI (number + %) =====
// Note: must be GLOBAL (used inside loadTrack)
function fmtDeltaWithPctTrack(delta, base, decimals=0){
  const d = Number(delta);
  const b = Number(base);
  if (!Number.isFinite(d)) return '-';

  const dTxt = (d > 0 ? '+' : '') + d.toFixed(decimals);

  if (!Number.isFinite(b) || b === 0) return dTxt; // no base => only delta

  const pct = (d / b) * 100;
  const pTxt = (pct > 0 ? '+' : '') + pct.toFixed(2) + '%';
  return dTxt + ' <span class="muted">(' + pTxt + ')</span>';
}


function getOiDeltaStyle(){
  try{ return (localStorage.getItem('oiDeltaStyle') || 'arrows'); }catch(e){ return 'arrows'; }
}
function setOiDeltaStyle(v){
  try{ localStorage.setItem('oiDeltaStyle', v); }catch(e){}
}
function calcDeltaPct(delta, base){
  const d = Number(delta||0);
  const b = Number(base||0);
  if (!Number.isFinite(d) || !Number.isFinite(b) || b===0) return 0;
  return (d / b) * 100;
}





function pctIntensityClass(pctAbs){
  const a = Math.abs(Number(pctAbs)||0);
  if (a >= 4) return 'i5';
  if (a >= 2) return 'i4';
  if (a >= 1) return 'i3';
  if (a >= 0.5) return 'i2';
  if (a >= 0.2) return 'i1';
  return '';
}

function fmtDeltaWithPctTrackArrow(delta, base, minutes, decimals=0){
  const d = Number(delta);
  const b = Number(base);
  if (!Number.isFinite(d)) return '-';

  const m = Number(minutes);
  const tfLbl = Number.isFinite(m) ? nseTfLabel(m) : '';
  const tip = tfLbl ? `Net build-up over last ${tfLbl}` : 'Net build-up';

  let cls = '';
  let arrow = '';
  if (d > 0) { cls = 'pos'; arrow = '▲ '; }
  else if (d < 0) { cls = 'neg'; arrow = '▼ '; }

  const dTxt = fmtNum(Math.abs(d).toFixed(decimals));

  // No base -> no pct
  if (!Number.isFinite(b) || b === 0){
    return `<span class="delta-wrap ${cls}" data-tip="${tip}">${arrow}${dTxt}</span>`;
  }

  const pct = (d / b) * 100;
  const pAbs = Math.abs(pct);
  const pTxt = `${(pAbs < 0.05 ? 0 : pct).toFixed(2)}%`;

  const inten = pctIntensityClass(pAbs);
  const wrapCls = `delta-wrap ${cls} ${inten}`.trim();

  return `<span class="${wrapCls}" data-tip="${tip}">${arrow}${dTxt} <span class="muted">(${pTxt})</span></span>`;
}



async function loadTrack(){
  const symbol = document.getElementById('symbol').value;
  const expiry = document.getElementById('expirySel')?.value || '';

  // Request lookbacks for BOTH OI Track + NSE-style OC.
  // Always include 1m + hour windows so hourly columns never show blank.
    // OI Track selected timeframes (display)
  const selRaw = (document.getElementById('tfInput')?.value || '1,3,5,10,15,30').replace(/\s+/g,'');
  const selArr = selRaw.split(',').filter(Boolean);
  const selNum = Array.from(new Set(selArr.map(x=>parseInt(x,10)).filter(n=>Number.isFinite(n) && n>=1 && n<=240))).sort((a,b)=>a-b);

  // NSE OC can optionally show a different set (picker stored in localStorage).
  // We fetch the UNION so whichever TFs are visible have data, but OI Track will still *display* selNum only.
  const union = new Set(selNum.length ? selNum : [5]);
  try { ((window.getNseVisibleTfs && window.getNseVisibleTfs()) || []).forEach(m=>union.add(Number(m))); } catch(e){}
  try { const tf = window.getNseSelectedTf && window.getNseSelectedTf(); if (tf && tf !== 'DAY') union.add(Number(tf)); } catch(e){}

  const lbsNum = Array.from(union).filter(n=>Number.isFinite(n) && n>=1 && n<=240).sort((a,b)=>a-b);
  const lbs = lbsNum.join(',');
  const strikes = (document.getElementById('strikeWin')?.value || '5');

  const url = `/oi/track?symbol=${encodeURIComponent(symbol)}`
            + (expiry ? `&expiry=${encodeURIComponent(expiry)}` : '')
            + `&lookbacks=${encodeURIComponent(lbs)}&strikes=${encodeURIComponent(strikes)}`;

  const showLoadError = (msg, detail)=>{
    try{
      const st = document.getElementById('nseOcStatus');
      if(st){
        st.style.display = 'inline-block';
        st.textContent = msg;
      }
    }catch(e){}
    try{
      const body = document.getElementById('trackTable');
      if(body){
        body.innerHTML = `<tr><td colspan="24" class="muted" style="text-align:center;padding:14px">${msg}${detail?`<div style='opacity:.7;margin-top:6px;font-size:12px'>${detail}</div>`:''}</td></tr>`;
      }
    }catch(e){}
  };

  let res;
  try{
    res = await fetch(url, { cache: 'no-store' });
  }catch(err){
    showLoadError('Fetch failed', String(err?.message || err));
    return;
  }

  if(!res || !res.ok){
    showLoadError('API error', `HTTP ${res ? res.status : '0'}`);
    return;
  }

  // Robust JSON parsing (handles cases where server returns HTML/error page)
  const raw = await res.text();
  let j = null;
  try{ j = JSON.parse(raw); }catch(e){
    const snippet = raw ? raw.slice(0, 160).replace(/</g,'&lt;').replace(/>/g,'&gt;') : '';
    showLoadError('Invalid JSON from /oi/track', snippet);
    return;
  }

  if (!j || j.ok === false){
    showLoadError('No data', (j && j.msg) ? String(j.msg) : '');
    return;
  }

  // --- ATM fallback: recompute if stuck/wrong ---
  (function fixAtm(obj){
    if(!obj) return;
    const step = strikeStepOf(obj); // NIFTY 50, BANKNIFTY 100
    const spot = Number(obj.spot || obj.underlying_now || obj.underlying || obj.ltp || obj.fut || obj.index || NaN);
    if (!Number.isFinite(spot)) return;
    const derived = Math.round(spot / step) * step;
    if (!Number.isFinite(Number(obj.atm)) || Number(obj.atm) !== derived) obj.atm = derived;
  })(j);

    // Display lookbacks for OI Track come from user's selection (#tfInput), not necessarily the fetch union.
  const _selRaw = (document.getElementById('tfInput')?.value || '').trim();
  const _sel = Array.from(new Set(_selRaw.split(',').map(x=>parseInt(x,10)).filter(n=>Number.isFinite(n) && n>=1 && n<=240)));
  const _have = new Set((j.lookbacks||[]).map(Number));
  const minsDesc = (_sel.length ? _sel.filter(m=>_have.has(m)) : (j.lookbacks||[])).slice().sort((a,b)=>b-a);

  // Dominance marker (higher TFs): compare absolute ΣΔOI across strikes for CE vs PE
  const domByM = {};
  try{
    minsDesc.forEach(m=>{
      if (Number(m) < 60) return;
      let ceSum = 0, peSum = 0;
      (j.rows||[]).forEach(r=>{
        if (r && r.CE) ceSum += Number(r.CE['chg_' + m + 'm'] || 0);
        if (r && r.PE) peSum += Number(r.PE['chg_' + m + 'm'] || 0);
      });
      const dom = (Math.abs(ceSum) >= Math.abs(peSum)) ? 'CE' : 'PE';
      domByM[m] = { dom, ceSum, peSum };
    });
  }catch(e){}

  const head = document.getElementById('trackHeadRow');
  head.innerHTML = `
    <th>Strike</th><th>Type</th>
    ${minsDesc
      .map(m => {
        const badge = (Number(m) >= 60 && domByM[m])
          ? `<span class="dom-badge ${domByM[m].dom==='CE'?'dom-ce':'dom-pe'}" data-tip="Dominance (abs ΣΔOI): ${domByM[m].dom}">${domByM[m].dom}</span>`
          : '';
        return `<th class="tf-oi">OI (${nseTfLabel(m)})</th><th class="tf-delta">ΔOI (${nseTfLabel(m)})${badge}</th>`;
      })
      .join('')}
    <th>Current OI</th><th>Cur ΔOI (chg & %)</th><th>Volume</th><th>Cur Vol (chg & %)</th>
  `;

  const fmt = n => (n===null||n===undefined) ? '-' : Number(n).toLocaleString('en-IN');
  const body = document.getElementById('trackTable');
  body.innerHTML = '';

  (j.rows || []).forEach(r => {
    ['CE','PE'].forEach(side=>{
      if (UI.optFilter === 'calls' && side==='PE') return;
      if (UI.optFilter === 'puts'  && side==='CE') return;

      const s = r[side];
      if (!s) return;

      const cells = minsDesc.map(m =>
        `<td class="tf-oi">${fmt(s['oi_' + m + 'm'])}</td>` +
        `<td class="tf-delta" data-delta-pct="${calcDeltaPct(s['chg_' + m + 'm'], s['oi_' + m + 'm'])}">${(m>=60 ? (getOiDeltaStyle()==='pm' ? fmtDeltaWithPctTrack(s['chg_' + m + 'm'], s['oi_' + m + 'm'], 0) : fmtDeltaWithPctTrackArrow(s['chg_' + m + 'm'], s['oi_' + m + 'm'], m, 0)) : fmtDeltaWithPctTrack(s['chg_' + m + 'm'], s['oi_' + m + 'm'], 0))}</td>`
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


      // ----- Volume -----
      const curVol  = s.cur_vol;
      const volChg  = s.cur_vol_chg;

      let volPctText = '';
      let vcls = '';
      let varrow = '';

      if (volChg !== null && volChg !== undefined &&
          curVol !== null && curVol !== undefined) {

        const baseVol = curVol - volChg;
        let vpct = 0;
        if (baseVol !== 0) {
          vpct = (volChg / baseVol) * 100;
        }
        const vp = (Math.abs(vpct) < 0.05) ? 0 : vpct;
        volPctText = ` <span class="muted">(${vp.toFixed(1)}%)</span>`;

        if (volChg > 0) { vcls='up'; varrow='▲ '; }
        else if (volChg < 0) { vcls='down'; varrow='▼ '; }
      }

      const volChgCell =
        (volChg === null || volChg === undefined)
          ? '-'
          : `<span class="cur-delta ${vcls}">${varrow}${fmt(volChg)}${volPctText}</span>`;

      body.insertAdjacentHTML(
        'beforeend',
        `<tr>
          <td>${r.strike}</td>
          <td>${side}</td>
          ${cells}
         
          <td>${fmt(curOi)}</td>
          <td>${curChgCell}</td>
          <td>${fmt(curVol)}</td>
          <td>${volChgCell}</td>
        </tr>`
      );
    });
  });

  // Cache latest track JSON + rendered rows so header context can refresh on every tick.
  try{ window.__lastTrackJson = j; window.__lastNseRows = Array.isArray(j.rows) ? j.rows : []; }catch(e){}

  try{ if (window.renderNseOcFromTrack) window.renderNseOcFromTrack(j); }catch(e){ console.warn('NSE OC render failed', e); const _st=document.getElementById('nseOcStatus'); if(_st){ _st.style.display='inline-block'; _st.textContent='NSE table error (check Console)'; }}

  try{ window.updateNseHeaderContext && window.updateNseHeaderContext(); }catch(e){}

  // (window.lastJson stays the /oi/json snapshot; the track payload is window.__lastTrackJson)

  if (window.enhanceAll) window.enhanceAll();
  try{ applyAtmRowHighlights(j.atm ?? (lastJson && lastJson.atm)); }catch(e){}
}

try{ buildOiTfMenu(); }catch(e){}
try{
  const sel = document.getElementById('oiDeltaStyle');
  if (sel){
    sel.value = getOiDeltaStyle();   // init from storage
    sel.addEventListener('change', (e)=>{
      setOiDeltaStyle(String(e.target.value || 'arrows'));
      // Re-render everything that depends on ΔOI style
      loadTrack();
    });
  }
}catch(e){}
// Call the current loadTrack (a later script wraps it), not the reference held at this point
document.getElementById('tfApply')?.addEventListener('click', () => loadTrack());
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

<script>
(function(){
  /* ================= CONFIG ================= */
  const CRON_WARN_MIN = 5;      // blink after
  const CRON_SOUND_MIN = 8;     // 🔊 sound after
  const STALE_PAUSE_MIN = 15;   // during Market OPEN, pause signals if DB older than this
  const GAP_PAUSE = true;

  /* ================= ELEMENTS ================= */
  const audio = document.getElementById("audioCronAlert");

  const soundPlayed = {};   // per status dot: the alarm plays once per stale episode

  /* ================= TIME HELPERS ================= */
  function parseIST(txt){
    if(!txt) return null;
    // accept "YYYY-MM-DD HH:MM:SS" optionally followed by " IST" or other text
    const m = String(txt).match(/\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}/);
    if(!m) return null;
    const iso = m[0].replace(" ","T") + "+05:30";
    const d = new Date(iso);
    return isNaN(d) ? null : d;
  }

  /* ================= MARKET STATE ================= */
  // OPEN / PREOPEN / POST / CLOSED from the shared, holiday- and special-session-aware
  // getMarketStatusIST(); blink, sound and the stale-data pause apply only while OPEN.
  function marketState(){
    return getMarketStatusIST().state;
  }


  /* ================= DOT + SOUND ================= */
  // Returns true when this dot just became due for the alarm (updateFreshness plays it once)
  function setDot(dot, mins){
    if(!dot) return false;
    dot.classList.remove("ok","warn","bad","blink");

    const open = marketState() === "OPEN";

    if (mins <= 2) {
      dot.classList.add("ok");
      soundPlayed[dot.id] = false;
    }
    else if (mins <= CRON_WARN_MIN) {
      dot.classList.add("warn");
      soundPlayed[dot.id] = false;
    }
    else {
      dot.classList.add("bad");
      if (open) dot.classList.add("blink"); // 📉 hide blink when closed

      if (mins >= CRON_SOUND_MIN && open && !soundPlayed[dot.id]) {
        soundPlayed[dot.id] = true;
        return true;
      }
    }
    return false;
  }

  // Runs every 60 s and after each /oi/healthz refresh (loadHealth), which rewrites the timestamps
  function updateFreshness(){
    let dbAge = 999;
    let alarm = false;

    [
      {ts:"tsDb", dot:"dotDb", ago:"agoDb", isDb:true},
      {ts:"tsFetch", dot:"dotFetch", ago:"agoFetch"},
      {ts:"tsEnrich", dot:"dotEnrich", ago:"agoEnrich"},
    ].forEach(x=>{
      const tsEl = document.getElementById(x.ts);
      const dotEl = document.getElementById(x.dot);
      const d = parseIST(tsEl?.textContent);
      if(!d) return;
      const mins = Math.max(0, Math.floor((Date.now() - d.getTime())/60000));
      if (setDot(dotEl, mins)) alarm = true;
      const agoEl = document.getElementById(x.ago);
      if (agoEl) agoEl.textContent = `(${mins} min ago)`;
      if (typeof healthClass === 'function') tsEl.className = healthClass(mins);
      if (x.isDb) dbAge = mins;
    });

    if (alarm) audio?.play().catch(()=>{});   // one sound even when several dots go stale together

    // ===== Data gap + auto-pause signals when stale =====
    const gapBadge = document.getElementById("dataGapBadge");
    const pauseBadge = document.getElementById("signalsPausedBadge");
    const st = marketState(); // OPEN / PREOPEN / POST / CLOSED
    const openNow = (st === "OPEN");

    // parse last DB timestamp (IST text already in DOM)
    const tsDbEl = document.getElementById("tsDb");
    const lastDb = parseIST(tsDbEl?.textContent);
    let gapDays = 0;

    if (lastDb){
      // calendar days between the IST dates of the last snapshot and now (0 = today)
      const lastDay = Date.parse(istNowParts(lastDb).ymd);
      const nowDay  = Date.parse(istNowParts().ymd);
      gapDays = Math.round((nowDay - lastDay) / (24*3600*1000));
      if (gapDays < 0) gapDays = 0;
    }

    const hasGap = (gapDays >= 1);

    if (gapBadge){
      gapBadge.classList.remove("good","warn","bad","neu");
      if (hasGap){
        gapBadge.style.display = "";
        gapBadge.textContent = `Data Gap: ${gapDays} day${gapDays>1?'s':''}`;
        gapBadge.title = lastDb ? `Last snapshot: ${tsDbEl?.textContent || ''} (IST)` : 'Last snapshot not found';
        gapBadge.classList.add("warn");
      }else{
        gapBadge.style.display = "none";
        gapBadge.classList.remove("warn");
      }
    }

    // Pause rules:
    // - during OPEN: pause if dbAge >= STALE_PAUSE_MIN
    // - any time: pause if gap exists AND GAP_PAUSE enabled
    const shouldPause = ((GAP_PAUSE && hasGap && !window.__oiSafetyMode) || (openNow && dbAge >= STALE_PAUSE_MIN));

    window.__signalsPaused = !!shouldPause;

    // Auto-resume toast (show once when PAUSED -> LIVE)
    const prevPaused = (window.__wasSignalsPaused === undefined) ? null : !!window.__wasSignalsPaused;
    window.__wasSignalsPaused = !!shouldPause;
    if (prevPaused === true && shouldPause === false){
      const t = document.getElementById("resumeToast");
      if (t){
        t.classList.add("show");
        clearTimeout(window.__resumeToastTimer);
        window.__resumeToastTimer = setTimeout(()=>t.classList.remove("show"), 3500);
      }
    }

    if (shouldPause){
      ["readinessBadge","trendAlignBadge","strikeShiftBadge","falseBreakBadge"].forEach(id=>{
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove("good","warn","bad","neu");
        el.classList.add("neu");
        const base = id==="readinessBadge" ? "Ready" :
                     id==="trendAlignBadge" ? "Align" :
                     id==="strikeShiftBadge" ? "Strike Shift" : "Big move";
        el.textContent = `${base}: PAUSED`;
        el.title = "Signals paused due to stale or gapped data.";
      });
    }

    if (pauseBadge){
      pauseBadge.classList.remove("good","warn","bad","neu");
      if (shouldPause){
        pauseBadge.style.display = "";
        pauseBadge.textContent = openNow && dbAge >= STALE_PAUSE_MIN
          ? `Signals: PAUSED (stale ${dbAge}m)`
          : `Signals: PAUSED (gap ${gapDays}d)`;
        pauseBadge.title = "Auto-pause is enabled to prevent trade decisions on stale/gapped data.";
        pauseBadge.classList.add("bad");
      }else{
        pauseBadge.style.display = "none";
        pauseBadge.classList.remove("bad");
      }
    }

  }



  /* ================= LOOPS ================= */
  window.__oiUpdateFreshness = updateFreshness;   // called by loadHealth() after each /oi/healthz refresh
  updateMarketBadge();
  updateFreshness();

  setInterval(updateFreshness, 60000);
})();
</script>
<script src="<?= base_url('assets/js/oi_track_enhancer.js?v=2026-09-28-p5') ?>"></script>


<!-- ========================================================= -->
<!-- INDEX MOVEMENT (EOD) : NIFTY & BANKNIFTY                  -->
<!-- Daily / Weekly / Monthly close-to-close movement         -->
<!-- ========================================================= -->

<script>
(function () {
  const el = document.getElementById('purgeBadge');
  if (!el) return;

  // hide after 10.5 seconds
  setTimeout(() => {
    el.style.opacity = '0';
    // remove from layout after fade
    setTimeout(() => el.remove(), 500);
  }, 10500);
})();
</script>


<script>
(function () {
  function convert(el){
    if (!el) return;

    const t = el.getAttribute('title');
    if (!t || !t.trim()) return;

    // A title set after the first conversion (badge updates etc.) replaces the older text;
    // skipping converted elements left a stale custom tooltip next to the browser's one
    el.dataset.tip = t.trim();   // custom tooltip text
    el.removeAttribute('title'); // kill browser default tooltip
  }

  function convertAll(){
    document.querySelectorAll('[title]').forEach(convert);
  }

  // Convert once on load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', convertAll);
  } else {
    convertAll();
  }

  // Convert dynamically created elements too
  const mo = new MutationObserver((mutations) => {
    for (const m of mutations) {
      for (const node of m.addedNodes) {
        if (!(node instanceof Element)) continue;
        if (node.hasAttribute && node.hasAttribute('title')) convert(node);
        node.querySelectorAll && node.querySelectorAll('[title]').forEach(convert);
      }
    }
  });
  mo.observe(document.documentElement, { childList: true, subtree: true });

  // Also convert on hover as a fallback
  document.addEventListener('mouseover', (e) => {
    const el = e.target.closest && e.target.closest('[title]');
    if (el) convert(el);
  }, true);
})();
</script>
<script>
(function(){
  const pad = 12;

  function clamp(v, a, b){ return Math.max(a, Math.min(b, v)); }

  document.addEventListener('mousemove', (e) => {
    const el = e.target.closest && e.target.closest('[data-tip]');
    if (!el) return;

    const w = 320; // max width (safe)
    const x = clamp(e.clientX + 14, pad, window.innerWidth - w - pad);
    const y = clamp(e.clientY + 14, pad, window.innerHeight - 60 - pad);

    el.style.setProperty('--tip-x', x + 'px');
    el.style.setProperty('--tip-y', y + 'px');
  }, true);

  document.addEventListener('mouseenter', (e) => {
    const el = e.target.closest && e.target.closest('[data-tip]');
    if (el) el.classList.add('tip-show');
  }, true);

  document.addEventListener('mouseleave', (e) => {
    const el = e.target.closest && e.target.closest('[data-tip]');
    if (el) el.classList.remove('tip-show');
  }, true);
})();
</script>

<script>
setTimeout(() => {
  document.querySelectorAll('[title]').forEach(el => {
    if (!el.dataset.tip) {
      el.dataset.tip = el.getAttribute('title');
    }
    el.removeAttribute('title'); // force kill browser tooltip
  });
}, 0);

/* ===== NSE Option Chain renderer (sync from /oi/track) ===== */
(function(){
  const fmt = (n) => {
    if(n===null||n===undefined||Number.isNaN(Number(n))) return '-';
    return Number(n).toLocaleString('en-IN');
  };
  const fmt2 = (n) => {
    if(n===null||n===undefined||Number.isNaN(Number(n))) return '—';
    return Number(n).toFixed(2);
  };

  function getAtmStrike(j){
    const atm = Number(j?.atm);
    if(Number.isFinite(atm)) return atm;
    // fallback: closest to spot if present
    const spot = Number(j?.spot || j?.underlying || j?.ltp || j?.price || NaN);
    if(!Number.isFinite(spot) || !Array.isArray(j?.rows)) return null;
    let best=null, bestD=Infinity;
    j.rows.forEach(r=>{
      const st=Number(r?.strike);
      if(!Number.isFinite(st)) return;
      const d=Math.abs(st-spot);
      if(d<bestD){ bestD=d; best=st; }
    });
    return best;
  }


  function computeMaxPain(rows){
    // classic max pain using OI only (lot size cancels)
    const strikes = rows.map(r=>Number(r?.strike)).filter(Number.isFinite).sort((a,b)=>a-b);
    if(!strikes.length) return null;
    const map = new Map();
    rows.forEach(r=>{
      const st=Number(r?.strike);
      if(!Number.isFinite(st)) return;
      map.set(st, {
        ce: Number(r?.CE?.cur_oi) || 0,
        pe: Number(r?.PE?.cur_oi) || 0
      });
    });

    let bestK=null, bestPain=Infinity;
    for(const K of strikes){
      let pain=0;
      for(const S of strikes){
        const o=map.get(S) || {ce:0,pe:0};
        // For a settlement at K:
        // CE writers pain when K > S: (K-S)*CE_OI
        // PE writers pain when K < S: (S-K)*PE_OI
        if(K>S) pain += (K-S) * o.ce;
        if(K<S) pain += (S-K) * o.pe;
      }
      if(pain < bestPain){ bestPain=pain; bestK=K; }
    }
    return bestK;
  }

  // ===== NSE OC timeframe support =====
  window.__nseOcLastJ = window.__nseOcLastJ || null;
  let __nseOcTf = null; // number (minutes) or 'DAY'
  let __nseOcMetric = 'OI'; // 'OI' or 'VOL'

  // Visible timeframes (for column priority + TF button list)
  // Minutes. Hours are represented as 60/120/180.
  const __NSE_TF_ALL = [1,2,3,5,10,15,30,60,120,180];

  function nseTfText(m){
    m = Number(m);
    if(!Number.isFinite(m)) return String(m);
    if(m >= 60 && m % 60 === 0) return (m/60) + 'hr';
    return m + 'm';
  }
  function getNseVisibleTfs(){
    try{
      const raw = localStorage.getItem('nseOcVisibleTfs');
      if(!raw) return __NSE_TF_ALL.slice();
      const a = JSON.parse(raw);
      if(!Array.isArray(a) || !a.length) return __NSE_TF_ALL.slice();
      const mins = Array.from(new Set(a.map(Number).filter(Number.isFinite)))
        .filter(x => __NSE_TF_ALL.includes(x))
        .sort((p,q)=>p-q);
      return mins.length ? mins : __NSE_TF_ALL.slice();
    }catch(e){
      return __NSE_TF_ALL.slice();
    }
  }
  function setNseVisibleTfs(mins){
    const clean = Array.from(new Set((mins||[]).map(Number).filter(Number.isFinite)))
      .filter(x => __NSE_TF_ALL.includes(x))
      .sort((p,q)=>p-q);
    try{ localStorage.setItem('nseOcVisibleTfs', JSON.stringify(clean.length?clean:__NSE_TF_ALL)); }catch(e){}
    return clean.length ? clean : __NSE_TF_ALL.slice();
  }
  // loadTrack() (another script) requests these lookbacks too, so every visible option-chain
  // column gets data (these helpers were private to this block, so that call always failed)
  window.getNseVisibleTfs = getNseVisibleTfs;
  window.getNseSelectedTf = () => __nseOcTf;

  function buildNseTfButtons(lookbacks){
    const wrap = document.getElementById('nseOcTf');
    if(!wrap) return;

    const lbs = (Array.isArray(lookbacks) && lookbacks.length) ? lookbacks.slice() : [1,2,3,5,10,15,30,60,120,180];

    // Unique minutes (ascending), intersect with user's visible set
    const visible = new Set(getNseVisibleTfs());
    let mins = Array.from(new Set(lbs.map(Number).filter(Number.isFinite)))
      .filter(m => visible.has(m));
    // If none match, fall back to user's set (still keep in ascending)
    if(!mins.length){
      mins = getNseVisibleTfs().slice();
    }
    mins = mins.sort((a,b)=>a-b);

    // Default selected TF
    if(__nseOcTf === null) __nseOcTf = 5;

    // Build buttons: 1m,3m,... + Day
    wrap.innerHTML = '';

    const lbl = document.createElement('span');
    lbl.className = 'tflbl';
    lbl.textContent = 'Timeframe:';
    wrap.appendChild(lbl);

    const mkBtn = (label,val)=>{
      const b=document.createElement('button');
      b.type='button';
      b.className='tfbtn' + ((String(val)===String(__nseOcTf))?' active':'');
      b.textContent=label;
      b.addEventListener('click', ()=>{
        __nseOcTf = val;
        if(window.__nseOcLastJ) window.renderNseOcFromTrack(window.__nseOcLastJ, true);
      });
      return b;
    };

    // Picker (show/hide timeframes)
    (function addPicker(){
      const menu = document.createElement('span');
      menu.className = 'nse-tf-menu';

      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'tfbtn picker';
      b.textContent = 'TFs ▾';
      b.addEventListener('click', (e)=>{
        e.preventDefault();
        menu.classList.toggle('open');
      });

      const pop = document.createElement('div');
      pop.className = 'nse-tf-pop';

      const cur = new Set(getNseVisibleTfs());
      __NSE_TF_ALL.forEach(m=>{
        const row = document.createElement('div');
        row.className = 'row';
        const lab = document.createElement('label');
        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.checked = cur.has(m);
        cb.addEventListener('change', ()=>{
          const next = new Set(getNseVisibleTfs());
          if(cb.checked) next.add(m); else next.delete(m);
          // keep at least one
          if(next.size===0){ cb.checked = true; return; }
          setNseVisibleTfs(Array.from(next));
          if(window.__nseOcLastJ) window.renderNseOcFromTrack(window.__nseOcLastJ, true);
        });
        const t = document.createElement('span');
        t.textContent = nseTfText(m);
        lab.appendChild(cb);
        lab.appendChild(t);
        row.appendChild(lab);
        pop.appendChild(row);
      });

      const actions = document.createElement('div');
      actions.className = 'actions';
      const allBtn = document.createElement('button');
      allBtn.type='button';
      allBtn.className='tfbtn mini';
      allBtn.textContent='All';
      allBtn.addEventListener('click', ()=>{
        setNseVisibleTfs(__NSE_TF_ALL);
        if(window.__nseOcLastJ) window.renderNseOcFromTrack(window.__nseOcLastJ, true);
      });
      const doneBtn = document.createElement('button');
      doneBtn.type='button';
      doneBtn.className='tfbtn mini active';
      doneBtn.textContent='Done';
      doneBtn.addEventListener('click', ()=> menu.classList.remove('open'));
      actions.appendChild(allBtn);
      actions.appendChild(doneBtn);
      pop.appendChild(actions);

      // close on outside click: one shared document listener (this builder runs on every
      // option-chain render; a listener per render leaked, each holding a detached menu)
      if(!window.__nseTfMenuCloser){
        window.__nseTfMenuCloser = true;
        document.addEventListener('click', (ev)=>{
          document.querySelectorAll('.nse-tf-menu.open').forEach(m=>{
            if(!m.contains(ev.target)) m.classList.remove('open');
          });
        });
      }

      menu.appendChild(b);
      menu.appendChild(pop);
      wrap.appendChild(menu);
    })();

    mins.forEach(m=>wrap.appendChild(mkBtn(nseTfText(m), Number(m))));
    wrap.appendChild(mkBtn('Day','DAY'));

}


// ===== NSE OC size toggle (compact / normal / large) + Condensed + Auto-Compact =====
let __nseOcSize = (function(){
  try { return localStorage.getItem('nseOcSize') || 'normal'; } catch(e){ return 'normal'; }
})();

let __nseOcSizeUserSet = (function(){
  try { return localStorage.getItem('nseOcSizeUserSet') === '1'; } catch(e){ return false; }
})();

let __nseOcCondensed = (function(){
  try { return localStorage.getItem('nseOcCondensed') === '1'; } catch(e){ return false; }
})();

// ===== NSE OC: show percentages inline for timeframe delta columns (not for DAY ΔOI) =====
let __nseOcShowPct = (function(){
  try {
    const v = localStorage.getItem('nseOcShowPct');
    if(v === null) return true; // default ON
    return v === '1';
  } catch(e){
    return true;
  }
})();

function buildNsePctToggle(){
  const cb = document.getElementById('nseShowPct');
  if(!cb) return;

  cb.checked = !!__nseOcShowPct;

  // bind once
  if(cb.dataset.bound === '1') return;
  cb.dataset.bound = '1';

  cb.addEventListener('change', ()=>{
    __nseOcShowPct = !!cb.checked;
    try { localStorage.setItem('nseOcShowPct', __nseOcShowPct ? '1' : '0'); } catch(e){}

    // User requested: treat toggle as a refresh (re-render + reflow widths)
    // Prefer a full refresh so table boxes adjust to current values.
    const st = document.getElementById('nseOcStatus');
    if(st){ st.style.display='inline-block'; st.textContent = 'Refreshing…'; }

    if(typeof loadTrack === 'function'){
      // loadTrack() will fetch and then call renderNseOcFromTrack()
      try{ loadTrack(); }catch(e){
        if(window.__nseOcLastJ) window.renderNseOcFromTrack(window.__nseOcLastJ, true);
      }
    } else {
      if(window.__nseOcLastJ) window.renderNseOcFromTrack(window.__nseOcLastJ, true);
    }
  });
}

function applyNseOcSize(sz, source){

  __nseOcSize = (sz==='compact'||sz==='large') ? sz : 'normal';

  // If user clicked, lock auto switching
  if(source === 'user'){
    __nseOcSizeUserSet = true;
    try { localStorage.setItem('nseOcSizeUserSet', '1'); } catch(e){}
  }

  // Apply the SAME size class to BOTH tables
  const ids = ['nseOcWrap', 'oiTrackTable'];
  for(const id of ids){
    const el = document.getElementById(id);
    if(!el) continue;
    el.classList.remove('size-compact','size-normal','size-large');
    el.classList.add('size-' + __nseOcSize);
  }

  // Condensed only for NSE OC
  const nseWrap = document.getElementById('nseOcWrap');
  if(nseWrap){
    nseWrap.classList.toggle('condensed', !!__nseOcCondensed);
  }

  try { localStorage.setItem('nseOcSize', __nseOcSize); } catch(e){}
}


function buildNseSizeButtons(){
  const wrap = document.getElementById('nseOcSize');
  if(!wrap) return;

  wrap.innerHTML = '';

  const mkSize = (label, val)=>{
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'tfbtn mini' + ((__nseOcSize===val)?' active':'');
    b.textContent = label;
    b.addEventListener('click', ()=>{
      applyNseOcSize(val, 'user');
      buildNseSizeButtons(); // refresh active state
    });
    return b;
  };

  const mkToggle = (label, isOn, onClick)=>{
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'tfbtn mini' + (isOn ? ' active' : '');
    b.textContent = label;
    b.addEventListener('click', onClick);
    return b;
  };

  wrap.appendChild(mkSize('Compact','compact'));
  wrap.appendChild(mkSize('Normal','normal'));
  wrap.appendChild(mkSize('Large','large'));

  // Condensed: NSE-style tighter font (NSE only)
  wrap.appendChild(mkToggle('Condensed', __nseOcCondensed, ()=>{
    __nseOcCondensed = !__nseOcCondensed;
    try { localStorage.setItem('nseOcCondensed', __nseOcCondensed ? '1' : '0'); } catch(e){}
    const nseWrap = document.getElementById('nseOcWrap');
    if(nseWrap) nseWrap.classList.toggle('condensed', __nseOcCondensed);
    buildNseSizeButtons();
  }));

  // Apply current size (do not mark as user action)
  applyNseOcSize(__nseOcSize, 'auto');
}


function buildNseMetricButtons(){
    const wrap = document.getElementById('nseOcMetric');
    if(!wrap) return;
    wrap.innerHTML = '';

    const lbl = document.createElement('span');
    lbl.className = 'tflbl';
    lbl.textContent = 'Metric:';
    wrap.appendChild(lbl);

    const mk = (label,val)=>{
      const b=document.createElement('button');
      b.type='button';
      b.className='tfbtn' + ((String(val)===String(__nseOcMetric))?' active':'');
      b.textContent=label;
      b.addEventListener('click', ()=>{
        __nseOcMetric = val;
        if(window.__nseOcLastJ) window.renderNseOcFromTrack(window.__nseOcLastJ, true);
      });
      return b;
    };

    wrap.appendChild(mk('OI','OI'));
    wrap.appendChild(mk('VOL','VOL'));
  }


  function getDeltaForTf(sideSnap, tf, metric){
    if(!sideSnap) return null;
    const met = (metric || __nseOcMetric || 'OI');
    if(tf === 'DAY'){
      if(met === 'VOL') return (sideSnap.cur_vol_chg ?? null);
      return (sideSnap.cur_chg ?? null);
    }
    const m = Number(tf);
    if(!Number.isFinite(m)) return null;

    if(met === 'VOL'){
      const prevVol = sideSnap['vol_'+m+'m'];
      const curVol  = sideSnap.cur_vol;
      if(prevVol===null || prevVol===undefined) return null;
      if(curVol===null || curVol===undefined) return null;
      const d = Number(curVol) - Number(prevVol);
      return Number.isFinite(d) ? d : null;
    }

    const prevOi = sideSnap['oi_'+m+'m'];
    const curOi  = sideSnap.cur_oi;
    if(prevOi===null || prevOi===undefined) return null;
    if(curOi===null || curOi===undefined) return null;
    const d = Number(curOi) - Number(prevOi);
    return Number.isFinite(d) ? d : null;
  }

    function getPrevBaseForTf(sideSnap, tf, metric){
    if(!sideSnap) return null;
    const met = (metric || __nseOcMetric || 'OI');

    if(tf === 'DAY'){
      if(met === 'VOL'){
        const curVol = sideSnap.cur_vol;
        const chgVol = sideSnap.cur_vol_chg;
        if(curVol===null || curVol===undefined) return null;
        if(chgVol===null || chgVol===undefined) return null;
        const prev = Number(curVol) - Number(chgVol);
        return Number.isFinite(prev) ? prev : null;
      }
      const curOi = sideSnap.cur_oi;
      const chg  = sideSnap.cur_chg;
      if(curOi===null || curOi===undefined) return null;
      if(chg===null || chg===undefined) return null;
      const prev = Number(curOi) - Number(chg);
      return Number.isFinite(prev) ? prev : null;
    }

    const m = Number(tf);
    if(!Number.isFinite(m)) return null;

    if(met === 'VOL'){
      const prevVol = sideSnap['vol_'+m+'m'];
      if(prevVol===null || prevVol===undefined) return null;
      return Number(prevVol);
    }

    const prevOi = sideSnap['oi_'+m+'m'];
    if(prevOi===null || prevOi===undefined) return null;
    return Number(prevOi);
  }

    // Floating tooltip (fixes clipping issues inside overflow containers)
    (function(){
      let box = null;
      function ensure(){
        if(box) return;
        box = document.createElement("div");
        box.id = "miniTipBox";
        document.body.appendChild(box);
      }
      function place(x,y){
        if(!box) return;
        const pad = 14;
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        box.style.left = "0px";
        box.style.top  = "0px";
        const rect = box.getBoundingClientRect();
        let xx = x + pad;
        let yy = y + pad;
        if(xx + rect.width  + 10 > vw) xx = Math.max(10, x - rect.width - pad);
        if(yy + rect.height + 10 > vh) yy = Math.max(10, y - rect.height - pad);
        box.style.left = xx + "px";
        box.style.top  = yy + "px";
      }
      function show(text,x,y){
        ensure();
        if(!text) return;
        box.textContent = String(text);
        box.classList.add("show");
        box.style.display = "block";
        place(x,y);
      }
      function hide(){
        if(!box) return;
        box.classList.remove("show");
        box.style.display = "none";
      }

      document.addEventListener("mouseover", function(e){
        const el = e.target && e.target.closest ? e.target.closest("[data-tip]") : null;
        if(!el) return;
        const t = el.getAttribute("data-tip");
        if(!t) return;
        show(t, e.clientX, e.clientY);
      }, true);

      document.addEventListener("mousemove", function(e){
        if(!box || box.style.display !== "block") return;
        place(e.clientX, e.clientY);
      }, true);

      document.addEventListener("mouseout", function(e){
        const from = e.target && e.target.closest ? e.target.closest("[data-tip]") : null;
        if(from) hide();
      }, true);

      window.addEventListener("scroll", hide, true);
      window.addEventListener("blur", hide);
    })();

    // Delta cell renderer
    // - Shows ONLY the absolute delta value (no inline percentage) to avoid border overflow.
    // - Provides a hover tooltip with mini breakup: OI -> % -> volume impact.
    function fmtDeltaCell(v, prevOi, curOi, curVol, tf, showPctInline){
    if(v===null || v===undefined || !Number.isFinite(Number(v))){
      return `<span class="muted">—</span>`;
    }
    const n = Number(v);

    // Use the SAME ΔOI style toggle as OI Track (Option B: affects all TFs)
    const style = (typeof getOiDeltaStyle === 'function') ? getOiDeltaStyle() : 'arrows';
    const useArrows = (style === 'arrows');
    const arr = useArrows ? (n>0 ? '▲' : (n<0 ? '▼' : '•')) : '';
    const signTxt = (n>0 ? '+' : (n<0 ? '-' : ''));
    const arrCls = n>0 ? 'up' : (n<0 ? 'down' : 'flat');

    // % change (relative to previous OI)
    let pct = null;
    let prev = null;
    if(prevOi!==null && prevOi!==undefined){
      const p = Number(prevOi);
      if(Number.isFinite(p)){
        prev = p;
        if(p!==0) pct = (n / p) * 100;
      }
    }

    const oiNow = Number(curOi);
    const volNow = Number(curVol);

    // Volume impact heuristic: how big the delta is vs volume
    let volImpact = null;
    if(Number.isFinite(volNow) && volNow > 0){
      volImpact = (Math.abs(n) / volNow) * 100;
    }

    const tipParts = [];
    if(Number.isFinite(oiNow)) tipParts.push(`OI: ${Math.round(oiNow).toLocaleString('en-IN')}`);
    if(prev!==null && Number.isFinite(prev)) tipParts.push(`Prev: ${Math.round(prev).toLocaleString('en-IN')}`);
    tipParts.push(`Δ: ${ (n>0?'+':'') + Math.round(n).toLocaleString('en-IN') }`);
    if(pct!==null && Number.isFinite(pct)) tipParts.push(`%: ${Math.abs(pct).toFixed(1)}%`);
    if(Number.isFinite(volNow)) tipParts.push(`VOL: ${Math.round(volNow).toLocaleString('en-IN')}`);
    if(volImpact!==null && Number.isFinite(volImpact)) tipParts.push(`Impact: ${volImpact.toFixed(1)}% of VOL`);

    const tip = tipParts.join('  •  ');

    // Inline percent:
    // - Allowed only for timeframe cols (1m..30m) when user enables it
    // - Never for DAY ΔOI column (keeps table clean + prevents overflow)
    const allowInlinePct = (tf !== 'DAY') && !!showPctInline && (pct!==null) && Number.isFinite(pct);
    // When showing inline %, also allow hover tooltip (without dotted underline)
    const pctTxt = allowInlinePct
      ? ` <span class="pct muted tip tip-pct" data-tip="${tip}">(${(pct>0?'+':'')}${pct.toFixed(1)}%)</span>`
      : '';
    const wrapTip = (tf === "DAY") ? ` data-tip="${tip}"` : ``;

    // Show absolute number; tooltip holds the detailed breakup.
    return `<span class="dcell"${wrapTip}><span class="arr ${arrCls} tip" data-tip="${tip}">${arr}</span><span class="num">${useArrows ? fmt(Math.abs(n)) : (signTxt + fmt(Math.abs(n)))}</span>${pctTxt}</span>`;
  }

  function fmtLakhCr(x){
    const n = Number(x);
    if(!Number.isFinite(n)) return '—';
    const a = Math.abs(n);
    const signArr = n>0 ? '▲' : (n<0 ? '▼' : '•');
    // contracts → display as K / L / Cr like broker UIs
    let s;
    if(a >= 1e7) s = (a/1e7).toFixed(2).replace(/\.00$/,'') + 'Cr';
    else if(a >= 1e5) s = (a/1e5).toFixed(2).replace(/\.00$/,'') + 'L';
    else if(a >= 1e3) s = (a/1e3).toFixed(2).replace(/\.00$/,'') + 'K';
    else s = fmt(a);
    return `${signArr} ${s}`;
  }

  function fmtISTTime12(ts){
    if(!ts) return '';
    const d = new Date(String(ts).replace(' ','T') + 'Z');
    return d.toLocaleTimeString('en-IN',{hour:'numeric',minute:'2-digit',hour12:true,timeZone:'Asia/Kolkata'});
  }

  function updateNseOcSummary(j, rows){
    const sumWrap = document.getElementById('nseOcSum');
    const elCall = document.getElementById('nseSumCall');
    const elPut  = document.getElementById('nseSumPut');
    const elPx   = document.getElementById('nseSumPrice');
    const elLbl  = document.getElementById('nseSumLabel');
    const elImb  = document.getElementById('nseSumImb');
    const elCallLbl = document.getElementById('nseSumCallLbl');
    const elPutLbl  = document.getElementById('nseSumPutLbl');
    if(!sumWrap || !elCall || !elPut || !elPx || !elLbl) return;

    const isVol = (__nseOcMetric === 'VOL');
    if(elCallLbl) elCallLbl.textContent = isVol ? 'Call VOL change' : 'Call OI change';
    if(elPutLbl)  elPutLbl.textContent  = isVol ? 'Put VOL change'  : 'Put OI change';

    // totals across displayed strikes
    let ceTot = 0, peTot = 0;
    rows.forEach(r=>{
      const ce = getDeltaForTf(r?.CE, __nseOcTf, __nseOcMetric);
      const pe = getDeltaForTf(r?.PE, __nseOcTf, __nseOcMetric);
      if(Number.isFinite(Number(ce))) ceTot += Number(ce);
      if(Number.isFinite(Number(pe))) peTot += Number(pe);
    });

    elCall.textContent = fmtLakhCr(ceTot);
    elPut.textContent  = fmtLakhCr(peTot);

    // Imbalance % (PE vs CE dominance)
    if(elImb){
      const denom = Math.abs(ceTot) + Math.abs(peTot);
      const imb = denom>0 ? ((peTot - ceTot) / denom) * 100 : 0;
      const signArr = imb>0 ? 'PE↑' : (imb<0 ? 'CE↑' : '•');
      elImb.textContent = `${signArr} ${Math.abs(imb).toFixed(1)}%`;
    }

    // Price: show past → current for selected TF (and points moved) using /oi/track underlying history
    const sym = (j?.symbol || (window.lastJson?.symbol) || 'NIFTY');

    const pxNow  = (j?.underlying_now!=null) ? Number(j.underlying_now)
                 : ((window.lastJson && window.lastJson.price!=null) ? Number(window.lastJson.price) : null);

    const tsNow  = (j?.latestTs || (window.lastJson?.ts) || null);
    const tNow12 = fmtISTTime12(tsNow);

    let pxPrev = null;
    let tsPrev = null;
    let tfLabel = '';

    if(__nseOcTf === 'DAY'){
      pxPrev = (j?.underlying_day_open!=null) ? Number(j.underlying_day_open) : null;
      tsPrev = (j?.underlying_day_open_ts || null);
      tfLabel = 'Full Day';
    } else {
      const m = Number(__nseOcTf);
      const u = (j?.underlying_prev && j.underlying_prev[m]) ? j.underlying_prev[m] : null;
      pxPrev = (u && u.price!=null) ? Number(u.price) : null;
      tsPrev = (u && u.ts) ? u.ts : null;
      tfLabel = 'Last ' + nseTfLabel(m);
    }

    const tPrev12 = fmtISTTime12(tsPrev);

    let moveTxt = '';
    if(pxNow!=null && Number.isFinite(pxNow) && pxPrev!=null && Number.isFinite(pxPrev)){
      const pts = pxNow - pxPrev;
      const arr = pts>0 ? '▲' : (pts<0 ? '▼' : '•');
      moveTxt = ` &nbsp; <span class="muted">(${arr} ${Math.abs(pts).toFixed(2)} pts)</span>`;
    }

    if(pxNow!=null && Number.isFinite(pxNow) && pxPrev!=null && Number.isFinite(pxPrev)){
      elPx.innerHTML = `<b>${sym}</b> <span class="mono"><b>${pxPrev.toFixed(2)}</b> → <b>${pxNow.toFixed(2)}</b></span> <span class="muted">(${tfLabel})</span>${moveTxt}`
                     + `<div class="muted" style="font-size:12px;margin-top:2px">${tPrev12||'—'} → ${tNow12||'—'}</div>`;
    } else if(pxNow!=null && Number.isFinite(pxNow)){
      elPx.innerHTML = `<b>${sym}</b> at <b>${tNow12||'—'}</b> &nbsp; <b class="mono">${pxNow.toFixed(2)}</b>`;
    } else {
      elPx.textContent = '—';
    }

    const tfLab2 = (__nseOcTf==='DAY') ? 'Full Day' : ('Last ' + nseTfLabel(Number(__nseOcTf)));
    elLbl.textContent = (isVol ? 'Volume · ' : 'OI · ') + tfLab2;

    sumWrap.style.display = 'flex';
  }


  // Ensure NSE header price/time block stays in sync with auto-refresh.
  // We keep latest track JSON + NSE rows and can re-run summary render safely.
  window.__lastTrackJson = window.__lastTrackJson || null;
  window.__lastNseRows   = window.__lastNseRows   || null;

  window.updateNseHeaderContext = function(){
    try{
      if (window.__lastTrackJson && window.__lastNseRows && typeof updateNseOcSummary === 'function'){
        updateNseOcSummary(window.__lastTrackJson, window.__lastNseRows);
      }
    }catch(e){}
  };



  window.renderNseOcFromTrack = function(j, _rerenderOnly){

    // Accept either full track JSON or NSE-OC payload
    try{
      if(j && !j.rows){
        const maybe = j.nse_oc || j.nse || j.nseOc || j.nse_oc_json || j.nseOcJson;
        if(maybe && maybe.rows) j = maybe;
      }
    }catch(e){}


    // JS html-escape helper (avoid PHP esc() in JS)
    const esc = (v)=>{
      const s = (v===null || v===undefined) ? '' : String(v);
      return s.replace(/[&<>"']/g, (ch)=>({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
      }[ch]));
    };

    // LTP renderer: show ONLY current LTP + arrow.
    // No brackets. Change value is shown ONLY as tooltip on the arrow.
    // Enhancements:
    //   - Fade arrows for non-ATM strikes
    //   - Blink arrow on sudden ΔLTP
    //   - Tooltip combines ΔLTP + build-up classification (Option-B)
    function buildupLabel(side, chgLtp, chgOi){
      const dl = Number(chgLtp);
      const doii = Number(chgOi);
      if(!Number.isFinite(dl) || !Number.isFinite(doii) || dl===0 || doii===0) return '';

      // Option-B mapping (simple + readable)
      if(side === 'CE'){
        if(dl < 0 && doii > 0) return 'Call Writing';
        if(dl > 0 && doii < 0) return 'Short Covering';
        if(dl > 0 && doii > 0) return 'Long Build-up';
        if(dl < 0 && doii < 0) return 'Long Unwinding';
      } else { // PE
        if(dl < 0 && doii > 0) return 'Put Writing';
        if(dl > 0 && doii < 0) return 'Long Unwinding';
        if(dl > 0 && doii > 0) return 'Long Build-up';
        if(dl < 0 && doii < 0) return 'Short Covering';
      }
      return '';
    }

    function renderLtpWithChange(ltp, chgLtp, chgOi, isATM, side){
      const x = Number(ltp);
      if(!Number.isFinite(x) || x===0) return '<span class="muted">—</span>';

      const d = Number(chgLtp);
      if(!Number.isFinite(d) || d===0){
        return `<span class="dcell"><span class="num">${x.toFixed(2)}</span></span>`;
      }

      const isUp = d > 0;
      const arrSym = isUp ? '▲' : '▼';
      const sign = isUp ? '+' : '';

      // Sudden ΔLTP threshold (dynamic, avoids over-blink on low prices)
      const spikeTh = Math.max(5, x * 0.03); // 3% or 5 points
      const isSpike = Math.abs(d) >= spikeTh;

      const parts = [];
      const bu = buildupLabel(side, d, chgOi);
      if(bu) parts.push(bu);
      parts.push(`ΔLTP: ${sign}${d.toFixed(2)}`);

      const cls = [
        'ltp-arrow',
        isUp ? 'ltp-up' : 'ltp-down',
        (isATM ? '' : 'faded'),
        (isSpike ? 'blink' : '')
      ].filter(Boolean).join(' ');

      return `<span class="dcell"><span class="num">${x.toFixed(2)}</span><span class="${cls}" title="${esc(parts.join(' · '))}">${arrSym}</span></span>`;
    }

    // ===== OI Absorption Detector =====
    // Heuristic:
    //  - Big DAY ΔOI (relative to session max)
    //  - High current VOL (relative to session max)
    //  - Small ΔLTP (price "stuck" while activity is high)
    // Shows a small 🧲 badge on the DAY ΔOI cell with tooltip.
    function detectAbsorption(side, strike, dayDeltaOi, chgLtp, ltp, vol, maxVol, maxAbsDayDelta){
      const d = Number(dayDeltaOi);
      const dl = Number(chgLtp);
      const px = Number(ltp);
      const vv = Number(vol);
      const mv = Number(maxVol);
      const md = Number(maxAbsDayDelta);

      if(!Number.isFinite(d) || !Number.isFinite(vv) || !Number.isFinite(mv) || mv<=0 || !Number.isFinite(md) || md<=0) return null;

      // stability threshold: allow tiny wiggles
      const thLtp = (Number.isFinite(px) && px>0)
        ? Math.max(0.15, Math.min(1.25, px * 0.006))  // 0.6% of premium (clamped)
        : 0.35;
      const isStable = (Number.isFinite(dl) ? Math.abs(dl) <= thLtp : true);
      if(!isStable) return null;

      const dRatio = Math.abs(d) / md;
      const vRatio = vv / mv;

      let level = null;
      if(dRatio >= 0.70 && vRatio >= 0.60) level = 'strong';
      else if(dRatio >= 0.55 && vRatio >= 0.45) level = 'med';
      else return null;

      const stTxt = (strike!=null && Number.isFinite(Number(strike))) ? `@ ${Number(strike)}` : '';
      const bu = buildupLabel(side, dl, d);
      const tip = [
        `Absorption ${level==='strong'?'(Strong)':'(Medium)'} ${side} ${stTxt}`.trim(),
        bu ? bu : null,
        `DAY ΔOI: ${(d>0?'+':'') + Math.round(d).toLocaleString('en-IN')}`,
        Number.isFinite(dl) ? `ΔLTP: ${(dl>0?'+':'') + dl.toFixed(2)} (≤ ${thLtp.toFixed(2)} stable)` : `ΔLTP: —`,
        `VOL: ${Math.round(vv).toLocaleString('en-IN')} (${(vRatio*100).toFixed(0)}% of max)`
      ].filter(Boolean).join('  •  ');

      return { level, tip };
    }

    const absBadgeHTML = (info)=>{
      if(!info) return '';
      return `<span class="abs-badge ${info.level}" data-tip="${esc(info.tip)}">🧲</span>`;
    };
    const statusEl = document.getElementById('nseOcStatus');
    const bodyEl = document.getElementById('nseOcBody');
    const headEl = document.getElementById('nseOcHead');
    // If called before DOM is ready, keep payload pending and retry shortly
    if(!statusEl || !bodyEl || !headEl){
      try{ window.__nseOcPending = j || window.__nseOcPending; }catch(e){}
      try{
        clearTimeout(window.__nseOcRetryT);
        window.__nseOcRetryT = setTimeout(function(){
          try{ if(window.__nseOcPending) window.renderNseOcFromTrack(window.__nseOcPending); }catch(e){}
        }, 50);
      }catch(e){}
      return;
    }

    // clear pending once DOM is ready and we can render
    try{ window.__nseOcPending = null; }catch(e){}

    // remember last payload for highlight re-render
    if(j) window.__nseOcLastJ = j;

    // build timeframe info + highlight selector
    buildNseTfButtons(j && j.lookbacks);
    buildNseMetricButtons();
    buildNseSizeButtons();
    buildNsePctToggle();

    if(!j || !j.ok || !Array.isArray(j.rows) || !j.rows.length){
      statusEl.textContent = 'No data';
      headEl.innerHTML = '';
      bodyEl.innerHTML = `<tr><td colspan="7" class="muted" style="text-align:center;padding:14px">No data</td></tr>`;
      document.getElementById('nseMaxPain') && (document.getElementById('nseMaxPain').textContent = '—');
      const sw=document.getElementById('nseOcSum'); if(sw) sw.style.display='none';
      return;
    }

    const lookbacksRaw = (Array.isArray(j.lookbacks) && j.lookbacks.length)
      ? j.lookbacks.slice()
      : [1,3,5,10,15,30,60,120,180];
    // Normalize lookbacks, then apply user's visible timeframes (column priority)
    let lookAsc = Array.from(new Set(lookbacksRaw.map(Number).filter(Number.isFinite)));
    lookAsc = lookAsc.sort((a,b)=>a-b);

    // Intersect with user's chosen list (1,3,5,10,15,30). If empty, fall back.
    const visSet = new Set(getNseVisibleTfs());
    const filtered = lookAsc.filter(m => visSet.has(m));
    if(filtered.length) lookAsc = filtered;

    // NSE-style requested order:
    // CALLS:  Vol | 30m..1m | Change | OI | STRIKE
    // PUTS :  STRIKE | OI | Change | 1m..30m | Vol
    // (Day remains available via the "Change" column when TF=Day)
    const colsCalls = [...lookAsc].sort((a,b)=>b-a); // 30,15,10,5,3,1
    const colsPuts  = lookAsc;                        // 1,3,5,10,15,30
    const callSpan = 1 + colsCalls.length + 2 + 1; // Vol + TF cols + (Core + Change) + LTP // Vol + TF cols + (Core + Change)
    const putSpan  = 2 + colsPuts.length + 1 + 1; // (Core + Change) + TF cols + Vol + LTP // (Core + Change) + TF cols + Vol

    // For status / helper UI (keep Day in the list)
    // ensure highlight TF is valid
    const _validTfs = new Set([...colsCalls, ...colsPuts]);
    if(__nseOcTf !== 'DAY' && !_validTfs.has(Number(__nseOcTf))){
      __nseOcTf = 'DAY';
    }

    statusEl.textContent = ''; statusEl.style.display='none';

    const rows = j.rows.slice(); // keep order
    const atmStrike = getAtmStrike(j);
    const spotNow = Number(j?.underlying_now ?? window.lastJson?.price ?? NaN);

    // Max pain (expiry PCR and totals are shown once, in the top dock)
    const mp  = (j.max_pain != null && Number.isFinite(Number(j.max_pain))) ? Number(j.max_pain) : computeMaxPain(rows); // server: full chain
    const mpEl  = document.getElementById('nseMaxPain'); if(mpEl) mpEl.textContent = (mp===null? '—' : fmt(mp));

    // Summary bar (timeframe click)
    updateNseOcSummary(j, rows);


    // max highlights (OI always, CHG for selected highlight TF only)
    let maxCeOi=-Infinity, maxPeOi=-Infinity, maxCeAbsChg=-Infinity, maxPeAbsChg=-Infinity;
    let maxCeVol=-Infinity, maxPeVol=-Infinity;
    rows.forEach(r=>{
      const ceVal = (__nseOcMetric==='VOL') ? Number(r?.CE?.cur_vol) : Number(r?.CE?.cur_oi);
      if(Number.isFinite(ceVal)) maxCeOi=Math.max(maxCeOi, ceVal);
      const peVal = (__nseOcMetric==='VOL') ? Number(r?.PE?.cur_vol) : Number(r?.PE?.cur_oi);
      const ceVolV = Number(r?.CE?.cur_vol); if(Number.isFinite(ceVolV)) maxCeVol=Math.max(maxCeVol, ceVolV);
      const peVolV = Number(r?.PE?.cur_vol); if(Number.isFinite(peVolV)) maxPeVol=Math.max(maxPeVol, peVolV);
      if(Number.isFinite(peVal)) maxPeOi=Math.max(maxPeOi, peVal);
      const ceV = Number(getDeltaForTf(r?.CE, __nseOcTf, __nseOcMetric));
      const peV = Number(getDeltaForTf(r?.PE, __nseOcTf, __nseOcMetric));
      const ceChg=Math.abs(ceV); if(Number.isFinite(ceChg)) maxCeAbsChg=Math.max(maxCeAbsChg, ceChg);
      const peChg=Math.abs(peV); if(Number.isFinite(peChg)) maxPeAbsChg=Math.max(maxPeAbsChg, peChg);
  });

// Core Δ (for the central Change column): ALWAYS DAY ΔOI (independent of metric / selected TF)
    // This keeps the "Change OI" column stable and prevents blanks when Metric=VOL.
    let maxCeAbsCore = 0, maxPeAbsCore = 0;
    let maxCeCore = {abs:0, val:0, strike:null};
    let maxPeCore = {abs:0, val:0, strike:null};
    rows.forEach(r=>{
      // Max Δ CE/PE should be based on DAY ΔOI (stable) even if metric is VOL.
      const ceV = Number(getDeltaForTf(r?.CE, 'DAY', 'OI'));
      const peV = Number(getDeltaForTf(r?.PE, 'DAY', 'OI'));
      const ceA = Math.abs(ceV); if(Number.isFinite(ceA)) maxCeAbsCore = Math.max(maxCeAbsCore, ceA);
      const peA = Math.abs(peV); if(Number.isFinite(peA)) maxPeAbsCore = Math.max(maxPeAbsCore, peA);

      const st = Number(r?.strike);
      if(Number.isFinite(ceA) && ceA >= maxCeCore.abs){
        maxCeCore = {abs:ceA, val:ceV, strike: (Number.isFinite(st)? st : null)};
      }
      if(Number.isFinite(peA) && peA >= maxPeCore.abs){
        maxPeCore = {abs:peA, val:peV, strike: (Number.isFinite(st)? st : null)};
      }
    });

    // Render MAX Δ CE / MAX Δ PE chips (stable, always DAY ΔOI)
    const maxCeEl = document.getElementById('nseMaxDeltaCe');
    const maxPeEl = document.getElementById('nseMaxDeltaPe');
    const fmtSigned = (v)=>{
      const n = Number(v);
      if(!Number.isFinite(n) || n===0) return '0';
      return (n>0?'+':'') + Math.round(n).toLocaleString('en-IN');
    };
    if(maxCeEl){
      maxCeEl.textContent = (maxCeCore.strike!==null)
        ? `${fmtSigned(maxCeCore.val)} @ ${maxCeCore.strike}`
        : '—';
    }
    if(maxPeEl){
      maxPeEl.textContent = (maxPeCore.strike!==null)
        ? `${fmtSigned(maxPeCore.val)} @ ${maxPeCore.strike}`
        : '—';
    }

    const _metLbl = (__nseOcMetric==='VOL') ? 'VOL' : 'ΔOI';

const thColsCalls = colsCalls.map((c,i)=>{
      const label = _metLbl + ' (' + String(c).toUpperCase() + 'M)';
      const hi = (String(c)===String(__nseOcTf)) ? ' th-hi' : '';
      const sep = (i === colsCalls.length-1) ? ' sep-right' : '';
      return `<th class="${hi}${sep}">${esc(label)}</th>`;
    }).join('');

    const thColsPuts = colsPuts.map((c,i)=>{
      const label = _metLbl + ' (' + String(c).toUpperCase() + 'M)';
      const hi = (String(c)===String(__nseOcTf)) ? ' th-hi' : '';
      const sep = (i === 0) ? ' sep-left' : '';
      return `<th class="${hi}${sep}">${esc(label)}</th>`;
    }).join('');

headEl.innerHTML =
      `<tr>
        <th colspan="${callSpan}" class="call-head">CALLS</th>
        <th class="strike-head sticky-strike">STRIKE</th>
        <th colspan="${putSpan}" class="put-head">PUTS</th>
      </tr>
	      <tr>
	        <th>Vol</th>
	        <th class="ltp-h">LTP</th>
        ${thColsCalls}
        <th class="core-chg col-delta-day">${(__nseOcMetric==='VOL') ? 'ΔVOL (DAY)' : 'ΔOI (DAY)'}</th>
        <th class="core-oi">${(__nseOcMetric==='VOL') ? 'VOL' : 'OI'}</th>

        <th class="strike-head2 sticky-strike">Strike</th>

        <th>${(__nseOcMetric==='VOL') ? 'VOL' : 'OI'}</th>
        <th class="core-chg col-delta-day">${(__nseOcMetric==='VOL') ? 'ΔVOL (DAY)' : 'ΔOI (DAY)'}</th>
        ${thColsPuts}
	        <th class="ltp-h">LTP</th>
	        <th>Vol</th>
      </tr>`;

    // ===== Build TBODY =====
    const html = [];
    rows.forEach(r=>{
      const st = Number(r?.strike);
      const hint = (j && (j.row_hints || j.rowHints)) ? ((j.row_hints && j.row_hints[String(st)]) || (j.rowHints && j.rowHints[String(st)]) || '') : '';
      // Strike signal: replace BUY/SELL text with compact arrows (no extra columns / no scroll)
      // ▲ = BUY, ▼ = SELL (keep tooltip via title)
      const hintBadge = (hint==='BUY')
        ? `<span class="oc-hint oc-hint-buy" title="BUY">▲</span>`
        : (hint==='SELL')
          ? `<span class="oc-hint oc-hint-sell" title="SELL">▼</span>`
          : '';
      const callItm = Number.isFinite(spotNow) && Number.isFinite(st) && st < spotNow;
      const putItm  = Number.isFinite(spotNow) && Number.isFinite(st) && st > spotNow;
      const CE = r?.CE || {};
      const PE = r?.PE || {};

      const ceOi  = (CE.cur_oi ?? null);
      const ceVol = (CE.cur_vol ?? null);

      const peOi  = (PE.cur_oi ?? null);
      const peVol = (PE.cur_vol ?? null);

      const ceOiCls  = (Number((__nseOcMetric==='VOL')?ceVol:ceOi)===maxCeOi) ? 'max-oi' : '';
      const ceItmCls = callItm ? 'itm-call' : '';
      const peOiCls  = (Number((__nseOcMetric==='VOL')?peVol:peOi)===maxPeOi) ? 'max-oi' : '';
      const peItmCls = putItm ? 'itm-put' : '';

      const strikeCls = (atmStrike!==null && Number.isFinite(st) && Number(st)===Number(atmStrike))
        ? 'strike atm sticky-strike'
        : 'strike sticky-strike';

      const isATMRow = (atmStrike!==null && Number.isFinite(st) && Number(st)===Number(atmStrike));
      const rowCls = isATMRow ? 'atm-row' : '';
      const rowStyle = isATMRow ? 'outline:2px solid rgba(59,130,246,.55);outline-offset:-2px;box-shadow:inset 0 0 0 9999px rgba(59,130,246,.08);' : '';

      const __nseSpikeThr = (function(){
        const w = document.getElementById('nseOcWrap') || document.documentElement;
        const v = Number(getComputedStyle(w).getPropertyValue('--nse-oc-spike-thresh'));
        return Number.isFinite(v) && v>0 && v<1 ? v : 0.85;
      })();
      const volSpikeCls = (v, maxV)=>{
        const vv = Number(v);
        const mm = Number(maxV);
        if(!Number.isFinite(vv) || !Number.isFinite(mm) || mm<=0) return '';
        if(vv >= mm) return ' vol-max';
        if(vv >= (mm * __nseSpikeThr)) return ' vol-spike';
        return '';
      };


      const ceTfCells = colsCalls.map((c,i)=>{
        const hi  = (String(c)===String(__nseOcTf)) ? ' col-hi' : '';
        const sep = (i === colsCalls.length-1) ? ' sep-right' : '';
        // Metric-driven: OI shows ΔOI(tf), VOL shows VOL value (no blanks)
        if(__nseOcMetric === 'VOL'){
          const vv = (CE?.cur_vol ?? null);
          return `<td class="volcell${volSpikeCls(vv, maxCeVol)} ${hi}${sep} ${ceItmCls}">${(vv===null||vv===undefined)?'<span class="muted">—</span>':fmt(vv)}</td>`;
        }
        const v = getDeltaForTf(CE, c, __nseOcMetric);
        const cls = ''; // keep numbers white; arrow shows direction
        return `<td class="${cls}${hi}${sep} ${ceItmCls}">${fmtDeltaCell(v, getPrevBaseForTf(CE, c, __nseOcMetric), CE?.cur_oi, CE?.cur_vol, c, __nseOcShowPct)}</td>`;
      }).join('');

      const peTfCells = colsPuts.map(c=>{
        const hi  = (String(c)===String(__nseOcTf)) ? ' col-hi' : '';
        // Metric-driven: OI shows ΔOI(tf), VOL shows VOL value (no blanks)
        if(__nseOcMetric === 'VOL'){
          const vv = (PE?.cur_vol ?? null);
          return `<td class="${hi} ${peItmCls}">${(vv===null||vv===undefined)?'<span class="muted">—</span>':fmt(vv)}</td>`;
        }
        const v = getDeltaForTf(PE, c, __nseOcMetric);
        const cls = ''; // keep numbers white; arrow shows direction
        return `<td class="${cls}${hi} ${peItmCls}">${fmtDeltaCell(v, getPrevBaseForTf(PE, c, __nseOcMetric), PE?.cur_oi, PE?.cur_vol, c, __nseOcShowPct)}</td>`;
      }).join('');

      // "Change" column: ALWAYS DAY ΔOI (not affected by Metric/TF)
      const ceSelV = getDeltaForTf(CE, 'DAY', __nseOcMetric);
      const peSelV = getDeltaForTf(PE, 'DAY', __nseOcMetric);

      // OI Absorption badge (computed from DAY ΔOI + VOL + ΔLTP stability)
      const ceAbsInfo = detectAbsorption('CE', st, ceSelV, CE?.chg_ltp, CE?.cur_ltp, CE?.cur_vol, maxCeVol, maxCeAbsCore);
      const peAbsInfo = detectAbsorption('PE', st, peSelV, PE?.chg_ltp, PE?.cur_ltp, PE?.cur_vol, maxPeVol, maxPeAbsCore);
      const ceAbsBadge = absBadgeHTML(ceAbsInfo);
      const peAbsBadge = absBadgeHTML(peAbsInfo);

      const ceSelCls = (ceSelV===null||ceSelV===undefined) ? '' : (Number(ceSelV)>=0 ? 'pos':'neg');
      const peSelCls = (peSelV===null||peSelV===undefined) ? '' : (Number(peSelV)>=0 ? 'pos':'neg');

      const ceSelHi  = (Math.abs(Number(ceSelV))===maxCeAbsCore) ? 'max-chg' : '';
      const peSelHi  = (Math.abs(Number(peSelV))===maxPeAbsCore) ? 'max-chg' : '';

      const ceSelCell = `<td class="core-chg col-delta-day ${ceSelCls} ${ceSelHi} col-hi ${ceItmCls}">${fmtDeltaCell(ceSelV, getPrevBaseForTf(CE, 'DAY', 'OI'), CE?.cur_oi, CE?.cur_vol, 'DAY', false)}${ceAbsBadge}</td>`;
      const peSelCell = `<td class="core-chg col-delta-day ${peSelCls} ${peSelHi} col-hi ${peItmCls}">${fmtDeltaCell(peSelV, getPrevBaseForTf(PE, 'DAY', 'OI'), PE?.cur_oi, PE?.cur_vol, 'DAY', false)}${peAbsBadge}</td>`;

      html.push(
	        `<tr class="${rowCls}" style="${rowStyle}">
	          <td class="volcell${volSpikeCls(ceVol, maxCeVol)} ${ceItmCls}">${fmt(ceVol)}</td>
	          <td class="ltp-col ${ceItmCls}">${renderLtpWithChange(CE?.cur_ltp, CE?.chg_ltp, CE?.cur_chg, isATMRow, 'CE')}</td>
          ${ceTfCells}
          ${ceSelCell}
          <td class="core-oi ${ceOiCls} ${ceItmCls}${(__nseOcMetric==='VOL'?volSpikeCls(ceVol, maxCeVol):'')}">${fmt(__nseOcMetric==='VOL'?ceVol:ceOi)}</td>

          <td class="${strikeCls}">${fmt(st)} ${hintBadge}</td>

          <td class="core-oi ${peOiCls} ${peItmCls}${(__nseOcMetric==='VOL'?volSpikeCls(peVol, maxPeVol):'')}">${fmt(__nseOcMetric==='VOL'?peVol:peOi)}</td>
          ${peSelCell}
          ${peTfCells}
          <td class="ltp-col ${peItmCls}">${renderLtpWithChange(PE?.cur_ltp, PE?.chg_ltp, PE?.cur_chg, isATMRow, 'PE')}</td>
          <td class="volcell${volSpikeCls(peVol, maxPeVol)} ${peItmCls}">${fmt(peVol)}</td>
        </tr>`
      );
    });

    const totalCols = 5 + colsCalls.length + colsPuts.length;
    bodyEl.innerHTML = html.join('') || `<tr><td colspan="${totalCols}" class="muted" style="text-align:center;padding:14px">No rows</td></tr>`;
    
    // ===== Auto-switch to Compact when strikes/rows exceed threshold (unless user locked size)
    const AUTO_COMPACT_ROWS = 28; // <-- change this X anytime
    try{
      const rowCount = bodyEl.querySelectorAll('tr').length;
      if(!__nseOcSizeUserSet && rowCount > AUTO_COMPACT_ROWS){
        applyNseOcSize('compact', 'auto');
        // refresh size buttons active state
        buildNseSizeButtons();
      }
    }catch(e){}
// ===== Totals: expiry totals live in the top dock (renderBeautify); nothing in this header =====
    // 3) Remove totals from table footer (keep footer empty)
    const foot = document.getElementById('nseOcFoot');
    if(foot) foot.innerHTML = '';

    try{ applyAtmRowHighlights(atmStrike); }catch(e){}
  };
  // ---- If loadTrack() finished before this renderer existed, render the payload the placeholder kept ----
  if (window.__nseOcPending) {
    try { window.renderNseOcFromTrack(window.__nseOcPending); } catch(e) {}
    window.__nseOcPending = null;
  }
})();

</script>


<script>
(function(){
  function fmtINR(n){
    n = Number(n);
    if(!Number.isFinite(n)) return '—';
    const s = Math.round(n).toString();
    const last3 = s.slice(-3);
    const other = s.slice(0, -3);
    return (other ? other.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + "," : "") + last3;
  }

  function setDockTop(){
    const sb = document.querySelector('.status-bar');
    const h = sb ? Math.ceil(sb.getBoundingClientRect().height) : 0;
    const top = (h ? (h + 8) : 54);
    document.documentElement.style.setProperty('--oi-dock-top', top + 'px');
  }

  // rAF throttle so we don't spam DOM updates during metric toggles / rerenders
  let __beautifyPending = false;
  function scheduleBeautify(){
    if(__beautifyPending) return;
    __beautifyPending = true;
    requestAnimationFrame(()=>{
      __beautifyPending = false;
      try{ setDockTop(); }catch(e){}
      try{ renderBeautify(); }catch(e){}
    });
  }

  function pcrTone(p){
    if(!Number.isFinite(p)) return '';
    if(p > 1.10) return 'oiPcrBull';
    if(p < 0.80) return 'oiPcrBear';
    return '';
  }
  function pcrGlow(p){
    // 🔔 glow thresholds
    if(!Number.isFinite(p)) return '';
    return (p > 1.20 || p < 0.70) ? ' oiPcrGlow' : '';
  }
  function buildChip(label, ce, pe, pcr){
    ce = Number(ce); pe = Number(pe); pcr = Number(pcr);
    const denom = (Number.isFinite(ce) && Number.isFinite(pe)) ? (ce+pe) : 0;
    const pePct = denom ? Math.max(0, Math.min(1, pe/denom)) : 0.5;
    const barW = Math.round(pePct*100);
    const cls = pcrTone(pcr) + pcrGlow(pcr);
    return `
      <span class="oiTotChip ${cls}">
        <span class="k">${label}</span>
        <span class="v ce">CE ${fmtINR(ce)}</span>
        <span class="v pe">PE ${fmtINR(pe)}</span>
        <span class="oiDomBar" title="PE dominance (PE/(CE+PE))"><i style="width:${barW}%"></i></span>
        <span class="pcrPill" title="PCR = PE/CE">PCR ${Number.isFinite(pcr) ? pcr.toFixed(2) : '—'}</span>
      </span>
    `;
  }


  // ATM CE/PE current OI for the dock / Quick Stats chip, from the /oi/track payload.
  // (Scraping the OI Track table counted only "OI (Nm)" headers, so once hour columns such as
  // "OI (1hr)" were shown it read an older lookback column instead of Current OI.)
  function getAtmFromTrackTable(atm){
    const j = window.__lastTrackJson;
    const rows = (j && Array.isArray(j.rows)) ? j.rows : [];
    const atmN = Number(atm);
    if(!rows.length || !Number.isFinite(atmN)) return null;

    const r = rows.find(x => Number(x && x.strike) === atmN);
    if(!r) return null;

    const pick = (leg)=> (leg && leg.cur_oi != null)
      ? { oi: Number(leg.cur_oi), curDelta: Number(leg.cur_chg), d5: Number(leg.chg_5m) }
      : null;
    const out = { ce: pick(r.CE), pe: pick(r.PE) };
    return (out.ce || out.pe) ? out : null;
  }

  function buildAtmChip(atm, atmData){
    const ce = atmData?.ce?.oi;
    const pe = atmData?.pe?.oi;
    const pcr = (Number.isFinite(ce) && ce>0 && Number.isFinite(pe)) ? (pe/ce) : NaN;

    const fmt = (n)=> Number.isFinite(n) ? Math.round(n).toLocaleString('en-IN') : '—';

    // Compact chip: match Σ Expiry styling
    const cls = pcrTone(pcr) + pcrGlow(pcr);
    return `
      <span class="oiTotChip ${cls} oiDockChip">
        <span class="k">ATM</span><span class="v">${Number.isFinite(atm)?String(atm):'—'}</span>
        <span class="k">CE</span><span class="v">${fmt(ce)}</span>
        <span class="k">PE</span><span class="v">${fmt(pe)}</span>
        <span class="k">PCR</span><span class="v">${Number.isFinite(pcr)?pcr.toFixed(2):'—'}</span>
      </span>
    `;
  }





  
  function highlightNseAtmRow(j){
    // ✅ NSE OC: stable FULL-row ATM highlight + ±1/±2 bands + max OI cells + ATM-change animation + auto-scroll
    if(!j) return;

    const atm = Number(j.atm ?? j.atm_strike ?? j.atmStrike ?? j.atmStrikePrice ?? NaN);
    if(!Number.isFinite(atm)) return;

    const body = document.getElementById('nseOcBody');
    if(!body) return;

    const rows = Array.from(body.querySelectorAll('tr'));
    if(!rows.length) return;

    // First number of the strike cell ("25,100 ▲" has a BUY/SELL hint badge after the strike)
    const parseStrike = (td)=>{
      const m = String(td?.textContent ?? '').replace(/,/g,'').match(/\d+(\.\d+)?/);
      return m ? Number(m[0]) : NaN;
    };

    // Clear previous marks
    rows.forEach(tr=>{
      tr.classList.remove('atm-row','near-atm-1','near-atm-2','atm-flash');
      tr.querySelectorAll('td').forEach(td=>{
        td.classList.remove('atm-td','near-td','max-call-oi','max-put-oi');
      });
    });

    // Find ATM row (by STRIKE column only)
    let atmRow = null;
    let atmStrikeTd = null;
    let atmIdx = -1;

    for(let i=0;i<rows.length;i++){
      const tr = rows[i];
      const td = tr.querySelector('td.strike');
      if(!td) continue;
      const st = parseStrike(td);
      if(Number.isFinite(st) && st === atm){
        atmRow = tr;
        atmStrikeTd = td;
        atmIdx = i;
        break;
      }
    }

    // (Max OI cells are marked by renderNseOcFromTrack ('max-oi'); the outer columns here are Volume)

    if(!atmRow) return;

    // Apply FULL-row ATM highlight (paint every TD, because cells may have inline BG)
    atmRow.classList.add('atm-row');
    atmRow.querySelectorAll('td').forEach(td=>td.classList.add('atm-td'));

    // Ensure strike cell keeps ATM emphasis (for the "center marker" CSS)
    if(atmStrikeTd){
      atmStrikeTd.classList.add('atm');
    }

    // ±1 / ±2 strike lighter bands
    const markNear = (idx, cls)=>{
      const tr = rows[idx];
      if(!tr) return;
      tr.classList.add(cls);
      tr.querySelectorAll('td').forEach(td=>td.classList.add('near-td'));
    };
    if(atmIdx >= 0){
      markNear(atmIdx-1, 'near-atm-1');
      markNear(atmIdx+1, 'near-atm-1');
      markNear(atmIdx-2, 'near-atm-2');
      markNear(atmIdx+2, 'near-atm-2');
    }

    // Animate ONLY when ATM changes
    const last = Number(window.__nseAtmLast ?? NaN);
    if(!Number.isFinite(last) || last !== atm){
      window.__nseAtmLast = atm;
      atmRow.classList.add('atm-flash');
      setTimeout(()=>{ try{ atmRow.classList.remove('atm-flash'); }catch(e){} }, 900);
    }

    // Auto-scroll ATM row to center: disabled (user request)
  }




  

  // ===== NSE ATM highlight re-apply (table re-renders on timeframe/metric toggles) =====
  let __nseAtmTimers = [];
  function scheduleNseAtmReapply(delays){
    // delays: number or array of numbers (ms)
    const ds = Array.isArray(delays) ? delays : [ (typeof delays === 'number' ? delays : 120) ];
    __nseAtmTimers.forEach(t=>clearTimeout(t));
    __nseAtmTimers = ds.map(d=>setTimeout(()=>{
      try{ highlightNseAtmRow(window.__lastTrackJson || window.lastJson); }catch(e){}  // table is built from /oi/track
    }, d));
  }

function renderBeautify(){
    const j = window.lastJson || window.__lastJson || null;
    const expiryTotals = j && j.expiry_totals && j.expiry_totals.now ? j.expiry_totals.now : null;

    // ---- Top dock (full-page sticky): Σ Expiry totals + ATM CE/PE — the one place they are shown ----
    if(expiryTotals){
      const ce = Number(expiryTotals.ce_oi);
      const pe = Number(expiryTotals.pe_oi);
      const pcr = (expiryTotals.pcr != null && Number.isFinite(+expiryTotals.pcr)) ? +expiryTotals.pcr : (ce>0 ? pe/ce : NaN);

      let dock = document.getElementById('oiExpiryDock');
      if(!dock){
        dock = document.createElement('div');
        dock.id = 'oiExpiryDock';
        document.body.appendChild(dock);
      }
      const atm = (j?.atm || window.lastJson?.atm || (typeof lastJson!=='undefined' ? lastJson?.atm : NaN));
      const atmData = getAtmFromTrackTable(atm);
      const atmHtml = (atmData && (atmData.ce || atmData.pe)) ? ('<span class="oiTotWrap">' + buildAtmChip(atm, atmData) + '</span>') : '';
      const html = '<div class="oiDockRow"><span class="oiTotWrap">' + buildChip('Σ Expiry', ce, pe, pcr) + '</span>' + atmHtml + '</div>';
      if(dock.dataset.src !== html){ dock.innerHTML = html; dock.dataset.src = html; }
    }

// Highlight ATM row in 📋 Live Option Chain (OI Synced); the table is built from /oi/track
    try{ highlightNseAtmRow(window.__lastTrackJson || j); }catch(e){}
}

  
  // Re-apply NSE ATM highlight after NSE timeframe clicks (table gets rebuilt)
  document.addEventListener('click', function(e){
    const t = e.target;
    if(!t) return;

    const txt = (t.textContent || '').trim().toLowerCase();
    const isTfLabel = (txt === 'day' || txt === '1m' || txt === '2m' || txt === '3m' || txt === '5m' ||
                       txt === '10m' || txt === '15m' || txt === '30m' || txt === '60m');

    // Only react for small pill/buttons/links
    if(isTfLabel && (t.matches('button, a, .pill, .chip, .tag, span, div'))){
      scheduleNseAtmReapply([0, 220, 650, 1100]);
    }
  }, true);

renderBeautify();
  // expose for manual triggers (e.g., end of refresh)
  window.__oiBeautifyNow = scheduleBeautify;

  // Apply immediately (no "normal then beautify" flash)
  scheduleBeautify();

  // Re-apply whenever NSE strip changes (metric OI/VOL toggle rebuilds DOM)
  const __target = document.getElementById('nseOcSum') || document.getElementById('nseOcWrap') || document.body;
  try{
    const mo = new MutationObserver(()=>scheduleBeautify());
    mo.observe(__target, {subtree:true, childList:true, characterData:true});
  }catch(e){}

  // keep dock positioned below the fixed status bar (also when the bar's height changes)
  window.addEventListener('resize', ()=>setDockTop(), {passive:true});
  try{
    const sb = document.querySelector('.status-bar');
    if(sb && window.ResizeObserver) new ResizeObserver(()=>setDockTop()).observe(sb);
  }catch(e){}

  document.addEventListener('change', function(e){
    if(e.target && e.target.id === 'strikeWin'){
      setTimeout(scheduleBeautify, 0);
    }
  });
})();



// ===== Move Market Meter box between OI Track and NSE Style table (user request) =====
function moveMarketMeterToBetween(){
  try{
    const slot = document.getElementById('betweenOiAndNseMarketMeter');
    const mm = document.getElementById('marketMeter');
    if(!slot || !mm) return;
    const row = mm.closest('.row-2col') || mm;
    if(row && row.dataset && row.dataset.mmMoved === '1') return;
    slot.appendChild(row); // moves the real node (keeps live updates + IDs)
    if(row && row.dataset) row.dataset.mmMoved = '1';
    row.style.marginTop = '0';
  }catch(e){}
}
document.addEventListener('DOMContentLoaded', moveMarketMeterToBetween);

</script>


<script id="autoScrollToNseAfterLoad">
(function(){
  const NSE_WRAP_ID = 'nseOcWrap';
  const NSE_BODY_ID = 'nseOcBody';
  const SESSION_KEY = 'nse_autoscroll_done_v2';

  function safeGetSession(key){
    try { return sessionStorage.getItem(key); } catch(e){ return null; }
  }
  function safeSetSession(key, val){
    try { sessionStorage.setItem(key, val); } catch(e){}
  }

  function scrollToNseTop(){
    const wrap = document.getElementById(NSE_WRAP_ID);
    if(!wrap) return;
    wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function nseHasRealRows(){
    const body = document.getElementById(NSE_BODY_ID);
    if(!body) return false;

    const rows = Array.from(body.querySelectorAll('tr'));
    if(rows.length === 0) return false;

    // If still showing a single Waiting row
    if(rows.length === 1){
      const t = (rows[0].innerText || '').trim().toLowerCase();
      if(t.includes('waiting') || t.includes('no data')) return false;
    }

    // Heuristic: NSE OC should typically have many strikes (>= 5 rows)
    if(rows.length >= 5) return true;

    // Otherwise accept only if first few rows have multiple cells and a strike-like number exists
    const sample = rows.slice(0,3).map(r => (r.innerText||'')).join(' ');
    if(/\b\d{4,6}\b/.test(sample) && body.querySelectorAll('td').length >= 10) return true;

    return false;
  }

  function setupAutoScrollOnceAfterNseLoads(){
    if(safeGetSession(SESSION_KEY) === '1') return;

    const body = document.getElementById(NSE_BODY_ID);
    const wrap = document.getElementById(NSE_WRAP_ID);
    if(!body || !wrap) return;

    // If it's already loaded, scroll immediately (once).
    if(nseHasRealRows()){
      safeSetSession(SESSION_KEY, '1');
      scrollToNseTop();
      return;
    }

    const obs = new MutationObserver(() => {
      if(safeGetSession(SESSION_KEY) === '1'){ try{ obs.disconnect(); }catch(e){}; return; }
      if(nseHasRealRows()){
        safeSetSession(SESSION_KEY, '1');
        scrollToNseTop();
        try{ obs.disconnect(); }catch(e){}
      }
    });

    obs.observe(body, { childList: true, subtree: true });

    // Safety stop (avoid long observers)
    setTimeout(() => { try{ obs.disconnect(); }catch(e){} }, 30000);
  }

  function addJumpButton(){
    if(document.getElementById('jumpToNseBtn')) return;
    const btn = document.createElement('button');
    btn.id = 'jumpToNseBtn';
    btn.type = 'button';
    btn.className = 'jump-nse-btn';
    btn.textContent = 'Jump to NSE';
    btn.addEventListener('click', scrollToNseTop);
    document.body.appendChild(btn);
  }

  document.addEventListener('DOMContentLoaded', function(){
    addJumpButton();
    setupAutoScrollOnceAfterNseLoads();
  });

  window.addEventListener('load', function(){
    // safety: in case DOMContentLoaded ran before NSE nodes existed
    setupAutoScrollOnceAfterNseLoads();
  });

})();

// =====================================================
// Collapsible cards: PCR Trend / Last 8 Classifications / Next box
// - Click the header area to toggle
// - Remembers per-card state in localStorage
// =====================================================
(function initCollapsibleCards(){
  function applyState(card, collapsed){
    if(!card) return;
    card.classList.toggle('collapsed', !!collapsed);
  }

  document.querySelectorAll('.card.collapsible').forEach(card => {
    const key = (card.dataset && card.dataset.panel) ? String(card.dataset.panel) : '';
    const head = card.querySelector('.card-head');
    if(!head) return;

    // restore
    if(key){
      const saved = localStorage.getItem('collapse_' + key);
      if(saved === '1') applyState(card, true);
      if(saved === '0') applyState(card, false);
    }

    head.addEventListener('click', (ev) => {
      // ignore clicks on interactive controls inside header (just in case later)
      const t = ev.target;
      if(t && (t.tagName === 'A' || t.tagName === 'BUTTON' || t.closest('a,button,input,select,textarea'))){
        return;
      }
      const nowCollapsed = !card.classList.contains('collapsed');
      applyState(card, nowCollapsed);
      if(key){
        localStorage.setItem('collapse_' + key, nowCollapsed ? '1' : '0');
      }
    });
  });
})();


// =====================================================
// Toggle All (Collapse/Expand) button for all collapsible cards
// - Uses the same structure as existing collapsible cards (.card-head/.card-body)
// =====================================================
(function initToggleAll(){
  const btn = document.getElementById('toggleAllBtn');
  if(!btn) return;

  // Same effect as a header click: the .collapsed class hides the body (CSS) and rotates the
  // ▼ icon. (Setting an inline display:none here stuck: a later header click removed the class
  // but the body stayed hidden.)
  function applyState(card, collapsed){
    card.classList.toggle('collapsed', !!collapsed);
    card.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    const body = card.querySelector('.card-body');
    if(body) body.style.display = '';

    const key = card.getAttribute('data-panel') || '';
    if(key){
      localStorage.setItem('collapse_' + key, collapsed ? '1' : '0');
    }
  }

  function anyExpanded(){
    return !!document.querySelector('.card.collapsible:not(.collapsed)');
  }

  function updateLabel(){
    btn.textContent = anyExpanded() ? 'Collapse All' : 'Expand All';
  }

  btn.addEventListener('click', ()=>{
    const collapse = anyExpanded(); // if anything is open -> collapse all, else expand all
    document.querySelectorAll('.card.collapsible').forEach(card => applyState(card, collapse));
    updateLabel();
  });

  // keep label correct on load and after individual toggles
  updateLabel();
  document.addEventListener('click', (e)=>{
    const h = e.target && e.target.closest && e.target.closest('.card.collapsible .card-head');
    if(!h) return;
    // label update after per-card toggle
    setTimeout(updateLabel, 0);
  });
})();

</script>
<script>
function __renderDaywiseSafe(j){
  const tb = document.getElementById('daywiseBody');
  if(!tb) return;
  const daywise = j && j.daywise ? j.daywise : null;
  const rows = daywise && Array.isArray(daywise.rows) ? daywise.rows : [];
  if(!rows.length){
    tb.innerHTML = '<tr><td colspan="9" class="muted">No day-wise data</td></tr>';
    return;
  }
  let html = '';
  rows.forEach(r=>{
    html += `
      <tr>
        <td style="text-align:left">${r.trade_date || ''}</td>
        <td style="text-align:left">${(r.day_bias||'').toString()}</td>
        <td style="text-align:right">${Number(r.pcr_window||0).toFixed(2)}</td>
        <td style="text-align:right">${Number(r.win_ce_oi||0).toLocaleString()}</td>
        <td style="text-align:right">${Number(r.win_pe_oi||0).toLocaleString()}</td>
        <td style="text-align:right">${Number(r.d_ce_oi_window||0).toLocaleString()}</td>
        <td style="text-align:right">${Number(r.d_pe_oi_window||0).toLocaleString()}</td>
        <td style="text-align:right">${Number(r.max_oi_ce_strike||0).toLocaleString()}</td>
        <td style="text-align:right">${Number(r.max_oi_pe_strike||0).toLocaleString()}</td>
      </tr>`;
  });
  tb.innerHTML = html;
}
function __renderExpiryBoxSafe(j){
  const el = document.getElementById('expiryRangeBox');
  if(!el) return;
  const b = j && j.expiry_box ? j.expiry_box : null;
  if(!b || !b.lower){ el.innerHTML=''; return; }
  const c = b.confidence < 30 ? 'red' : (b.confidence < 60 ? 'yellow' : 'green');
  el.innerHTML = `
    <div class="box ${c}">
      <div class="title">Expiry Range (Auto)</div>
      <div class="range">${b.lower} – ${b.upper}</div>
      <div class="small">Confidence: ${b.confidence}%</div>
      <div class="muted small">${b.status || ''}</div>
    </div>`;
}
(function(){
  const _lt = window.loadTrack;
  if(typeof _lt === 'function'){
    window.loadTrack = async function(){
      const r = await _lt.apply(this, arguments);
      if(window.__lastTrackJson){
        __renderExpiryBoxSafe(window.__lastTrackJson);
        __renderDaywiseSafe(window.__lastTrackJson);
        __renderTop5FromJson(window.__lastTrackJson);
      }
      return r;
    }
  }
})();
</script>


<script>
/* =======================
   SIMPLE COLLAPSE TOGGLE
   ======================= */
(function(){
  function toggle(btn){
    const sel = btn.getAttribute('data-collapse-toggle');
    if(!sel) return;
    const panel = document.querySelector(sel);
    if(!panel) return;

    const key = 'collapse:' + sel;
    const willHide = !panel.classList.contains('collapse-hidden');
    panel.classList.toggle('collapse-hidden', willHide);
    btn.classList.toggle('is-open', !willHide);
    try{ localStorage.setItem(key, willHide ? '1' : '0'); }catch(e){}
  }

  function init(){
    document.querySelectorAll('[data-collapse-toggle]').forEach(btn=>{
      const sel = btn.getAttribute('data-collapse-toggle');
      const panel = sel ? document.querySelector(sel) : null;
      if(!panel) return;
      const key = 'collapse:' + sel;
      let v = null;
      try{ v = localStorage.getItem(key); }catch(e){}
      const isHidden = (v === '1');
      panel.classList.toggle('collapse-hidden', isHidden);
      btn.classList.toggle('is-open', !isHidden);
    });
  }

  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-collapse-toggle]');
    if(!btn) return;
    e.preventDefault();
    toggle(btn);
  });

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
</script>




<script>
function __renderTop5FromJson(j){
  const card = document.getElementById('top5OiCard');
  const mixEl = document.getElementById('miniTopAllMix');
  const ceEl  = document.getElementById('miniTopAllCE');
  const peEl  = document.getElementById('miniTopAllPE');
  const nearCE = document.getElementById('miniNearCE');
  const nearPE = document.getElementById('miniNearPE');
  const nearMeta = document.getElementById('miniNearMeta');
  if(!card || !mixEl || !ceEl || !peEl || !nearCE || !nearPE) return;

  const top = j && j.top5_oi ? j.top5_oi : null;
  const ce = top && Array.isArray(top.ce) ? top.ce : [];
  const pe = top && Array.isArray(top.pe) ? top.pe : [];

  // Near ATM from backend (preferred)
  const nearBox = j && j.top5_oi_near_atm ? j.top5_oi_near_atm : null;
  const nearCeArr = nearBox && Array.isArray(nearBox.ce) ? nearBox.ce : [];
  const nearPeArr = nearBox && Array.isArray(nearBox.pe) ? nearBox.pe : [];
  if(nearMeta && nearBox && nearBox.range && nearBox.range.from!=null && nearBox.range.to!=null){
    nearMeta.textContent = `±${nearBox.win || 5} strikes • ${Number(nearBox.range.from).toLocaleString()}–${Number(nearBox.range.to).toLocaleString()}`;
  }

  if(!ce.length && !pe.length){
    card.style.display = 'none';
    return;
  }
  card.style.display = '';

  
  const expBadge = document.getElementById('top5ExpiryBadge');
  if(expBadge){ expBadge.textContent = `Expiry: ${(j && j.expiry) ? j.expiry : '--'}`; }
const spot = (j && j.underlying_now!=null) ? Number(j.underlying_now) : null;
  const atmStrike = Number((j && (j.atm_strike || j.atm)) ? (j.atm_strike || j.atm) : 0);
  const nearFrom = (nearBox && nearBox.range && nearBox.range.from!=null) ? Number(nearBox.range.from) : (atmStrike||0);
  const nearTo   = (nearBox && nearBox.range && nearBox.range.to!=null) ? Number(nearBox.range.to) : (atmStrike||0);
  const isNear = (s)=>{ const x=Number(s); return Number.isFinite(x) && x>=nearFrom && x<=nearTo; };
  const nearSet = new Set();
  nearCeArr.forEach(x=>nearSet.add(Number(x.strike)));
  nearPeArr.forEach(x=>nearSet.add(Number(x.strike)));
  const wallCEstrike = (j && j.walls && j.walls.ce && j.walls.ce.strike!=null) ? Number(j.walls.ce.strike) : 0;
  const wallPEstrike = (j && j.walls && j.walls.pe && j.walls.pe.strike!=null) ? Number(j.walls.pe.strike) : 0;
  const doiBox = (j && j.top_doi && j.top_doi['5']) ? j.top_doi['5'] : null;   // 5m ΔOI
  const doiPosSet = new Set();
  if(doiBox && Array.isArray(doiBox.ce)) doiBox.ce.forEach(x=>{ if(Number(x.doi||0)>0) doiPosSet.add(Number(x.strike)); });
  if(doiBox && Array.isArray(doiBox.pe)) doiBox.pe.forEach(x=>{ if(Number(x.doi||0)>0) doiPosSet.add(Number(x.strike)); });


  const rowHtml = (tag, strike, val, dist)=> {
    const distTxt = (dist==null || !Number.isFinite(dist)) ? '' : `<span class=\"oiDist\" title=\"Distance from spot (current level)\">${dist>0?'+':''}${dist.toFixed(2)} pts</span>`;
    return `<div class="oiMiniRow">
      <div class="oiLeft"><span class="oiTag ${tag.toLowerCase()}">${tag}</span><span class="oiStrike">${strike.toLocaleString()}</span>${(nearSet.has(Number(strike)) || isNear(strike)) ? `<span class="oiBadge atm" title="Within Near-ATM window">ATM</span>` : ``}${((nearSet.has(Number(strike)) || isNear(strike)) && (doiPosSet.has(Number(strike)) || Number(strike)===wallCEstrike || Number(strike)===wallPEstrike)) ? `<span class="oiBadge conf" title="Confluence: Near-ATM + (ΔOI>0 or Wall)">🔥</span>` : ``}</div>
      <div style="text-align:right"><div class="oiVal">${val.toLocaleString()}</div>${distTxt}</div>
    </div>`;
  };

  const renderList = (el, items, tagLabel)=> {
    if(!el) return;
    if(!items || !items.length){ el.innerHTML = '<div class="muted small">No data</div>'; return; }
    const out = items.slice(0,5).map(x=>{
      const strike = Number(x.strike||0);
      const oi = Number(x.oi||0);
      const dist = (spot!=null) ? (strike-spot) : null;
      return rowHtml(tagLabel, strike, oi, dist);
    }).join('');
    el.innerHTML = out;
  };

  // All-strikes CE/PE
  renderList(ceEl, ce, 'CE');
  renderList(peEl, pe, 'PE');

  // Mixed Top-3 (CE+PE)
  const mix = []
    .concat((ce||[]).map(x=>({t:'CE', ...x})))
    .concat((pe||[]).map(x=>({t:'PE', ...x})))
    .sort((a,b)=>Number(b.oi||0)-Number(a.oi||0))
    .slice(0,3);

  mixEl.innerHTML = mix.map(x=>{
    const strike = Number(x.strike||0);
    const oi = Number(x.oi||0);
    const dist = (spot!=null) ? (strike-spot) : null;
    return rowHtml(x.t, strike, oi, dist);
  }).join('') || '<div class="muted small">No data</div>';

  // Near ATM CE/PE (backend)
  const renderNear = (el, items, tagLabel)=> {
    if(!el) return;
    if(!items || !items.length){ el.innerHTML = '<div class="muted small">No data</div>'; return; }
    el.innerHTML = items.slice(0,3).map(x=>{
      const strike = Number(x.strike||0);
      const oi = Number(x.oi||0);
      const dist = (spot!=null) ? (strike-spot) : null;
      return rowHtml(tagLabel, strike, oi, dist);
    }).join('');
  };
  renderNear(nearCE, nearCeArr, 'CE');
  renderNear(nearPE, nearPeArr, 'PE');

  // Walls badges (±2 strikes around ATM)
  const wallCE = document.getElementById('wallBadgeCE');
  const wallPE = document.getElementById('wallBadgePE');
  if(j && j.walls){
    const w = j.walls;
    if(w.ce && w.ce.strike){
      const pct = (w.ce.share!=null) ? Math.round(Number(w.ce.share)*100) : null;
      wallCE.style.display = '';
      wallCE.textContent = `CE Wall ${w.ce.strike}` + (pct!=null ? ` (${pct}%)` : '');
      wallCE.title = `Largest CE OI within ±${w.win} strikes. OI=${(w.ce.oi||0).toLocaleString()}`;
    } else if(wallCE){ wallCE.style.display='none'; }
    if(w.pe && w.pe.strike){
      const pct = (w.pe.share!=null) ? Math.round(Number(w.pe.share)*100) : null;
      wallPE.style.display = '';
      wallPE.textContent = `PE Wall ${w.pe.strike}` + (pct!=null ? ` (${pct}%)` : '');
      wallPE.title = `Largest PE OI within ±${w.win} strikes. OI=${(w.pe.oi||0).toLocaleString()}`;
    } else if(wallPE){ wallPE.style.display='none'; }

    // Wall classification tag (Magnet vs Rejection)
    const wallTag = document.getElementById('wallBadgeTag');
    if(wallTag){
      let label = '';
      let title = '';
      if(w && w.ce && w.pe && w.ce.strike!=null && w.pe.strike!=null){
        const cs = Number(w.ce.share||0);
        const ps = Number(w.pe.share||0);
        const cst = Number(w.ce.strike);
        const pst = Number(w.pe.strike);
        if(cst===pst && Math.abs(cs-ps) <= 0.08){
          label = '⚖ Magnet';
          title = 'CE & PE walls at same strike → pinning/range likely';
        }else if((cs-ps) >= 0.12){
          label = '🚧 Rejection (CE)';
          title = 'CE wall dominant → upside resistance likely';
        }else if((ps-cs) >= 0.12){
          label = '🧲 Support (PE)';
          title = 'PE wall dominant → downside support likely';
        }else{
          label = '⚖ Balanced';
          title = 'Walls balanced → range / wait for breakout';
        }
        wallTag.style.display = '';
        wallTag.textContent = label;
        wallTag.title = title;
      }else{
        wallTag.style.display = 'none';
      }
    }

  } else {
    if(wallCE) wallCE.style.display='none';
    if(wallPE) wallPE.style.display='none';
    const wallTag = document.getElementById('wallBadgeTag');
    if(wallTag) wallTag.style.display='none';
  }
}

</script>


<!-- FINAL FIX: auto-apply scrollable height to the correct Daily OI Bias container (no other sections) -->
<script>
(function(){
  function findBiasContainer(tbl){
    var el = tbl;
    while(el && el !== document.body){
      try{
        var t = (el.innerText || "");
        if(t.indexOf("Daily OI Bias History") !== -1) return el;
      }catch(e){}
      el = el.parentElement;
    }
    return tbl.parentElement || tbl;
  }

  function applyFix(){
    var tbl = document.getElementById("daywiseOiTbl");
    if(!tbl) return;

    var container = findBiasContainer(tbl);
    if(container && !container.classList.contains("oi-bias-history-section")){
      container.classList.add("oi-bias-history-section");
    }

    // Also ensure the immediate table wrap can scroll horizontally if needed
    var wrap = tbl.parentElement;
    if(wrap && wrap.style){
      wrap.style.overflowX = wrap.style.overflowX || "auto";
    }
  }

  if(document.readyState === "loading"){
    document.addEventListener("DOMContentLoaded", applyFix);
  } else {
    applyFix();
  }
})();
</script>


<!-- FINAL FIX v8: Detect collapsed state and shrink the box (scoped to Daily OI Bias only) -->
<script>
(function(){
  function updateBiasCollapse(){
    var tbl = document.getElementById("daywiseOiTbl");
    if(!tbl) return;

    // The container class is added in v7
    var container = tbl.closest(".oi-bias-history-section");
    if(!container) return;

    // Collapsed = the card's ▼ toggle hid its body (data-collapse-toggle adds .collapse-hidden).
    // (Inferring it from hidden/missing children kept the card collapsed for good: this
    // class's own CSS hides those children.)
    var body = document.getElementById("daywiseOiBody");
    var collapsed = !!(body && body.classList.contains("collapse-hidden"));

    container.classList.toggle("is-collapsed", !!collapsed);
  }

  function wire(){
    updateBiasCollapse();

    // Update on any click within the bias block (collapse toggles are usually click-based)
    document.addEventListener("click", function(e){
      var tbl = document.getElementById("daywiseOiTbl");
      if(!tbl) return;
      var container = tbl.closest(".oi-bias-history-section");
      if(!container) return;
      if(container.contains(e.target)){
        setTimeout(updateBiasCollapse, 50);
        setTimeout(updateBiasCollapse, 250);
      }
    }, true);

    // Watch for DOM/class changes that happen when collapsing
    var tbl = document.getElementById("daywiseOiTbl");
    if(!tbl) return;
    var container = tbl.closest(".oi-bias-history-section") || tbl.parentElement;
    if(!container) return;

    var mo = new MutationObserver(function(){
      updateBiasCollapse();
    });
    mo.observe(container, {attributes:true, childList:true, subtree:true});

    window.addEventListener("resize", function(){ setTimeout(updateBiasCollapse, 50); });
  }

  if(document.readyState === "loading"){
    document.addEventListener("DOMContentLoaded", wire);
  } else {
    wire();
  }
})();
</script>


<!-- FINAL FIX v9: Build and render Bias Trend / Buy-Sell icons / Weighted score / Flip alert dynamically from the table -->
<script>
(function(){
  function txt(el){ return (el && (el.textContent || el.innerText) || "").trim(); }

  function parseNum(s){
    if(!s) return 0;
    // keep minus, digits, dot; remove commas and other chars
    var m = s.replace(/,/g,'').match(/-?\d+(?:\.\d+)?/);
    return m ? parseFloat(m[0]) : 0;
  }

  function classifyBias(b){
    b = (b||"").toUpperCase();
    if(b.indexOf("BULL")>=0) return "BULL";
    if(b.indexOf("BEAR")>=0) return "BEAR";
    return "NEU";
  }

  function makeEnhancementsUI(section, tbl){
    // Ensure container exists right above table
    var enh = section.querySelector("#oi-bias-history-enhancements");
    if(!enh){
      enh = document.createElement("div");
      enh.id = "oi-bias-history-enhancements";
      // insert before table wrap or table
      var anchor = tbl.closest(".oi-bias-table-wrap") || tbl;
      anchor.parentNode.insertBefore(enh, anchor);
    }
    enh.innerHTML = [
      '<div class="row">',
        '<span class="label">Bias Trend (5D):</span>',
        '<div class="trendbar" data-role="trend"></div>',
        '<span class="chip" data-role="wscore" title="Time-weighted score (recent days weighted higher)">🕒 Weighted: <b data-role="wscoreVal">—</b></span>',
      '</div>',
      '<div class="flip-alert" data-role="flip">⚠ OI FLOW FLIP DETECTED: ΔCE & ΔPE both flipped vs yesterday</div>'
    ].join("");

    // Ensure verdict exists (below table)
    var verdict = section.querySelector(".oi-verdict");
    if(!verdict){
      verdict = document.createElement("div");
      verdict.className = "oi-verdict";
      var after = tbl.closest(".oi-bias-table-wrap") || tbl;
      after.parentNode.insertBefore(verdict, after.nextSibling);
    }
    verdict.setAttribute("data-role","verdict");
    verdict.innerHTML = '🧠 <b>OI Memory Verdict:</b> <span data-role="verdictText">—</span>';

    return enh;
  }

  function findColumnIndex(headers, candidates){
    candidates = candidates.map(function(s){ return s.toUpperCase(); });
    for(var i=0;i<headers.length;i++){
      var h = headers[i].toUpperCase();
      for(var j=0;j<candidates.length;j++){
        if(h.indexOf(candidates[j])>=0) return i;
      }
    }
    return -1;
  }

  function applyDeltaColors(tbl, idxBias, idxDce, idxDpe){
    var rows = Array.from(tbl.tBodies[0]?.rows || []);
    rows.forEach(function(r){
      var tds = Array.from(r.cells || []);
      if(idxDce>=0 && tds[idxDce]){
        var v = parseNum(txt(tds[idxDce]));
        tds[idxDce].classList.remove("delta-ce-up","delta-ce-down","delta-cover");
        if(v>0) tds[idxDce].classList.add("delta-ce-up");
        else if(v<0) tds[idxDce].classList.add("delta-ce-down");
      }
      if(idxDpe>=0 && tds[idxDpe]){
        var v2 = parseNum(txt(tds[idxDpe]));
        tds[idxDpe].classList.remove("delta-pe-up","delta-pe-down","delta-cover");
        if(v2>0) tds[idxDpe].classList.add("delta-pe-up");
        else if(v2<0) tds[idxDpe].classList.add("delta-pe-down");
      }
      // covering highlight when both negative
      if(idxDce>=0 && idxDpe>=0 && tds[idxDce] && tds[idxDpe]){
        var a = parseNum(txt(tds[idxDce]));
        var b = parseNum(txt(tds[idxDpe]));
        if(a<0 && b<0){
          tds[idxDce].classList.add("delta-cover");
          tds[idxDpe].classList.add("delta-cover");
        }
      }
    });
  }

  function renderTrendAndVerdict(section, tbl){
    var thead = tbl.tHead;
    if(!thead || !thead.rows.length) return;

    var headers = Array.from(thead.rows[0].cells).map(function(th){ return txt(th); });
    var idxDate = findColumnIndex(headers, ["DATE"]);
    var idxBias = findColumnIndex(headers, ["BIAS"]);
    var idxPcr  = findColumnIndex(headers, ["PCR"]);
    var idxDce  = findColumnIndex(headers, ["ΔCE","DCE"]);
    var idxDpe  = findColumnIndex(headers, ["ΔPE","DPE"]);

    var tbody = tbl.tBodies[0];
    if(!tbody) return;
    var rows = Array.from(tbody.rows);
    if(!rows.length) return;

    // latest first (assume already latest at top)
    var last5 = rows.slice(0,5).map(function(r){
      var cells = Array.from(r.cells);
      return {
        date: idxDate>=0 && cells[idxDate] ? txt(cells[idxDate]) : "",
        bias: idxBias>=0 && cells[idxBias] ? txt(cells[idxBias]) : "",
        pcr:  idxPcr>=0 && cells[idxPcr] ? parseNum(txt(cells[idxPcr])) : 0,
        dce:  idxDce>=0 && cells[idxDce] ? parseNum(txt(cells[idxDce])) : 0,
        dpe:  idxDpe>=0 && cells[idxDpe] ? parseNum(txt(cells[idxDpe])) : 0
      };
    });

    // Color deltas in table
    applyDeltaColors(tbl, idxBias, idxDce, idxDpe);

    // Trend dots
    var trend = section.querySelector('#oi-bias-history-enhancements [data-role="trend"]');
    if(trend){
      trend.innerHTML = "";
      last5.slice().reverse().forEach(function(d){ // oldest -> newest for visual
        var c = classifyBias(d.bias);
        var dot = document.createElement("span");
        dot.className = "dot " + (c==="BULL"?"bull":(c==="BEAR"?"bear":"neu"));
        dot.textContent = (c==="BULL"?"B":(c==="BEAR"?"S":"N"));
        dot.title = (d.date? (d.date + " • ") : "") + (d.bias||"NEUTRAL");
        trend.appendChild(dot);
      });
    }

    // Bias flip indicator (show if top two rows differ)
    var flipEl = section.querySelector('#oi-bias-history-enhancements [data-role="flip"]');
    if(flipEl){
      var flip = false;
      if(last5.length>=2){
        var b0 = classifyBias(last5[0].bias);
        var b1 = classifyBias(last5[1].bias);
        // both flip alert requires both deltas flip direction
        var dceFlip = (last5[0].dce>0 && last5[1].dce<0) || (last5[0].dce<0 && last5[1].dce>0);
        var dpeFlip = (last5[0].dpe>0 && last5[1].dpe<0) || (last5[0].dpe<0 && last5[1].dpe>0);
        flip = dceFlip && dpeFlip;
      }
      flipEl.classList.toggle("show", !!flip);
    }

    // Weighted score (recent day > old): bias score mapping
    var weights = [0.40,0.25,0.20,0.10,0.05];
    var score = 0;
    for(var i=0;i<last5.length && i<weights.length;i++){
      var c = classifyBias(last5[i].bias);
      var s = (c==="BULL") ? 60 : (c==="BEAR" ? 40 : 50); // neutral baseline 50
      score += s * weights[i];
    }
    score = Math.round(score);
    var wv = section.querySelector('#oi-bias-history-enhancements [data-role="wscoreVal"]');
    if(wv) wv.textContent = score + " / 100";

    var latest = last5[0];

    // Verdict line (simple + actionable)
    var verdict = section.querySelector('[data-role="verdictText"]');
    if(verdict){
      var v = "";
      if(classifyBias(latest.bias)==="BULL"){
        v = "Bullish positioning dominates.";
        if(latest.dce>0) v += " Rising call writing suggests overhead resistance — prefer long-on-dips, book near CE walls.";
        else v += " Call resistance is easing — breakouts have better odds if price confirms.";
      } else if(classifyBias(latest.bias)==="BEAR"){
        v = "Bearish positioning dominates.";
        if(latest.dpe<0) v += " Put covering suggests support weakening — protect longs, favor sell-on-rallies.";
        else v += " Put support still present — wait for price confirmation before aggressive shorts.";
      } else {
        v = "Bias is mixed/neutral.";
        if(latest.dpe>0 && latest.dce>0) v += " Both sides adding OI — likely range; trade levels, avoid chasing.";
        else if(latest.dpe<0 && latest.dce<0) v += " Covering on both sides — volatility expansion possible; wait for breakout confirmation.";
        else v += " Watch ΔCE/ΔPE direction for the next push.";
      }
      verdict.textContent = v;
    }
  }

  function attach(){
    var tbl = document.getElementById("daywiseOiTbl");
    if(!tbl) return;

    // The container class is added in v7/v8
    var section = tbl.closest(".oi-bias-history-section") || tbl.parentElement;
    if(!section) return;

    makeEnhancementsUI(section, tbl);
    renderTrendAndVerdict(section, tbl);

    // Re-render after data refresh (many dashboards re-render table)
    var mo = new MutationObserver(function(){
      // avoid heavy loops
      clearTimeout(window.__oiBiasEnhTimer);
      window.__oiBiasEnhTimer = setTimeout(function(){ 
        var t = document.getElementById("daywiseOiTbl");
        if(t) renderTrendAndVerdict(section, t);
      }, 120);
    });
    mo.observe(tbl, {childList:true, subtree:true});

    // Also re-run on refresh button clicks
    document.addEventListener("click", function(e){
      var t = e.target;
      if(!t) return;
      var label = (t.innerText || t.textContent || "").trim().toLowerCase();
      if(label === "refresh" || label.indexOf("refresh")>=0){
        setTimeout(function(){
          var tb = document.getElementById("daywiseOiTbl");
          if(tb) renderTrendAndVerdict(section, tb);
        }, 300);
      }
    }, true);
  }

  if(document.readyState === "loading") document.addEventListener("DOMContentLoaded", attach);
  else attach();
})();
</script>

</body>
</html>
