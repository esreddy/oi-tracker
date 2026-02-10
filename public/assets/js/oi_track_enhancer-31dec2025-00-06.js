/* OI Dashboard Enhancer (FINAL)
 * - Robust column mapping using #trackHeadRow (preferred) + THEAD grid fallback
 * - Timeframe toggles hide BOTH header + body (no shifting)
 * - ΔOI(T) arrows + %: Current OI − Start OI(T) for 1/2/3/5/10/15/30 (whatever exists)
 * - Bigger OI values; calmer ΔOI
 * - Color-scale Current OI by intensity (auto-normalized every refresh)
 * - Fade ΔOI when flat (near-zero change)
 * - Bold only ATM ±2 strikes (OI Track)
 * - Blink on sudden OI spike (3m vs 10m)
 * - CE/PE dominance shading per strike (based on Current OI)
 * - ATM Summary layout:
 *    1) CE/PE bars row (top)
 *    2) ALL 6 boxes in ONE row: TopCur, TopΔ, ATM Snap, Leaders, Quick Read, Signals
 *    3) Below: Dominance line + Regime line (pills)
 *    4) Below: Legend + ATM-100/ATM-50/... mini strip (as-is)
 */

(function () {
  // ===================== CSS =====================
  function injectCssOnce() {
    if (document.getElementById("oiEnhancerUltraCSS")) return;

    const css = `
      /* ATM highlight */
      .atm-row{ background:#173057 !important; box-shadow: inset 3px 0 0 #3ba3ff; }
      .atm-badge{ font-size:11px; padding:2px 6px; border-radius:8px; background:#0f2e5f; color:#93c5fd; margin-left:6px; }

      /* Base typography */
      .oi-value{ font-size:15.5px; font-weight:600; color:#e5e7eb; }
      .delta-compact{ white-space:nowrap; font-size:13px; font-weight:600; opacity:0.82; }
      .delta-arrow{ font-weight:800; margin-right:4px; font-size:12px; }
      .delta-up{ color:#16a34a; } .delta-down{ color:#ef4444; }

      /* ATM ±2 emphasis (only) */
      .atm-strong .oi-value{ font-size:16px; font-weight:700; }

      /* Toolbar + legend */
      #oiTrackLegend{ margin-top:6px; font-size:12px; color:#9aa4b2; }
      #oiTrackLegend b{ color:#cbd5e1; font-weight:600; margin-right:6px; }
      #oiTrackToolbar{ margin:6px 0 8px; display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
      #oiTrackToolbar .btn{ padding:6px 10px; border-radius:8px; background:#0f2e5f; color:#cbd5e1; border:1px solid #2a4a7a; cursor:pointer; }
      #oiTrackToolbar .btn:hover{ filter:brightness(1.08); }
      #oiTrackToolbar label{ display:inline-flex; align-items:center; gap:6px; }
      .nowrap{ white-space:nowrap; }

      /* Intensity scale (Current OI) – subtle overlay */
      .oi-intensity{ transition: background-color .15s ease, outline-color .15s ease; }

      /* Flat ΔOI fade */
      .delta-flat{ opacity:0.45 !important; filter:saturate(0.85); }

      /* Spike blink (3m vs 10m) */
      @keyframes oiSpikeBlink {
        0%, 100% { box-shadow: 0 0 0 rgba(0,0,0,0); transform: translateZ(0); }
        50% { box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.35); }
      }
      .spike-blink{
        animation: oiSpikeBlink 0.9s ease-in-out 0s 3;
        border-radius:6px;
      }

      /* CE/PE dominance shading (very subtle) */
      .dom-ce{ background: rgba(22, 163, 74, 0.06) !important; }
      .dom-pe{ background: rgba(239, 68, 68, 0.06) !important; }
      .dom-weak{ background: rgba(148, 163, 184, 0.04) !important; }

      /* Ensure ATM shading always wins over dominance/intensity */
      .atm-row{ background:#173057 !important; }
      .atm-row > td{ background:#173057 !important; }
      .atm-row.atm-strong > td{ background:#173057 !important; }

      /* ===== ATM Summary Card ===== */
      #oiAtmSummaryCard{
        margin:8px 0 6px;
        padding:10px 10px;
        border:1px solid #2a4a7a;
        border-radius:12px;
        background:rgba(15,46,95,.35);
        color:#cbd5e1;
      }
      #oiAtmSummaryCard .title{ font-weight:800; margin-bottom:4px; }
      #oiAtmSummaryCard .muted{ opacity:.85; font-size:12px; margin-bottom:8px; }

      /* top CE/PE bars row */
      #atmBarsRow{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        align-items:center;
        justify-content:center;
        margin:6px 0 10px;
      }
      #atmBarsRow .barRow{
        display:flex;
        gap:8px;
        align-items:center;
        padding:6px 8px;
        border:1px solid rgba(148,163,184,.18);
        background:rgba(2,6,23,.18);
        border-radius:12px;
        min-width:320px;
      }
      #atmBarsRow .pill{
        padding:2px 8px; border-radius:999px; font-size:12px; font-weight:800;
        border:1px solid rgba(148,163,184,.25); background:#0f2e5f; color:#cbd5e1;
      }
      #atmBarsRow .pill.ce{ border-color: rgba(22,163,74,.45); }
      #atmBarsRow .pill.pe{ border-color: rgba(239,68,68,.45); }
      #atmBarsRow .metric{ font-size:12px; opacity:.9; }
      #atmBarsRow .big{ font-size:14px; font-weight:900; margin-left:4px; }
      #atmBarsRow .bar{
        width:120px; height:10px; border-radius:8px; overflow:hidden;
        border:1px solid rgba(148,163,184,.25); background:rgba(148,163,184,.08);
      }
      #atmBarsRow .fill{ height:100%; }
      #atmBarsRow .fill.ce{ background:rgba(22,163,74,.55); }
      #atmBarsRow .fill.pe{ background:rgba(239,68,68,.55); }

      /* ===== 6 boxes in ONE row ===== */
      #atmBoxRow{
        display:grid;
        grid-template-columns: repeat(6, minmax(180px, 1fr));
        gap:10px;
        align-items:stretch;
        width:100%;
        overflow-x:auto;
        padding-bottom:2px;
      }
      #atmBoxRow::-webkit-scrollbar{ height:6px; }
      #atmBoxRow::-webkit-scrollbar-thumb{ background:rgba(148,163,184,.25); border-radius:6px; }

      .atmBox{
        border:1px solid rgba(148,163,184,.20);
        background:rgba(2,6,23,.25);
        border-radius:12px;
        padding:8px 10px;
        min-height:108px;
      }
      .atmBox .h{
        font-weight:900; font-size:12px; opacity:.95; margin-bottom:6px;
        display:flex; justify-content:space-between; align-items:center;
      }
      .atmBox .h .w{ font-weight:700; font-size:11px; opacity:.85; }
      .atmBox .item{
        display:flex; justify-content:space-between; gap:10px; font-size:11px;
        padding:2px 0; border-top:1px dashed rgba(148,163,184,.14);
      }
      .atmBox .item:first-of-type{ border-top:none; }
      .atmBox .lhs{ display:flex; gap:8px; align-items:center; min-width:0; }
      .atmBox .tag{
        font-size:10px; font-weight:900; padding:1px 6px; border-radius:999px;
        border:1px solid rgba(148,163,184,.25);
        background:rgba(15,46,95,.55);
        flex:0 0 auto;
      }
      .atmBox .tag.ce{ border-color: rgba(22,163,74,.45); }
      .atmBox .tag.pe{ border-color: rgba(239,68,68,.45); }
      .atmBox .num{ font-weight:900; white-space:nowrap; }
      .atmBox .up{ color:rgba(34,197,94,.95); }
      .atmBox .dn{ color:rgba(248,113,113,.95); }

      /* Quick Read / Signals inside their boxes (2-col key-value) */
      .kvGrid{
        display:grid;
        grid-template-columns: 1fr auto;
        gap:6px 10px;
        font-size:11px;
      }
      .kvGrid .k{ opacity:.85; }
      .kvGrid .v{ font-weight:900; }
      .kvGrid .v.ce{ color:rgba(34,197,94,.95); }
      .kvGrid .v.pe{ color:rgba(248,113,113,.95); }
      .pillMini{
        display:inline-block;
        padding:1px 6px;
        border-radius:999px;
        border:1px solid rgba(148,163,184,.25);
        background:rgba(2,6,23,.25);
        font-weight:900;
        cursor:pointer;
      }

      /* ===== Below rows (no gaps) ===== */
      #atmInfoRow{
        display:flex;
        flex-wrap:wrap;
        gap:8px 12px;
        align-items:center;
        margin-top:10px;
      }
      #atmDominanceText{
        font-size:12px;
        opacity:.95;
        flex: 1 1 520px;
        min-width:360px;
      }
      #atmRegime{
        flex: 1 1 520px;
        min-width:360px;
        text-align:right;
        font-size:12px;
      }
      #atmRegime .pillLine{
        display:inline-block;
        padding:2px 8px;
        border-radius:999px;
        border:1px solid rgba(148,163,184,.22);
        background:rgba(2,6,23,.18);
        margin-left:6px;
        white-space:nowrap;
      }
      #atmRegime .up{ border-color:rgba(34,197,94,.35); }
      #atmRegime .dn{ border-color:rgba(248,113,113,.35); }
      #atmRegime .rng{ border-color:rgba(59,130,246,.35); }
      #atmRegime .warn{ border-color:rgba(250,204,21,.45); }

      /* ===== Legend + Mini strip (as-is below) ===== */
      #atmMiniStrip{
        margin-top:10px;
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        align-items:center;
      }
      #atmMiniStrip .legendTxt{
        font-size:11px; opacity:.85; flex: 1 1 520px; min-width:320px;
      }
      #atmMiniStrip .mini{
        padding:4px 8px;
        border-radius:10px;
        border:1px solid rgba(148,163,184,.25);
        background:rgba(2,6,23,.35);
        font-size:11px;
        white-space:nowrap;
      }
      #atmMiniStrip .mini .s{ font-weight:900; margin-right:6px; }
      #atmMiniStrip .mini .k{ opacity:.85; }
      #atmMiniStrip .mini .v{ font-weight:900; }
      #atmMiniStrip .mini .ce{ color:rgba(34,197,94,.95); }
      #atmMiniStrip .mini .pe{ color:rgba(248,113,113,.95); }
      #atmMiniStrip .mini .warn{ color:rgba(250,204,21,.95); }

      /* ===== MAX highlights ===== */
      .max-oi-cell{
        outline:2px solid rgba(34,197,94,.75);
        outline-offset:-2px;
        border-radius:6px;
        position:relative;
        padding-right:12px;
      }
      .max-delta-cell{
        outline:2px solid rgba(59,130,246,.85);
        outline-offset:-2px;
        border-radius:6px;
        position:relative;
        padding-right:12px;
      }
      .max-tag{
        display:inline-block;
        margin-left:6px;
        font-size:10px; font-weight:800;
        padding:1px 5px;
        border-radius:999px;
        background:rgba(15,46,95,.9);
        border:1px solid rgba(148,163,184,.25);
        color:#e5e7eb;
        pointer-events:none;
        opacity:.95;
      }

      /* Responsive: if screen is small, allow scroll but keep one row intent */
      @media (max-width: 1200px){
        #atmBoxRow{ grid-template-columns: repeat(6, minmax(200px, 1fr)); }
        #atmRegime{ text-align:left; }
      }


      #atmHeaderRow{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        margin-bottom:10px;
      }

      #atmHeaderRow .hdrLeft{
        display:flex;
        align-items:center;
        gap:8px;
        min-width:380px;
        white-space:nowrap;
      }

      #atmHeaderRow .title{ font-weight:700; font-size:14px; }
      #atmHeaderRow .sep{ opacity:.7; }
      #atmHeaderRow .muted{ opacity:.85; font-size:12px; }

      #atmHeaderRow .hdrRight{
        display:flex;
        align-items:center;
        gap:14px;
        flex:1;
        justify-content:flex-end;
      }

      /* make CE/PE rows compact so they fit in one line */
      #atmHeaderRow .barRow.mini{
        display:flex;
        align-items:center;
        gap:8px;
      }

      #atmHeaderRow .barRow.mini .metric{
        font-size:11px;
        opacity:.9;
        white-space:nowrap;
      }

      #atmHeaderRow .barRow.mini .big{
        font-size:12px;
        font-weight:700;
      }

      #atmHeaderRow .barRow.mini .bar{
        width:110px;          /* adjust if you want more/less */
        height:8px;
        border-radius:999px;
        overflow:hidden;
        background:rgba(148,163,184,.15);
      }

      #atmHeaderRow .barRow.mini .fill{
        height:100%;
        border-radius:999px;
      }


      /* Put meta + CE/PE bars in one row */
        #atmMetaBarsRow{
          display:flex;
          align-items:center;
          justify-content:space-between;
          gap:12px;
          flex-wrap:wrap;            /* wraps nicely on small width */
          margin-top:6px;
        }

        /* Highlight the meta text */
        #oiAtmSummaryMeta.atm-meta-pill{
          display:inline-flex;
          align-items:center;
          padding:4px 10px;
          border-radius:999px;
          border:1px solid rgba(59,130,246,.35);
          background:rgba(59,130,246,.12);
          color:#cbd5e1;
          font-weight:800;
          font-size:12px;
          letter-spacing:.2px;
          white-space:nowrap;
        }

        /* Bars row stays to the right */
        #atmBarsRow{
          display:flex;
          align-items:center;
          gap:14px;
          margin-left:auto;
        }


        /* ONE LINE: title + meta + CE/PE bars */
          #atmHeaderRow{
            display:flex;
            align-items:center;
            gap:10px;
            flex-wrap:nowrap;
          }

          /* keep title in same row */
          #oiAtmSummaryCard .title{
            font-weight:900;
            white-space:nowrap;
            margin:0;
          }

          /* highlighted meta pill */
          #oiAtmSummaryMeta.atm-meta-pill{
            display:inline-flex;
            align-items:center;
            padding:4px 10px;
            border-radius:999px;
            border:1px solid rgba(59,130,246,.35);
            background:rgba(59,130,246,.12);
            color:#cbd5e1;
            font-weight:800;
            font-size:12px;
            letter-spacing:.2px;
            white-space:nowrap;
          }

          /* bars stay on the right */
          #atmBarsRow{
            margin-left:auto;
            display:flex;
            align-items:center;
            gap:14px;
            white-space:nowrap;
          }

        #atmHeaderRow{
            display:flex;
            align-items:center;
            gap:12px;
            flex-wrap:nowrap;
          }

          #atmHeaderLeft{
            font-weight:900;
            white-space:nowrap;
          }

          #atmHeaderRight{
            margin-left:auto;          /* pushes to right */
            display:flex;
            align-items:center;
            gap:12px;
            flex-wrap:nowrap;
          }

          #oiAtmSummaryMeta.atm-meta-pill{
            display:inline-flex;
            align-items:center;
            padding:4px 10px;
            border-radius:999px;
            border:1px solid rgba(59,130,246,.35);
            background:rgba(59,130,246,.12);
            color:#cbd5e1;
            font-weight:800;
            font-size:12px;
            white-space:nowrap;
          }

          #atmBarsRow{
            display:flex;
            align-items:center;
            gap:14px;
            white-space:nowrap;
          }

          /* MAX ΔOI per type (Option B) */
          .max-delta-ce{
            outline:2px solid rgba(34,197,94,.85);
            outline-offset:-2px;
            border-radius:6px;
            position:relative;
            padding-right:12px;
          }
          .max-delta-pe{
            outline:2px solid rgba(248,113,113,.90);
            outline-offset:-2px;
            border-radius:6px;
            position:relative;
            padding-right:12px;
          }


    `;

    const style = document.createElement("style");
    style.id = "oiEnhancerUltraCSS";
    style.textContent = css;
    document.head.appendChild(style);
  }

  // ===================== Utilities =====================
  const num = (t) =>
    parseFloat(
      String(t || "")
        .replace(/[▲▼,]/g, "")
        .replace(/[^\d.\-]/g, "")
    );

  const fmt = (n) => {
    const v = Number(n);
    if (!isFinite(v)) return "-";
    return v.toLocaleString("en-IN");
  };

  function norm(s) {
    return String(s || "")
      .trim()
      .replace(/\s+/g, " ")
      .toLowerCase();
  }

  function getATM() {
    const atmEl = document.getElementById("atm");
    if (atmEl) {
      const v = parseInt(
        String(atmEl.textContent || "").replace(/\D+/g, ""),
        10
      );
      if (!isNaN(v)) return v;
    }
    if (window.lastJson && window.lastJson.atm != null) {
      const n = Number(window.lastJson.atm);
      if (!isNaN(n)) return n;
    }
    return null;
  }

  function getTrackRefs() {
    const tbody = document.getElementById("trackTable");
    if (!tbody) return null;
    const table = tbody.closest("table");
    const thead = table ? table.querySelector("thead") : null;
    return table && tbody ? { table, thead, tbody } : null;
  }

  // ===================== Header mapping =====================
  function buildHeaderGrid(thead) {
    if (!thead) return null;
    const rows = Array.from(thead.querySelectorAll("tr"));
    if (!rows.length) return null;

    const grid = [];
    const spanLeft = [];

    for (let r = 0; r < rows.length; r++) {
      grid[r] = [];
      const cells = Array.from(rows[r].querySelectorAll("th,td"));
      let c = 0;

      while (spanLeft[c] > 0) {
        grid[r][c] = "";
        spanLeft[c]--;
        c++;
      }

      for (const cell of cells) {
        while (spanLeft[c] > 0) {
          grid[r][c] = "";
          spanLeft[c]--;
          c++;
        }

        const text = norm(cell.textContent || "");
        const colspan = parseInt(cell.getAttribute("colspan") || "1", 10) || 1;
        const rowspan = parseInt(cell.getAttribute("rowspan") || "1", 10) || 1;

        for (let k = 0; k < colspan; k++) {
          grid[r][c + k] = text;
          if (rowspan > 1)
            spanLeft[c + k] = (spanLeft[c + k] || 0) + (rowspan - 1);
        }
        c += colspan;
      }

      while (spanLeft[c] > 0) {
        grid[r][c] = "";
        spanLeft[c]--;
        c++;
      }
    }

    const colCount = Math.max(...grid.map((row) => row.length));
    const labels = new Array(colCount).fill("");
    for (let c = 0; c < colCount; c++) {
      let last = "";
      for (let r = 0; r < grid.length; r++) if (grid[r][c]) last = grid[r][c];
      labels[c] = last;
    }
    return { labels, colCount };
  }

  function labelsFromTrackHeadRow() {
    const headRow = document.getElementById("trackHeadRow");
    if (!headRow) return null;
    const ths = Array.from(headRow.querySelectorAll("th"));
    if (!ths.length) return null;
    return ths.map((th) => norm(th.textContent || ""));
  }

  function findCol(labels, regs) {
    for (let i = 0; i < labels.length; i++) {
      const t = labels[i] || "";
      if (regs.some((r) => r.test(t))) return i;
    }
    return -1;
  }

  function mapColumns() {
    const refs = getTrackRefs();
    if (!refs) return null;
    const { thead, tbody } = refs;

    const L =
      labelsFromTrackHeadRow() || buildHeaderGrid(thead)?.labels || null;
    const m = {
      strike: -1,
      type: -1,
      curOi: -1,
      curDelta: -1, // ✅ add this
      oi: {},
      d: {},
      _labels: L,
    };

    if (L && L.length) {
      m.strike = findCol(L, [/^strike$/i, /^strike\s*price$/i]);
      m.type = findCol(L, [/^type$/i, /^option\s*type$/i, /^cp$/i]);

      m.curOi = findCol(L, [
        /^current\s*oi$/i,
        /^cur\s*oi$/i,
        /^oi\s*\(current\)$/i,
      ]);

      m.curDelta = findCol(L, [
        /^cur\s*Δ\s*oi$/i,
        /^current\s*Δ\s*oi$/i,
        /^cur\s*doi$/i,
        /^current\s*doi$/i,
        /^Δ\s*oi$/i,
        /^doi$/i,
        /^chg$/i,
        /^change$/i,
        /^oi\s*chg$/i,
        /^chg\s*oi$/i,
        /^change\s*in\s*oi$/i,
      ]);

      for (let i = 0; i < L.length; i++) {
        const t = L[i];
        let mm = null;

        let m1 =
          t.match(/^oi\s*\((\d+)\s*m\)$/i) ||
          t.match(/^(start\s*)?oi\s*\((\d+)\s*m\)$/i);
        if (m1) {
          mm = parseInt(m1[m1.length - 1], 10);
          if (mm) m.oi[mm] = i;
          continue;
        }
        let m2 =
          t.match(/^oi\s*\((\d+)\s*min\)$/i) ||
          t.match(/^(start\s*)?oi\s*\((\d+)\s*min\)$/i);
        if (m2) {
          mm = parseInt(m2[m2.length - 1], 10);
          if (mm) m.oi[mm] = i;
          continue;
        }

        let d1 =
          t.match(/^(Δ|delta)\s*oi\s*\((\d+)\s*m\)$/i) ||
          t.match(/^doi\s*\((\d+)\s*m\)$/i) ||
          t.match(/^chg.*\((\d+)\s*m\)$/i);
        if (d1) {
          mm = parseInt(d1[d1.length - 1], 10);
          if (mm) m.d[mm] = i;
          continue;
        }
        let d2 =
          t.match(/^(Δ|delta)\s*oi\s*\((\d+)\s*min\)$/i) ||
          t.match(/^doi\s*\((\d+)\s*min\)$/i) ||
          t.match(/^chg.*\((\d+)\s*min\)$/i);
        if (d2) {
          mm = parseInt(d2[d2.length - 1], 10);
          if (mm) m.d[mm] = i;
          continue;
        }
      }

      Object.keys(m.oi).forEach((k) => {
        const mm = parseInt(k, 10);
        if (m.oi[mm] >= 0 && (m.d[mm] == null || m.d[mm] < 0))
          m.d[mm] = m.oi[mm] + 1;
      });

      if (m.strike < 0) m.strike = 0;
      if (m.type < 0) m.type = 1;
    } else {
      const sample = tbody.querySelector("tr");
      const cols = sample ? sample.children.length : 0;
      if (cols > 2) {
        m.strike = 0;
        m.type = 1;
        m.curOi = cols - 2;
      }
    }

    return m;
  }

  // ===================== Toolbar & legend =====================
  function ensureToolbar() {
    if (document.getElementById("oiTrackToolbar")) return;
    const refs = getTrackRefs();
    if (!refs) return;

    const bar = document.createElement("div");
    bar.id = "oiTrackToolbar";
    bar.innerHTML = `
      <label class="nowrap">Timeframes:
        <input type="checkbox" id="col1"> 1m
        <input type="checkbox" id="col2"> 2m
        <input type="checkbox" id="col3" checked> 3m
        <input type="checkbox" id="col5" checked> 5m
        <input type="checkbox" id="col10" checked> 10m
        <input type="checkbox" id="col15" checked> 15m
        <input type="checkbox" id="col30" checked> 30m
      </label>
      <label class="nowrap" title="Show rows within ±N × 50 around ATM (OI Track only)">
        Strikes ± <input id="strikeWinTrack" type="number" min="1" value="10" style="width:70px">
      </label>
      <div class="nowrap" title="Spike alert: 3m change much larger than 10m (ratio + min threshold)">
        Spike ratio <input id="spikeRatio" type="number" min="1.1" step="0.1" value="1.8" style="width:70px">
        Min Δ <input id="spikeMin" type="number" min="0" step="1000" value="5000" style="width:80px">
      </div>
    `;

    refs.table.parentElement.insertBefore(bar, refs.table);

    [
      "col1",
      "col2",
      "col3",
      "col5",
      "col10",
      "col15",
      "col30",
      "strikeWinTrack",
      "spikeRatio",
      "spikeMin",
    ].forEach((id) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener("change", enhanceAll);
      el.addEventListener("keyup", (e) => {
        if (e.key === "Enter") enhanceAll();
      });
    });
  }

  function ensureLegend() {
    const refs = getTrackRefs();
    if (!refs) return;
    if (document.getElementById("oiTrackLegend")) return;
    const leg = document.createElement("div");
    leg.id = "oiTrackLegend";
    leg.innerHTML = `
      <b>Legend:</b>
      <span class="delta-arrow delta-up">▲</span> added OI,
      <span class="delta-arrow delta-down">▼</span> reduced OI.
      <span style="opacity:.85">ΔOI(T)=Current−Start(T). Intensity auto-normalizes each refresh. Spike blink = 3m vs 10m surge.</span>
    `;
    refs.table.insertAdjacentElement("afterend", leg);
  }

  // ===================== Column show/hide =====================
  function toggleTimeframeColumns() {
    const refs = getTrackRefs();
    if (!refs) return;
    const { table, tbody } = refs;

    const show = {
      1: document.getElementById("col1")?.checked === true,
      2: document.getElementById("col2")?.checked === true,
      3: document.getElementById("col3")?.checked !== false,
      5: document.getElementById("col5")?.checked !== false,
      10: document.getElementById("col10")?.checked !== false,
      15: document.getElementById("col15")?.checked !== false,
      30: document.getElementById("col30")?.checked !== false,
    };

    const m = mapColumns();
    if (!m) return;

    const headRow = document.getElementById("trackHeadRow");

    function setColVis(idx, vis) {
      if (idx == null || idx < 0) return;

      if (headRow && headRow.children[idx]) {
        headRow.children[idx].style.display = vis ? "" : "none";
      } else {
        table.querySelectorAll("thead tr").forEach((tr) => {
          if (tr.children[idx])
            tr.children[idx].style.display = vis ? "" : "none";
        });
      }

      tbody.querySelectorAll("tr").forEach((tr) => {
        if (tr.children[idx])
          tr.children[idx].style.display = vis ? "" : "none";
      });
    }

    [30, 15, 10, 5, 3, 2, 1].forEach((min) => {
      if (m.oi[min] != null) setColVis(m.oi[min], !!show[min]);
      if (m.d[min] != null) setColVis(m.d[min], !!show[min]);
    });
  }

  // ===================== ΔOI arrows + % + flat fade =====================
  function addDeltaArrowsAndStyles() {
    const refs = getTrackRefs();
    if (!refs) return;
    const { tbody } = refs;

    const m = mapColumns();
    if (!m || m.curOi < 0) return;

    const windows = Object.keys(m.oi)
      .map((k) => parseInt(k, 10))
      .filter((v) => !isNaN(v))
      .sort((a, b) => b - a);
    const curWin = pickDeltaWindowForSummary(m); // ✅ window used for CUR ΔOI

    const flatPct = 0.12;
    const flatAbs = 200;

    Array.from(tbody.querySelectorAll("tr")).forEach((tr) => {
      const cur = num(tr.children[m.curOi]?.textContent);

      if (tr.children[m.curOi]) tr.children[m.curOi].classList.add("oi-value");

      windows.forEach((min) => {
        const oiIdx = m.oi[min];
        const dIdx = m.d[min];
        if (oiIdx == null || dIdx == null || oiIdx < 0 || dIdx < 0) return;

        const oiTd = tr.children[oiIdx];
        const dTd = tr.children[dIdx];
        if (oiTd) oiTd.classList.add("oi-value");

        if (!dTd) return;
        dTd.classList.add("delta-compact");
        dTd.classList.remove("delta-flat", "spike-blink");

        const startVal = num(oiTd?.textContent);
        if (isNaN(cur) || isNaN(startVal)) {
          dTd.textContent = "-";
          return;
        }

        const delta = cur - startVal;
        let pct = 0;
        if (startVal !== 0) pct = (delta / startVal) * 100;

        if (Math.abs(pct) < flatPct && Math.abs(delta) < flatAbs)
          dTd.classList.add("delta-flat");

        if (delta === 0) {
          dTd.innerHTML = `0 (0.00%)`;
        } else {
          const up = delta > 0;
          const sign = pct >= 0 ? "+" : "-";
          const pctStr =
            startVal !== 0 ? ` (${sign}${Math.abs(pct).toFixed(2)}%)` : "";
          dTd.innerHTML = `
            <span class="delta-arrow ${up ? "delta-up" : "delta-down"}">${
            up ? "▲" : "▼"
          }</span>
            ${fmt(Math.abs(delta))}${pctStr}
          `;
        }
      });

      // ✅ CUR ΔOI (CHG & %) column — same calc as other ΔOI: Current − Start(curWin)
      if (m.curDelta != null && m.curDelta >= 0 && m.oi[curWin] != null) {
        const dTd = tr.children[m.curDelta];
        const oiTd = tr.children[m.oi[curWin]];

        if (dTd && oiTd) {
          dTd.classList.add("delta-compact");
          dTd.classList.remove("delta-flat", "spike-blink");

          const startVal = num(oiTd.textContent);
          if (!isNaN(cur) && !isNaN(startVal)) {
            const delta = cur - startVal;
            let pct = 0;
            if (startVal !== 0) pct = (delta / startVal) * 100;

            if (Math.abs(pct) < flatPct && Math.abs(delta) < flatAbs)
              dTd.classList.add("delta-flat");

            if (delta === 0) {
              dTd.innerHTML = `0 (0.00%)`;
            } else {
              const up = delta > 0;
              const sign = pct >= 0 ? "+" : "-";
              const pctStr =
                startVal !== 0 ? ` (${sign}${Math.abs(pct).toFixed(2)}%)` : "";
              dTd.innerHTML = `
              <span class="delta-arrow ${up ? "delta-up" : "delta-down"}">${
                up ? "▲" : "▼"
              }</span>
              ${fmt(Math.abs(delta))}${pctStr}
            `;
            }
          } else {
            dTd.textContent = "-";
          }
        }
      }
    });
  }

  // ===================== Auto-normalized OI intensity =====================
  function applyOiIntensity() {
    const refs = getTrackRefs();
    if (!refs) return;
    const { tbody } = refs;
    const m = mapColumns();
    if (!m || m.curOi < 0) return;

    const rows = Array.from(tbody.querySelectorAll("tr"));
    const vals = rows
      .map((tr) => num(tr.children[m.curOi]?.textContent))
      .filter((v) => isFinite(v));
    if (!vals.length) return;

    const sorted = vals.slice().sort((a, b) => a - b);
    const p = (q) =>
      sorted[
        Math.max(
          0,
          Math.min(sorted.length - 1, Math.floor(q * (sorted.length - 1)))
        )
      ];
    const lo = p(0.1);
    const hi = p(0.9);
    const denom = hi - lo || 1;

    rows.forEach((tr) => {
      const td = tr.children[m.curOi];
      if (!td) return;

      const v = num(td.textContent);
      td.classList.add("oi-intensity");
      td.style.backgroundColor = "";

      if (!isFinite(v)) return;

      let t = (v - lo) / denom;
      t = Math.max(0, Math.min(1, t));

      const a = 0.03 + t * 0.15;
      td.style.backgroundColor = `rgba(59, 130, 246, ${a})`;
      td.style.borderRadius = "6px";
    });
  }

  // ===================== ATM highlight + bold only ATM ±2 =====================
  function strikeColIndex(table) {
    const headRow = document.getElementById("trackHeadRow");
    if (headRow) {
      const ths = Array.from(headRow.querySelectorAll("th"));
      for (let i = 0; i < ths.length; i++) {
        const t = norm(ths[i].textContent || "");
        if (t.startsWith("strike")) return i;
      }
    }
    const thead = table.querySelector("thead");
    if (thead) {
      let idx = -1;
      Array.from(thead.querySelectorAll("th")).forEach((th, i) => {
        const t = norm(th.textContent || "");
        if (t.startsWith("strike")) idx = idx === -1 ? i : idx;
      });
      if (idx >= 0) return idx;
    }
    return 0;
  }

  function highlightATMInTrack(atm, winN = 10) {
    const refs = getTrackRefs();
    if (!refs) return;
    const { table, tbody } = refs;
    const sIdx = strikeColIndex(table);
    const step = 50;

    Array.from(tbody.querySelectorAll("tr")).forEach((tr) => {
      tr.classList.remove("atm-row", "atm-strong");

      const cell = tr.children[sIdx] || tr.children[0];
      if (!cell) return;

      const strike = parseInt(
        String(cell.textContent || "").replace(/\D+/g, ""),
        10
      );
      const isAtm = atm != null && strike === atm;

      if (isAtm) {
        tr.classList.remove("dom-ce", "dom-pe", "dom-weak");
        tr.classList.add("atm-row");
        if (!tr.querySelector(".atm-badge")) {
          cell.insertAdjacentHTML(
            "beforeend",
            ' <span class="atm-badge">ATM</span>'
          );
        }
      }

      if (atm != null && winN > 0 && !Number.isNaN(strike)) {
        const show = Math.abs(strike - atm) <= winN * step;
        tr.style.display = show ? "" : "none";
      } else {
        tr.style.display = "";
      }

      if (atm != null && !Number.isNaN(strike)) {
        const diffSteps = Math.round((strike - atm) / step);
        if (Math.abs(diffSteps) <= 2) tr.classList.add("atm-strong");
      }
    });
  }

  function highlightATMInOtherTables(atm) {
    document.querySelectorAll("table").forEach((t) => {
      const refs = getTrackRefs();
      if (refs && t === refs.table) return;
      const tbody = t.querySelector("tbody");
      if (!tbody) return;

      const sIdx = strikeColIndex(t);
      Array.from(tbody.querySelectorAll("tr")).forEach((tr) => {
        const cell = tr.children[sIdx] || tr.children[0];
        if (!cell) return;
        const strike = parseInt(
          String(cell.textContent || "").replace(/\D+/g, ""),
          10
        );
        if (atm != null && strike === atm) tr.classList.add("atm-row");
        else tr.classList.remove("atm-row");
      });
    });
  }

  // ===================== Spike blink: 3m vs 10m =====================
  function applySpikeBlink() {
    const refs = getTrackRefs();
    if (!refs) return;
    const { tbody } = refs;
    const m = mapColumns();
    if (!m) return;

    const d3 = m.d[3];
    const d10 = m.d[10];
    if (d3 == null || d10 == null || d3 < 0 || d10 < 0) return;

    const ratio = parseFloat(
      document.getElementById("spikeRatio")?.value || "1.8"
    );
    const minAbs = parseFloat(
      document.getElementById("spikeMin")?.value || "5000"
    );

    Array.from(tbody.querySelectorAll("tr")).forEach((tr) => {
      const td3 = tr.children[d3];
      const td10 = tr.children[d10];
      if (!td3 || !td10) return;

      const v3 = Math.abs(num(td3.textContent));
      const v10 = Math.abs(num(td10.textContent));

      td3.classList.remove("spike-blink");

      if (!isFinite(v3) || !isFinite(v10)) return;
      if (v3 >= minAbs && (v10 === 0 ? true : v3 / v10 >= ratio)) {
        td3.classList.add("spike-blink");
      }
    });
  }

  // ===================== CE/PE dominance shading =====================
  function applyCePeDominance() {
    const refs = getTrackRefs();
    if (!refs) return;
    const { tbody } = refs;
    const m = mapColumns();
    if (!m || m.curOi < 0) return;

    const sIdx = m.strike >= 0 ? m.strike : 0;
    const tIdx = m.type >= 0 ? m.type : 1;

    const map = new Map();

    Array.from(tbody.querySelectorAll("tr")).forEach((tr) => {
      tr.classList.remove("dom-ce", "dom-pe", "dom-weak");

      const strike = parseInt(
        String(tr.children[sIdx]?.textContent || "").replace(/\D+/g, ""),
        10
      );
      const typ = String(tr.children[tIdx]?.textContent || "")
        .trim()
        .toUpperCase();
      const cur = num(tr.children[m.curOi]?.textContent);

      if (!isFinite(strike) || (typ !== "CE" && typ !== "PE")) return;

      if (!map.has(strike)) map.set(strike, {});
      map.get(strike)[typ] = { oi: cur, row: tr };
    });

    map.forEach((v) => {
      const ce = v.CE,
        pe = v.PE;
      if (!ce || !pe || !isFinite(ce.oi) || !isFinite(pe.oi)) return;

      const total = Math.max(1, ce.oi + pe.oi);
      const shareCE = ce.oi / total;

      if (shareCE > 0.55) {
        ce.row.classList.add("dom-ce");
        pe.row.classList.add("dom-weak");
      } else if (shareCE < 0.45) {
        pe.row.classList.add("dom-pe");
        ce.row.classList.add("dom-weak");
      } else {
        ce.row.classList.add("dom-weak");
        pe.row.classList.add("dom-weak");
      }
    });
  }

  // ===================== ATM Summary Card =====================
  function pickDeltaWindowForSummary(m) {
    const pref = [3, 5, 10, 15, 30, 2, 1];
    for (const min of pref) {
      const cb = document.getElementById("col" + min);
      if (cb && cb.checked === false) continue;
      if (m && m.oi && m.d && m.oi[min] != null && m.d[min] != null) return min;
    }
    const any = Object.keys(m?.oi || {})
      .map((x) => parseInt(x, 10))
      .filter((n) => !isNaN(n))
      .sort((a, b) => a - b)[0];
    return any || 3;
  }

  function ensureAtmSummaryCard() {
    if (document.getElementById("oiAtmSummaryCard")) return;
    const refs = getTrackRefs();
    if (!refs) return;

    const card = document.createElement("div");
    card.id = "oiAtmSummaryCard";
    card.innerHTML = `
        <div id="atmHeaderRow">
          <div class="title">ATM Summary</div>
          <div class="muted atm-meta-pill" id="oiAtmSummaryMeta">—</div>

          <div class="hdrRight" id="atmBarsRow">
            <div class="barRow mini" id="atmCeRow">
              <span class="pill ce">CE</span>
              <span class="metric">Cur <span class="big" id="atmCeCur">-</span></span>
              <div class="bar"><div class="fill ce" id="atmCeCurBar" style="width:0%"></div></div>
              <span class="metric">Δ(<span id="atmWinLbl">-</span>) <span class="big" id="atmCeD">-</span></span>
              <div class="bar"><div class="fill ce" id="atmCeDBar" style="width:0%"></div></div>
            </div>

            <div class="barRow mini" id="atmPeRow">
              <span class="pill pe">PE</span>
              <span class="metric">Cur <span class="big" id="atmPeCur">-</span></span>
              <div class="bar"><div class="fill pe" id="atmPeCurBar" style="width:0%"></div></div>
              <span class="metric">Δ(<span id="atmWinLbl2">-</span>) <span class="big" id="atmPeD">-</span></span>
              <div class="bar"><div class="fill pe" id="atmPeDBar" style="width:0%"></div></div>
            </div>
          </div>
        </div>

        <div id="atmBoxRow">
          <div class="atmBox" id="boxTopCur"></div>
          <div class="atmBox" id="boxTopDelta"></div>
          <div class="atmBox" id="boxAtmSnap"></div>
          <div class="atmBox" id="boxLeaders"></div>
          <div class="atmBox" id="boxQuickRead"></div>
          <div class="atmBox" id="boxSignals"></div>
        </div>

        <div id="atmInfoRow">
          <div id="atmDominanceText">—</div>
          <div id="atmRegime">—</div>
        </div>

        <div id="atmMiniStrip"></div>
      `;

    const toolbar = document.getElementById("oiTrackToolbar");
    if (toolbar && toolbar.parentElement)
      toolbar.parentElement.insertBefore(card, toolbar);
    else refs.table.parentElement.insertBefore(card, refs.table);

    // click-to-copy strike values from pillMini
    card.addEventListener("click", (e) => {
      const t = e.target;
      if (!t) return;
      const pill = t.closest && t.closest(".pillMini");
      if (!pill) return;
      const txt = (pill.textContent || "").trim();
      const mm = txt.match(/\b\d{4,6}\b/);
      if (!mm) return;
      const val = mm[0];

      try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(val);
          return;
        }
      } catch (err) {}

      try {
        const ta = document.createElement("textarea");
        ta.value = val;
        ta.style.position = "fixed";
        ta.style.left = "-9999px";
        ta.style.top = "0";
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        document.execCommand("copy");
        document.body.removeChild(ta);
      } catch (err) {}
    });
  }

  function updateAtmSummaryCard() {
    const card = document.getElementById("oiAtmSummaryCard");
    if (!card) return;

    const refs = getTrackRefs();
    if (!refs) return;

    const m = mapColumns();
    if (!m || m.curOi < 0) return;

    const atm = getATM();
    const win = pickDeltaWindowForSummary(m);

    const sym =
      window.lastJson && window.lastJson.symbol
        ? String(window.lastJson.symbol)
        : "NIFTY";
    const ks = document.getElementById("ksPrice");
    const spot = ks
      ? parseFloat(String(ks.textContent || "").replace(/,/g, ""))
      : NaN;

    const meta = document.getElementById("oiAtmSummaryMeta");
    if (meta) {
      meta.textContent = `${sym}${
        isFinite(spot)
          ? ` @ ${spot.toLocaleString("en-IN", {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            })}`
          : ""
      }${atm != null ? ` | ATM ${atm}` : ""}`;
      if (meta) meta.classList.add("atm-meta-pill");
    }

    const w1 = document.getElementById("atmWinLbl");
    const w2 = document.getElementById("atmWinLbl2");
    if (w1) w1.textContent = `${win}m`;
    if (w2) w2.textContent = `${win}m`;

    // Find ATM CE/PE rows (respect filter if your UI has one)
    const optFilter =
      typeof UI !== "undefined" && UI && UI.optFilter
        ? String(UI.optFilter)
        : "both";

    let ce = null,
      pe = null;

    Array.from(refs.tbody.querySelectorAll("tr")).forEach((tr) => {
      if (tr.style && tr.style.display === "none") return;

      const rowType = String(tr.children[m.type]?.textContent || "")
        .trim()
        .toUpperCase();
      if (optFilter === "calls" && rowType !== "CE") return;
      if (optFilter === "puts" && rowType !== "PE") return;

      const strike = parseInt(
        String(tr.children[m.strike]?.textContent || "").replace(/\D+/g, ""),
        10
      );
      if (atm != null && strike !== atm) return;

      const typ = rowType;
      if (typ !== "CE" && typ !== "PE") return;

      const cur = num(tr.children[m.curOi]?.textContent);
      const start = num(tr.children[m.oi[win]]?.textContent);
      const d = isFinite(cur) && isFinite(start) ? cur - start : NaN;

      if (typ === "CE") ce = { cur, d };
      else pe = { cur, d };
    });

    const ceCur = ce && isFinite(ce.cur) ? ce.cur : NaN;
    const peCur = pe && isFinite(pe.cur) ? pe.cur : NaN;
    const ceD = ce && isFinite(ce.d) ? ce.d : NaN;
    const peD = pe && isFinite(pe.d) ? pe.d : NaN;

    const ceCurEl = document.getElementById("atmCeCur");
    const peCurEl = document.getElementById("atmPeCur");
    const ceDEl = document.getElementById("atmCeD");
    const peDEl = document.getElementById("atmPeD");

    if (ceCurEl) ceCurEl.textContent = isFinite(ceCur) ? fmt(ceCur) : "-";
    if (peCurEl) peCurEl.textContent = isFinite(peCur) ? fmt(peCur) : "-";

    const deltaFmt = (v) =>
      !isFinite(v) ? "-" : `${v >= 0 ? "▲" : "▼"} ${fmt(Math.abs(v))}`;
    if (ceDEl) ceDEl.textContent = deltaFmt(ceD);
    if (peDEl) peDEl.textContent = deltaFmt(peD);

    const curMax = Math.max(ceCur || 0, peCur || 0, 1);
    const dMax = Math.max(Math.abs(ceD || 0), Math.abs(peD || 0), 1);

    const setBar = (id, pct) => {
      const el = document.getElementById(id);
      if (el) el.style.width = `${Math.max(0, Math.min(100, pct))}%`;
    };
    setBar("atmCeCurBar", isFinite(ceCur) ? (ceCur / curMax) * 100 : 0);
    setBar("atmPeCurBar", isFinite(peCur) ? (peCur / curMax) * 100 : 0);
    setBar("atmCeDBar", isFinite(ceD) ? (Math.abs(ceD) / dMax) * 100 : 0);
    setBar("atmPeDBar", isFinite(peD) ? (Math.abs(peD) / dMax) * 100 : 0);

    // leaders
    let oiLeader = "-";
    if (isFinite(ceCur) && isFinite(peCur))
      oiLeader = ceCur > peCur ? "CE" : peCur > ceCur ? "PE" : "Tie";

    let dLeader = "-";
    if (isFinite(ceD) && isFinite(peD))
      dLeader =
        Math.abs(ceD) > Math.abs(peD)
          ? "CE"
          : Math.abs(peD) > Math.abs(ceD)
          ? "PE"
          : "Tie";

    // Dominance line (exact format you asked)
    const domEl = document.getElementById("atmDominanceText");
    if (domEl) {
      const totOi =
        (isFinite(ceCur) ? ceCur : 0) + (isFinite(peCur) ? peCur : 0);
      const ceOiP =
        totOi > 0 ? ((isFinite(ceCur) ? ceCur : 0) / totOi) * 100 : NaN;
      const peOiP =
        totOi > 0 ? ((isFinite(peCur) ? peCur : 0) / totOi) * 100 : NaN;

      const ceAbsD = isFinite(ceD) ? Math.abs(ceD) : 0;
      const peAbsD = isFinite(peD) ? Math.abs(peD) : 0;
      const totD = ceAbsD + peAbsD;
      const ceDP = totD > 0 ? (ceAbsD / totD) * 100 : NaN;
      const peDP = totD > 0 ? (peAbsD / totD) * 100 : NaN;

      const fmtP = (x) => (isFinite(x) ? `${x.toFixed(0)}%` : "-");
      domEl.textContent = `Dominance → OI: CE ${fmtP(ceOiP)} / PE ${fmtP(
        peOiP
      )} | |Δ|: CE ${fmtP(ceDP)} / PE ${fmtP(peDP)}`;
    }

    // Build rows list (visible window + filter) for boxes
    const rows = [];
    Array.from(refs.tbody.querySelectorAll("tr")).forEach((tr) => {
      if (tr.style && tr.style.display === "none") return;

      const rowType = String(tr.children[m.type]?.textContent || "")
        .trim()
        .toUpperCase();
      if (optFilter === "calls" && rowType !== "CE") return;
      if (optFilter === "puts" && rowType !== "PE") return;

      const strike = parseInt(
        String(tr.children[m.strike]?.textContent || "").replace(/\D+/g, ""),
        10
      );
      if (!isFinite(strike)) return;

      const cur = num(tr.children[m.curOi]?.textContent);
      const start = num(tr.children[m.oi[win]]?.textContent);
      const delta = isFinite(cur) && isFinite(start) ? cur - start : NaN;

      rows.push({ strike, rowType, cur, delta });
    });

    // helpers to render boxes
    const box = (id, title, winLbl) => {
      const el = document.getElementById(id);
      if (!el) return null;
      el.innerHTML = `<div class="h"><span>${title}</span>${
        winLbl ? `<span class="w">${winLbl}</span>` : ""
      }</div>`;
      return el;
    };
    const addItem = (el, lhsHtml, rhsHtml) => {
      if (!el) return;
      const d = document.createElement("div");
      d.className = "item";
      d.innerHTML = `<div class="lhs">${lhsHtml}</div><div class="num">${rhsHtml}</div>`;
      el.appendChild(d);
    };
    const addStrikeItem = (el, r, kind) => {
      const typCls = r.rowType === "CE" ? "ce" : "pe";
      const val = kind === "cur" ? r.cur : r.delta;
      const isUp = kind === "cur" ? true : val >= 0;
      const numCls = kind === "cur" ? "" : isUp ? "up" : "dn";
      const valTxt =
        kind === "cur" ? fmt(val) : `${isUp ? "▲" : "▼"} ${fmt(Math.abs(val))}`;
      const d = document.createElement("div");
      d.className = "item";
      d.innerHTML = `
        <div class="lhs">
          <span class="tag ${typCls}">${r.rowType}</span>
          <span class="num">${r.strike}</span>
        </div>
        <div class="num ${numCls}">${valTxt}</div>
      `;
      el.appendChild(d);
    };

    // Box 1: Top Current OI
    const topByCur = rows
      .filter((r) => isFinite(r.cur))
      .sort((a, b) => b.cur - a.cur)
      .slice(0, 3);
    const bCur = box("boxTopCur", "Top Current OI", "");
    topByCur.forEach((r) => addStrikeItem(bCur, r, "cur"));

    // Box 2: Top ΔOI
    const topByDelta = rows
      .filter((r) => isFinite(r.delta))
      .sort((a, b) => Math.abs(b.delta) - Math.abs(a.delta))
      .slice(0, 3);
    const bD = box("boxTopDelta", "Top ΔOI", `Δ(${win}m)`);
    topByDelta.forEach((r) => addStrikeItem(bD, r, "delta"));

    // Box 3: ATM Snapshot (CE/PE Cur | Δ)
    const bA = box("boxAtmSnap", "ATM Snapshot", `ATM ${atm ?? "-"}`);
    const ceLine =
      (isFinite(ceCur) ? fmt(ceCur) : "-") +
      (isFinite(ceD)
        ? `  |  ${ceD >= 0 ? "▲" : "▼"} ${fmt(Math.abs(ceD))}`
        : "");
    const peLine =
      (isFinite(peCur) ? fmt(peCur) : "-") +
      (isFinite(peD)
        ? `  |  ${peD >= 0 ? "▲" : "▼"} ${fmt(Math.abs(peD))}`
        : "");
    addItem(
      bA,
      `<span class="tag ce">CE</span> Cur | Δ`,
      `<span class="${
        isFinite(ceD) ? (ceD >= 0 ? "up" : "dn") : ""
      }">${ceLine}</span>`
    );
    addItem(
      bA,
      `<span class="tag pe">PE</span> Cur | Δ`,
      `<span class="${
        isFinite(peD) ? (peD >= 0 ? "up" : "dn") : ""
      }">${peLine}</span>`
    );

    // Box 4: Leaders & Dominance
    const bL = box("boxLeaders", "Leaders & Dominance", `Δ(${win}m)`);
    addItem(bL, "Leaders", `OI ${oiLeader} | Δ ${dLeader}`);

    const totOi2 =
      (isFinite(ceCur) ? ceCur : 0) + (isFinite(peCur) ? peCur : 0);
    const ceOiP2 =
      totOi2 > 0 ? ((isFinite(ceCur) ? ceCur : 0) / totOi2) * 100 : NaN;
    const peOiP2 =
      totOi2 > 0 ? ((isFinite(peCur) ? peCur : 0) / totOi2) * 100 : NaN;

    const ceAbsD2 = isFinite(ceD) ? Math.abs(ceD) : 0;
    const peAbsD2 = isFinite(peD) ? Math.abs(peD) : 0;
    const totD2 = ceAbsD2 + peAbsD2;
    const ceDP2 = totD2 > 0 ? (ceAbsD2 / totD2) * 100 : NaN;
    const peDP2 = totD2 > 0 ? (peAbsD2 / totD2) * 100 : NaN;

    const fmtP2 = (x) => (isFinite(x) ? `${x.toFixed(0)}%` : "-");
    addItem(bL, "Dom OI", `CE ${fmtP2(ceOiP2)} / PE ${fmtP2(peOiP2)}`);
    addItem(bL, "Dom |Δ|", `CE ${fmtP2(ceDP2)} / PE ${fmtP2(peDP2)}`);

    // Compute Quick Read + Signals values (same content you had, just boxed)
    const byType = (t) =>
      rows.filter((r) => r.rowType === t && isFinite(r.cur));
    const byTypeD = (t) =>
      rows.filter((r) => r.rowType === t && isFinite(r.delta));
    const maxCur = (arr) => arr.sort((a, b) => b.cur - a.cur)[0];
    const maxAbsD = (arr) =>
      arr.sort((a, b) => Math.abs(b.delta) - Math.abs(a.delta))[0];

    const bestPut = maxCur(byType("PE").slice());
    const bestCall = maxCur(byType("CE").slice());
    const hotPut = maxAbsD(byTypeD("PE").slice());
    const hotCall = maxAbsD(byTypeD("CE").slice());
    const hotOverall = [hotPut, hotCall]
      .filter(Boolean)
      .sort((a, b) => Math.abs(b?.delta || 0) - Math.abs(a?.delta || 0))[0];

    const fmtStrike = (s) => (!isFinite(s) ? "-" : String(Math.round(s)));
    const hotArrow = (d) =>
      d == null || !isFinite(d) ? "" : d >= 0 ? "▲" : "▼";

    // spot change since refresh
    const spotKey = `oi_spot_prev_${sym}`;
    let prevSpot = NaN;
    try {
      prevSpot = parseFloat(String(localStorage.getItem(spotKey) || ""));
    } catch (e) {}
    const spotChg =
      isFinite(spot) && isFinite(prevSpot) ? spot - prevSpot : NaN;
    if (isFinite(spot)) {
      try {
        localStorage.setItem(spotKey, String(spot));
      } catch (e) {}
    }
    const spotLabel = isFinite(spotChg)
      ? `${spotChg >= 0 ? "+" : ""}${spotChg.toFixed(1)} pts`
      : "-";
    const dir = isFinite(spotChg)
      ? spotChg > 5
        ? "up"
        : spotChg < -5
        ? "down"
        : "flat"
      : "na";

    // MaxPain from visible strikes
    const byStrike = new Map(); // strike -> {ce, pe}
    rows.forEach((r) => {
      const key = r.strike;
      if (!isFinite(key)) return;
      const curv = isFinite(r.cur) ? r.cur : 0;
      const rec = byStrike.get(key) || { ce: 0, pe: 0 };
      if (r.rowType === "CE") rec.ce = curv;
      if (r.rowType === "PE") rec.pe = curv;
      byStrike.set(key, rec);
    });
    const strikesArr = Array.from(byStrike.keys()).sort((a, b) => a - b);
    const payoutAt = (K) => {
      let sum = 0;
      for (const s of strikesArr) {
        const rec = byStrike.get(s);
        sum += Math.max(0, K - s) * (rec?.ce || 0);
        sum += Math.max(0, s - K) * (rec?.pe || 0);
      }
      return sum;
    };
    let maxPain = NaN,
      maxPainCost = Infinity;
    for (const K of strikesArr) {
      const cost = payoutAt(K);
      if (cost < maxPainCost) {
        maxPainCost = cost;
        maxPain = K;
      }
    }
    const maxPainDist =
      isFinite(spot) && isFinite(maxPain) ? spot - maxPain : NaN;

    let pinHint = "—";
    if (isFinite(maxPainDist)) {
      const near = Math.abs(maxPainDist) <= 50;
      pinHint = near
        ? "High (near Max Pain)"
        : Math.abs(maxPainDist) <= 100
        ? "Medium"
        : "Low";
    }

    // Breakout hint (simple)
    const support = bestPut ? bestPut.strike : NaN;
    const resistance = bestCall ? bestCall.strike : NaN;
    const distSup = isFinite(spot) && isFinite(support) ? spot - support : NaN;
    const distRes =
      isFinite(spot) && isFinite(resistance) ? resistance - spot : NaN;

    let breakout = "—",
      bCls = "rng";
    const nearPts = 25;
    if (isFinite(distRes) && distRes >= 0 && distRes <= nearPts) {
      breakout =
        dLeader === "CE"
          ? "At resistance: rejection risk"
          : "At resistance: breakout watch";
      bCls = dLeader === "CE" ? "warn" : "up";
    } else if (isFinite(distSup) && distSup >= 0 && distSup <= nearPts) {
      breakout =
        dLeader === "PE"
          ? "At support: bounce risk"
          : "At support: breakdown watch";
      bCls = dLeader === "PE" ? "warn" : "dn";
    } else {
      breakout = "Not at key level";
      bCls = "rng";
    }

    let interp = "Spot data not ready";
    if (dir === "up") {
      interp =
        dLeader === "CE"
          ? "Spot ↑ + CE flow → bullish breakout attempt"
          : dLeader === "PE"
          ? "Spot ↑ + PE flow → support build / put writing"
          : "Spot ↑ → watch which side builds";
    } else if (dir === "down") {
      interp =
        dLeader === "PE"
          ? "Spot ↓ + PE flow → bearish breakdown attempt"
          : dLeader === "CE"
          ? "Spot ↓ + CE flow → resistance build / call writing"
          : "Spot ↓ → watch which side builds";
    } else if (dir === "flat") {
      interp =
        dLeader === "CE"
          ? "Spot flat + CE flow → resistance forming"
          : dLeader === "PE"
          ? "Spot flat + PE flow → support forming"
          : "Spot flat → low conviction";
    }

    // Regime line (exact format you referenced)
    let regime = "—",
      rCls = "rng";
    if (dir === "up") {
      regime =
        dLeader === "CE"
          ? "Trending Up (CE-led)"
          : dLeader === "PE"
          ? "Up + Put-writing"
          : "Up (unclear)";
      rCls = "up";
    } else if (dir === "down") {
      regime =
        dLeader === "PE"
          ? "Trending Down (PE-led)"
          : dLeader === "CE"
          ? "Down + Call-writing"
          : "Down (unclear)";
      rCls = "dn";
    } else if (dir === "flat") {
      regime =
        dLeader === "PE"
          ? "Ranging / support forming"
          : dLeader === "CE"
          ? "Ranging / resistance forming"
          : "Ranging / low flow";
      rCls = "rng";
    }

    const regEl = document.getElementById("atmRegime");
    if (regEl) {
      regEl.innerHTML = `
        <span class="pillLine ${rCls}">Regime: ${regime}</span>
        <span class="pillLine ${bCls}">Alert: ${breakout}</span>
        <span class="pillLine rng">MaxPain: ${
          isFinite(maxPain) ? Math.round(maxPain) : "-"
        } | Pin: ${pinHint}</span>
      `;
    }

    // Box 5: Quick Read
    const bQR = box("boxQuickRead", "Quick Read", "");
    if (bQR) {
      const sideCls = (t) => (t === "CE" ? "ce" : t === "PE" ? "pe" : "");
      bQR.insertAdjacentHTML(
        "beforeend",
        `
        <div class="kvGrid">
          <div class="k">Support (max Put OI)</div><div class="v pe"><span class="pillMini">${
            bestPut ? fmtStrike(bestPut.strike) : "-"
          }</span></div>
          <div class="k">Resistance (max Call OI)</div><div class="v ce"><span class="pillMini">${
            bestCall ? fmtStrike(bestCall.strike) : "-"
          }</span></div>
          <div class="k">Hot Move (|Δ| max)</div><div class="v ${sideCls(
            hotOverall?.rowType
          )}"><span class="pillMini">${
          hotOverall
            ? `${hotOverall.rowType} ${fmtStrike(hotOverall.strike)} ${hotArrow(
                hotOverall.delta
              )} ${fmt(Math.abs(hotOverall.delta))}`
            : "-"
        }</span></div>
          <div class="k">Max Pain</div><div class="v"><span class="pillMini">${
            isFinite(maxPain) ? Math.round(maxPain) : "-"
          } ${
          isFinite(maxPainDist)
            ? `(${maxPainDist >= 0 ? "+" : ""}${maxPainDist.toFixed(0)} pts)`
            : ""
        }</span></div>
        </div>
      `
      );
    }

    // Box 6: Signals
    const bS = box("boxSignals", "Signals", "");
    if (bS) {
      bS.insertAdjacentHTML(
        "beforeend",
        `
        <div class="kvGrid">
          <div class="k">Pin Pressure</div><div class="v"><span class="pillMini">${pinHint}</span></div>
          <div class="k">Breakout Alert</div><div class="v"><span class="pillMini">${breakout}</span></div>
          <div class="k">Spot Δ (since refresh)</div><div class="v"><span class="pillMini">${spotLabel}</span></div>
          <div class="k">Interpretation</div><div class="v"><span class="pillMini">${interp}</span></div>
          <div class="k">Bias</div><div class="v"><span class="pillMini">${
            oiLeader === "PE"
              ? "Put-heavy"
              : oiLeader === "CE"
              ? "Call-heavy"
              : "Balanced"
          } / ${
          dLeader === "PE"
            ? "PE active"
            : dLeader === "CE"
            ? "CE active"
            : "Flat"
        }</span></div>
        </div>
      `
      );
    }

    // Mini strip: keep EXACT legend + ATM-100/ATM-50/... lines below
    const strip = document.getElementById("atmMiniStrip");
    if (strip) {
      strip.innerHTML = `<span class="legendTxt">Legend: Pos = higher Current OI (C=CE, P=PE), Move = higher |Δ| in window. ✅=Pos&Move agree (wall). ⚠️=conflict (flow vs position).</span>`;

      const step = 50;
      const ksList = [-2, -1, 0, 1, 2]
        .map((k) => (atm != null ? atm + k * step : null))
        .filter((x) => x != null);

      const getRowStats = (strikeVal) => {
        let ce2 = null,
          pe2 = null;
        Array.from(refs.tbody.querySelectorAll("tr")).forEach((tr) => {
          if (tr.style && tr.style.display === "none") return;
          const strike = parseInt(
            String(tr.children[m.strike]?.textContent || "").replace(
              /\D+/g,
              ""
            ),
            10
          );
          if (strike !== strikeVal) return;
          const typ = String(tr.children[m.type]?.textContent || "")
            .trim()
            .toUpperCase();
          if (typ !== "CE" && typ !== "PE") return;
          const cur = num(tr.children[m.curOi]?.textContent);
          const start = num(tr.children[m.oi[win]]?.textContent);
          const d = isFinite(cur) && isFinite(start) ? cur - start : NaN;
          if (typ === "CE") ce2 = { cur, d };
          else pe2 = { cur, d };
        });
        return { ce2, pe2 };
      };

      ksList.forEach((kStrike) => {
        const { ce2, pe2 } = getRowStats(kStrike);
        const ceC = ce2 && isFinite(ce2.cur) ? ce2.cur : NaN;
        const peC = pe2 && isFinite(pe2.cur) ? pe2.cur : NaN;
        const ceDd = ce2 && isFinite(ce2.d) ? ce2.d : NaN;
        const peDd = pe2 && isFinite(pe2.d) ? pe2.d : NaN;

        let oiL = "-";
        if (isFinite(ceC) && isFinite(peC))
          oiL = ceC > peC ? "CE" : peC > ceC ? "PE" : "Tie";

        let dL = "-";
        if (isFinite(ceDd) && isFinite(peDd))
          dL =
            Math.abs(ceDd) > Math.abs(peDd)
              ? "CE"
              : Math.abs(peDd) > Math.abs(ceDd)
              ? "PE"
              : "Tie";

        const oiCls = oiL === "CE" ? "ce" : oiL === "PE" ? "pe" : "";
        const dCls = dL === "CE" ? "ce" : dL === "PE" ? "pe" : "";

        const diff = atm != null ? kStrike - atm : null;
        const lab =
          diff == null
            ? String(kStrike)
            : diff === 0
            ? "ATM"
            : diff > 0
            ? `ATM+${diff}`
            : `ATM${diff}`;

        const posSide = oiL === "CE" ? "C" : oiL === "PE" ? "P" : "-";
        const movSide = dL === "CE" ? "C" : dL === "PE" ? "P" : "-";

        let badge = "•",
          bCls = "";
        if (posSide !== "-" && movSide !== "-" && posSide === movSide) {
          badge = "✅";
          bCls = posSide === "C" ? "ce" : "pe";
        } else if (posSide !== "-" && movSide !== "-" && posSide !== movSide) {
          badge = "⚠️";
          bCls = "warn";
        }

        const div = document.createElement("div");
        div.className = "mini";
        div.innerHTML = `<span class="${bCls}" style="font-weight:900; margin-right:6px" title="${
          badge === "✅"
            ? "Pos & Move agree (wall)"
            : "Conflict: position vs flow"
        }">${badge}</span>
          <span class="s">• ${lab} (${kStrike})</span>
          <span class="k">Pos:</span> <span class="v ${oiCls}">${posSide}</span>
          <span class="k" style="margin-left:6px">Move:</span> <span class="v ${dCls}">${movSide}</span>`;
        strip.appendChild(div);
      });
    }
  }

  // ===================== MAX highlights =====================
  /*
  function applyMaxHighlights() {
    const refs = getTrackRefs();
    if (!refs) return;

    const m = mapColumns();
    if (!m || m.curOi < 0) return;

    const win = pickDeltaWindowForSummary(m);
    const curIdx = m.curOi;
    const startIdx = m.oi[win];
    const dIdx = m.d[win];

    refs.tbody
      .querySelectorAll(".max-oi-cell, .max-delta-cell")
      .forEach((td) => {
        td.classList.remove("max-oi-cell", "max-delta-cell");
        const tag = td.querySelector(".max-tag");
        if (tag) tag.remove();
      });

    let bestCurV = -Infinity,
      bestCurTd = null;
    let bestDV = -Infinity,
      bestDTd = null;

    Array.from(refs.tbody.querySelectorAll("tr")).forEach((tr) => {
      if (tr.style && tr.style.display === "none") return;

      const curTd = tr.children[curIdx];
      if (curTd) {
        const v = num(curTd.textContent);
        if (isFinite(v) && v > bestCurV) {
          bestCurV = v;
          bestCurTd = curTd;
        }
      }

      const cur = num(tr.children[curIdx]?.textContent);
      const start = num(tr.children[startIdx]?.textContent);
      const delta = isFinite(cur) && isFinite(start) ? cur - start : NaN;

      const dTd = tr.children[dIdx];
      if (dTd && isFinite(delta)) {
        const mag = Math.abs(delta);
        if (mag > bestDV) {
          bestDV = mag;
          bestDTd = dTd;
        }
      }
    });

    if (bestCurTd) {
      bestCurTd.classList.add("max-oi-cell");
      bestCurTd.insertAdjacentHTML(
        "beforeend",
        `<span class="max-tag">MAX OI</span>`
      );
    }

    if (bestDTd) {
      bestDTd.classList.add("max-delta-cell");
      bestDTd.insertAdjacentHTML(
        "beforeend",
        `<span class="max-tag">MAX Δ(${win}m)</span>`
      );
    }
  }
*/
  // ===================== MAX highlights =====================
  function applyMaxHighlights() {
    const refs = getTrackRefs();
    if (!refs) return;

    const m = mapColumns();
    if (!m || m.curOi < 0) return;

    const win = pickDeltaWindowForSummary(m);
    const curIdx = m.curOi;
    const startIdx = m.oi[win];
    const dIdx = m.d[win];

    // ✅ clear old MAX tags / classes (add new CE/PE delta classes too)
    refs.tbody
      .querySelectorAll(
        ".max-oi-cell, .max-delta-cell, .max-delta-ce, .max-delta-pe"
      )
      .forEach((td) => {
        td.classList.remove(
          "max-oi-cell",
          "max-delta-cell",
          "max-delta-ce",
          "max-delta-pe"
        );
        const tag = td.querySelector(".max-tag");
        if (tag) tag.remove();
      });

    let bestCurV = -Infinity,
      bestCurTd = null;

    // ✅ Option B: best Δ separately for CE and PE
    let bestCeMag = -Infinity,
      bestCeTd = null;
    let bestPeMag = -Infinity,
      bestPeTd = null;

    Array.from(refs.tbody.querySelectorAll("tr")).forEach((tr) => {
      if (tr.style && tr.style.display === "none") return;

      // ---- MAX Current OI (as-is) ----
      const curTd = tr.children[curIdx];
      if (curTd) {
        const v = num(curTd.textContent);
        if (isFinite(v) && v > bestCurV) {
          bestCurV = v;
          bestCurTd = curTd;
        }
      }

      // ---- MAX Δ per type (CE/PE) ----
      const typ = String(tr.children[m.type]?.textContent || "")
        .trim()
        .toUpperCase();
      if (typ !== "CE" && typ !== "PE") return;

      const cur = num(tr.children[curIdx]?.textContent);
      const start = num(tr.children[startIdx]?.textContent);
      const delta = isFinite(cur) && isFinite(start) ? cur - start : NaN;
      if (!isFinite(delta)) return;

      const td = tr.children[dIdx];
      if (!td) return;

      const mag = Math.abs(delta);
      if (typ === "CE" && mag > bestCeMag) {
        bestCeMag = mag;
        bestCeTd = td;
      }
      if (typ === "PE" && mag > bestPeMag) {
        bestPeMag = mag;
        bestPeTd = td;
      }
    });

    // ---- apply MAX Current OI tag (as-is) ----
    if (bestCurTd) {
      bestCurTd.classList.add("max-oi-cell");
      bestCurTd.insertAdjacentHTML(
        "beforeend",
        `<span class="max-tag">MAX OI</span>`
      );
    }

    // ---- apply MAX Δ tags for CE/PE ----
    if (bestCeTd) {
      bestCeTd.classList.add("max-delta-ce");
      bestCeTd.insertAdjacentHTML(
        "beforeend",
        `<span class="max-tag">MAX Δ CE (${win}m)</span>`
      );
    }
    if (bestPeTd) {
      bestPeTd.classList.add("max-delta-pe");
      bestPeTd.insertAdjacentHTML(
        "beforeend",
        `<span class="max-tag">MAX Δ PE (${win}m)</span>`
      );
    }
  }

  // ===================== Main refresh =====================
  function enhanceAll() {
    injectCssOnce();
    ensureToolbar();
    ensureLegend();

    ensureAtmSummaryCard();
    updateAtmSummaryCard();

    const refs = getTrackRefs();
    if (!refs) return;

    toggleTimeframeColumns();
    addDeltaArrowsAndStyles();

    applyOiIntensity();
    applyCePeDominance();

    const atm = getATM();
    const winN = parseInt(
      document.getElementById("strikeWinTrack")?.value || "10",
      10
    );
    highlightATMInTrack(atm, winN);
    highlightATMInOtherTables(atm);

    applyMaxHighlights();
    applySpikeBlink();
  }

  window.enhanceAll = enhanceAll;

  window._oiEnhancerDebug = function () {
    const m = mapColumns();
    console.log("Labels:", m?._labels);
    console.log("Map:", m);
    return { labels: m?._labels, map: m };
  };

  setInterval(enhanceAll, 1500);

  if (
    document.readyState === "complete" ||
    document.readyState === "interactive"
  ) {
    setTimeout(enhanceAll, 250);
  } else {
    window.addEventListener("DOMContentLoaded", () =>
      setTimeout(enhanceAll, 250)
    );
  }
})();
