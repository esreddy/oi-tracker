
/* Ensure dropdowns are not clipped by the NSE section containers */
.nse-oc-shell, .nse-oc-header, .nse-oc-subbar, .nse-oc-wrap { overflow: visible; }
/* Make TF menu scrollable on small heights */
.tf-menu{ max-height: 70vh; overflow:auto; }
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>OI Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

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

/* Animate ONLY NSE table ATM row */
@keyframes nseAtmPulse {
  0%, 100% { box-shadow: inset 0 0 0 rgba(255,255,255,0); }
  50%      { box-shadow: inset 0 0 0 9999px rgba(255,255,255,0.03); }
}
#nseOcWrap tbody tr.atm-row {
  animation: nseAtmPulse 1.2s ease-in-out infinite;
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
#nseOcWrap tbody tr.atm-row td.strike,
#nseOcWrap tbody tr.atm-row td.col-strike,
#nseOcWrap tbody tr.atm-row td[data-col="strike"]{
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

/* Near-ATM rows (±1 / ±2 strikes) */
#nseOcWrap tbody tr.atm-near1 td{
  background: rgba(59,130,246,.06) !important;
}
#nseOcWrap tbody tr.atm-near2 td{
  background: rgba(59,130,246,.03) !important;
}

/* Animate ATM row when it changes */
@keyframes atmChangedFlash {
  0%   { box-shadow: 0 0 0 rgba(250,204,21,0); }
  20%  { box-shadow: 0 0 0 6px rgba(250,204,21,.12); }
  60%  { box-shadow: 0 0 0 10px rgba(250,204,21,.06); }
  100% { box-shadow: 0 0 0 rgba(250,204,21,0); }
}
#nseOcWrap tbody tr.atm-changed{
  animation: atmChangedFlash 1.25s ease-out 1;
}

/* Max OI should NOT dominate ATM */
tr.maxoi-row:not(.atm-row) .core-oi {
  background: rgba(34,197,94,0.10);
}


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
/* Thin separator between 1m and ΔOI (and between ΔOI and 1m on PUT side) */
#nseOcWrap th.sep-r, #nseOcWrap td.sep-r{ border-right: 1px solid rgba(255,255,255,0.14); }
#nseOcWrap th.sep-l, #nseOcWrap td.sep-l{ border-left:  1px solid rgba(255,255,255,0.14); }

/* Animate ONLY ATM row inside NSE style table */
@keyframes nseAtmPulse {
  0%,100% { box-shadow: inset 3px 0 0 rgba(59,163,255,0.85), 0 0 0 rgba(59,163,255,0); }
  50%     { box-shadow: inset 3px 0 0 rgba(59,163,255,1),    0 0 0 3px rgba(59,163,255,0.18); }
}
#nseOcWrap tbody tr.atm-row{
  animation: nseAtmPulse 1.35s ease-in-out infinite;
}


  

/* ================================
   Actionable Zones (row badges)
   ================================ */
.zone-badge{display:inline-flex;align-items:center;gap:6px;margin-left:6px;padding:2px 7px;border-radius:999px;font-size:11px;line-height:1.2;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);}
.zone-badge.ce{background:rgba(16,185,129,.12);border-color:rgba(16,185,129,.32);}
.zone-badge.pe{background:rgba(244,63,94,.12);border-color:rgba(244,63,94,.32);}
.zone-badge.nt{background:rgba(234,179,8,.10);border-color:rgba(234,179,8,.28);}

/* Keep wide tables from breaking layout */
.oi-track-scroll{max-width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch;}
#oiTrackTable{min-width:1400px;}
#nseOcWrap .nse-oc-table-wrap{max-width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch;}

/* Ensure tooltip targets don't show dotted underline */
.tip{border-bottom:none !important;}

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
  background: linear-gradient(180deg, rgba(37,99,235,0.35), rgba(37,99,235,0.18));
  border: 1px solid rgba(96,165,250,0.85);
  box-shadow: 0 0 0 1px rgba(37,99,235,0.25), 0 8px 20px rgba(0,0,0,0.35);
  animation: jumpPulse 1.8s ease-in-out infinite;
}
@keyframes jumpPulse{
  0%,100%{ transform: translateY(0); box-shadow: 0 0 0 1px rgba(37,99,235,0.22), 0 8px 20px rgba(0,0,0,0.35); }
  50%{ transform: translateY(-1px); box-shadow: 0 0 0 2px rgba(96,165,250,0.22), 0 10px 26px rgba(0,0,0,0.45); }
}

/* Ensure NSE timeframe dropdown is not clipped */
#nseOcWrap, #nseOcShell, .nse-oc-wrap, .nse-oc-shell, .nse-oc-toolbar, .nse-oc-controls, .nse-oc-header{
  overflow: visible !important;
}
.nse-tf-pop, .tf-menu, .tf-pop{
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

/* Top PCR chips row (always visible) */
#topPcrChips{
  position: sticky;
  top: 28px; /* below main status bar */
  z-index: 9998;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 6px 10px;
  margin: 0 8px 8px;
  border-radius: 12px;
  background: rgba(8,12,22,0.70);
  border: 1px solid rgba(148,163,184,0.16);
  backdrop-filter: blur(10px);
}
.pcr-pill{
  display:flex; align-items:center; gap:10px;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(15,23,42,0.55);
  border: 1px solid rgba(148,163,184,0.18);
  font-weight: 600;
  white-space: nowrap;
}
.pcr-pill .tag{ opacity:0.9; }
.pcr-pill .ce{ color:#60a5fa; }
.pcr-pill .pe{ color:#f472b6; }
.pcr-pill .pcr{ color:#e5e7eb; padding:2px 8px; border-radius:999px; background: rgba(0,0,0,0.25); border:1px solid rgba(148,163,184,0.18); font-weight:700; }

</style>



  <style>

  /* ===== Form controls visibility (dark mode) ===== */
  select, input[type="text"], input[type="number"], button {
    background: rgba(255,255,255,.06);
    color: #e5e7eb;
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 10px;
    padding: 4px 8px;
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
[data-tip]::after{
  left: var(--tip-x);
  top: var(--tip-y);
}

[data-tip]::before{
  left: var(--arrow-x);
  top: var(--arrow-y);
}

/* ===== FAST SMART TOOLTIP ===== */
[data-tip]{ cursor: default; text-decoration: none !important; text-underline-offset: 0; position: relative; }
.arr.tip[data-tip]{ cursor: help; }

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
  .oc-lite {
    opacity: .65;
    font-size: 12px;
    margin-left: 4px;
  }
</style>
<style>
.tip{ cursor: help; border-bottom: none !important; }
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
    .mm-headline{margin-top:2px;}
    .mm-why{margin-top:6px;}
    .mm-levels{margin-top:6px;}
    @media (max-width: 980px){
      .mm-grid{grid-template-columns:1fr;}
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
  display:flex;
  align-items:center;
  gap:6px;
  flex-wrap:wrap;
  margin-left:10px;
}

  .nse-oc-metric{ display:flex; align-items:center; gap:6px; margin-left:10px; }
  .nse-oc-metric .tflbl{ margin-right:4px; opacity:.8; font-size:12px; }

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

/* Timeframe picker (show/hide 1,3,5,10,15,30) */
.nse-tf-menu{ position:relative; display:inline-flex; align-items:center; }
.nse-tf-menu .tfbtn.picker{ padding:3px 8px; font-size:11px; }
.nse-tf-pop{
  position:absolute;
  top:28px;
  left:0;
  z-index:50;
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
#nseOcWrap{ overflow-x:auto; }
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

/* Apply vars */
#oiTrackTable{
  font-size: var(--ot-font, 13.5px);
  line-height: var(--ot-line, 1.25);
}
#oiTrackTable th, #oiTrackTable td{
  padding: var(--ot-pad-y, 6px) var(--ot-pad-x, 8px);
  font-variant-numeric: tabular-nums;
  font-feature-settings: "tnum" 1, "lnum" 1;
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

.nse-oc .dcell{ display:inline-flex; align-items:center; gap:6px; }
.nse-oc .dcell .num{ color: inherit; }
.nse-oc .dcell .arr.up{ color:#22c55e; }
.nse-oc .dcell .arr.down{ color:#ef4444; }
.nse-oc .dcell .arr.flat{ opacity:.65; }
.nse-oc td.pos, .nse-oc span.pos{ color:#22c55e; font-weight:700; }
.nse-oc td.neg, .nse-oc span.neg{ color:#ef4444; font-weight:700; }

/* LTP column (with change) */
.nse-oc td.ltp-col{ text-align:right; font-weight:700; }
.nse-oc .ltp-chg-pos{ color:#22c55e; font-weight:800; }
.nse-oc .ltp-chg-neg{ color:#ef4444; font-weight:800; }

/* LTP arrow behavior (safe, no layout changes) */
.nse-oc .ltp-arrow{ cursor: help; user-select:none; }
.nse-oc .ltp-up{ color:#22c55e; }
.nse-oc .ltp-down{ color:#ef4444; }
.nse-oc .ltp-arrow.faded{ opacity:.35; }
@keyframes ltpBlink{ 0%{opacity:1} 50%{opacity:.2} 100%{opacity:1} }
.nse-oc .ltp-arrow.blink{ animation:ltpBlink .9s ease-in-out 3; }

.nse-oc .max-oi{
  outline:2px solid rgba(34,197,94,.55);
  outline-offset:-2px;
  background: rgba(34,197,94,.08);
}

.nse-oc .nse-max-row td{
  background: rgba(34,197,94,.045);
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
.oiTotChipWrap{ display:inline-flex; align-items:center; gap:10px; flex-wrap:wrap; margin-left:10px; vertical-align:middle; }
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
/* Sticky Expiry chip (only when it sits in a scrollable header row) */
.oiStickyTop{ position:sticky; top:6px; z-index:9999; }

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


/* NSE Option Chain: ATM row highlight */
.nseAtmRow{
  /* highlight whole ATM row in NSE table */
  background: rgba(34,197,94,.10) !important;
  outline: 1px solid rgba(34,197,94,.55);
  box-shadow: inset 0 0 0 1px rgba(34,197,94,.20);
}
.nseAtmRow td{
  background: rgba(34,197,94,.10) !important;
  border-top: 1px solid rgba(34,197,94,.40) !important;
  border-bottom: 1px solid rgba(34,197,94,.40) !important;
}
.nseAtmRow td:first-child{
  border-left: 3px solid rgba(34,197,94,.85) !important;
}
.nseAtmRow td:last-child{
  border-right: 3px solid rgba(34,197,94,.55) !important;
}

.nseAtmRow td:first-child{
  border-left: 2px solid rgba(34,197,94,.75) !important;
}

/* ===== Bottom-right info bubble (optional) ===== */
#oiInfoBubble{
  position: fixed;
  right: 12px;
  bottom: 14px;
  z-index: 1000000;
  pointer-events: none;
}
#oiInfoBubble .oiBubble{
  width: min(360px, calc(100vw - 24px));
  pointer-events: auto;
  background: rgba(6,10,18,.78);
  border: 1px solid rgba(255,255,255,.14);
  border-radius: 14px;
  box-shadow: 0 14px 38px rgba(0,0,0,.45);
  backdrop-filter: blur(10px);
  padding: 10px 10px 8px;
}
#oiInfoBubble .oiBubble.is-closed{ display:none; }
#oiInfoBubble .oiBubbleHead{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  margin-bottom:8px;
}
#oiInfoBubble .oiBubbleTitle{ font-size:12px; opacity:.9; letter-spacing:.2px; }
#oiInfoBubble .oiBubbleClose{
  width: 26px; height: 26px;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,.14);
  background: rgba(255,255,255,.06);
  color: rgba(255,255,255,.88);
  cursor: pointer;
  line-height: 1;
}
#oiInfoBubble .oiBubbleClose:hover{ background: rgba(255,255,255,.10); }
#oiInfoBubble .oiBubbleBody{ display:flex; flex-direction:column; gap:8px; }
#oiInfoBubble .oiBubbleRow{ display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
#oiInfoBubble .oiBubbleFab{
  pointer-events: auto;
  position:absolute;
  right: 0;
  bottom: 0;
  transform: translateY(-4px);
  width: 38px; height: 38px;
  border-radius: 14px;
  border: 1px solid rgba(255,255,255,.18);
  background: rgba(6,10,18,.70);
  color: rgba(255,255,255,.9);
  box-shadow: 0 10px 24px rgba(0,0,0,.35);
  cursor:pointer;
}
#oiInfoBubble .oiBubbleFab.is-hidden{ display:none; }

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
  #safetyDock, .dock, .nse-oc-hdr .rhs, .nse-oc-hdr .nse-oc-tf, .nse-oc-hdr .nse-oc-metric, .nse-oc-hdr .nse-oc-size,
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

/* If you use a fixed-width change box class (common) */
.coreChangeBox, .chgBox, .oiBox {
  width: auto !important;
  min-width: 0 !important;
}

</style>
<style> 
  #nseOcWrap th.ltp-col,
#nseOcWrap td.ltp-col,
#nseOcWrap td.ltp-cell {
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
</head>

<body>

<div id="topPcrChips" aria-label="Top PCR Summary" style="display:none;"></div>

  
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

      <button id="refreshBtn" style="margin-left:4px">Refresh</button>
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
    <div class="card collapsible collapsed" id="marketMeter" style="margin-bottom:16px;display:none" data-panel="marketMeterCard">
    <div class="card-head" title="Click to expand/collapse">
      <div class="head-left"><b>Market Meter</b></div>
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
        <div id="meterHeadline" class="mm-headline"></div>
        <div id="meterWhy" class="muted mm-why"></div>
        <div id="meterAlerts"></div>
      </div>

      <!-- Price Context -->
      <div class="mm-sec">
        <div class="mm-sec-h">
          <div class="mm-sec-title">Price Context</div>
        </div>

        <div class="levels mm-levels">
          <span><b>Price:</b> <span id="ksPrice" class="mono">-</span></span>
          <span><b>ATM:</b> <span id="ksAtm" class="mono">-</span></span>
          <span><b>Exp:</b> <span id="ksExp" class="mono">-</span></span>
          <span><b>Bias:</b> <span id="ksBias">-</span></span>
        </div>

        <div class="levels mm-levels">
          <span><b>Day Range:</b> <span id="ksDayRange" class="mono">-</span></span>
          <span><b>From Low / High:</b> <span id="ksFromHL" class="mono">-</span></span>
        </div>
      
      </div>
      <!-- Gamma & Risk -->
      <div class="mm-sec">
        <div class="mm-sec-h">
          <div class="mm-sec-title">Gamma &amp; Risk</div>
        </div>

        <div class="levels mm-levels">
          <span><b>Max Pain (approx):</b> <span id="ksMaxPain" class="mono">-</span></span>
          <span><b>Gamma Zone:</b> <span id="ksGammaZone" class="mono">-</span></span>
        </div>
        <div class="levels mm-levels">
          <span><b>Gamma Hint:</b> <span id="ksGammaHint" class="mono tip" data-tip="">-</span></span>
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
          <span><b>Support (Put OI):</b> <span id="meterSupport"></span></span>
          <span><b>Resistance (Call OI):</b> <span id="meterResistance"></span></span>
        </div>

        <div class="levels mm-levels">
          <span>
            <b>Quick Read (Intra <span id="qrIntraWin" class="mono">-</span>):</b>
            <span id="qrIntra">—</span><span class="tip tip-i" title="Quick Read: Intra window shows the strongest Put/Call writing strikes in that window.">ⓘ</span>
          </span>
          <span>
            <b><span id="qrDayLbl">Quick Read (Day)</span>:</b>
            <span id="qrDay">—</span> <span id="qrDayMeta" class="mono tip" data-tip="Use Day quick read for day-level support/resistance to avoid wrong bias.">ⓘ</span>
          </span>
        </div>

        <div class="levels mm-levels">
          <span><b>Price vs OI:</b> <span id="ksPriceOI">-</span></span>
          <span><b>OI Pressure:</b> <span id="ksOiPressure">-</span></span>
          <span><b>Trend Confidence:</b> <span id="ksTrendConf">-</span></span>
        </div>

        <div class="levels mm-levels">
          <span><b>Breakout Prob:</b>
            <span id="ksBoUp" class="mono">-</span> ↑ /
            <span id="ksBoDown" class="mono">-</span> ↓
          </span>
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
            <div class="head-left"><b>ZigZag % Swings</b></div>
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
    </div>
    
    </div>
</div>
  </div>

  <!-- TOP ROW: PCR Trend + Classifications -->
  <div class="row">
    <!-- Collapsible: PCR Trend (latest 12) -->
    <div class="card collapsible collapsed" data-panel="pcrTrendCard">
      <div class="card-head" title="Click to expand/collapse">
        <div class="head-left">
          <b>PCR Trend (latest 12)</b>
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
          <span class="pill">Bias: <b id="bias">-</b></span>
          <span class="pill">PCR: <b id="pcr">-</b></span>
          <span class="pill">ATM: <b id="atm">-</b></span>
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

  <div class="card collapsible collapsed" id="monthYtdCard" data-panel="monthYtdContext" style="margin-top:10px;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.14);background:rgba(0,0,0,.22);">
    <div class="card-head" title="Click to expand/collapse">
      <div class="head-left">
        <b>Month / YTD Context</b>
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
  </div>

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
      <div>
        <div class="card-oi-title">OI Track</div>
        <div class="muted small">Multi-timeframe OI &amp; ΔOI by strike (today vs intraday windows)</div>
      </div>
      <div class="oi-toolbar">
        <label class="pill">Timeframes:
          <!-- OI Track TF picker (NSE-style). Selected values are stored in #tfInput for loadTrack(). -->
          <span class="nse-tf-menu oi-tf-menu" id="oiTfMenu"></span>
          <input id="tfInput" value="1,2,3,5,10,15,30,60,120,180" class="mono" style="display:none">
          <button id="tfApply" class="btn" type="button">Apply</button>
        </label>

          <label class="pill" style="margin-left:8px">ΔOI style
            <select id="oiDeltaStyle" style="width:96px;margin-left:6px">
              <option value="arrows">Arrows</option>
              <option value="pm">+/-</option>
            </select>
          </label>

        <label class="pill" style="margin-left:8px">Strikes ±
          <select id="strikeWin2" style="width:70px;margin-left:6px">
            <option value="3">3</option>
            <option value="5" selected>5</option>
            <option value="10">10</option>
            <option value="all">All</option>
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

  <!-- ===== NSE Style Option Chain (synced from OI Track /oi/track) ===== -->
  <div class="card collapsible" data-panel="nseOcCard" style="margin-top:16px">
    <div class="card-head" title="Click to expand/collapse">
      <div class="head-left"><b>NSE Style Option Chain</b><span class="muted" style="font-size:12px;">Synced from OI Track</span></div>
      <span class="card-toggle" aria-hidden="true">▼</span>
    </div>
    <div class="card-body">
<div class="nse-oc-wrap size-normal" id="nseOcWrap">
    <div class="nse-oc-hdr">
      <div class="ttl" style="display: none;">NSE Style Option Chain</div>
      <div class="nse-oc-tf" id="nseOcTf"></div>
      <div class="nse-oc-metric" id="nseOcMetric"></div>
      <div class="nse-oc-size" id="nseOcSize"></div>
      <div class="nse-oc-expiry chip" id="nseOcExpiryTotals" style="display:none"></div>
      <div class="nse-oc-window chip" id="nseOcWindowTotals" style="display:none"></div>
      <div class="rhs">
        <span class="chip" id="nseOcStatus">Waiting…</span>
        <span class="chip">PCR: <b id="nsePcr">—</b></span>
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
        <span class="sum-item" id="nseOcWindowTotals" style="display:none"></span>
      </div>
      <div class="sum-right">
        <span id="nseSumPrice">—</span>
      </div>
    </div>



    
    <div class="tb-box" id="tbBox" style="display: none;">
      <div class="tb-main">
        <div class="tb-bias" id="tbBias">—</div>
        <div class="tb-sub" id="tbSub">—</div>
      </div>
      <div class="tb-reasons" id="tbReasons"></div>
      <div class="tb-sl" id="tbSl" style="display:none"></div>
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
    const strikeWinRaw = (window.UI && UI.strikeWin!=null) ? UI.strikeWin : 5;
    const strikeWin = (strikeWinRaw === 'all') ? 'all' : (Number(strikeWinRaw) || 5);
    const step = 50;

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


(function(){
  const sw1 = document.getElementById('strikeWin');
  const sw2 = document.getElementById('strikeWin2');
  function applyVal(v){
    // keep UI as number or 'all'
    UI.strikeWin = (v==='all') ? 'all' : (parseInt(v,10) || 5);
    if(sw1 && sw1.value !== v) sw1.value = v;
    if(sw2 && sw2.value !== v) sw2.value = v;
  }
  if(sw1){
    sw1.addEventListener('change', e=>{
      const v = e.target.value;
      applyVal(v);
      try{ renderDeltaTable(lastJson); renderNetBars(lastJson); }catch(_){}
      loadTrack();
    });
  }
  if(sw2){
    // initialize from sw1
    if(sw1) sw2.value = sw1.value;
    sw2.addEventListener('change', e=>{
      const v = e.target.value;
      applyVal(v);
      try{ renderDeltaTable(lastJson); renderNetBars(lastJson); }catch(_){}
      loadTrack();
    });
  }
})();
document.getElementById('atmBand').addEventListener('change', e=>{ UI.atmBand = parseInt(e.target.value,10); renderDeltaTable(lastJson); });
document.getElementById('expirySel').addEventListener('change', e=>{ UI.expiry = e.target.value; countdown = AUTO_REFRESH_SEC; doRefresh(); });
document.getElementById('exportDelta').addEventListener('click', exportDeltaCSV);
document.getElementById('exportPCR').addEventListener('click', exportPCRCSV);
document.getElementById('showNextExp').addEventListener('change', ()=>{ loadNextExpiryCard().catch(()=>{}); });

window.addEventListener('keydown', (e)=>{
  if (e.key==='r' || e.key==='R'){ countdown = AUTO_REFRESH_SEC; doRefresh(); }
  if (e.key==='1'){ document.getElementById('strikeWin').value='3';  UI.strikeWin=3;  renderDeltaTable(lastJson); renderNetBars(lastJson); }
  if (e.key==='2'){ document.getElementById('strikeWin').value='5'; UI.strikeWin=5; renderDeltaTable(lastJson); renderNetBars(lastJson); }
  if (e.key==='3'){ document.getElementById('strikeWin').value='10'; UI.strikeWin=10; renderDeltaTable(lastJson); renderNetBars(lastJson); }
  if (e.key==='e' || e.key==='E'){ const sel=document.getElementById('expirySel'); sel.selectedIndex = (sel.selectedIndex+1) % sel.options.length; sel.dispatchEvent(new Event('change')); }
});

// ====== Countdown loop ======
let countdown = AUTO_REFRESH_SEC;

// Auto refresh only between 09:00 and 16:00 IST
function inAutoRefreshWindow(){
  try{
    const parts = new Intl.DateTimeFormat('en-GB',{
      timeZone:'Asia/Kolkata', hour:'2-digit', minute:'2-digit', hour12:false
    }).formatToParts(new Date());
    const hh = parseInt(parts.find(p=>p.type==='hour')?.value||'0',10);
    const mm = parseInt(parts.find(p=>p.type==='minute')?.value||'0',10);
    const mins = hh*60 + mm;
    return mins >= (9*60) && mins < (16*60);
  }catch(e){
    // fallback to local time if Intl/timeZone not available
    const d = new Date();
    const mins = d.getHours()*60 + d.getMinutes();
    return mins >= (9*60) && mins < (16*60);
  }
}

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
    try{ window.__oiBeautifyNow && window.__oiBeautifyNow(); }catch(e){}
  }catch(e){}
}

function tick(){
  // Pause auto refresh outside 09:00–16:00 IST (manual Refresh still works)
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
  // setTimeout(scrollToOiTrack, 250); // disabled: NSE autoscroll handles focus
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
    const ksGammaHintEl= document.getElementById('ksGammaHint');
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
    if (ksMaxPainEl){
      ksMaxPainEl.textContent = mp?.maxPain ? String(mp.maxPain) : '-';
    }
    if (ksGammaEl){
      ksGammaEl.textContent = mp?.gammaZone
        ? `${mp.gammaZone.lo}–${mp.gammaZone.hi}`
        : '-';

      // Tooltip on Gamma Zone value (kept simple)
      const spot0 = Number(j.price || j.spot || j.underlying || j.ltp || j.index || NaN);
      if (mp?.gammaZone && Number.isFinite(spot0)){
        const lo0 = Number(mp.gammaZone.lo);
        const hi0 = Number(mp.gammaZone.hi);
        if (Number.isFinite(lo0) && Number.isFinite(hi0) && hi0 > lo0){
          let pos0 = 'Inside';
          if (spot0 < lo0) pos0 = 'Below';
          else if (spot0 > hi0) pos0 = 'Above';
          ksGammaEl.classList.add('tip');
          ksGammaEl.dataset.tip =
            'Gamma Zone (approx)\n'
            + 'Spot: ' + spot0.toFixed(2) + '\n'
            + 'Zone: ' + lo0 + '–' + hi0 + '\n'
            + 'Position: ' + pos0 + '\n\n'
            + 'Idea: Inside zone often behaves range/mean-revert; outside can trend until it returns.';
        }else{
          ksGammaEl.dataset.tip = '';
        }
      }else{
        ksGammaEl.dataset.tip = '';
      }
    }


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
'
            + 'Spot: ' + spot0.toFixed(2) + '
'
            + 'Zone: ' + lo0 + '–' + hi0 + '
'
            + 'Position: ' + pos0 + '

'
            + 'Idea: Inside zone often behaves range/mean-revert; outside can trend until it returns.`;
        }else{
          nseGammaEl.dataset.tip = '';
        }
      }else{
        nseGammaEl.dataset.tip = '';
      }
    }

    // Gamma hint (compare zone vs current market)
    if (ksGammaHintEl){
      const spot = Number(j.price || j.spot || j.underlying || j.ltp || j.index || NaN);
      const gz = mp?.gammaZone || null;
      if (!gz || !Number.isFinite(gz.lo) || !Number.isFinite(gz.hi) || !Number.isFinite(spot)){
        ksGammaHintEl.textContent = '-';
        ksGammaHintEl.dataset.tip = '';
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

        // Suggestion (lightweight, not a guarantee)
        let sug = '';
        if (pos === 'Inside'){
          sug = 'Usually mean-reversion / range behavior; watch zone edges for breakout.';
        }else if (pos === 'Above'){
          sug = 'Upside extension possible, but snap-back risk to zone is common; use tight risk.';
        }else{
          sug = 'Downside pressure possible, but rebound into zone can happen; watch for reclaim.';
        }

        ksGammaHintEl.textContent = hint;
        ksGammaHintEl.dataset.tip =
          `Gamma Zone (approx)\n`+
          `Spot: ${spot.toFixed(2)}\n`+
          `Zone: ${lo}–${hi} (mid ${mid.toFixed(0)})\n`+
          `Position: ${pos}\n\n`+
          `${sug}\n\n`+
          `Note: This is a proxy from OI distribution (not true dealer gamma). Use with price action + levels.`;
        ksGammaHintEl.classList.add('tip');
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

function updateTradeBoxFromTrack(j){
  const box = document.getElementById('tradeBox');
  if(!box) return;

  const hintEl = document.getElementById('tbHint');
  const confEl = document.getElementById('tbConf');
  const supEl  = document.getElementById('tbSupports');
  const resEl  = document.getElementById('tbResistances');
  const netEl  = document.getElementById('tbNet');

  const tr = j && (j.trade || null);
  const hint = tr?.hint || 'WAIT';
  const bt   = tr?.backtest || null;

  const cls = (hint==='BUY') ? 'tb-buy' : (hint==='SELL' ? 'tb-sell' : 'tb-wait');
  if(hintEl){
    hintEl.className = `tb-pill ${cls}`;
    hintEl.textContent = hint;
  }

  if(confEl){
    if(bt && bt.pct !== null && bt.pct !== undefined && bt.n){
      confEl.textContent = `${bt.pct}% (${bt.correct}/${bt.n})`;
    }else{
      confEl.textContent = '—';
    }
  }

  const fmtLvl = (arr)=>{
    if(!Array.isArray(arr) || !arr.length) return '—';
    return arr.map(x=>`${x.strike}`).join(', ');
  };

  supEl && (supEl.textContent = fmtLvl(tr?.supports));
  resEl && (resEl.textContent = fmtLvl(tr?.resistances));

  if(netEl){
    const net = tr?.net_sum;
    if(net===null || net===undefined) netEl.textContent = '—';
    else netEl.textContent = Number(net).toLocaleString('en-IN');
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


// ================= FYERS Insights (no FIA-lite textbox) =================
// Populates the FYERS tiles between OI Track and NSE table.
// Keeps information duplicated elsewhere (as requested).

/* =========================================================
   Helpers for FYERS strip: ATM CE/PE from OI Track DOM
   ========================================================= */
function _numFromText(txt){
  if(txt==null) return NaN;
  const s = String(txt).replace(/[,\s]/g,'');
  const m = s.match(/-?\d+(?:\.\d+)?/);
  return m ? Number(m[0]) : NaN;
}
function _getAtmCePeFromOiTrackDom(){
  const tbl = document.getElementById('oiTrackTable');
  if(!tbl) return null;

  const ths = Array.from(tbl.querySelectorAll('thead th')).map(th => (th.textContent||'').trim().toUpperCase());
  const idxStrike = ths.findIndex(t => t.includes('STRIKE'));
  const idxType   = ths.findIndex(t => t === 'TYPE' || t.includes(' TYPE'));
  const idxCurOi  = ths.findIndex(t => t.includes('CURRENT OI'));
  if(idxCurOi < 0 || idxType < 0) return null;

  let rows = Array.from(tbl.querySelectorAll('tbody tr.atm-row'));
  // fallback: if ATM row class not present, scan all rows and match by strike
  if(!rows.length) rows = Array.from(tbl.querySelectorAll('tbody tr'));
  if(!rows.length) return null;

  let atmStrike = NaN, ce = NaN, pe = NaN;
  for(const r of rows){
    const tds = Array.from(r.children);
    if(idxStrike >= 0 && Number.isNaN(atmStrike)){
      atmStrike = _numFromText(tds[idxStrike]?.textContent);
    }
    const typ = (tds[idxType]?.textContent||'').trim().toUpperCase();
    const cur = _numFromText(tds[idxCurOi]?.textContent);
    if(typ === 'CE') ce = cur;
    if(typ === 'PE') pe = cur;
  }
  if(!Number.isFinite(ce) && !Number.isFinite(pe)) return null;
  return { atmStrike, ce, pe };
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


function fmtDeltaWithPctTrackPM(delta, base, minutes, decimals=0){
  const d = Number(delta);
  const b = Number(base);
  if (!Number.isFinite(d)) return '-';

  const m = Number(minutes);
  const tfLbl = Number.isFinite(m) ? nseTfLabel(m) : '';
  const tip = tfLbl ? `Net build-up over last ${tfLbl}` : 'Net build-up';

  let cls = '';
  if (d > 0) cls = 'pos';
  else if (d < 0) cls = 'neg';

  const sign = d > 0 ? '+' : (d < 0 ? '-' : '');
  const dTxt = fmtNum(Math.abs(d).toFixed(decimals));

  if (!Number.isFinite(b) || b === 0){
    return `<span class="delta-wrap ${cls}" data-tip="${tip}">${sign}${dTxt}</span>`;
  }

  const pct = (d / b) * 100;
  const pAbs = Math.abs(pct);
  const pTxt = `${(pAbs < 0.05 ? 0 : pct).toFixed(2)}%`;

  const inten = pctIntensityClass(pAbs);
  const wrapCls = `delta-wrap ${cls} ${inten}`.trim();

  return `<span class="${wrapCls}" data-tip="${tip}">${sign}${dTxt} <span class="muted">(${pTxt})</span></span>`;
}

function fmtDeltaWithPctTrackStyled(delta, base, minutes, decimals=0){
  const st = getOiDeltaStyle(); // 'arrows' or 'pm'
  return (st === 'pm')
    ? fmtDeltaWithPctTrackPM(delta, base, minutes, decimals)
    : fmtDeltaWithPctTrackArrow(delta, base, minutes, decimals);
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
  try { (getNseVisibleTfs()||[]).forEach(m=>union.add(Number(m))); } catch(e){}
  try { if (typeof __nseOcTf !== 'undefined' && __nseOcTf) union.add(Number(__nseOcTf)); } catch(e){}

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
    const step = 50; // NIFTY/BANKNIFTY usually 50
    const spot = Number(obj.spot || obj.underlying_now || obj.underlying || obj.ltp || obj.fut || obj.index || NaN);
    if (!Number.isFinite(spot)) return;
    const derived = Math.round(spot / step) * step;
    if (!Number.isFinite(Number(obj.atm)) || Number(obj.atm) !== derived) obj.atm = derived;
  })(j);
  if (j.strike_window) {
    const el = document.getElementById('strikeWin');
    if (el) el.value = j.strike_window;
  }

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
      (j.data||[]).forEach(r=>{
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
  try{ window.__lastTrackJson = j; window.__lastNseRows = rows; }catch(e){}

  try{ if (window.renderNseOcFromTrack) window.renderNseOcFromTrack(j); }catch(e){ console.warn('NSE OC render failed', e); const _st=document.getElementById('nseOcStatus'); if(_st){ _st.style.display='inline-block'; _st.textContent='NSE table error (check Console)'; }}

  try{ window.updateNseHeaderContext && window.updateNseHeaderContext(); }catch(e){}

  try{ updateTradeBoxFromTrack(j); }catch(e){}

  // attach dominance map so FYERS Insights can render
  try{ j.__domByM = domByM; }catch(e){}
  try{ window.lastJson = j; }catch(e){}

  if (window.enhanceAll) window.enhanceAll();
  try{ applyAtmRowHighlights(j.atm ?? (lastJson && lastJson.atm)); }catch(e){}
  // Apply ΔOI % heat shading (subtle) on OI Track delta cells
  try{
    const tbl = document.getElementById('oiTrackTable');
    if (tbl){
      tbl.querySelectorAll('td.tf-delta[data-delta-pct]').forEach(td=>{
        const pct = Number(td.getAttribute('data-delta-pct'));
        if (window.__decorateHeatCell) window.__decorateHeatCell(td, pct);
      });
    }
  }catch(e){}
}

try{ buildOiTfMenu(); }catch(e){}
try{
  const sel1 = document.getElementById('oiDeltaStyle');
  const sel2 = document.getElementById('oiDeltaStyle2');

  const syncDeltaStyleUI = (v)=>{
    if (sel1 && sel1.value !== v) sel1.value = v;
    if (sel2 && sel2.value !== v) sel2.value = v;
  };

  // init from storage
  const initV = getOiDeltaStyle();
  if (sel1) sel1.value = initV;
  if (sel2) sel2.value = initV;

  const onChange = (e)=>{
    const v = String(e.target.value || 'arrows');
    setOiDeltaStyle(v);
    syncDeltaStyleUI(v);
    // Re-render everything that depends on ΔOI style
    loadTrack();
  };

  sel1 && sel1.addEventListener('change', onChange);
  sel2 && sel2.addEventListener('change', onChange);
}catch(e){}
document.getElementById('tfApply')?.addEventListener('click', loadTrack);
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

// ---- If loadTrack ran early, render now (with DOM-ready retry safety) ----
if (window.__nseOcPending) {
  try { window.renderNseOcFromTrack(window.__nseOcPending); } catch(e) { console.warn('NSE pending render failed', e); }
  // Don't force-clear here; renderer will clear when it successfully renders.
}
document.addEventListener('DOMContentLoaded', function(){
  if (window.__nseOcPending) {
    try { window.renderNseOcFromTrack(window.__nseOcPending); } catch(e) {}
  }
});

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

      // close on outside click
      document.addEventListener('click', (ev)=>{
        if(!menu.contains(ev.target)) menu.classList.remove('open');
      });

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
    // HTML escape helper for tooltips
    function escHtml(v){
      const s = (v===null || v===undefined) ? "" : String(v);
      return s.replace(/[&<>"']/g, function(ch){
        switch(ch){
          case '&': return '&amp;';
          case '<': return '&lt;';
          case '>': return '&gt;';
          case '"': return '&quot;';
          case "'": return '&#39;';
          default: return ch;
        }
      });
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
      document.getElementById('nsePcr') && (document.getElementById('nsePcr').textContent = '—');
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
    const colsStatus = ['DAY', ...lookAsc];


    // ensure highlight TF is valid
    if(__nseOcTf === null) __nseOcTf = 'DAY';
    const _validTfs = new Set([...colsCalls, ...colsPuts]);
    if(__nseOcTf !== 'DAY' && !_validTfs.has(Number(__nseOcTf))){
      __nseOcTf = 'DAY';
    }

        if(window.__showNseOcStatus){
      statusEl.style.display='block';
      statusEl.textContent = 'SYNC ✓ · ' + (__nseOcMetric==='VOL'?'VOL':'OI') + ' · cols ' + colsStatus.map(c => (c==='DAY'?'Day':(c+'m'))).join(', ') + ' · hi ' + ((__nseOcTf==='DAY')?'Day':(__nseOcTf+'m'));
    } else {
      statusEl.textContent = ''; statusEl.style.display='none';
    }

    const rows = j.rows.slice(); // keep order
    const atmStrike = getAtmStrike(j);
    const spotNow = Number(j?.underlying_now ?? window.lastJson?.price ?? NaN);

    // PCR + Max pain
    const pcr = (typeof j?.expiry_totals?.now?.pcr === 'number') ? j.expiry_totals.now.pcr
            : (typeof j?.expiry_totals?.pcr === 'number') ? j.expiry_totals.pcr
            : (typeof j?.pcr === 'number') ? j.pcr
            : computePCR(rows);
    const mp  = computeMaxPain(rows);
    const pcrEl = document.getElementById('nsePcr'); if(pcrEl) pcrEl.textContent = (pcr===null? '—' : fmt2(pcr));
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
    bodyEl.innerHTML = html.join('') || `<tr><td colspan="${totalCols}" class="muted" style="text-align:center;

    // ===== ATM band helpers for dynamic NSE table =====
    (function applyAtmBand(){
      // clear previous near markers
      const trs = Array.from(bodyEl.querySelectorAll('tr'));
      trs.forEach(tr => tr.classList.remove('atm-near1','atm-near2','atm-changed'));

      if(atmStrike==null || !Number.isFinite(Number(atmStrike))) return;

      // find ATM row index by existing atm-row (best) else by strike match
      let atmIdx = trs.findIndex(tr => tr.classList.contains('atm-row'));
      if(atmIdx < 0){
        atmIdx = trs.findIndex(tr=>{
          const sc = tr.querySelector('td.strike, td.col-strike, td[data-col="strike"]');
          if(!sc) return false;
          const v = parseInt(String(sc.textContent||'').replace(/[^\d]/g,''),10);
          return Number.isFinite(v) && v === Number(atmStrike);
        });
      }
      if(atmIdx < 0) return;

      // add near highlights (±1 / ±2)
      const addCls = (i, cls)=>{ if(trs[i]) trs[i].classList.add(cls); };
      addCls(atmIdx-1, 'atm-near1');
      addCls(atmIdx+1, 'atm-near1');
      addCls(atmIdx-2, 'atm-near2');
      addCls(atmIdx+2, 'atm-near2');

      // animate if ATM changed from last render
      const prev = window.__nsePrevAtmStrike;
      if(prev !== undefined && prev !== null && Number(prev) !== Number(atmStrike)){
        trs[atmIdx].classList.add('atm-changed');
        setTimeout(()=> trs[atmIdx] && trs[atmIdx].classList.remove('atm-changed'), 1400);
      }
      window.__nsePrevAtmStrike = Number(atmStrike);
    })();
padding:14px">No rows</td></tr>`;
    
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
// ===== Totals placement (per requirements) =====
    // Expiry totals (authoritative) from controller: DOES NOT change with strike window
    const exp = j?.expiry_totals?.now || j?.expiry_totals || null;

    // Window totals computed from currently displayed rows (ATM ±N): DOES change with strike window
    let wCeOi = 0, wPeOi = 0;
    rows.forEach(r=>{
      const ceOi = Number(r?.CE?.cur_oi); if(Number.isFinite(ceOi)) wCeOi += ceOi;
      const peOi = Number(r?.PE?.cur_oi); if(Number.isFinite(peOi)) wPeOi += peOi;
    });
    const wPcr = (wCeOi > 0) ? (wPeOi / wCeOi) : null;

    const selStrikes = String(document.getElementById('strikeWin')?.value || '10');
    const showWindow = (selStrikes !== 'all');

    function fmtLine(prefix, ceOi, peOi, pcrVal){
      return `${prefix} CE ${fmt(ceOi)} · PE ${fmt(peOi)} · PCR ${(typeof pcrVal==='number') ? pcrVal.toFixed(2) : '—'}`;
    }

    // 1) Show Expiry totals after Metric button (top header)
    const expEl = document.getElementById('nseOcExpiryTotals');
    if(expEl){
      if(exp && (Number.isFinite(+exp.ce_oi) || Number.isFinite(+exp.pe_oi))){
        const expCe = Number(exp.ce_oi) || 0;
        const expPe = Number(exp.pe_oi) || 0;
        const expP  = (typeof exp.pcr === 'number') ? exp.pcr : ((expCe>0)?(expPe/expCe):null);
        expEl.textContent = fmtLine('Total OI (Expiry):', expCe, expPe, expP);
        expEl.style.display = '';
      } else {
        expEl.style.display = 'none';
        expEl.textContent = '';
      }
    }

    // 2) Show Window totals after Imbalance (in the summary strip)
    const winEl = document.getElementById('nseOcWindowTotals');
    if(winEl){
      if(showWindow){
        winEl.innerHTML = `<span class="muted">Total OI (Window ATM ±${selStrikes}):</span> <b style="margin-left:6px">CE ${fmt(wCeOi)}</b> <span class="muted" style="margin:0 8px">·</span> <b>PE ${fmt(wPeOi)}</b> <span class="muted" style="margin:0 8px">·</span> <b>PCR ${(typeof wPcr==='number') ? wPcr.toFixed(2) : '—'}</b>`;
        winEl.style.display = '';
      } else {
        winEl.style.display = 'none';
        winEl.textContent = '';
      }
    }

    // 3) Remove totals from table footer (keep footer empty)
    const foot = document.getElementById('nseOcFoot');
    if(foot) foot.innerHTML = '';

    try{ applyAtmRowHighlights(atmStrike); }catch(e){}
  };
  // ---- NSE OC: if loadTrack ran before renderer was defined, consume pending payload now ----
  try{
    if(window.__nseOcPending){
      const _p = window.__nseOcPending;
      // keep pending until a successful render clears it inside the renderer
      setTimeout(function(){ try{ window.renderNseOcFromTrack(_p); }catch(e){} }, 0);
    }
  }catch(e){}

  // Also retry once DOM is ready (safe no-op if already rendered)
  document.addEventListener('DOMContentLoaded', function(){
    try{
      if(window.__nseOcPending){
        const _p = window.__nseOcPending;
        setTimeout(function(){ try{ window.renderNseOcFromTrack(_p); }catch(e){} }, 0);
      }
    }catch(e){}
  });


    // ---- NSE OC render placeholder flush ----
    if (window.__nseOcPending) {
      try { window.renderNseOcFromTrack(window.__nseOcPending, true); } catch(e) {}
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


  function numFromText(s){
    if(s===null||s===undefined) return NaN;
    const v = String(s).replace(/[^0-9.\-]/g,'');
    const n = Number(v);
    return Number.isFinite(n) ? n : NaN;
  }

  function getAtmFromTrackTable(atm){
    const body = document.getElementById('trackTable');
  
  // Dominance marker (higher TFs): compare absolute ΣΔOI across strikes for CE vs PE
  const domByM = {};
  try{
    minsDesc.forEach(m=>{
      if (Number(m) < 60) return;
      let ceSum = 0, peSum = 0;
      (j.data||[]).forEach(r=>{
        if (r && r.CE) ceSum += Number(r.CE['chg_' + m + 'm'] || 0);
        if (r && r.PE) peSum += Number(r.PE['chg_' + m + 'm'] || 0);
      });
      const dom = (Math.abs(ceSum) >= Math.abs(peSum)) ? 'CE' : 'PE';
      domByM[m] = { dom, ceSum, peSum };
    });
  }catch(e){}

  const head = document.getElementById('trackHeadRow');
    if(!body || !atm) return null;

    const ths = head ? Array.from(head.querySelectorAll('th')) : [];
    const minsCount = ths.filter(th => /^OI\s*\(\d+m\)$/i.test((th.textContent||'').trim())).length;
    const currentOiIdx = 2 + (minsCount * 2);
    const curDeltaIdx  = currentOiIdx + 1;

    let delta5Idx = null;
    if(ths.length){
      ths.forEach((th,i)=>{
        const t = (th.textContent||'').trim().toLowerCase();
        if(t === 'δoi (5m)' || t === 'ΔOI (5m)'.toLowerCase()) delta5Idx = i;
      });
    }

    const out = { ce:null, pe:null };
    const rows = Array.from(body.querySelectorAll('tr'));
    for(const tr of rows){
      const tds = Array.from(tr.querySelectorAll('td'));
      if(tds.length < currentOiIdx+1) continue;
      const strike = numFromText(tds[0].textContent);
      const typ = (tds[1].textContent||'').trim().toUpperCase();
      if(strike !== Number(atm)) continue;
      if(typ !== 'CE' && typ !== 'PE') continue;

      const curOi = numFromText(tds[currentOiIdx].textContent);
      const curD  = numFromText(tds[curDeltaIdx]?.textContent);
      const d5    = (delta5Idx!==null) ? numFromText(tds[delta5Idx]?.textContent) : NaN;

      const obj = { oi:curOi, curDelta:curD, d5:d5 };
      if(typ === 'CE') out.ce = obj;
      if(typ === 'PE') out.pe = obj;

      if(out.ce && out.pe) break;
    }
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

  function findElContaining(substr){
    substr = substr.toLowerCase();
    const nodes = Array.from(document.querySelectorAll('span,div,td,th'));
    nodes.sort((a,b)=> (a.textContent||'').length - (b.textContent||'').length);
    return nodes.find(el => ((el.textContent||'').toLowerCase().includes(substr)));
  }

  function parseTotalsFromText(txt){
    if(!txt) return null;
    const t = txt.replace(/\u00a0/g,' ');
    const m = t.match(/CE\s+([\d,]+).*?PE\s+([\d,]+).*?PCR\s+([\d.]+)/i);
    if(!m) return null;
    const ce = Number(m[1].replace(/,/g,''));
    const pe = Number(m[2].replace(/,/g,''));
    const pcr = Number(m[3]);
    if(!Number.isFinite(ce) || !Number.isFinite(pe)) return null;
    return {ce, pe, pcr};
  }

  function getStrikeMode(){
    const sel = document.getElementById('strikeWin') || document.getElementById('strikeWinServer');
    const v = sel && sel.value ? String(sel.value).toLowerCase() : '';
    return v;
  }

  function computeWindowTotalsFromNseTable(){
    function num(x){
      const t = String(x||'').replace(/[, ]+/g,'').trim();
      const n = Number(t);
      return Number.isFinite(n) ? n : NaN;
    }
    const titleEl = Array.from(document.querySelectorAll('div,span,h1,h2,h3'))
      .find(el => /nse style option chain/i.test((el.textContent||'').trim()));
    if(!titleEl) return null;

    const container = titleEl.closest('section, .card, .panel, .box, div') || titleEl.parentElement;
    if(!container) return null;

    const table = container.querySelector('table');
    if(!table) return null;

    const rows = table.querySelectorAll('tbody tr');
    if(!rows || !rows.length) return null;

    let ce = 0, pe = 0, any = false;
    rows.forEach(tr=>{
      const tds = tr.querySelectorAll('td');
      if(!tds || tds.length < 3) return;
      const callOi = num(tds[0].textContent);
      const putOi  = num(tds[tds.length-1].textContent);
      if(Number.isFinite(callOi)){ ce += callOi; any = true; }
      if(Number.isFinite(putOi)){  pe += putOi;  any = true; }
    });
    if(!any) return null;
    return {ce, pe, pcr: (ce>0 ? (pe/ce) : NaN)};
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

    const num = (t)=>{
      const s = String(t ?? '').replace(/[, ]+/g,'').trim();
      const n = Number(s);
      return Number.isFinite(n) ? n : NaN;
    };

    const parseStrike = (td)=>{
      const raw = (td?.textContent ?? '').replace(/,/g,'').trim();
      const n = Number(raw);
      return Number.isFinite(n) ? n : NaN;
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

    // Highlight max OI (left-most and right-most numeric columns)
    let maxCall = -Infinity, maxPut = -Infinity;
    let maxCallTd = null, maxPutTd = null;

    rows.forEach(tr=>{
      const tds = tr.querySelectorAll('td');
      if(!tds || tds.length < 3) return;
      const callV = num(tds[0].textContent);
      const putV  = num(tds[tds.length-1].textContent);

      if(Number.isFinite(callV) && callV > maxCall){ maxCall = callV; maxCallTd = tds[0]; }
      if(Number.isFinite(putV)  && putV  > maxPut ){ maxPut  = putV;  maxPutTd  = tds[tds.length-1]; }
    });

    if(maxCallTd) maxCallTd.classList.add('max-call-oi');
    if(maxPutTd)  maxPutTd.classList.add('max-put-oi');

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
      try{ highlightNseAtmRow(window.lastJson); }catch(e){}
    }, d));
  }

function renderBeautify(){
    const j = window.lastJson || window.__lastJson || null;
    const expiryTotals = j && j.expiry_totals && j.expiry_totals.now ? j.expiry_totals.now : null;

    // --- Cleanup duplicates (can happen after metric toggle re-renders header) ---
    (function dedupeById(id){
      const els = document.querySelectorAll('[id="'+id+'"]');
      if(els && els.length > 1){
        // Keep the LAST one (most recently rendered), remove older ones
        for(let i=0;i<els.length-1;i++){
          try{ els[i].remove(); }catch(e){}
        }
      }
    })('nseOcWindowTotals');
    (function dedupeById(id){
      const els = document.querySelectorAll('[id="'+id+'"]');
      if(els && els.length > 1){
        for(let i=0;i<els.length-1;i++){
          try{ els[i].remove(); }catch(e){}
        }
      }
    })('nseOcExpiryTotals');

    // ---- Expiry totals chip (after Metric button; element is generated with this id) ----
    const expTextEl = document.getElementById('nseOcExpiryTotals') || findElContaining('total oi (expiry)');
    if(expTextEl){
      const parsed = parseTotalsFromText(expTextEl.textContent);
      const ce = (expiryTotals && Number.isFinite(+expiryTotals.ce_oi)) ? +expiryTotals.ce_oi : (parsed ? parsed.ce : NaN);
      const pe = (expiryTotals && Number.isFinite(+expiryTotals.pe_oi)) ? +expiryTotals.pe_oi : (parsed ? parsed.pe : NaN);
      const pcr = (expiryTotals && Number.isFinite(+expiryTotals.pcr)) ? +expiryTotals.pcr : (parsed ? parsed.pcr : (ce>0 ? pe/ce : NaN));

      // Replace content in-place (no moving nodes -> avoids duplicates)
      expTextEl.innerHTML = '<span id="oiExpiryChipInner" class="oiTotChipWrap oiStickyTop">' + buildChip('Σ Expiry', ce, pe, pcr) + '</span>';

      // ===== Top dock (full-page sticky) for Expiry totals =====
      let dock = document.getElementById('oiExpiryDock');
      if(!dock){
        dock = document.createElement('div');
        dock.id = 'oiExpiryDock';
        document.body.appendChild(dock);
      }
      // keep only one chip in dock (no duplicates on metric toggle / rerender)
      {
        const atm = (j?.atm || window.lastJson?.atm || (typeof lastJson!=='undefined' ? lastJson?.atm : NaN));
        const atmData = getAtmFromTrackTable(atm);
        const atmHtml = (atmData && (atmData.ce || atmData.pe)) ? ('<span class="oiTotWrap">' + buildAtmChip(atm, atmData) + '</span>') : '';
        dock.innerHTML = '<div class="oiDockRow"><span class="oiTotWrap">' + buildChip('Σ Expiry', ce, pe, pcr) + '</span>' + atmHtml + '</div>';
// ===== Bottom-right info bubble (optional; can be closed) =====
(function(){
  try{
    let root = document.getElementById('oiInfoBubble');
    if(!root){
      root = document.createElement('div');
      root.id = 'oiInfoBubble';
      document.body.appendChild(root);
    }
    const closed = (localStorage.getItem('oiInfoBubbleClosed') === '1');

    const expiryInner = (document.getElementById('oiExpiryChipInner')?.innerHTML) || '';
    const winInner = (document.getElementById('oiWindowChipInner')?.innerHTML) || '';

    const atmChip = (atmData && Number.isFinite(+atm)) ? buildAtmChip(atm, atmData) : '';

    root.innerHTML =
      '<div class="oiBubble' + (closed ? ' is-closed' : '') + '">'
      +   '<div class="oiBubbleHead">'
      +     '<span class="oiBubbleTitle">Quick Stats</span>'
      +     '<button type="button" class="oiBubbleClose" title="Close">×</button>'
      +   '</div>'
      +   '<div class="oiBubbleBody">'
      +     (expiryInner ? ('<div class="oiBubbleRow"><span class="oiTotChip">' + expiryInner + '</span></div>') : '')
      +     (atmChip ? ('<div class="oiBubbleRow">' + atmChip + '</div>') : '')
      +     (winInner ? ('<div class="oiBubbleRow"><span class="oiTotChip">' + winInner + '</span></div>') : '')
      +   '</div>'
      + '</div>'
      + '<button type="button" class="oiBubbleFab' + (closed ? '' : ' is-hidden') + '" title="Show stats">ⓘ</button>';

    const closeBtn = root.querySelector('.oiBubbleClose');
    const fab = root.querySelector('.oiBubbleFab');
    const bub = root.querySelector('.oiBubble');

    if(closeBtn){
      closeBtn.onclick = function(){
        localStorage.setItem('oiInfoBubbleClosed', '1');
        if(bub) bub.classList.add('is-closed');
        if(fab) fab.classList.remove('is-hidden');
      };
    }
    if(fab){
      fab.onclick = function(){
        localStorage.removeItem('oiInfoBubbleClosed');
        if(bub) bub.classList.remove('is-closed');
        fab.classList.add('is-hidden');
      };
    }
  }catch(e){}
})();
      }
    }

    // ---- Window totals chip (after Imbalance; element is generated with this id) ----
    const winTextEl = document.getElementById('nseOcWindowTotals') || findElContaining('total oi (window');
    if(winTextEl){
      const strikeMode = getStrikeMode();
      const showWindow = !!(strikeMode && strikeMode !== 'all');
      if(!showWindow){
        // 🎯 auto-hide when Window = All
        winTextEl.innerHTML = '';
      }else{
        const parsed = parseTotalsFromText(winTextEl.textContent);
        const N = parseInt(strikeMode, 10);
        const w = computeWindowTotalsFromNseTable() || (parsed ? {ce: parsed.ce, pe: parsed.pe, pcr: parsed.pcr} : null);
        if(w){
          const pcr = (Number.isFinite(w.pcr)) ? w.pcr : (w.ce>0 ? w.pe/w.ce : NaN);
          winTextEl.innerHTML = '<span id="oiWindowChipInner" class="oiTotChipWrap">' + buildChip(`ATM ±${Number.isFinite(N)?N:'?'}`, w.ce, w.pe, pcr) + '</span>';
        }else{
          winTextEl.innerHTML = '';
        }
      }
    }
  
    

  // ================================
  // Decision Add-ons: Trade Bias, Zones, Smart SL, Snapshots
  // ================================
  function parseGammaRangeFromText(txt){
    if(!txt) return null;
    const m = String(txt).match(/(\d{4,6})\s*[-–]\s*(\d{4,6})/);
    if(!m) return null;
    const lo = Number(m[1]), hi = Number(m[2]);
    if(!Number.isFinite(lo) || !Number.isFinite(hi)) return null;
    return {lo: Math.min(lo,hi), hi: Math.max(lo,hi)};
  }

  function computeTradePlan(j){
    try{
      const rows = Array.isArray(j?.rows) ? j.rows : [];
      if(!rows.length) return null;
      const spot = Number(j?.underlying_now ?? window.lastJson?.price ?? j?.price ?? NaN);
      const atm  = Number(j?.atm ?? NaN);
      const step = 50;

      // Net DAY ΔOI: PutDAY - CallDAY (positive => bullish leaning)
      let callDay = 0, putDay = 0;
      for(const r of rows){
        const cd = Number(getDeltaForTf(r?.CE, 'DAY', 'OI'));
        const pd = Number(getDeltaForTf(r?.PE, 'DAY', 'OI'));
        if(Number.isFinite(cd)) callDay += cd;
        if(Number.isFinite(pd)) putDay  += pd;
      }
      const net = putDay - callDay;
      const tot = Math.abs(putDay) + Math.abs(callDay);
      const imb = (tot>0) ? (net / tot) * 100 : 0;

      // Gamma range from already-rendered label (preferred)
      const gzTxt = document.getElementById('nseGammaZone')?.textContent || '';
      const gr = parseGammaRangeFromText(gzTxt);
      let gammaPos = 'unknown';
      if(gr && Number.isFinite(spot)){
        if(spot < gr.lo) gammaPos = 'below';
        else if(spot > gr.hi) gammaPos = 'above';
        else gammaPos = 'inside';
      }

      // Bias decision
      let bias = 'NEUTRAL';
      if(gammaPos === 'inside') bias = 'NEUTRAL';
      else if(net > 0) bias = 'BULLISH';
      else if(net < 0) bias = 'BEARISH';

      // Confidence 0..10
      const netStrength = (tot>0) ? Math.min(1, Math.abs(net)/tot) : 0;
      const gammaDist = (gr && Number.isFinite(spot))
        ? (gammaPos==='above' ? Math.max(0, spot - gr.hi) : (gammaPos==='below' ? Math.max(0, gr.lo - spot) : 0))
        : 0;
      const gammaScore = gr ? Math.min(1, gammaDist / 80) : 0.35; // 80pts ~ decent move
      let conf = 10 * (0.65*netStrength + 0.35*gammaScore);
      // penalty if bias contradicts gamma direction
      if(gr && Number.isFinite(spot)){
        if(gammaPos==='above' && bias==='BEARISH') conf *= 0.55;
        if(gammaPos==='below' && bias==='BULLISH') conf *= 0.55;
      }
      conf = Math.max(0, Math.min(10, conf));

      // Smart SL suggestion (simple, robust)
      let sl = null;
      if(gr && Number.isFinite(spot)){
        if(bias==='BULLISH' && gammaPos==='above') sl = Math.round((gr.hi - step/2)/step)*step;
        else if(bias==='BEARISH' && gammaPos==='below') sl = Math.round((gr.lo + step/2)/step)*step;
        else if(gammaPos==='inside') sl = null;
      }
      if(!sl && Number.isFinite(atm)){
        if(bias==='BULLISH') sl = atm - step;
        if(bias==='BEARISH') sl = atm + step;
      }

      // Build reasons
      const reasons = [];
            reasons.push(`Net DAY ΔOI: ${(net>0?'+':'') + Math.round(net).toLocaleString('en-IN')}`);
      reasons.push(`Imbalance: ${(imb>0?'+':'') + imb.toFixed(1)}%`);
      if(gr && Number.isFinite(spot)){
        const tag = (gammaPos==='inside') ? 'Inside Gamma Zone' : (gammaPos==='above' ? `Above Gamma (+${Math.round(spot-gr.hi)} pts)` : `Below Gamma (+${Math.round(gr.lo-spot)} pts)`);
        reasons.push(tag);
      }

      return { spot, atm, step, callDay, putDay, net, imb, bias, conf, gammaPos, gammaRange: gr, sl, reasons };
    }catch(e){
      console.warn('computeTradePlan failed', e);
      return null;
    }
  }

  function updateTradeBiasUI(plan){
    const row = document.getElementById('decRow');
    if(!row) return;
    if(!plan){ row.style.display='none'; return; }
    row.style.display='grid';

    const bEl = document.getElementById('tbBias');
    const sub = document.getElementById('tbSub');
    const confEl = document.getElementById('tbConfidence');
    const modeEl = document.getElementById('tbMode');
    const rsEl = document.getElementById('tbReasons');
    const slEl = document.getElementById('tbSl');

    const biasTxt = plan.bias;
    bEl.textContent = (biasTxt==='BULLISH'?'BULLISH ⚡':(biasTxt==='BEARISH'?'BEARISH ⚡':'NEUTRAL ⚠️'));
    bEl.className = 'tb-bias ' + (biasTxt==='BULLISH'?'bull':(biasTxt==='BEARISH'?'bear':'neu'));

    const spotStr = Number.isFinite(plan.spot) ? plan.spot.toFixed(2) : '—';
    const atmStr  = Number.isFinite(plan.atm)  ? Math.round(plan.atm).toLocaleString('en-IN') : '—';
    sub.textContent = `Spot: ${spotStr} · ATM: ${atmStr}`;

    confEl.textContent = `Conf: ${plan.conf.toFixed(1)}/10`;
    modeEl.textContent = `Mode: ${plan.gammaPos==='inside'?'Range':'Trend'}`;

    rsEl.innerHTML = (plan.reasons||[]).slice(0,5).map(r=>`<span class="r">${esc(r)}</span>`).join('');

    if(plan.sl && plan.bias!=='NEUTRAL'){
      slEl.style.display='block';
      const slTxt = `Suggested SL (spot basis): <b>${Math.round(plan.sl).toLocaleString('en-IN')}</b> · Use with your premium SL too.`;
      slEl.innerHTML = slTxt;
    } else {
      slEl.style.display='none';
      slEl.innerHTML = '';
    }
  }

  function getActionBadge(plan, strike){
    if(!plan || !Number.isFinite(Number(strike))) return '';
    const st = Number(strike);
    const atm = Number(plan.atm);
    const step = Number(plan.step||50);
    const near = (Number.isFinite(atm) ? Math.abs(st-atm) <= step : false);

    // Inside gamma zone => no-trade
    if(plan.gammaPos==='inside') return `<span class="zone-badge nt" data-tip="Inside Gamma Zone → prefer No-Trade / scalps only">⛔ No‑Trade</span>`;

    // Directional
    if(plan.bias==='BULLISH' && near) return `<span class="zone-badge ce" data-tip="Bullish bias near ATM → prefer CE setups">🟩 CE Zone</span>`;
    if(plan.bias==='BEARISH' && near) return `<span class="zone-badge pe" data-tip="Bearish bias near ATM → prefer PE setups">🟥 PE Zone</span>`;

    // Far strikes: keep clean
    return '';
  }

  // Snapshot storage
  const SNAP_KEY = 'oi_snapshots_v1';
  // called after NSE table render
  function updateDecisionAddons(j){
    const plan = computeTradePlan(j);
    updateTradeBiasUI(plan);

    // inject action badges into strike cells
    try{
      const body = document.getElementById('nseOcBody');
      if(body && plan){
        body.querySelectorAll('td.sticky-strike').forEach(td=>{
          // remove old badge
          const old = td.querySelector('.zone-badge');
          if(old) old.remove();
          const st = parseInt(String(td.textContent||'').replace(/[^0-9]/g,''),10);
          if(Number.isFinite(st)) td.insertAdjacentHTML('beforeend', ' ' + getActionBadge(plan, st));
        });
      }
    }catch(e){ console.warn('zones inject failed', e); }

    // init snapshot UI once
    if(!window.__snapBound){
      window.__snapBound = true;
      bindSnapshotUI();
    }
  }

  // Fallback: ensure Snapshot UI is bound even if init path changes
  document.addEventListener('DOMContentLoaded', ()=>{
    try{
      if(!window.__snapBound){
        window.__snapBound = true;
        bindSnapshotUI();
      }
    }catch(e){
      console.warn('bindSnapshotUI fallback failed', e);
    }
  });
  window.addEventListener('load', ()=>{
    try{
      if(!window.__snapBound){
        window.__snapBound = true;
        bindSnapshotUI();
      }
    }catch(e){
      console.warn('bindSnapshotUI load fallback failed', e);
    }
  });

// Highlight ATM row in NSE Style Option Chain
    try{ highlightNseAtmRow(j); }catch(e){}
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

  // keep dock positioned below the fixed status bar
  window.addEventListener('resize', ()=>setDockTop(), {passive:true});

  // Fallback: very light periodic refresh (in case something renders outside observer)
  setInterval(scheduleBeautify, 5000);

  document.addEventListener('change', function(e){
    if(e.target && (e.target.id === 'strikeWin' || e.target.id === 'strikeWinServer')){
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

// ==== TOP PCR CHIPS (Expiry + ATM) ====
function __safeNum(x){ const n = Number(x); return Number.isFinite(n) ? n : null; }
function __fmtPcr(x){ const n = __safeNum(x); return (n==null) ? '—' : n.toFixed(2); }
function __calcPcr(pe, ce){
  const p = __safeNum(pe), c = __safeNum(ce);
  if(p==null || c==null || c===0) return null;
  return p / c;
}
function __pick(obj, keys){
  for(const k of keys){
    if(obj && Object.prototype.hasOwnProperty.call(obj,k) && obj[k]!=null) return obj[k];
  }
  return null;
}
function __getExpiryTotals(j){
  const now = j?.expiry_totals?.now || j?.expiry_totals?.current || j?.expiry_totals || {};
  const ce = __pick(now, ['ce_oi','ce','call_oi','ceOi','ceOI']);
  const pe = __pick(now, ['pe_oi','pe','put_oi','peOi','peOI']);
  const pcr = __pick(now, ['pcr','PCR']);
  return {ce, pe, pcr: (pcr!=null ? pcr : __calcPcr(pe, ce))};
}
function __getAtmTotals(j){
  const snap = j?.atm_totals?.now || j?.atm_totals || j?.atm_snapshot || j?.atmSnapshot || j?.atm || {};
  const atm = __pick(j, ['atm','atm_strike','atmStrike','atm_strike_price']) ?? __pick(snap, ['atm','strike','atmStrike']);
  const ce = __pick(snap, ['ce_oi','ce','ce_cur','ceCur','ceOi','ceOI']);
  const pe = __pick(snap, ['pe_oi','pe','pe_cur','peCur','peOi','peOI']);
  const pcr = __pick(snap, ['pcr','PCR']);
  return {atm, ce, pe, pcr: (pcr!=null ? pcr : __calcPcr(pe, ce))};
}
function renderTopPcrChips(j){
  try{
    const el = document.getElementById('topPcrChips');
    if(!el) return;
    const exp = __getExpiryTotals(j||{});
    const atm = __getAtmTotals(j||{});
    // if nothing usable, hide
    const hasAny = (exp.ce!=null || exp.pe!=null || exp.pcr!=null || atm.ce!=null || atm.pe!=null || atm.pcr!=null);
    if(!hasAny){ el.style.display='none'; return; }
    el.style.display='flex';

    const expCe = (exp.ce==null)?'—':fmtNum(exp.ce);
    const expPe = (exp.pe==null)?'—':fmtNum(exp.pe);
    const expP  = __fmtPcr(exp.pcr);

    const atmCe = (atm.ce==null)?'—':fmtNum(atm.ce);
    const atmPe = (atm.pe==null)?'—':fmtNum(atm.pe);
    const atmP  = __fmtPcr(atm.pcr);
    const atmStrike = (atm.atm==null)?'ATM':('ATM '+atm.atm);

    el.innerHTML = `
      <div class="pcr-pill" title="Expiry totals (all strikes)">
        <span class="tag">Σ Expiry</span>
        <span class="ce">CE ${expCe}</span>
        <span class="pe">PE ${expPe}</span>
        <span class="pcr">PCR ${expP}</span>
      </div>
      <div class="pcr-pill" title="ATM strike totals">
        <span class="tag">${atmStrike}</span>
        <span class="ce">CE ${atmCe}</span>
        <span class="pe">PE ${atmPe}</span>
        <span class="pcr">PCR ${atmP}</span>
      </div>
    `;
  }catch(e){}
}

// Hook into existing data refresh pipeline (best-effort)
(function(){
  const _origSetLastJson = window.setLastJson;
  if(typeof _origSetLastJson === 'function'){
    window.setLastJson = function(j){
      const out = _origSetLastJson.apply(this, arguments);
      try{ renderTopPcrChips(j); }catch(e){}
      return out;
    };
  }else{
    // fallback: watch window.lastJson changes after loads
    let _lastSig = '';
    setInterval(()=>{
      try{
        const j = window.lastJson;
        const sig = j ? (j.ts || j.updated_at || JSON.stringify([j.symbol,j.expiry,j.price,j.atm]).slice(0,120)) : '';
        if(sig && sig !== _lastSig){
          _lastSig = sig;
          renderTopPcrChips(j);
        }
      }catch(e){}
    }, 800);
  }
})();

// ==== TF dropdown flip if it would be clipped ====
function ensureTfDropdownFits(popEl){
  if(!popEl) return;
  try{
    popEl.style.bottom = '';
    popEl.style.top = '';
    popEl.style.transform = '';
    const r = popEl.getBoundingClientRect();
    const pad = 8;
    if(r.bottom > window.innerHeight - pad){
      // flip upward
      popEl.style.top = 'auto';
      popEl.style.bottom = '38px';
    }
    popEl.style.zIndex = '99999';
  }catch(e){}
}
// If your TF popup is toggled via class "show", adjust on open
document.addEventListener('click', ()=>{
  const pop = document.querySelector('.nse-tf-pop.show, .tf-pop.show, .tf-menu.show');
  if(pop) ensureTfDropdownFits(pop);
});

// Ensure condensed toggle always works even if controls were re-rendered
document.addEventListener('click', (e)=>{
  const b = e.target && e.target.closest && e.target.closest('#nseOcSize .btn');
  if(!b) return;
  if((b.textContent||'').trim().toLowerCase()==='condensed'){
    // force visual refresh
    setTimeout(()=>{ 
      const wrap = document.getElementById('nseOcWrap');
      if(wrap){ wrap.classList.toggle('condensed', window.__nseOcCondensed===true); }
    }, 0);
  }
});

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

  function applyState(card, collapsed){
    card.classList.toggle('collapsed', !!collapsed);
    card.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    const body = card.querySelector('.card-body');
    const summary = card.querySelector('.card-summary');
    const icon = card.querySelector('.card-toggle');

    if(body) body.style.display = collapsed ? 'none' : '';
    if(summary) summary.style.display = collapsed ? '' : 'none';
    if(icon) icon.textContent = collapsed ? '▼' : '▲';

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
</body>
</html>