/* OI Dashboard Enhancer (ULTRA)
 * - Robust column mapping using #trackHeadRow (preferred) + THEAD grid fallback
 * - Timeframe toggles hide BOTH header + body (no shifting)
 * - ΔOI(T) arrows + %: Current OI − Start OI(T) for 1/2/3/5/10/15/30 (whatever exists)
 * - Bigger OI values; calmer ΔOI
 * - Color-scale Current OI by intensity (auto-normalized every refresh)
 * - Fade ΔOI when flat (near-zero change)
 * - Bold only ATM ±2 strikes (OI Track)
 * - Blink on sudden OI spike (3m vs 10m)
 * - CE/PE dominance shading per strike (based on Current OI)
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
      #oiTrackToolbar{ margin:6px 0 8px; display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
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
      // window -> column index
      oi: {},
      d: {},
      _labels: L,
    };

    if (L && L.length) {
      // common basics
      m.strike = findCol(L, [/^strike$/i, /^strike\s*price$/i]);
      m.type = findCol(L, [/^type$/i, /^option\s*type$/i, /^cp$/i]);

      m.curOi = findCol(L, [
        /^current\s*oi$/i,
        /^cur\s*oi$/i,
        /^oi\s*\(current\)$/i,
      ]);

      // detect any "oi (Xm)" and "Δoi (Xm)" present
      // OI(3m), OI (3 min), Start OI (3m) variants
      for (let i = 0; i < L.length; i++) {
        const t = L[i];
        let mm = null;

        // OI columns
        let m1 =
          t.match(/^oi\s*\((\d+)\s*m\)$/i) ||
          t.match(/^(start\s*)?oi\s*\((\d+)\s*m\)$/i);
        if (m1) {
          mm = parseInt(m1[m1.length - 1], 10);
          if (mm) m.oi[mm] = i;
          continue;
        }
        // OI (3 min)
        let m2 =
          t.match(/^oi\s*\((\d+)\s*min\)$/i) ||
          t.match(/^(start\s*)?oi\s*\((\d+)\s*min\)$/i);
        if (m2) {
          mm = parseInt(m2[m2.length - 1], 10);
          if (mm) m.oi[mm] = i;
          continue;
        }

        // ΔOI columns
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

      // fallback if Δ missing: assume right after OI
      Object.keys(m.oi).forEach((k) => {
        const mm = parseInt(k, 10);
        if (m.oi[mm] >= 0 && (m.d[mm] == null || m.d[mm] < 0))
          m.d[mm] = m.oi[mm] + 1;
      });

      // hard fallback for strike/type if not labeled
      if (m.strike < 0) m.strike = 0;
      if (m.type < 0) m.type = 1;
    } else {
      // last resort: infer by first row width
      const sample = tbody.querySelector("tr");
      const cols = sample ? sample.children.length : 0;
      if (cols > 2) {
        m.strike = 0;
        m.type = 1;
        m.curOi = cols - 2; // often currentOI near end
      }
    }

    return m;
  }

  // ===================== Toolbar & legend =====================
  function ensureToolbar() {
    if (document.getElementById("oiTrackToolbar")) return;
    const refs = getTrackRefs();
    if (!refs) return;

    // detect available windows from existing checkboxes (we keep your current set)
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
      1: document.getElementById("col1")?.checked === true, // default off unless you tick
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

      // header
      if (headRow && headRow.children[idx]) {
        headRow.children[idx].style.display = vis ? "" : "none";
      } else {
        table.querySelectorAll("thead tr").forEach((tr) => {
          if (tr.children[idx])
            tr.children[idx].style.display = vis ? "" : "none";
        });
      }

      // body
      tbody.querySelectorAll("tr").forEach((tr) => {
        if (tr.children[idx])
          tr.children[idx].style.display = vis ? "" : "none";
      });
    }

    // show/hide any detected windows that match our checkboxes set
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

    // which windows exist in table (keys from mapping)
    const windows = Object.keys(m.oi)
      .map((k) => parseInt(k, 10))
      .filter((v) => !isNaN(v))
      .sort((a, b) => b - a);

    const flatPct = 0.12; // % under this treated "flat" (tweak)
    const flatAbs = 200; // absolute under this treated "flat" (tweak)

    Array.from(tbody.querySelectorAll("tr")).forEach((tr) => {
      const cur = num(tr.children[m.curOi]?.textContent);

      // mark Current OI cell style
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

        // flat fade
        if (Math.abs(pct) < flatPct && Math.abs(delta) < flatAbs)
          dTd.classList.add("delta-flat");

        // render
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

    // robust-ish range: use percentiles to avoid outliers dominating
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

      // remove any previous inline bg before reapplying
      td.style.backgroundColor = "";

      if (!isFinite(v)) return;

      // normalize 0..1, clamp
      let t = (v - lo) / denom;
      t = Math.max(0, Math.min(1, t));

      // subtle: alpha 0.03..0.18
      const a = 0.03 + t * 0.15;
      // neutral blue-ish tint (works on dark theme)
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

      // filter window around ATM (show only ±N*50)
      if (atm != null && winN > 0 && !Number.isNaN(strike)) {
        const show = Math.abs(strike - atm) <= winN * step;
        tr.style.display = show ? "" : "none";
      } else {
        tr.style.display = "";
      }

      // bold ONLY ATM ±2 strikes (not all rows)
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

    // need both 3m and 10m Δ columns
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

      // parse delta numbers from rendered content
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

    // need strike + type columns
    const sIdx = m.strike >= 0 ? m.strike : 0;
    const tIdx = m.type >= 0 ? m.type : 1;

    // build strike -> {CE:{oi,row}, PE:{oi,row}}
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

    // apply shading based on dominance ratio
    map.forEach((v) => {
      const ce = v.CE,
        pe = v.PE;
      if (!ce || !pe || !isFinite(ce.oi) || !isFinite(pe.oi)) return;

      const total = Math.max(1, ce.oi + pe.oi);
      const shareCE = ce.oi / total; // 0..1

      // weak dominance zone: 45..55%
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

  // ===================== Main refresh =====================
  function enhanceAll() {
    injectCssOnce();
    ensureToolbar();
    ensureLegend();

    const refs = getTrackRefs();
    if (!refs) return;

    // 1) compute / render
    toggleTimeframeColumns();
    addDeltaArrowsAndStyles();

    // 2) visuals
    applyOiIntensity(); // auto-normalize per refresh
    applyCePeDominance(); // CE/PE shading

    // 3) ATM behavior
    const atm = getATM();
    const winN = parseInt(
      document.getElementById("strikeWinTrack")?.value || "10",
      10
    );
    highlightATMInTrack(atm, winN);
    highlightATMInOtherTables(atm);

    // 4) alerts
    applySpikeBlink();
  }

  // expose for your page to call after re-render
  window.enhanceAll = enhanceAll;

  // debug
  window._oiEnhancerDebug = function () {
    const refs = getTrackRefs();
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
