/* OI Dashboard Enhancer (ULTRA - FIXED)
 * Fixes:
 *  - Much more reliable Current OI column detection (label + numeric fallback)
 *  - Colspan/rowspan-safe header hide/show (no shifting)
 *  - ΔOI(T) arrows + % always recomputed (cleans previous HTML safely)
 *  - Spike blink works even after repeated rerenders
 */

(function () {
  // ===================== CSS =====================
  function injectCssOnce() {
    if (document.getElementById("oiEnhancerUltraCSS")) return;

    const css = `
      .atm-row{ background:#173057 !important; box-shadow: inset 3px 0 0 #3ba3ff; }
      .atm-badge{ font-size:11px; padding:2px 6px; border-radius:8px; background:#0f2e5f; color:#93c5fd; margin-left:6px; }

      .oi-value{ font-size:15.5px; font-weight:600; color:#e5e7eb; }
      .delta-compact{ white-space:nowrap; font-size:13px; font-weight:600; opacity:0.82; }
      .delta-arrow{ font-weight:800; margin-right:4px; font-size:12px; }
      .delta-up{ color:#16a34a; } .delta-down{ color:#ef4444; }

      .atm-strong .oi-value{ font-size:16px; font-weight:700; }

      #oiTrackLegend{ margin-top:6px; font-size:12px; color:#9aa4b2; }
      #oiTrackLegend b{ color:#cbd5e1; font-weight:600; margin-right:6px; }
      #oiTrackToolbar{ margin:6px 0 8px; display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
      #oiTrackToolbar label{ display:inline-flex; align-items:center; gap:6px; }
      .nowrap{ white-space:nowrap; }

      .oi-intensity{ transition: background-color .15s ease, outline-color .15s ease; }
      .delta-flat{ opacity:0.45 !important; filter:saturate(0.85); }

      @keyframes oiSpikeBlink {
        0%, 100% { box-shadow: 0 0 0 rgba(0,0,0,0); transform: translateZ(0); }
        50% { box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.35); }
      }
      .spike-blink{ animation: oiSpikeBlink 0.9s ease-in-out 0s 3; border-radius:6px; }

      .dom-ce{ background: rgba(22, 163, 74, 0.06) !important; }
      .dom-pe{ background: rgba(239, 68, 68, 0.06) !important; }
      .dom-weak{ background: rgba(148, 163, 184, 0.04) !important; }

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
  function labelsFromTrackHeadRow() {
    const headRow = document.getElementById("trackHeadRow");
    if (!headRow) return null;
    const ths = Array.from(headRow.querySelectorAll("th"));
    if (!ths.length) return null;
    return ths.map((th) => norm(th.textContent || ""));
  }

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

  function findCol(labels, regs) {
    for (let i = 0; i < labels.length; i++) {
      const t = labels[i] || "";
      if (regs.some((r) => r.test(t))) return i;
    }
    return -1;
  }

  // ====== NEW: numeric-based detection for Current OI (fixes 3m update issues) ======
  function guessCurrentOiColByNumbers(tbody, labels, avoidIdxSet) {
    const rows = Array.from(tbody.querySelectorAll("tr")).slice(0, 8);
    if (!rows.length) return -1;

    const colCount = Math.max(...rows.map((r) => r.children.length));
    let best = { idx: -1, score: -1 };

    for (let c = 0; c < colCount; c++) {
      if (avoidIdxSet && avoidIdxSet.has(c)) continue;

      // avoid strike/type-like columns
      const lab = labels && labels[c] ? labels[c] : "";
      if (/^strike/.test(lab) || /^type/.test(lab) || /ce|pe/.test(lab))
        continue;

      let numericHits = 0;
      let bigHits = 0;

      for (const r of rows) {
        const td = r.children[c];
        if (!td) continue;
        const v = num(td.textContent);
        if (isFinite(v)) {
          numericHits++;
          if (Math.abs(v) >= 1000) bigHits++;
        }
      }

      // score: must be numeric in most rows, prefer bigger magnitudes (OI usually big)
      const score = numericHits * 10 + bigHits * 2;
      if (
        numericHits >= Math.max(3, Math.floor(rows.length * 0.6)) &&
        score > best.score
      ) {
        best = { idx: c, score };
      }
    }

    return best.idx;
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

      // prefer explicit "Current OI"
      m.curOi = findCol(L, [
        /^current\s*oi$/i,
        /^cur\s*oi$/i,
        /^oi\s*\(current\)$/i,
        /current.*open.*interest/i,
      ]);

      // detect any "oi (Xm)" and "Δoi (Xm)" present
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

      if (m.strike < 0) m.strike = 0;
      if (m.type < 0) m.type = 1;

      // ===== NEW: if current OI label not found / wrong, infer from numbers =====
      const avoid = new Set([m.strike, m.type]);
      Object.values(m.oi).forEach((x) => isFinite(x) && avoid.add(x));
      Object.values(m.d).forEach((x) => isFinite(x) && avoid.add(x));

      if (
        m.curOi < 0 ||
        m.curOi >= (tbody.querySelector("tr")?.children.length || 9999)
      ) {
        const guessed = guessCurrentOiColByNumbers(tbody, L, avoid);
        if (guessed >= 0) m.curOi = guessed;
      }
    } else {
      // last resort: infer by first row width
      const sample = tbody.querySelector("tr");
      const cols = sample ? sample.children.length : 0;
      if (cols > 2) {
        m.strike = 0;
        m.type = 1;
        m.curOi = Math.max(2, cols - 2);
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

  // ===================== Colspan-safe header hide/show =====================
  function theadCellsCoveringCol(thead, targetCol) {
    if (!thead) return [];
    const out = [];
    const rows = Array.from(thead.querySelectorAll("tr"));
    for (const tr of rows) {
      let col = 0;
      const cells = Array.from(tr.children);
      for (const cell of cells) {
        const colspan = parseInt(cell.getAttribute("colspan") || "1", 10) || 1;
        const start = col;
        const end = col + colspan - 1;
        if (targetCol >= start && targetCol <= end) out.push(cell);
        col += colspan;
      }
    }
    return out;
  }

  function toggleTimeframeColumns() {
    const refs = getTrackRefs();
    if (!refs) return;
    const { table, thead, tbody } = refs;

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

      // header (prefer trackHeadRow)
      if (headRow) {
        const th = headRow.children[idx];
        if (th) th.style.display = vis ? "" : "none";
      } else {
        // colspan-safe: hide any thead cell covering this physical column
        theadCellsCoveringCol(thead, idx).forEach((cell) => {
          cell.style.display = vis ? "" : "none";
        });
      }

      // body
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
      const curTd = tr.children[m.curOi];
      const cur = num(curTd?.textContent);

      if (curTd) {
        curTd.classList.add("oi-value");
        curTd.classList.add("oi-intensity");
      }

      windows.forEach((min) => {
        const oiIdx = m.oi[min];
        const dIdx = m.d[min];
        if (oiIdx == null || dIdx == null || oiIdx < 0 || dIdx < 0) return;

        const oiTd = tr.children[oiIdx];
        const dTd = tr.children[dIdx];
        if (oiTd) oiTd.classList.add("oi-value");
        if (!dTd) return;

        // always reset (important!)
        dTd.classList.add("delta-compact");
        dTd.classList.remove("delta-flat", "spike-blink");
        dTd.innerHTML = ""; // wipe previous markup so we never accumulate stale display

        const startVal = num(oiTd?.textContent);

        if (!isFinite(cur) || !isFinite(startVal)) {
          dTd.textContent = "-";
          return;
        }

        const delta = cur - startVal;
        const pct = startVal !== 0 ? (delta / startVal) * 100 : NaN;

        if (
          isFinite(pct) &&
          Math.abs(pct) < flatPct &&
          Math.abs(delta) < flatAbs
        ) {
          dTd.classList.add("delta-flat");
        }

        if (delta === 0) {
          dTd.textContent = "0 (0.00%)";
          return;
        }

        const up = delta > 0;
        const pctStr =
          startVal !== 0 && isFinite(pct)
            ? ` (${pct >= 0 ? "+" : "-"}${Math.abs(pct).toFixed(2)}%)`
            : "";

        dTd.innerHTML = `
          <span class="delta-arrow ${up ? "delta-up" : "delta-down"}">${
          up ? "▲" : "▼"
        }</span>
          ${fmt(Math.abs(delta))}${pctStr}
        `;
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

  // ===================== Main refresh =====================
  function enhanceAll() {
    injectCssOnce();
    ensureToolbar();
    ensureLegend();

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

    applySpikeBlink();
  }

  window.enhanceAll = enhanceAll;

  window._oiEnhancerDebug = function () {
    const refs = getTrackRefs();
    const m = mapColumns();
    console.log("Labels:", m?._labels);
    console.log("Map:", m);
    console.log("curOi index:", m?.curOi);
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
