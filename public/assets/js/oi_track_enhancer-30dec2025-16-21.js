/* OI Dashboard Enhancer (ULTRA - FINAL NEAT)
 * - Robust column mapping using #trackHeadRow (preferred) + THEAD grid fallback
 * - Timeframe toggles hide BOTH header + body (no shifting)
 * - ΔOI(T) arrows + %: Current OI − Start OI(T) for 1/2/3/5/10/15/30 (whatever exists)
 * - Bigger OI values; calmer ΔOI
 * - Color-scale Current OI by intensity (auto-normalized every refresh)
 * - Fade ΔOI when flat (near-zero change)
 * - Bold only ATM ±2 strikes (OI Track)
 * - Blink on sudden OI spike (3m vs 10m)
 * - CE/PE dominance shading per strike (based on Current OI)
 *
 * + FINAL NEAT LAYOUT:
 *   - Replaces big right-side Quick Read with TWO LEFT-STYLE boxes (Quick Read + Signals)
 *   - Keeps your 4 left boxes (Top Cur OI, Top ΔOI, ATM Snapshot, Leaders)
 *   - Total 6 boxes in one neat row, scrollable if screen is narrow
 *   - Regime/Alert/MaxPain pills remain clean & right-aligned
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
        padding:9px 10px;
        border:1px solid #2a4a7a;
        border-radius:12px;
        background:rgba(15,46,95,.35);
        color:#cbd5e1;
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        align-items:center;
      }
      #oiAtmSummaryCard .title{ font-weight:700; }
      #oiAtmSummaryCard .muted{ opacity:.85; font-size:12px; }
      #oiAtmSummaryCard .row{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
      #oiAtmSummaryCard .pill{
        padding:3px 8px; border-radius:999px; font-size:12px; font-weight:700;
        border:1px solid #2a4a7a; background:#0f2e5f; color:#cbd5e1;
      }
      #oiAtmSummaryCard .pill.ce{ border-color: rgba(22,163,74,.45); }
      #oiAtmSummaryCard .pill.pe{ border-color: rgba(239,68,68,.45); }
      #oiAtmSummaryCard .metric{ font-size:12px; opacity:.9; }
      #oiAtmSummaryCard .big{ font-size:15px; font-weight:800; margin-left:6px; }
      #oiAtmSummaryCard .bar{
        width:120px; height:10px; border-radius:8px; overflow:hidden;
        border:1px solid rgba(148,163,184,.25); background:rgba(148,163,184,.08);
      }
      #oiAtmSummaryCard .fill{ height:100%; }
      #oiAtmSummaryCard .fill.ce{ background:rgba(22,163,74,.55); }
      #oiAtmSummaryCard .fill.pe{ background:rgba(239,68,68,.55); }
      #oiAtmSummaryCard .leader{ font-size:12px; opacity:.95; }

      /* Boxes (same style as your left) */
      #atmTopMovers .box{
        border:1px solid rgba(148,163,184,.20);
        background:rgba(2,6,23,.25);
        border-radius:12px;
        padding:7px 9px;
        min-height:88px;
        height:100%;
      }
      #atmTopMovers .box .h{
        font-weight:900; font-size:11px; opacity:.95;
        margin-bottom:5px;
        display:flex; justify-content:space-between; align-items:center;
      }
      #atmTopMovers .box .h .w{ font-weight:700; font-size:11px; opacity:.85; }
      #atmTopMovers .item{
        display:flex; justify-content:space-between; gap:8px;
        font-size:10px; padding:1px 0;
        border-top:1px dashed rgba(148,163,184,.14);
        align-items:flex-start;
      }
      #atmTopMovers .item:first-of-type{ border-top:none; }
      #atmTopMovers .lhs{ display:flex; gap:8px; align-items:center; max-width:38%; }
      #atmTopMovers .rhs{
        text-align:right; margin-left:auto; max-width:62%;
        white-space:normal; line-height:1.25;
      }
      #atmTopMovers .tag{
        font-size:10px; font-weight:900; padding:1px 6px; border-radius:999px;
        border:1px solid rgba(148,163,184,.25); background:rgba(15,46,95,.55);
      }
      #atmTopMovers .tag.ce{ border-color: rgba(22,163,74,.45); }
      #atmTopMovers .tag.pe{ border-color: rgba(239,68,68,.45); }
      #atmTopMovers .num{ font-weight:900; }
      #atmTopMovers .up{ color:rgba(34,197,94,.95); }
      #atmTopMovers .dn{ color:rgba(248,113,113,.95); }

      /* Row highlight when MAX OI is not ATM */
      .max-oi-row > td{ background:rgba(34,197,94,.10) !important; }
      .max-oi-row.atm-row > td{ background:inherit !important; }

      /* ATM Summary fine-tune */
      #oiAtmSummaryCard .title{ flex:0 0 auto; }
      #oiAtmSummaryCard .muted{ flex:1 1 260px; min-width:220px; }
      #oiAtmSummaryCard .leader{ flex:1 1 260px; min-width:220px; text-align:right; }
      #atmDominance{ flex:1 1 520px; min-width:260px; }
      #atmLeaders, #atmDominance{ display:none; }

      /* REGIME pills row: full width, aligned right */
      #atmRegime{
        flex: 1 1 100%;
        order: 2;
        margin-left: 0;
        min-width: 0;
        text-align: right;
        font-size: 12px;
      }
      #atmRegime .pill{
        display:inline-block;
        padding:2px 8px;
        border-radius:999px;
        border:1px solid rgba(148,163,184,.22);
        background:rgba(2,6,23,.18);
        margin-left:6px;
      }
      #atmRegime .up{ border-color:rgba(34,197,94,.35); }
      #atmRegime .dn{ border-color:rgba(248,113,113,.35); }
      #atmRegime .rng{ border-color:rgba(59,130,246,.35); }
      #atmRegime .warn{ border-color:rgba(250,204,21,.45); }

      /* Hide old big Quick Read container (we now use two boxes) */
      #atmQuickRead{ display:none !important; }

      /* Mini strip stays below */
      #atmMiniStrip{ flex:1 1 100%; order: 4; }

      /* 6 boxes in one row, scroll if narrow */
      #atmTopMovers{
        flex: 1 1 100%;
        order: 3;
        display:grid;
        grid-template-columns: repeat(6, minmax(180px, 1fr));
        gap:10px;
        overflow-x:auto;
        padding-bottom:2px;
        align-items:stretch;
      }

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

  // ===================== ATM Summary Card + MAX highlights =====================
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
      <div class="title">ATM Summary</div>
      <div class="muted" id="oiAtmSummaryMeta">—</div>
      <div class="row" style="flex:1 1 auto">
        <span class="pill ce">CE</span>
        <span class="metric">Cur OI <span class="big" id="atmCeCur">-</span></span>
        <div class="bar"><div class="fill ce" id="atmCeCurBar" style="width:0%"></div></div>
        <span class="metric">Δ (<span id="atmWinLbl">-</span>) <span class="big" id="atmCeD">-</span></span>
        <div class="bar"><div class="fill ce" id="atmCeDBar" style="width:0%"></div></div>
      </div>
      <div class="row" style="flex:1 1 auto">
        <span class="pill pe">PE</span>
        <span class="metric">Cur OI <span class="big" id="atmPeCur">-</span></span>
        <div class="bar"><div class="fill pe" id="atmPeCurBar" style="width:0%"></div></div>
        <span class="metric">Δ (<span id="atmWinLbl2">-</span>) <span class="big" id="atmPeD">-</span></span>
        <div class="bar"><div class="fill pe" id="atmPeDBar" style="width:0%"></div></div>
      </div>

      <div id="atmRegime" class="regime">—</div>

      <!-- old big panel kept for compatibility but hidden by CSS -->
      <div id="atmQuickRead" class="quick"></div>

      <div class="leader" id="atmLeaders">—</div>
      <div class="row" id="atmDominance" style="font-size:12px; opacity:.95;">—</div>
      <div class="row" id="atmMiniStrip" style="gap:8px; flex-wrap:wrap;"></div>
      <div class="row" id="atmTopMovers" style="gap:14px; flex-wrap:wrap; align-items:flex-start;"></div>
    `;

    const toolbar = document.getElementById("oiTrackToolbar");
    if (toolbar && toolbar.parentElement)
      toolbar.parentElement.insertBefore(card, toolbar);
    else refs.table.parentElement.insertBefore(card, refs.table);

    // click-to-copy numbers from pills inside boxes (if any)
    card.addEventListener("click", (e) => {
      const t = e.target;
      if (!t) return;
      const pill =
        t.closest &&
        t.closest("#oiAtmSummaryCard .pill, #oiAtmSummaryCard .tag");
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

    const optFilter =
      typeof UI !== "undefined" && UI && UI.optFilter
        ? String(UI.optFilter)
        : "both";

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
      meta.textContent =
        `${sym}${
          isFinite(spot)
            ? ` @ ${spot.toLocaleString("en-IN", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              })}`
            : ""
        }` + `${atm != null ? ` | ATM ${atm}` : ""}`;
    }

    const w1 = document.getElementById("atmWinLbl");
    const w2 = document.getElementById("atmWinLbl2");
    if (w1) w1.textContent = `${win}m`;
    if (w2) w2.textContent = `${win}m`;

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

      const typ = String(tr.children[m.type]?.textContent || "")
        .trim()
        .toUpperCase();
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

    // ===== Mini strip (keep as you had) =====
    const strip = document.getElementById("atmMiniStrip");
    if (strip) {
      strip.innerHTML = `<span style="font-size:11px; opacity:.85;">Legend: Pos = higher Current OI (C=CE, P=PE), Move = higher |Δ| in window. ✅=Pos&Move agree (wall). ⚠️=conflict (flow vs position).</span>`;
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
        div.innerHTML = `<span class="a ${bCls}">${badge}</span>
          <span class="s">${lab}</span> <span class="sub">(${kStrike})</span>
          <span class="k">Pos:</span> <span class="v ${
            oiL === "CE" ? "ce" : oiL === "PE" ? "pe" : ""
          }">${posSide}</span>
          <span class="k" style="margin-left:6px">Move:</span> <span class="v ${
            dL === "CE" ? "ce" : dL === "PE" ? "pe" : ""
          }">${movSide}</span>`;
        strip.appendChild(div);
      });
    }

    // ===================== Build 6 neat boxes =====================
    const movers = document.getElementById("atmTopMovers");
    if (movers) {
      movers.innerHTML = "";

      const optFilter2 =
        typeof UI !== "undefined" && UI && UI.optFilter
          ? String(UI.optFilter)
          : "both";

      const rows = [];
      Array.from(refs.tbody.querySelectorAll("tr")).forEach((tr) => {
        if (tr.style && tr.style.display === "none") return;

        const rowType = String(tr.children[m.type]?.textContent || "")
          .trim()
          .toUpperCase();
        if (optFilter2 === "calls" && rowType !== "CE") return;
        if (optFilter2 === "puts" && rowType !== "PE") return;

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

      const topByCur = rows
        .filter((r) => isFinite(r.cur))
        .sort((a, b) => b.cur - a.cur)
        .slice(0, 3);

      const topByDelta = rows
        .filter((r) => isFinite(r.delta))
        .sort((a, b) => Math.abs(b.delta) - Math.abs(a.delta))
        .slice(0, 3);

      const mkBox = (title, winLbl) => {
        const box = document.createElement("div");
        box.className = "box";
        box.innerHTML = `<div class="h"><span>${title}</span>${
          winLbl ? `<span class="w">${winLbl}</span>` : ""
        }</div>`;
        return box;
      };

      const mkItem = (r, kind) => {
        const div = document.createElement("div");
        div.className = "item";

        const typCls = r.rowType === "CE" ? "ce" : "pe";
        const val = kind === "cur" ? r.cur : r.delta;
        const isUp = kind === "cur" ? true : val >= 0;
        const numCls = kind === "cur" ? "" : isUp ? "up" : "dn";
        const valTxt =
          kind === "cur"
            ? fmt(val)
            : `${isUp ? "▲" : "▼"} ${fmt(Math.abs(val))}`;

        div.innerHTML = `
          <div class="lhs">
            <span class="tag ${typCls}">${r.rowType}</span>
            <span class="num">${r.strike}</span>
          </div>
          <div class="rhs num ${numCls}">${valTxt}</div>
        `;
        return div;
      };

      const addKV = (box, k, vHtml, cls = "") => {
        const d = document.createElement("div");
        d.className = "item";
        d.innerHTML = `<div class="lhs">${k}</div><div class="rhs num ${cls}">${vHtml}</div>`;
        box.appendChild(d);
      };

      const tag = (txt, cls = "") => `<span class="tag ${cls}">${txt}</span>`;
      const fmtStrike = (s) => (isFinite(s) ? String(Math.round(s)) : "-");

      // Box 1: Top Current OI
      const boxCur = mkBox("Top Current OI", "");
      topByCur.forEach((r) => boxCur.appendChild(mkItem(r, "cur")));

      // Box 2: Top ΔOI
      const boxD = mkBox("Top ΔOI", `Δ(${win}m)`);
      topByDelta.forEach((r) => boxD.appendChild(mkItem(r, "delta")));

      // Box 3: ATM Snapshot
      const boxA = mkBox("ATM Snapshot", `ATM ${atm ?? "-"}`);
      const ceLine =
        (isFinite(ceCur) ? fmt(ceCur) : "-") +
        (isFinite(ceD)
          ? ` | ${ceD >= 0 ? "▲" : "▼"} ${fmt(Math.abs(ceD))}`
          : "");
      const peLine =
        (isFinite(peCur) ? fmt(peCur) : "-") +
        (isFinite(peD)
          ? ` | ${peD >= 0 ? "▲" : "▼"} ${fmt(Math.abs(peD))}`
          : "");
      addKV(
        boxA,
        `${tag("CE", "ce")} Cur | Δ`,
        ceLine,
        isFinite(ceD) ? (ceD >= 0 ? "up" : "dn") : ""
      );
      addKV(
        boxA,
        `${tag("PE", "pe")} Cur | Δ`,
        peLine,
        isFinite(peD) ? (peD >= 0 ? "up" : "dn") : ""
      );

      // Box 4: Leaders & Dominance (compact)
      const boxL = mkBox("Leaders & Dominance", `Δ(${win}m)`);
      addKV(boxL, "Leaders", `OI ${oiLeader} | Δ ${dLeader}`);

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
      addKV(boxL, "Dom OI", `CE ${fmtP2(ceOiP2)} / PE ${fmtP2(peOiP2)}`);
      addKV(boxL, "Dom |Δ|", `CE ${fmtP2(ceDP2)} / PE ${fmtP2(peDP2)}`);

      // Compute quick read metrics from visible rows
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

      // Spot delta since last refresh
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

      // Max Pain
      const byStrike = new Map();
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

      // Breakout alert (simple)
      const support = bestPut ? bestPut.strike : NaN;
      const resistance = bestCall ? bestCall.strike : NaN;
      const distSup =
        isFinite(spot) && isFinite(support) ? spot - support : NaN;
      const distRes =
        isFinite(spot) && isFinite(resistance) ? resistance - spot : NaN;

      let breakout = "Not at key level",
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
      }

      // Regime + Interpretation (compact)
      let regime = "Ranging / low flow",
        rCls = "rng";
      let interp = "Spot flat → low conviction";

      if (isFinite(spotChg)) {
        if (spotChg > 5) {
          regime =
            dLeader === "CE"
              ? "Trending Up (CE-led)"
              : dLeader === "PE"
              ? "Up + Put-writing"
              : "Trending Up";
          rCls = "up";
        } else if (spotChg < -5) {
          regime =
            dLeader === "PE"
              ? "Trending Down (PE-led)"
              : dLeader === "CE"
              ? "Down + Call-writing"
              : "Trending Down";
          rCls = "dn";
        } else {
          regime =
            dLeader === "PE"
              ? "Ranging (support forming)"
              : dLeader === "CE"
              ? "Ranging (resistance forming)"
              : "Ranging / low flow";
          rCls = "rng";
        }

        if (spotChg > 5)
          interp =
            dLeader === "CE"
              ? "Spot ↑ + CE flow → bullish"
              : dLeader === "PE"
              ? "Spot ↑ + PE flow → support build"
              : "Spot ↑ → watch flow";
        else if (spotChg < -5)
          interp =
            dLeader === "PE"
              ? "Spot ↓ + PE flow → bearish"
              : dLeader === "CE"
              ? "Spot ↓ + CE flow → resistance build"
              : "Spot ↓ → watch flow";
        else
          interp =
            dLeader === "CE"
              ? "Spot flat + CE flow → resistance forming"
              : dLeader === "PE"
              ? "Spot flat + PE flow → support forming"
              : "Spot flat → low flow";
      }

      const reg = document.getElementById("atmRegime");
      if (reg) {
        reg.innerHTML = `
          <span class="pill ${rCls}">Regime: ${regime}</span>
          <span class="pill ${bCls}">Alert: ${breakout}</span>
          <span class="pill rng">MaxPain: ${
            isFinite(maxPain) ? Math.round(maxPain) : "-"
          } | Pin: ${pinHint}</span>
        `;
      }

      // Box 5: Quick Read
      const boxQR = mkBox("Quick Read", "");
      addKV(
        boxQR,
        "Support (max Put OI)",
        tag(bestPut ? fmtStrike(bestPut.strike) : "-", "pe")
      );
      addKV(
        boxQR,
        "Resistance (max Call OI)",
        tag(bestCall ? fmtStrike(bestCall.strike) : "-", "ce")
      );

      const hotTxt = hotOverall
        ? `${tag(
            hotOverall.rowType,
            hotOverall.rowType === "CE" ? "ce" : "pe"
          )} ${fmtStrike(hotOverall.strike)} ${
            hotOverall.delta >= 0 ? "▲" : "▼"
          } ${fmt(Math.abs(hotOverall.delta))}`
        : "-";
      addKV(boxQR, "Hot Move (|Δ| max)", hotTxt);

      addKV(
        boxQR,
        "Max Pain",
        `${tag(isFinite(maxPain) ? String(Math.round(maxPain)) : "-")} ${
          isFinite(maxPainDist)
            ? `(${maxPainDist >= 0 ? "+" : ""}${maxPainDist.toFixed(0)} pts)`
            : ""
        }`
      );

      // Box 6: Signals
      const boxSig = mkBox("Signals", "");
      addKV(boxSig, "Pin Pressure", tag(pinHint));
      addKV(boxSig, "Breakout Alert", tag(breakout));
      addKV(boxSig, "Spot Δ", tag(spotLabel));
      addKV(boxSig, "Interpretation", tag(interp));
      addKV(
        boxSig,
        "Bias",
        tag(
          `${
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
          }`
        )
      );

      movers.appendChild(boxCur);
      movers.appendChild(boxD);
      movers.appendChild(boxA);
      movers.appendChild(boxL);
      movers.appendChild(boxQR);
      movers.appendChild(boxSig);
    }
  }

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

    refs.tbody
      .querySelectorAll(".max-oi-row")
      .forEach((tr) => tr.classList.remove("max-oi-row"));

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

      const atmNow = getATM();
      try {
        const tr = bestCurTd.closest("tr");
        if (tr) {
          const strikeVal = parseInt(
            String(tr.children[m.strike]?.textContent || "").replace(
              /\D+/g,
              ""
            ),
            10
          );
          if (atmNow != null && isFinite(strikeVal) && strikeVal !== atmNow)
            tr.classList.add("max-oi-row");
        }
      } catch (e) {}
    }

    if (bestDTd) {
      bestDTd.classList.add("max-delta-cell");
      bestDTd.insertAdjacentHTML(
        "beforeend",
        `<span class="max-tag">MAX Δ(${win}m)</span>`
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

  // expose for your page to call after re-render
  window.enhanceAll = enhanceAll;

  // debug
  window._oiEnhancerDebug = function () {
    const m = mapColumns();
    console.log("Labels:", m?._labels);
    console.log("Map:", m);
    return { labels: m?._labels, map: m };
  };

  // Re-apply frequently to survive auto-refresh / DOM rebuilds
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
