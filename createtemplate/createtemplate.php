<?php
declare(strict_types=1);

$template = (string)($_GET['template'] ?? '');
$template = trim($template);
$template = basename($template);

// Default fragment if new / missing
$defaultFragment = "<h2>Template</h2>\n<p>(Start typing…)</p>\n";

if ($template === '' || !preg_match('/^[A-Za-z0-9_-]+\.html$/', $template)) {
    http_response_code(400);
    $template = 'example.html';
    $fragment = $defaultFragment;
} else {
    $path =  $template;
    $fragment = is_file($path) ? (string)file_get_contents($path) : $defaultFragment;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Edit Template</title>

<link rel="stylesheet" href="styles.css">

<style>
  * { box-sizing: border-box; }
  body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; margin: 24px; max-width: 950px; }

  header { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:14px; }
  h1 { font-size: 1.1rem; margin: 0; flex: 1 1 auto; }
  button, select, input { padding: 8px 10px; font-size: 0.95rem; }
  .status { margin-left:auto; font-size:0.9rem; opacity:0.8; }

  .toolbar {
    display:flex; gap:10px; align-items:center; flex-wrap:wrap;
    margin-bottom: 12px; padding: 10px;
    border: 1px solid #ddd; border-radius: 10px; background: #fafafa;
  }

  .editor {
    border: 1px solid #ccc; border-radius: 10px; padding: 14px;
    min-height: 280px; background: #fff; outline: none; line-height: 1.4;
  }
  .editor:focus { border-color:#888; }
  .editor p { margin: 0 0 10px; }

  .badge {
    font-size: 0.85rem; padding: 2px 8px;
    border: 1px solid #ddd; border-radius: 999px;
    background: #fff; opacity: 0.85;
  }
</style>
</head>

<body>

<header>
  <h1>Editable Section</h1>
  <span class="status" id="status">Loading styles…</span>
</header>

<div class="toolbar">
  <label>
    Paragraph style:
    <select id="pStyle" disabled>
      <option value="">Loading styles…</option>
    </select>
  </label>

  <button type="button" id="applyBtn">Apply to selected paragraph(s)</button>
  <button type="button" id="clearBtn">Clear style from selected paragraph(s)</button>

  <span class="badge" id="badge">styles.css</span>

  <span style="flex:1 1 auto"></span>

  <label>
    Save as:
    <input id="filename" type="text" value="" placeholder="Enter filename (e.g. epiphany_communion.html)">
  </label>
  <button type="button" id="saveBtn">Save</button>
</div>

<div id="editor" class="editor" contenteditable="true" spellcheck="true">
<?php echo $fragment; ?>
</div>

<script>
  const CSS_FILE = "styles.css";
  const CLASS_PREFIX = "p-";

  const editor = document.getElementById("editor");
  const status = document.getElementById("status");
  const badge = document.getElementById("badge");
  const pStyle = document.getElementById("pStyle");
  const applyBtn = document.getElementById("applyBtn");
  const clearBtn = document.getElementById("clearBtn");
  const saveBtn = document.getElementById("saveBtn");
  const filenameEl = document.getElementById("filename");

  const TEMPLATE_FILE = <?php echo json_encode($template, JSON_UNESCAPED_SLASHES); ?>;

  let allowedPClasses = [];

  function setStatus(msg) { status.textContent = msg; }

  function prettyLabel(className) {
    return className
      .replace(new RegExp("^" + CLASS_PREFIX), "")
      .replace(/[-_]+/g, " ")
      .trim()
      .replace(/\b\w/g, c => c.toUpperCase());
  }

  function parseCssClasses(cssText) {
    const noComments = cssText.replace(/\/\*[\s\S]*?\*\//g, "");
    const matches = [...noComments.matchAll(/\.([A-Za-z_][A-Za-z0-9_-]*)/g)];
    const set = new Set();
    for (const m of matches) {
      const cls = m[1];
      if (CLASS_PREFIX && !cls.startsWith(CLASS_PREFIX)) continue;
      set.add(cls);
    }
    return Array.from(set).sort((a,b) => a.localeCompare(b));
  }

  function fillDropdown(classNames) {
    pStyle.innerHTML = "";

    const optNone = document.createElement("option");
    optNone.value = "";
    optNone.textContent = "— None —";
    pStyle.appendChild(optNone);

    for (const cls of classNames) {
      const opt = document.createElement("option");
      opt.value = cls;
      opt.textContent = prettyLabel(cls) + ` (${cls})`;
      pStyle.appendChild(opt);
    }

    pStyle.disabled = false;
  }

  async function loadStyles() {
    badge.textContent = CSS_FILE;
    try {
      const res = await fetch(CSS_FILE, { cache: "no-store" });
      if (!res.ok) throw new Error(`Could not load ${CSS_FILE} (HTTP ${res.status})`);
      const css = await res.text();

      allowedPClasses = parseCssClasses(css);

      if (allowedPClasses.length === 0) {
        pStyle.innerHTML = `<option value="">No "${CLASS_PREFIX}" classes found</option>`;
        pStyle.disabled = true;
        setStatus(`No classes starting with "${CLASS_PREFIX}" found in ${CSS_FILE}`);
        return;
      }

      fillDropdown(allowedPClasses);
      setStatus(`Loaded ${allowedPClasses.length} style(s) from ${CSS_FILE}`);
    } catch (err) {
      pStyle.innerHTML = `<option value="">Failed to load styles</option>`;
      pStyle.disabled = true;
      setStatus("Error: " + err.message);
    }
  }

function getSelectedParagraphs() {
  const sel = window.getSelection();
  if (!sel || sel.rangeCount === 0) return [];

  const range = sel.getRangeAt(0);

  // If selection is outside editor, ignore
  if (!editor.contains(range.commonAncestorContainer)) return [];

  // Helper: find nearest <p> ancestor inside editor
  const nearestP = (node) => {
    let n = (node && node.nodeType === Node.ELEMENT_NODE) ? node : node?.parentElement;
    while (n && n !== editor) {
      if (n.tagName === "P") return n;
      n = n.parentElement;
    }
    return null;
  };

  // If it's just a caret (collapsed selection), apply to the paragraph at caret
  if (range.collapsed) {
    const p = nearestP(sel.anchorNode);
    return p ? [p] : [];
  }

  const paragraphs = Array.from(editor.querySelectorAll("p"));
  const hits = [];

  for (const p of paragraphs) {
    // Most modern browsers
    if (typeof range.intersectsNode === "function") {
      try {
        if (range.intersectsNode(p)) hits.push(p);
      } catch {
        // Some nodes can throw in weird edge cases; ignore
      }
      continue;
    }

    // Fallback (older Safari-ish): manual intersection test
    const pr = document.createRange();
    pr.selectNodeContents(p);

    const selectionEndsBeforePStarts =
      range.compareBoundaryPoints(Range.END_TO_START, pr) <= 0;

    const selectionStartsAfterPEnds =
      range.compareBoundaryPoints(Range.START_TO_END, pr) >= 0;

    if (!(selectionEndsBeforePStarts || selectionStartsAfterPEnds)) {
      hits.push(p);
    }
  }

  // Safety: if nothing matched, still try start/end paragraphs
  if (hits.length === 0) {
    const startP = nearestP(range.startContainer);
    const endP = nearestP(range.endContainer);
    if (startP) hits.push(startP);
    if (endP && endP !== startP) hits.push(endP);
  }

  // De-dup, preserve document order
  return Array.from(new Set(hits));
}


  function stripAllowedClasses(p) {
    allowedPClasses.forEach(c => p.classList.remove(c));
  }

  applyBtn.addEventListener("click", () => {
    const cls = pStyle.value;
    const ps = getSelectedParagraphs();
    if (ps.length === 0) { setStatus("No paragraph selected."); return; }

    ps.forEach(p => {
      stripAllowedClasses(p);
      if (cls) p.classList.add(cls);
    });

    setStatus(`Applied “${cls || "none"}” to ${ps.length} paragraph(s).`);
  });

  clearBtn.addEventListener("click", () => {
    const ps = getSelectedParagraphs();
    if (ps.length === 0) { setStatus("No paragraph selected."); return; }
    ps.forEach(stripAllowedClasses);
    setStatus(`Cleared style from ${ps.length} paragraph(s).`);
  });



async function confirmOverwriteIfNeeded(filename) {
  try {
    const res = await fetch("template-exists.php?file=" + encodeURIComponent(filename), {
      cache: "no-store"
    });

    const data = await res.json().catch(() => ({ exists: false }));

    if (data.exists) {
      return confirm(`"${filename}" already exists.\n\nOverwrite it?`);
    }
    return true;
  } catch (e) {
    setStatus("Error: cannot check if file exists (template-exists.php).");
    return false;
  }
}




  // SAVE: POST HTML fragment to save.php?template=some.html
  
  
 saveBtn.addEventListener("click", async () => {
  let file = (filenameEl.value || "")
    .replace(/\s+/g, "")   // remove all whitespace
    .trim();

  if (!file) {
    setStatus("Error: please enter a filename before saving.");
    filenameEl.focus();
    return;
  }

  // Auto-append .html if missing
  if (!/\.html$/i.test(file)) {
    file = file + ".html";
    filenameEl.value = file; // reflect the change in the UI
  }

  // Final validation
  if (!/^[A-Za-z0-9_-]+\.html$/i.test(file)) {
    setStatus("Error: filename must be letters, numbers, _ or -, ending in .html");
    filenameEl.focus();
    return;
  }

  if (!(await confirmOverwriteIfNeeded(file))) {
    setStatus("Save cancelled.");
    return;
  }

  setStatus("Saving…");

  try {
    const res = await fetch("save.php?template=" + encodeURIComponent(file), {
      method: "POST",
      headers: { "Content-Type": "text/html; charset=utf-8" },
      body: editor.innerHTML
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok || !data.ok) {
      throw new Error(data.error || `Save failed (HTTP ${res.status})`);
    }

    setStatus(`Saved to ${data.filename} at ${data.saved_at}`);
  } catch (err) {
    setStatus("Error: " + err.message);
  }
});



  loadStyles();
</script>

</body>
</html>
