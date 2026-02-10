<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>OI Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <style>
.mvLbl {
  cursor: pointer;
  text-decoration: underline dotted;
  text-underline-offset: 2px;
}
/* ===== Global tooltip UX =====
   Any element with a tooltip should look hoverable (hand cursor)
*/
[title]:not([title=""]) {
  cursor: pointer;                 /* hand */
}

/* Optional: also give a subtle dotted underline so users notice it */
[title]:not([title=""]) {
  text-decoration: underline dotted;
  text-underline-offset: 2px;
}
[data-tip]::after{
  left: var(--tip-x);
  top: var(--tip-y);
}

[data-tip]::before{
  left: var(--arrow-x);
  top: var(--arrow-y);
}

/* ===== FAST SMART TOOLTIP ===== */
[data-tip]{
  cursor: pointer;
  text-decoration: underline dotted;
  text-underline-offset: 2px;
  position: relative; /* keeps selector consistent */
}

[data-tip]::after{
  content: attr(data-tip);
  position: fixed;
  left: var(--tip-x, 12px);
  top:  var(--tip-y, 12px);
  backdrop-filter: blur(6px);
  max-width: 320px;
  white-space: normal;

  background: linear-gradient(135deg, #0b1220, #020617);
  color: #e5e7eb;
  font-size: 12px;
  line-height: 1.4;
  padding: 7px 10px;
  border-radius: 10px;

  box-shadow: 0 12px 30px rgba(0,0,0,.45),
              inset 0 0 0 1px rgba(255,255,255,.07);

  z-index: 999999;
  pointer-events: none;

  opacity: 0;
  transform: translateY(6px);
  transition: opacity .08s ease, transform .08s ease;
}

[data-tip].tip-show::after{
  opacity: 1;
  transform: translateY(0);
}


</style>
<style>
  .hint{ font-size:11.5px; margin-top:6px; opacity:.9; }
</style>
<style>
  .oc-lite {
    opacity: .65;
    font-size: 12px;
    margin-left: 4px;
  }
</style>
<style>
.tip {
  border-bottom: 1px dotted rgba(255,255,255,.35);
  cursor: help;
}

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

    /* ===== Countdown ===== */
    .market-countdown{
      font-size:12px;
      opacity:.85;
      margin-left:6px;
    }


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
    .row-2col,
    .row-60-40{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:10px;  /* less space between cards */
    }
    @media (min-width:1400px){
      .row{
        grid-template-columns:1.2fr 1.2fr 1fr;
      }
      .row-60-40{
        grid-template-columns:3fr 2fr;
      }
    }
    @media (max-width:900px){
      .row,
      .row-2col,
      .row-60-40{
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

    /* Side filter visual states */
    #sideFilterPill.side-both{
      border-color:rgba(59,130,246,.7);
    }
    #sideFilterPill.side-calls{
      border-color:rgba(248,113,113,.9);
      box-shadow:0 0 12px rgba(248,113,113,.4);
    }
    #sideFilterPill.side-puts{
      border-color:rgba(34,197,94,.85);
      box-shadow:0 0 12px rgba(34,197,94,.4);
    }

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
    .delta{display:inline-flex; align-items:center; gap:4px; font-weight:600;}
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

/* ROW 1: health + toggles */
.status-top-row{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  padding:6px 10px;
  white-space:nowrap;
}

/* left + right blocks */
.status-left,
.status-top-right{
  display:flex;
  align-items:center;
  gap:10px;
  flex-wrap:nowrap;
}

/* make timestamps compact */
.status-left strong{ font-weight:600; }
.status-left span{ opacity:.9; }

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

/* Collapse behavior */
.status-bar.collapsed .status-top-row{
  display:none;
}

/* ===== ROW 2: FILTER BAR (single line, scroll if needed) ===== */
.status-filter-row{
  display:flex;
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

/* ===== spacing below fixed bar ===== */
:root{ --statusbar-h: 78px; }   /* compact height */
.wrap{
  padding-top: calc(var(--statusbar-h) + 6px);
}

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

    /* Row 1: DB / Fetch / Enrich + toggles / timer / Refresh */
    .status-top-row{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      flex-wrap:wrap;
    }

    .status-left{
      font-size:12px;
      color:#97a6c3;
      display:flex;
      align-items:center;
      gap:8px;
      flex-wrap:wrap;
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

    table.nb-bars{
      width:100%;
      border-collapse:separate;
      border-spacing:0 6px;
    }
    .nb-strike-td{
      width:70px;
      text-align:right;
      font-variant-numeric:tabular-nums;
      white-space:nowrap;
      font-size:12px;
    }
    .nb-bar-td{width:auto;}
    .nb-val-td{
      width:140px;
      text-align:left;
      font-variant-numeric:tabular-nums;
      white-space:nowrap;
      font-size:12px;
    }
    .nb-svg{
      display:block;
      width:100%;
      height:14px;
    }

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
    body.light-mode .status-left,
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
    body.compact .row-2col,
    body.compact .row-60-40{
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
      .status-top-row{
        flex-direction:column;
        align-items:flex-start;
      }
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
  overflow:hidden;
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
.nse-oc-hdr .ttl{ font-weight:800; }
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

/* NSE OC timeframe buttons */
.nse-oc-tf{
  display:flex;
  align-items:center;
  gap:6px;
  flex-wrap:wrap;
  margin-left:10px;
}
/* NSE OC multi-timeframe column highlight */
.nse-oc .th-hi{ box-shadow: inset 0 -2px 0 rgba(253,224,71,.65); }
.nse-oc td.col-hi{ background: rgba(253,224,71,.06); }
.nse-oc-tf{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.nse-oc-tf .hlwrap{ display:inline-flex; align-items:center; gap:6px; }
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
  overflow:auto;
  max-height: 360px;
}
table.nse-oc{
  width:100%;
  border-collapse:collapse;
  font-size:12px;
  min-width: 980px;
}
.nse-oc th,.nse-oc td{
  padding:6px 8px;
  border:1px solid rgba(255,255,255,.08);
  white-space:nowrap;
  text-align:right;
  font-variant-numeric: tabular-nums;
}
.nse-oc thead th{ position:sticky; top:0; z-index:5; }
.nse-oc .call-head{ background:rgba(19,47,76,.95); color:#c7ecff; text-align:center; }
.nse-oc .put-head{ background:rgba(58,31,31,.95); color:#ffd0d0; text-align:center; }
.nse-oc .strike-head{ background:rgba(31,41,55,.95); text-align:center; }

.nse-oc .strike, .nse-oc .strike-head2{
  text-align:center;
  font-weight:800;
  background:rgba(17,24,39,.95);
}
.nse-oc .strike.atm{
  background: rgba(250,204,21,.95);
  color:#000;
}
.nse-oc td.pos, .nse-oc span.pos{ color:#22c55e; font-weight:700; }
.nse-oc td.neg, .nse-oc span.neg{ color:#ef4444; font-weight:700; }

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

.nse-oc tbody tr:hover{ background: rgba(255,255,255,.04); }

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
.nse-oc tbody tr.atm-row td{
  background: rgba(253,224,71,.06);
}
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

.nse-oc .foot-row td{
  background: rgba(0,0,0,.18);
  font-weight:800;
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
    <span class="pill" id="falseBreakBadge" style="margin-left:6px;">Breakout: —</span>
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
          <option value="5">5</option>
          <option value="10" selected>10</option>
          <option value="15">15</option>
        <option value="30">30</option>
          <option value="999">All</option>
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

      <button id="refreshBtn" style="margin-left:4px">Refresh</button>
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

      
      <span id="sessionTag" class="muted"></span>
      <span id="clockDrift" class="muted"></span>

      <span class="pill">
        ⏱ <span id="refreshTimer">60</span>s
      </span>
      <button id="refreshNowBtn" class="pill">Refresh</button>
    </div>
  </div>
  </div>

  
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
          Last updated: <?= esc($lu['maxFetched'] ?? '—') ?>
          <?php if (is_array($lu) && isset($lu['minsAgo']) && $lu['minsAgo'] !== null): ?>
            (<?= (int)$lu['minsAgo'] ?>m ago)
          <?php endif; ?>
          · Data: <?= esc($st) ?>

        </div>
      <?php endif; ?>
    </div>



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




<!-- Market Meter + Heatmap + ZigZag in one row -->
  <div class="row-2col" style="margin-top:10px;align-items:flex-start;">
    <!-- LEFT COLUMN: Market Meter -->
    <div class="card" id="marketMeter" style="margin-bottom:16px;display:none">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <div>
          <b>Market Meter</b>
          <div id="meterHeadline" style="margin-top:6px"></div>
          <div id="meterWhy" class="muted" style="margin-top:6px"></div>
        </div>
        <div id="meterBadge"></div>
      </div>

      <!-- Alert chips (PCR extremes, near day high/low, heavy ΔOI, etc.) -->
      <div id="meterAlerts"></div>

      <!-- Support / Resistance -->
      <div class="levels" style="margin-top:8px">
        <span><b>Support (Put OI):</b> <span id="meterSupport"></span></span>
        <span><b>Resistance (Cal
      <div class="levels" style="margin-top:6px">
        <span>
          <b>Quick Read (Intra <span id="qrIntraWin" class="mono">-</span>):</b>
          <span id="qrIntra">—</span><span class="tip tip-i" title="Intra Quick Read uses ΔOI since the selected window start (e.g., 3m/5m/10m). Support/Resistance shown here are the strongest Put/Call writing strikes in that window.">ⓘ</span>
        </span>
        <span>
          <b><span id="qrDayLbl">Quick Read (Day)</span>:</b>
          <span id="qrDay">—</span> <span id="qrDayMeta" class="muted"></span><span class="tip tip-i" id="qrDayTip" title="Day Quick Read uses ΔOI since market open (09:15 IST baseline) when available. If the system started late and the baseline is after 09:20, it switches to NSE official CHNG IN OI for day-level support/resistance to avoid wrong bias.">ⓘ</span>
        </span>
      </div>
l OI):</b> <span id="meterResistance"></span></span>
      </div>

      <!-- Key Stats -->
      <div class="levels" style="margin-top:6px">
        <span><b>Price:</b> <span id="ksPrice" class="mono">-</span></span>
        <span><b>ATM:</b> <span id="ksAtm" class="mono">-</span></span>
                <span><b>Exp:</b> <span id="ksExp" class="mono">-</span></span>
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

      <!-- Max Pain + Gamma zone (approx) -->
      <div class="levels" style="margin-top:6px">
        <span><b>Max Pain (approx):</b> <span id="ksMaxPain" class="mono">-</span></span>
        <span><b>Gamma Zone:</b> <span id="ksGammaZone" class="mono">-</span></span>
      </div>

      <!-- Session stats -->
      <div class="levels" style="margin-top:6px">
        <span><b>Day Range:</b> <span id="ksDayRange" class="mono">-</span></span>
        <span><b>From Low / High:</b> <span id="ksFromHL" class="mono">-</span></span>
      </div>

      <!-- Intraday OI shift summary (plain English) -->
      <div class="levels" style="margin-top:6px">
        <span><b>OI Shift:</b> <span id="ksOiShift" class="mono">-</span></span>
      </div>

      <!-- Time-of-day OI profile (from metrics top strikes) -->
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
    <!-- RIGHT COLUMN: Heatmap + ZigZag stacked -->
    <div>
      <!-- Heatmap (top of right column) -->
        <div class="card" style="margin-top:16px">
          <b>Strike OI Heatmap (near ATM)</b>
          <div id="strikeHeatmap" class="hm-container"></div>
          <small class="muted">
            Each row = Strike vs stacked Call / Put OI near ATM. Bar length ≈ total OI ·
            Green = Put-heavy support · Red = Call-heavy resistance · Blue = balanced.
          </small>
        </div>
      <!-- ZigZag directly under Heatmap -->
        <div class="card" id="zzCard" style="margin-top:10px; display:none">
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
          <b>Last 8 Classifications</b>
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

        // Week: last 5 trading sessions → end
        $weekStart = oi_backTradingDays($end, 5);

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
          'week' => 'Week change over last 5 trading sessions (' . oi_fmtRange($weekStart, $end) . ')',
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

            <?php foreach (['day'=>'Day','week'=>'Week','d30'=>'30D','mtd'=>'MTD','year'=>'YTD'] as $k=>$label): ?>
              <div class="mvLbl" title="<?= esc($tips[$k] ?? '') ?>"><?= esc($label) ?></div>

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
<?php endif; ?>
<a href="/oi/index-eod/export/<?= date('Y') ?>" class="btn">Export <?= date('Y') ?> CSV</a>
<?php
$lu = $indexLastUpdate ?? [];
$st = $lu['status'] ?? 'UNKNOWN';

$badge = [
  'OK'      => ['bg'=>'rgba(34,197,94,.18)',  'fg'=>'#22c55e', 'txt'=>'OK'],
  'STALE'   => ['bg'=>'rgba(239,68,68,.18)',  'fg'=>'#ef4444', 'txt'=>'STALE'],
  'UNKNOWN' => ['bg'=>'rgba(148,163,184,.18)','fg'=>'#94a3b8', 'txt'=>'UNKNOWN'],
];

$b = $badge[$st] ?? $badge['UNKNOWN'];
?>
<span style="font-size:12px;padding:4px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.12);
            background:<?= $b['bg'] ?>;color:<?= $b['fg'] ?>;">
  Index EOD: <?= esc($b['txt']) ?>
  · <?= esc($lu['maxDate'] ?? '—') ?>
  · Updated: <?= esc($lu['maxFetched'] ?? '—') ?>
  <?php if (($lu['minsAgo'] ?? null) !== null): ?>(<?= (int)$lu['minsAgo'] ?>m ago)<?php endif; ?>
</span>

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

  <!-- ========================================================= -->
  <!-- INDEX MOVEMENT (EOD) : NIFTY & BANKNIFTY                  -->
  <!-- Daily / Weekly / Monthly close-to-close movement         -->
  <!-- ========================================================= -->

  <div style="margin-top:10px;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(0,0,0,.22);">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
      <div style="font-weight:900;">Month / YTD Context</div><span class="tip tip-i" title="Move = Latest Close − Period start Close (close→close).
O→C = Latest Close − Period start Open (opening bias).
If O→C diverges a lot from Move, it often signals gap acceptance/rejection (gap trap)."
      style="margin-left:8px; font-size:12px; opacity:.75; cursor:help;">
  ⓘ
</span>
      <div class="muted" style="font-size:12px;">EOD-derived • fast cached</div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2,minmax(320px,1fr));gap:12px;margin-top:10px;">
      <?php foreach ($indexMY as $sym => $m): ?>
        <?php if (empty($m['ok'])) continue; ?>
        <div style="padding:10px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);">
          <div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px;">
            <div style="font-weight:900;"><?= esc($sym) ?></div>
            <div class="muted" style="font-size:12px;"><?= esc($m['date']) ?></div>
          </div>
          <div style="margin-top:6px;font-size:16px;font-weight:900;">Close: <?= $fmt2($m['close']) ?></div>

          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:10px;margin-top:10px;">
            <?php foreach (['month'=>'MTD','ytd'=>'YTD'] as $k => $label): ?>
              <?php $p = $m[$k] ?? []; $mv = $p['move'] ?? []; 
              $ocPts = (isset($p['o'], $p['c']) && $p['o'] !== null && $p['c'] !== null) ? ((float)$p['c'] - (float)$p['o']) : null;
              $ocPct = ($ocPts !== null && $p['o']) ? ($ocPts / (float)$p['o']) * 100.0 : null;

              // Auto-hide O→C if it is very close to Move (noise threshold)
              $showOC = true;

              if ($ocPts !== null && isset($mv['pts']) && $mv['pts'] !== null) {
                  // hide if difference < 0.15% of index value OR < 15 points
                  $diffPts = abs($ocPts - $mv['pts']);
                  $diffPct = abs(($ocPct ?? 0) - ($mv['pct'] ?? 0));

                  if ($diffPts < 15 || $diffPct < 0.15) {
                      $showOC = false;
                  }
              }

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
        title="MTD performance using closing prices.
Calculated as: Latest Close − First trading day Close of the period.">
    Move
  </span>
</div>

<div>
  <?= $arrow($mv['dir'] ?? 0) ?>
  <?= $fmt2($mv['pts'] ?? null) ?> (<?= $fmtPct($mv['pct'] ?? null) ?>)

  <?php if ($showOC): ?>
    <span class="muted">|</span>
    <span class="tip tip-i oc-lite"
          title="Opening bias for the period.
Calculated as: Latest Close − First trading day Open.">
      O→C <?= $fmt2($ocPts) ?> (<?= $fmtPct($ocPct) ?>)
    </span>
  <?php endif; ?>
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

                  <div>OI</div>
                  <div><?= esc($p['oi'] ?? '—') ?></div>
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
<?php endif; ?>



<div class="card" id="daywiseCard">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
        <b>Last Few Days — OI &amp; Price Behavior</b> <span class="muted" style="font-size:12px;" title="VOL is summed from NSE option-chain totalTradedVolume for CE+PE across strikes at the snapshot timestamp.">(VOL = NSE totalTradedVolume (CE+PE sum) at snapshot time)</span>
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
      </div>

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

    

  <!-- OI TRACK CARD -->
  <div class="card card-oi-track">
    <div class="card-oi-header">
      <div>
        <div class="card-oi-title">OI Track</div>
        <div class="muted small">Multi-timeframe OI &amp; ΔOI by strike (today vs intraday windows)</div>
      </div>
      <div class="oi-toolbar">
        <label class="pill">Timeframes (m):
          <input id="tfInput" value="3,5,10,15,30" class="mono" style="width:180px">
          <button id="tfApply">Apply</button>
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


    <div class="hscroll oi-track-scroll" style="overflow-x:visible">
      <table class="table table-sm" id="oiTrackTable">
        <thead><tr id="trackHeadRow"></tr></thead>
        <tbody id="trackTable"></tbody>
      </table>
    </div>
  </div>
  



  <!-- ===== NSE Style Option Chain (synced from OI Track /oi/track) ===== -->
  <div class="nse-oc-wrap" id="nseOcWrap">
    <div class="nse-oc-hdr">
      <div class="ttl">NSE Style Option Chain</div>
      <div class="nse-oc-tf" id="nseOcTf"></div>
      <div class="rhs">
        <span class="chip" id="nseOcStatus">Waiting…</span>
        <span class="chip">PCR: <b id="nsePcr">—</b></span>
        <span class="chip">Max Pain: <b id="nseMaxPain">—</b></span>
              <span class="chip">MAX Δ CE: <b id="nseMaxDeltaCe">—</b></span>
        <span class="chip">MAX Δ PE: <b id="nseMaxDeltaPe">—</b></span>
      </div>
    </div>

    <div class="nse-oc-sum" id="nseOcSum" style="display:none">
      <div class="sum-left">
        <span class="pill" id="nseSumLabel">Change</span>
        <span class="sum-item">Call OI change <b id="nseSumCall">—</b></span>
        <span class="sum-item">Put OI change <b id="nseSumPut">—</b></span>
      </div>
      <div class="sum-right">
        <span id="nseSumPrice">—</span>
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
      </table>
    </div>
  </div>


  <!-- Advanced (strike deltas) -->
  <details class="card" style="margin-top:16px" id="advancedStrikeDeltas">
    <summary style="cursor:pointer;font-weight:700">Advanced: Strike Deltas (Delta by Strike + Net ΔOI)</summary>
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


  </details>
<details id="holDetails" style="margin-top:10px;">
  <summary><strong>NSE Holidays (F&amp;O)</strong> <span class="muted">— used to ignore holidays in “Last Few Days — OI &amp; Price Behavior”</span></summary>
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
</details>

<script>
  // Start collapsed by default (same behavior as Advanced section)
  (function(){
    const d = document.getElementById('holDetails');
    if (d) d.open = false;
  })();
</script>


<script>
// ====== Config ======
const AUTO_REFRESH_SEC = 60;
const timerEl = document.getElementById('refreshTimer');

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

function getMarketStatusIST(){
  const now = new Date();
  const ist = new Date(now.toLocaleString('en-US', { timeZone:'Asia/Kolkata' }));

  const day = ist.getDay(); // 0 Sun ... 6 Sat
  if (day === 0) return { open:false, reason:'Sunday' };
  if (day === 6) return { open:false, reason:'Saturday' };

  const mins = ist.getHours()*60 + ist.getMinutes();
  const openM  = 9*60 + 15;
  const closeM = 15*60 + 30;

  if (mins < openM)  return { open:false, reason:'Pre-open' };
  if (mins > closeM) return { open:false, reason:'After-hours' };

  return { open:true, reason:'Market Hours' };
}

function isMarketOpenIST(){
  return getMarketStatusIST().open;
}

function updateMarketBadge(){
  const el = document.getElementById('marketBadge');
  if (!el) return;

  const st = getMarketStatusIST();
  el.classList.remove('market-open','market-closed','market-preopen','market-post');

  if (st.open){
    el.classList.add('market-open');
    el.textContent = 'Market Open';
    return;
  }

  // closed variants
  if (st.reason === 'Pre-open'){
    el.classList.add('market-preopen');
    el.textContent = 'Pre-open';
  } else {
    el.classList.add('market-closed');
    el.textContent = 'Market Closed';
  }

  // small hint text
  el.title = st.reason;
}

function updateSessionAndDrift(){
  const st = getMarketStatusIST();
  document.getElementById('sessionTag').textContent = st.open ? 'Live (Market Hours)' : `After Hours (${st.reason})`;

  updateMarketBadge(); // ✅ add this line

  if (UI.lastServerTsUTC){
    const d = new Date(String(UI.lastServerTsUTC).replace(' ','T')+'Z');
    const istNow = new Date(new Date().toLocaleString('en-US',{timeZone:'Asia/Kolkata'}));
    const diffMin = Math.round( Math.abs(istNow - d)/60000 );
    const el = document.getElementById('clockDrift');
    el.textContent = diffMin>2 ? `Clock drift ~${diffMin}m` : '';
  }
}
function initSafetyToggle(){
  const t = document.getElementById('safetyToggle');
  const badge = document.getElementById('safetyBadge');
  if (!t) return;

  const saved = localStorage.getItem('oiSafetyMode');
  const on = (saved === '1');
  t.checked = on;
  window.__safetyMode = on;
  if (badge) badge.style.display = on ? '' : 'none';

  t.addEventListener('change', ()=>{
    const v = !!t.checked;
    localStorage.setItem('oiSafetyMode', v ? '1' : '0');
    window.__safetyMode = v;
    if (badge) badge.style.display = v ? '' : 'none';

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
  document.getElementById('pcr').textContent = j.pcr ?? '-';
  document.getElementById('bias').textContent = j.bias ?? '-';
  //document.getElementById('atm').textContent = j.atm ?? '-';
  // --- Fix stuck ATM: derive from underlying if needed ---
  let atmVal = Number(j.atm);

  // ✅ use underlying if present, else fallback to price
  const spot = Number.isFinite(Number(j.underlying))
    ? Number(j.underlying)
    : Number(j.price);

  // NIFTY step = 50 (adjust if you ever add BANKNIFTY logic)
  const step = 50;

  if (Number.isFinite(spot)) {
    const derived = Math.round(spot / step) * step;

    // override if missing OR clearly wrong
    if (!Number.isFinite(atmVal) || Math.abs(atmVal - derived) >= step) {
      atmVal = derived;
    }
  }

  j.atm = atmVal;
  document.getElementById('atm').textContent = Number.isFinite(atmVal) ? String(atmVal) : '-';

  // keep both: UI + enhancer fallback
  j.atm = atmVal;
  document.getElementById('atm').textContent = Number.isFinite(atmVal) ? String(atmVal) : '-';


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
}
let sortKey = 'absnet'; // 'strike'|'calld'|'putd'|'net'|'absnet'
let sortAsc = false;

function renderDeltaTable(j){
  if(!j) return;
  window.__lastDeltaJson = j;

  const atm    = j.atm;
  const band   = UI.atmBand;
  const around = UI.strikeWin;

  const showCalls = UI.optFilter !== 'puts';
  const showPuts  = UI.optFilter !== 'calls';

  // ATM±2 only toggle (persist)
  const cb = document.getElementById('deltaAtmOnly');
  if (cb && cb.dataset.bound !== '1') {
    cb.dataset.bound = '1';
    const saved = localStorage.getItem('deltaAtmOnly');
    if (saved !== null) cb.checked = (saved === '1');
    cb.addEventListener('change', () => {
      localStorage.setItem('deltaAtmOnly', cb.checked ? '1' : '0');
      renderDeltaTable(window.__lastDeltaJson || j); // re-render
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
      withinATM: Math.abs(st - atm) <= band*50,
      withinWin: around===999 ? true : Math.abs(st - atm) <= around*50
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

  const showCalls = UI.optFilter !== 'puts';
  const showPuts  = UI.optFilter !== 'calls';

  const strikes = new Set([
    ...Object.keys(j.callDelta || {}),
    ...Object.keys(j.putDelta  || {})
  ]);

  let rows = [...strikes].map(s=>{
    const st = parseInt(s,10);
    const rawCE = Number(j.callDelta?.[s] ?? 0);
    const rawPE = Number(j.putDelta?.[s] ?? 0);

    return {
      st,
      cd: showCalls ? rawCE : 0,
      pd: showPuts  ? rawPE : 0,
      cdRaw: rawCE,
      pdRaw: rawPE,
      net: (showPuts ? rawPE : 0) - (showCalls ? rawCE : 0),
      withinWin: around===999 ? true : Math.abs(st - atm) <= around*50
    };
  }).filter(r => r.withinWin)
    .sort((a,b)=>a.st-b.st);

  const maxAbs = Math.max(1, ...rows.map(r=>Math.abs(r.net)));
  const fmt = n => Number(n).toLocaleString('en-IN');

  // Mini bars
  document.getElementById('netBarsBody').innerHTML = rows.map(r=>{
    const pct = Math.abs(r.net)/maxAbs;
    const bar = r.net>=0
      ? `<rect x="50" y="2" width="${50*pct}" height="10" fill="#103b2d"></rect>`
      : `<rect x="${50 - 50*pct}" y="2" width="${50*pct}" height="10" fill="#3b1010"></rect>`;
    const label = r.net>=0 ? 'PutΔ>' : '<CallΔ';
    return `
    <tr>
      <td class="mono">${r.st}</td>
      <td>
        <svg viewBox="0 0 100 14" preserveAspectRatio="none">
          <rect x="49.5" y="0" width="1" height="14" fill="#334155"></rect>
          ${bar}
        </svg>
      </td>
      <td class="mono">${label} ${fmt(r.net)}</td>
    </tr>`;
  }).join('');

  // Table
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

  UI.netDeltaSum = rows.reduce((s,r)=>s+r.net,0);
}

// ===== Big OI Δ vs Strike chart (Sensibull-style) =====
function renderOiChangeChart(j){
  if (!j) return;
  const svg = document.getElementById('oiChangeChart');
  if (!svg) return;

  const atm = j.atm;
  const around = UI.strikeWin;

  const showCalls = UI.optFilter !== 'puts';
  const showPuts  = UI.optFilter !== 'calls';

  const calls = j.callDelta || {};
  const puts  = j.putDelta  || {};

  const strikes = new Set([...Object.keys(calls), ...Object.keys(puts)]);

  const rows = [...strikes].map(st=>{
    st = Number(st);
    const cd = showCalls ? Number(calls[st]||0) : 0;
    const pd = showPuts  ? Number(puts[st] ||0) : 0;
    return {
      st,
      cd,
      pd,
      within: around===999 ? true : Math.abs(st-atm)<=around*50
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
    // Your UI.strikeWin is “± strikes” where each strike step is 50 (you use 50 elsewhere).
    // So range = strikeWin * 50 points
    const strikeWin = (window.UI && UI.strikeWin!=null) ? Number(UI.strikeWin) : 10;
    const step = 50;

    if (atm && strikeWin !== 999){
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
    _pill(brkEl,   'Breakout: —', 'neu');
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

  // --- False breakout warning (today move vs baseline OI follow-through)
  const dP = Number(j.current.d_price || 0);
  const dOi = Number(j.current.d_oi || 0);
  const baseOi = Number(j?.baseline?.avg_abs_doi || 0);
  const baseVol = Number(j?.baseline?.avg_abs_dvol || 0);
  const pxThresh = (symbol==='BANKNIFTY') ? 220 : (symbol==='FINNIFTY'?120:80);
  const breakout = Math.abs(dP) >= pxThresh;
  const oiWeak = baseOi>0 ? (Math.abs(dOi) < 0.45*baseOi) : (Math.abs(dOi) < 250000);
  const brkWarn = breakout && oiWeak;
  if (!breakout) {
    _pill(brkEl, `Breakout: No (${dP>=0?'+':''}${dP.toFixed(0)} pts)`, 'neu');
  } else if (brkWarn) {
    _pill(brkEl, `Breakout: ⚠︎ Not confirmed`, 'warn', `ΔP=${dP.toFixed(2)} pts, ΔOI=${dOi.toLocaleString('en-IN')} vs baseline≈${Math.round(baseOi).toLocaleString('en-IN')}`);
  } else {
    _pill(brkEl, `Breakout: Confirmed`, (dP>0?'good':'bad'), `ΔP=${dP.toFixed(2)} pts, ΔOI=${dOi.toLocaleString('en-IN')}`);
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
// --- Global Filter: Both / Calls / Puts ---
function updateSideFilterVisual(){
  const pill = document.getElementById('sideFilterPill');
  if (!pill) return;
  pill.classList.remove('side-both','side-calls','side-puts');
  if (UI.optFilter === 'calls')      pill.classList.add('side-calls');
  else if (UI.optFilter === 'puts')  pill.classList.add('side-puts');
  else                               pill.classList.add('side-both');
}

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

    updateSideFilterVisual();
  });
});

// Initial state on load
UI.optFilter = 'both';
updateSideFilterVisual();


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

    // If signals are paused due to stale/gap:
    // ✅ Safety mode ON -> still load everything (view last day data)
    // ✅ Safety mode OFF -> stop addons/signals decisions
    const safetyOn = !!window.__safetyMode;

    if (window.__signalsPaused && !safetyOn){
      // keep UI visible but avoid acting on stale/gapped data
      return;
    }

    await loadMetrics();
    await loadDaywise();
    await loadAddons();
    await loadSignals();
    await loadNextExpiryCard();
    await loadBreakout5m();
    await loadTrack();
    updateMarketBadge(); // ✅ add this at the end also
  }catch(e){}
}

function tick(){
  countdown--;
  if (countdown <= 0){ countdown = AUTO_REFRESH_SEC; doRefresh(); }
  timerEl.textContent = countdown;
  setTimeout(tick, 1000);
}
/*function scrollToOiTrack(){
  const tbl = document.getElementById('oiTrackTable');
  if (!tbl) return;

  const offset = 80;
  const top = tbl.getBoundingClientRect().top + window.scrollY - offset;

  window.scrollTo({
    top,
    behavior: 'smooth'
  });
}*/
function scrollToOiTrack(){
  const card = document.getElementById('oiAtmSummaryCard'); // ✅ new
  const tbl  = document.getElementById('oiTrackTable');
  const el   = card || tbl;
  if (!el) return;

  const r = el.getBoundingClientRect();
  const top = window.scrollY + r.top - 90;
  window.scrollTo({ top, behavior: 'smooth' });
}


// ====== Init ======
(async function init(){
  initModeToggles();
  initSafetyToggle();      // ✅ ADD THIS
  await loadExpiries();
  updateSessionAndDrift();
  await doRefresh();
  setTimeout(scrollToOiTrack, 250);
  timerEl.textContent = countdown;

  updateMarketBadge();     // ✅ ADD THIS
  setInterval(updateMarketBadge, 15*1000); // ✅ keep badge correct

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
  const lbl1 = document.getElementById('qrDayLbl');
  if (lbl1) lbl1.textContent = dayLblText;
  const meta1 = document.getElementById('qrDayMeta');
  if (meta1) meta1.textContent = dayMetaText;
  const lbl2 = document.getElementById('qrDayLbl2');
  if (lbl2) lbl2.textContent = dayLblText;
  const meta2 = document.getElementById('qrDayMeta2');
  if (meta2) meta2.textContent = dayMetaText;
  const wEl = document.getElementById('qrIntraWin');
  if (wEl) wEl.textContent = (j.window ? `${j.window}m` : '-');

  const iEl = document.getElementById('qrIntra');
  if (iEl) iEl.innerHTML = intraTxt;

  const dEl = document.getElementById('qrDay');
  if (dEl) dEl.innerHTML = dayTxt;

  // Also render inside OI Track header (visible near the table)
  const wEl2 = document.getElementById('qrIntraWin2');
  if (wEl2) wEl2.textContent = (j.window ? `${j.window}m` : '-');
  const iEl2 = document.getElementById('qrIntra2');
  if (iEl2) iEl2.innerHTML = intraTxt;
  const dEl2 = document.getElementById('qrDay2');
  if (dEl2) dEl2.innerHTML = dayTxt;
}


// Approximate Max Pain & Gamma zone using visible strikes
function computeMaxPainAndGamma(j){
  const calls = j.topCalls || {};
  const puts  = j.topPuts  || {};
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

  const supportHtml = (UI.supportList||[]).map(x=>`<span class="badge b-green">${x.strike}</span>`).join('');
  const resistHtml  = (UI.resistList ||[]).map(x=>`<span class="badge b-red">${x.strike}</span>`).join('');

  document.getElementById('meterBadge').innerHTML   = badgeHtml;
  document.getElementById('meterHeadline').innerHTML =
    `${j.symbol} @ <span class="mono">${Number(j.price||0).toFixed(2)}</span> · Exp <span class="mono">${j.expiry}</span>`;
  document.getElementById('meterWhy').innerHTML = reasons.length
    ? `Why: ${reasons.join(' · ')}`
    : 'Why: —';
  document.getElementById('meterSupport').innerHTML    = supportHtml || '—';
  document.getElementById('meterResistance').innerHTML = resistHtml  || '—';

  const alertsEl = document.getElementById('meterAlerts');
  if (alertsEl){
    const chips = buildAlerts(j);
    alertsEl.innerHTML = chips.join(' ');
  }

  const ksPriceEl = document.getElementById('ksPrice');
  if (ksPriceEl){
    ksPriceEl.textContent = Number(j.price || 0).toFixed(2);
    document.getElementById('ksAtm').textContent  = j.atm ?? '-';
    const ksExpEl = document.getElementById('ksExp');
    if (ksExpEl) ksExpEl.textContent = (j.expiry ?? '-');
    document.getElementById('ksPcr').textContent  = j.pcr ? Number(j.pcr).toFixed(2) : '-';
    document.getElementById('ksBias').textContent = j.bias ?? '-';

    const ksAtmZoneEl  = document.getElementById('ksAtmZone');
    const ksPriceOIEl  = document.getElementById('ksPriceOI');
    const ksOiPressEl  = document.getElementById('ksOiPressure');
    const ksTrendEl    = document.getElementById('ksTrendConf');
    const ksBoUpEl     = document.getElementById('ksBoUp');
    const ksBoDownEl   = document.getElementById('ksBoDown');
    const ksMaxPainEl  = document.getElementById('ksMaxPain');
    const ksGammaEl    = document.getElementById('ksGammaZone');
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

    // Max pain + gamma
    const mp = computeMaxPainAndGamma(j);
    UI.maxPainData = mp;
    if (ksMaxPainEl){
      ksMaxPainEl.textContent = mp?.maxPain ? String(mp.maxPain) : '-';
    }
    if (ksGammaEl){
      ksGammaEl.textContent = mp?.gammaZone
        ? `${mp.gammaZone.lo}–${mp.gammaZone.hi}`
        : '-';
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
async function loadTrack(){
  const symbol = document.getElementById('symbol').value;
  const expiry = document.getElementById('expirySel')?.value || '';

    let lbs = (document.getElementById('tfInput')?.value || '3,5,10,15,30').replace(/\s+/g,'');
  // Ensure 1m is always requested (for NSE-style table 1m ΔOI)
  const _lbsArr = lbs.split(',').filter(Boolean);
  if(!_lbsArr.includes('1')) _lbsArr.unshift('1');
  lbs = _lbsArr.join(',');
  const strikes = parseInt(document.getElementById('strikeWinServer')?.value || '10', 10) || 10;

  const url = `/oi/track?symbol=${encodeURIComponent(symbol)}`
            + (expiry ? `&expiry=${encodeURIComponent(expiry)}` : '')
            + `&lookbacks=${encodeURIComponent(lbs)}&strikes=${strikes}`;

  const res = await fetch(url);
  const j = await res.json().catch(()=>null);
  // --- ATM fallback: recompute if stuck/wrong ---
  (function fixAtm(j){
    const step = 50; // NIFTY/BANKNIFTY usually 50
    const spot =
      Number(j.spot || j.underlying || j.ltp || j.fut || j.index || NaN);

    if (Number.isFinite(spot)) {
      const derived = Math.round(spot / step) * step;

      // if atm missing OR looks stuck, override
      if (!Number.isFinite(Number(j.atm)) || Number(j.atm) !== derived) {
        j.atm = derived;
      }
    }
  })(j);
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

  try{ if (window.renderNseOcFromTrack) window.renderNseOcFromTrack(j); }catch(e){ console.warn('NSE OC render failed', e); }

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

<script>
(function(){
  /* ================= CONFIG ================= */
  const CRON_WARN_MIN = 5;      // blink after
  const CRON_SOUND_MIN = 8;     // 🔊 sound after
  const LIVE_MAX_MIN = 3;       // DB fresh = LIVE
  const STALE_PAUSE_MIN = 15;   // during Market OPEN, pause signals if DB older than this
  const GAP_PAUSE = true;

  const MARKET = {
    open:  9*60 + 15,
    close: 15*60 + 30
  };

  /* ================= ELEMENTS ================= */
  const bar = document.querySelector(".status-bar");
  const badge = document.getElementById("marketBadge");
  const audio = document.getElementById("audioCronAlert");

  let soundPlayed = false;

  /* ================= TIME HELPERS ================= */
  function nowIST(){
    return new Date(new Date().toLocaleString("en-US",{timeZone:"Asia/Kolkata"}));
  }
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
  function marketState(){
    const d = nowIST();
    const m = d.getHours()*60 + d.getMinutes();

    if (m < MARKET.open) return "PREOPEN";
    if (m <= MARKET.close) return "OPEN";
    if (m <= MARKET.close + 60) return "POST";
    return "CLOSED";
  }

  function getMarketBadgeEl(){
  // 1) preferred ids / selectors
  return (
    document.getElementById('marketBadge') ||
    document.querySelector('[data-market-badge]') ||
    document.querySelector('.marketBadge') ||
    document.querySelector('.market-badge') ||

    // 2) fallback: find a "Market ..." pill in header
    [...document.querySelectorAll('span,div,a,button')].find(el=>{
      const t = (el.textContent || '').trim();
      if (!t) return false;
      const looksLike = /^Market\s*(OPEN|Open|Closed|CLOSED|Pre-open|PRE-OPEN)$/i.test(t);
      const headerish = el.closest('#hdr, #topbar, .topbar, header, .header') || el.parentElement;
      return looksLike && headerish;
    })
  );
}

function updateMarketBadge(){
  const el = getMarketBadgeEl();
  if (!el) return;

  const st = getMarketStatusIST();

  // remove all variants
  el.classList.remove('market-open','market-closed','market-preopen','market-post');

  if (st.open){
    el.classList.add('market-open');
    el.textContent = 'Market OPEN';
    el.title = 'Market Hours';
    return;
  }

  if (st.reason === 'Pre-open'){
    el.classList.add('market-preopen');
    el.textContent = 'Pre-open';
    el.title = 'Pre-open';
  } else {
    el.classList.add('market-closed');
    el.textContent = 'Market CLOSED';
    el.title = st.reason; // Saturday/Sunday/After-hours
  }
}


  /* ================= DOT + SOUND ================= */
  function setDot(dot, mins){
    if(!dot) return;
    dot.classList.remove("ok","warn","bad","blink");

    const open = marketState() === "OPEN";

    if (mins <= 2) {
      dot.classList.add("ok");
      soundPlayed = false;
    }
    else if (mins <= CRON_WARN_MIN) {
      dot.classList.add("warn");
      soundPlayed = false;
    }
    else {
      dot.classList.add("bad");
      if (open) dot.classList.add("blink"); // 📉 hide blink when closed

      if (mins >= CRON_SOUND_MIN && open && !soundPlayed) {
        audio?.play().catch(()=>{});
        soundPlayed = true;
      }
    }
  }

  /* ================= LIVE / FROZEN ================= */
  function updateLiveState(dbMins){
    // Removed duplicate market status pill (it was showing CLOSED/LIVE/FROZEN in addition to the main Market badge)
    try{
      const old = document.getElementById("dataState");
      if(old) old.remove();
    }catch(_){}

    // Keep this useful behavior: auto-collapse Advanced Delta section after 15:30 IST
    try{
      const det = document.getElementById("advancedStrikeDeltas");
      const n = nowIST();
      const hhmm = Number(String(n.getHours()).padStart(2,'0') + String(n.getMinutes()).padStart(2,'0'));
      if(det && hhmm >= 1530) det.open = false;
    }catch(_){}
  }

  function updateFreshness(){
    const now = nowIST();
    let dbAge = 999;

    [
      {ts:"tsDb", dot:"dotDb", isDb:true},
      {ts:"tsFetch", dot:"dotFetch"},
      {ts:"tsEnrich", dot:"dotEnrich"},
    ].forEach(x=>{
      const tsEl = document.getElementById(x.ts);
      const dotEl = document.getElementById(x.dot);
      const d = parseIST(tsEl?.textContent);
      if(!d) return;
      const mins = Math.max(0, Math.floor((now - d)/60000));
      setDot(dotEl, mins);
      if (x.isDb) dbAge = mins;
    });

    updateLiveState(dbAge);

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
      const dNow = nowIST();
      const lastDay = new Date(lastDb.getFullYear(), lastDb.getMonth(), lastDb.getDate());
      const nowDay  = new Date(dNow.getFullYear(),  dNow.getMonth(),  dNow.getDate());
      gapDays = Math.round((nowDay - lastDay) / (24*3600*1000));
      // if lastDb is today => 0
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
                     id==="strikeShiftBadge" ? "Strike Shift" : "Breakout";
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


  // ===== Safety Mode (gap override) =====
  function getSafetyMode(){
    try{ return localStorage.getItem("oiSafetyMode")==="1"; }catch(_){ return false; }
  }
  function setSafetyMode(on){
    try{ localStorage.setItem("oiSafetyMode", on ? "1" : "0"); }catch(_){}
    window.__oiSafetyMode = !!on;
    const hint = document.getElementById("safetyHint");
    if (hint) hint.textContent = on ? "Show last day" : "Auto-pause on gap";
    const badge = document.getElementById("safetyBadge");
    if (badge) badge.style.display = on ? "" : "none";
  }
  function initSafetyToggle(){
    const cb = document.getElementById("safetyToggle");
    if(!cb) return;
    const keyMissing = (localStorage.getItem("oiSafetyMode")===null);
    const gapEl = document.getElementById("dataGapBadge");
    const hasGapUI = gapEl && gapEl.style.display !== "none";
    const initVal = keyMissing ? !!hasGapUI : getSafetyMode();
    cb.checked = initVal;
    setSafetyMode(initVal);
    cb.addEventListener("change", ()=> setSafetyMode(cb.checked));
  }

  /* ================= LOOPS ================= */
  initSafetyToggle();
  updateMarketBadge();
  updateFreshness();

  setInterval(updateMarketBadge, 1000);
  setInterval(updateFreshness, 60000);
})();
</script>
<script src="<?= base_url('assets/js/oi_track_enhancer.js?v=2025-12-31e') ?>"></script>


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
    if (el.dataset.tip) return;

    const t = el.getAttribute('title');
    if (!t || !t.trim()) return;

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

  function computePCR(rows){
    let ce=0, pe=0;
    rows.forEach(r=>{
      const ceOi = Number(r?.CE?.cur_oi);
      const peOi = Number(r?.PE?.cur_oi);
      if(Number.isFinite(ceOi)) ce += ceOi;
      if(Number.isFinite(peOi)) pe += peOi;
    });
    if(ce<=0) return null;
    return pe/ce;
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
  let __nseOcLastJ = null;
  let __nseOcTf = null; // number (minutes) or 'DAY'

  function buildNseTfButtons(lookbacks){
    const wrap = document.getElementById('nseOcTf');
    if(!wrap) return;

    const lbs = (Array.isArray(lookbacks) && lookbacks.length) ? lookbacks.slice() : [1,3,5,10,15,30];

    // Unique minutes (ascending), always include 1m if backend supports it
    let mins = Array.from(new Set(lbs.map(Number).filter(Number.isFinite)));
    if(!mins.includes(1)) mins.push(1);
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
        if(__nseOcLastJ) window.renderNseOcFromTrack(__nseOcLastJ, true);
      });
      return b;
    };

    mins.forEach(m=>wrap.appendChild(mkBtn(m+'m', Number(m))));
    wrap.appendChild(mkBtn('Day','DAY'));
  }

  function getChgForTf(sideSnap, tf){
    if(!sideSnap) return null;
    if(tf === 'DAY') return (sideSnap.cur_chg ?? null);
    const m = Number(tf);
    const prevOi = sideSnap['oi_'+m+'m'];
    const curOi  = sideSnap.cur_oi;
    if(prevOi===null || prevOi===undefined) return null;
    if(curOi===null || curOi===undefined) return null;
    const d = Number(curOi) - Number(prevOi);
    return Number.isFinite(d) ? d : null;
  }

  function getPrevOiForTf(sideSnap, tf){
    if(!sideSnap) return null;
    if(tf === 'DAY'){
      const curOi = sideSnap.cur_oi;
      const chg  = sideSnap.cur_chg;
      if(curOi===null || curOi===undefined) return null;
      if(chg===null || chg===undefined) return null;
      const prev = Number(curOi) - Number(chg);
      return Number.isFinite(prev) ? prev : null;
    }
    const m = Number(tf);
    const prevOi = sideSnap['oi_'+m+'m'];
    return (prevOi===null || prevOi===undefined) ? null : Number(prevOi);
  }

  function fmtDeltaCell(v, prevOi){
    if(v===null || v===undefined || !Number.isFinite(Number(v))){
      return `<span class="muted">—</span>`;
    }
    const n = Number(v);
    const arr = n>0 ? '▲' : (n<0 ? '▼' : '•');

    let pct = null;
    if(prevOi!==null && prevOi!==undefined){
      const p = Number(prevOi);
      if(Number.isFinite(p) && p!==0){
        pct = (n / p) * 100;
      }
    }

    // Remove '-' sign for negatives: arrow shows direction, numbers shown as absolute
    const pctHtml = (pct===null || !Number.isFinite(pct))
      ? ''
      : `<span class="pct">(${Math.abs(pct).toFixed(1)}%)</span>`;

    return `<span class="dcell"><span class="arr">${arr}</span><span class="num">${fmt(Math.abs(n))}</span>${pctHtml}</span>`;
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
    if(!sumWrap || !elCall || !elPut || !elPx || !elLbl) return;

    // totals across displayed strikes (same window as your OI Track strikes)
    let ceTot = 0, peTot = 0;
    rows.forEach(r=>{
      const ce = getChgForTf(r?.CE, __nseOcTf);
      const pe = getChgForTf(r?.PE, __nseOcTf);
      if(Number.isFinite(Number(ce))) ceTot += Number(ce);
      if(Number.isFinite(Number(pe))) peTot += Number(pe);
    });

    elCall.textContent = fmtLakhCr(ceTot);
    elPut.textContent  = fmtLakhCr(peTot);

    // Price & time from the main snapshot (lastJson), fallback to track meta
    const sym = (window.lastJson && window.lastJson.symbol) ? window.lastJson.symbol : (j?.symbol || 'NIFTY');
    const px  = (window.lastJson && window.lastJson.price!=null) ? Number(window.lastJson.price) : null;
    const ts  = (window.lastJson && window.lastJson.ts) ? window.lastJson.ts : (j?.latestTs || null);
    const t12 = fmtISTTime12(ts);

    if(px!==null && Number.isFinite(px)){
      elPx.innerHTML = `<b>${sym}</b> at <b>${t12||'—'}</b> &nbsp; <b class="mono">${px.toFixed(2)}</b>`;
    } else {
      elPx.innerHTML = `<b>${sym}</b> &nbsp; <span class="muted">TS:</span> <span class="mono">${t12||'—'}</span>`;
    }

    const tfLabel = (__nseOcTf==='DAY') ? 'Full Day' : ('Last ' + __nseOcTf + ' mins');
    elLbl.textContent = 'Change · ' + tfLabel;

    sumWrap.style.display = 'flex';
  }


  window.renderNseOcFromTrack = function(j, _rerenderOnly){
    const statusEl = document.getElementById('nseOcStatus');
    const bodyEl = document.getElementById('nseOcBody');
    const headEl = document.getElementById('nseOcHead');
    if(!statusEl || !bodyEl || !headEl) return;

    // remember last payload for highlight re-render
    if(j) __nseOcLastJ = j;

    // build timeframe info + highlight selector
    buildNseTfButtons(j && j.lookbacks);

    if(!j || !j.ok || !Array.isArray(j.rows) || !j.rows.length){
      statusEl.textContent = 'No data';
      headEl.innerHTML = '';
      bodyEl.innerHTML = `<tr><td colspan="7" class="muted" style="text-align:center;padding:14px">No data</td></tr>`;
      document.getElementById('nsePcr') && (document.getElementById('nsePcr').textContent = '—');
      document.getElementById('nseMaxPain') && (document.getElementById('nseMaxPain').textContent = '—');
      const sw=document.getElementById('nseOcSum'); if(sw) sw.style.display='none';
      return;
    }

    const lookbacksRaw = (Array.isArray(j.lookbacks) && j.lookbacks.length) ? j.lookbacks.slice() : [5,10,15,30];
    // Normalize lookbacks, force include 1m if possible
    let lookAsc = Array.from(new Set(lookbacksRaw.map(Number).filter(Number.isFinite)));
    if(!lookAsc.includes(1)) lookAsc.push(1);
    lookAsc = lookAsc.sort((a,b)=>a-b);

    // CALLS columns: Day first, then ascending minutes
    const colsCalls = ['DAY', ...lookAsc];
    // PUTS columns: descending minutes first (30m,15m,10m...), Day last
    const colsPuts  = [...lookAsc].sort((a,b)=>b-a).concat(['DAY']);

    // For status display we show unique cols (Day + asc minutes)
    const colsStatus = colsCalls;


    // ensure highlight TF is valid
    if(__nseOcTf === null) __nseOcTf = 'DAY';
    const _validTfs = new Set([...colsCalls, ...colsPuts]);
    if(__nseOcTf !== 'DAY' && !_validTfs.has(Number(__nseOcTf))){
      __nseOcTf = 'DAY';
    }

    statusEl.textContent = 'SYNC ✓ · cols ' + colsStatus.map(c => (c==='DAY'?'Day':(c+'m'))).join(', ')
                        + ' · hi ' + (__nseOcTf==='DAY'?'Day':(__nseOcTf+'m'));

    const rows = j.rows.slice(); // keep order
    const atmStrike = getAtmStrike(j);

    // PCR + Max pain
    const pcr = computePCR(rows);
    const mp  = computeMaxPain(rows);
    const pcrEl = document.getElementById('nsePcr'); if(pcrEl) pcrEl.textContent = (pcr===null? '—' : fmt2(pcr));
    const mpEl  = document.getElementById('nseMaxPain'); if(mpEl) mpEl.textContent = (mp===null? '—' : fmt(mp));

    // Summary bar (timeframe click)
    updateNseOcSummary(j, rows);


    // max highlights (OI always, CHG for selected highlight TF only)
    let maxCeOi=-Infinity, maxPeOi=-Infinity, maxCeAbsChg=-Infinity, maxPeAbsChg=-Infinity;
    rows.forEach(r=>{
      const ceOi=Number(r?.CE?.cur_oi); if(Number.isFinite(ceOi)) maxCeOi=Math.max(maxCeOi, ceOi);
      const peOi=Number(r?.PE?.cur_oi); if(Number.isFinite(peOi)) maxPeOi=Math.max(maxPeOi, peOi);
      const ceV = Number(getChgForTf(r?.CE, __nseOcTf));
      const peV = Number(getChgForTf(r?.PE, __nseOcTf));
      const ceChg=Math.abs(ceV); if(Number.isFinite(ceChg)) maxCeAbsChg=Math.max(maxCeAbsChg, ceChg);
      const peChg=Math.abs(peV); if(Number.isFinite(peChg)) maxPeAbsChg=Math.max(maxPeAbsChg, peChg);
    });

    // MAX Δ badges (for selected highlight TF)
    let maxCeDelta=null, maxCeStrike=null, maxPeDelta=null, maxPeStrike=null;
    rows.forEach(r=>{
      const st = Number(r?.strike);
      const ceV = getChgForTf(r?.CE, __nseOcTf);
      const peV = getChgForTf(r?.PE, __nseOcTf);
      if(Number.isFinite(Number(ceV)) && Math.abs(Number(ceV))===maxCeAbsChg){
        maxCeDelta = Number(ceV); maxCeStrike = st;
      }
      if(Number.isFinite(Number(peV)) && Math.abs(Number(peV))===maxPeAbsChg){
        maxPeDelta = Number(peV); maxPeStrike = st;
      }
    });
    const ceBadge = document.getElementById('nseMaxDeltaCe');
    const peBadge = document.getElementById('nseMaxDeltaPe');
    if(ceBadge){
      ceBadge.textContent = (maxCeDelta===null||!Number.isFinite(maxCeDelta)) ? '—' : `${fmtLakhCr(maxCeDelta)} @ ${fmt(maxCeStrike)}`;
      ceBadge.className = (maxCeDelta===null) ? '' : (maxCeDelta>=0 ? 'pos' : 'neg');
    }
    if(peBadge){
      peBadge.textContent = (maxPeDelta===null||!Number.isFinite(maxPeDelta)) ? '—' : `${fmtLakhCr(maxPeDelta)} @ ${fmt(maxPeStrike)}`;
      peBadge.className = (maxPeDelta===null) ? '' : (maxPeDelta>=0 ? 'pos' : 'neg');
    }

    // ===== Build dynamic THEAD =====
    const Lc = colsCalls.length;
    const Lp = colsPuts.length;
    const callSpan = 2 + Lc; // OI + (ΔOI cols) + Vol
    const putSpan  = 2 + Lp; // Vol + (ΔOI cols) + OI

    const esc = (x)=>String(x).replace(/[&<>\"]/g, s=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[s]));

    const thColsCalls = colsCalls.map(c=>{
      const label = (c==='DAY') ? 'ΔOI Day' : ('ΔOI ' + c + 'm');
      const hi = (String(c)===String(__nseOcTf)) ? ' th-hi' : '';
      return `<th class="${hi}">${esc(label)}</th>`;
    }).join('');

    const thColsPuts = colsPuts.map(c=>{
      const label = (c==='DAY') ? 'ΔOI Day' : ('ΔOI ' + c + 'm');
      const hi = (String(c)===String(__nseOcTf)) ? ' th-hi' : '';
      return `<th class="${hi}">${esc(label)}</th>`;
    }).join('');

headEl.innerHTML =
      `<tr>
        <th colspan="${callSpan}" class="call-head">CALLS</th>
        <th class="strike-head sticky-strike">STRIKE</th>
        <th colspan="${putSpan}" class="put-head">PUTS</th>
      </tr>
      <tr>
        <th>OI</th>
        ${thColsCalls}
        <th>Vol</th>

        <th class="strike-head2 sticky-strike">Price</th>

        <th>Vol</th>
        ${thColsPuts}
        <th>OI</th>
      </tr>`;

    // ===== Build TBODY =====
    const html = [];
    rows.forEach(r=>{
      const st = Number(r?.strike);
      const CE = r?.CE || {};
      const PE = r?.PE || {};

      const ceOi  = (CE.cur_oi ?? null);
      const ceVol = (CE.cur_vol ?? null);

      const peOi  = (PE.cur_oi ?? null);
      const peVol = (PE.cur_vol ?? null);

      const ceOiCls  = (Number(ceOi)===maxCeOi) ? 'max-oi' : '';
      const peOiCls  = (Number(peOi)===maxPeOi) ? 'max-oi' : '';

      const strikeCls = (atmStrike!==null && Number.isFinite(st) && Number(st)===Number(atmStrike))
        ? 'strike atm sticky-strike'
        : 'strike sticky-strike';

      const rowCls = (atmStrike!==null && Number.isFinite(st) && Number(st)===Number(atmStrike)) ? 'atm-row' : '';

      const ceChgCells = colsCalls.map(c=>{
        const v = getChgForTf(CE, c);
        const cls = (v===null||v===undefined) ? '' : (Number(v)>=0 ? 'pos':'neg');
        const hi  = (String(c)===String(__nseOcTf) && Math.abs(Number(v))===maxCeAbsChg) ? 'max-chg' : '';
        const colhi = (String(c)===String(__nseOcTf)) ? ' col-hi' : '';
        return `<td class="${cls} ${hi}${colhi}">${fmtDeltaCell(v, getPrevOiForTf(CE, c))}</td>`;
      }).join('');

      const peChgCells = colsPuts.map(c=>{
        const v = getChgForTf(PE, c);
        const cls = (v===null||v===undefined) ? '' : (Number(v)>=0 ? 'pos':'neg');
        const hi  = (String(c)===String(__nseOcTf) && Math.abs(Number(v))===maxPeAbsChg) ? 'max-chg' : '';
        const colhi = (String(c)===String(__nseOcTf)) ? ' col-hi' : '';
        return `<td class="${cls} ${hi}${colhi}">${fmtDeltaCell(v, getPrevOiForTf(PE, c))}</td>`;
      }).join('');

      html.push(
        `<tr class="${rowCls}">
          <td class="${ceOiCls}">${fmt(ceOi)}</td>
          ${ceChgCells}
          <td>${fmt(ceVol)}</td>

          <td class="${strikeCls}">${fmt(st)}</td>

          <td>${fmt(peVol)}</td>
          ${peChgCells}
          <td class="${peOiCls}">${fmt(peOi)}</td>
        </tr>`
      );
    });

    const totalCols = 5 + colsCalls.length + colsPuts.length;
    bodyEl.innerHTML = html.join('') || `<tr><td colspan="${totalCols}" class="muted" style="text-align:center;padding:14px">No rows</td></tr>`;
  };
})();

</script>

</body>
</html>
