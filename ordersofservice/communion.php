<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Editable Template with Paragraph Styles</title>

<link rel="stylesheet" href="styles.css">

<style>
  * { box-sizing: border-box; }
  body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; margin: 24px; max-width: 950px; }

  header { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:14px; }
  h1 { font-size: 1.1rem; margin: 0; flex: 1 1 auto; }
  button, select, input { padding: 8px 10px; font-size: 0.95rem; }
  .status { margin-left:auto; font-size:0.9rem; opacity:0.8; }

  .toolbar {
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
    margin-bottom: 12px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 10px;
    background: #fafafa;
  }

  .editor {
    border: 1px solid #ccc;
    border-radius: 10px;
    padding: 14px;
    min-height: 280px;
    background: #fff;
    outline: none;
    line-height: 1.4;
  }
  .editor:focus { border-color:#888; }

  .hint { margin-top:10px; font-size:0.9rem; opacity:0.75; }

  /* Optional: show paragraph boundaries subtly */
  .editor p { margin: 0 0 10px; }
</style>
</head>

<body>

<header>
  <h1>Editable Section</h1>
  <span class="status" id="status">Not saved yet</span>
</header>

<div class="toolbar">
  <label>
    Paragraph style:
    <select id="pStyle">
      <option value="">— None —</option>
      <option value="p-lead">Lead</option>
      <option value="p-rubric">Rubric</option>
      <option value="p-response">Response</option>
      <option value="p-warning">Warning</option>
      <option value="p-small">Small</option>
    </select>
  </label>

  <button type="button" id="applyBtn">Apply to selected paragraph(s)</button>
  <button type="button" id="clearBtn">Clear style from selected paragraph(s)</button>

  <span style="flex:1 1 auto"></span>

  <label>
    Save as:
    <input id="filename" type="text" value="saved.html" />
  </label>
  <button type="button" id="saveBtn">Save</button>
</div>

<div id="editor" class="editor" contenteditable="true" spellcheck="true">
  <h2>Template</h2>
  <p>Select text in a paragraph, choose a style, then click “Apply”.</p>
  <p>You can select across multiple paragraphs too.</p>
  <p>This paragraph can become a <em>rubric</em>, a <strong>response</strong>, etc.</p>
</div>

<p class="hint">
  How it works: this adds a CSS class (from <code>styles.css</code>) onto the relevant <code>&lt;p&gt;</code> tags,
  and the saved file keeps those classes.
</p>

<script>
  const editor = document.getElementById("editor");
  const status = document.getElementById("status");
  const pStyle = document.getElementById("pStyle");
  const applyBtn = document.getElementById("applyBtn");
  const clearBtn = document.getElementById("clearBtn");
  const saveBtn = document.getElementById("saveBtn");
  const filenameEl = document.getElementById("filename");

  // Keep this list in sync with the CSS classes you allow
  const allowedPClasses = ["p-lead", "p-rubric", "p-response", "p-warning", "p-small"];

  function setStatus(msg) { status.textContent = msg; }

  function getSelectedParagraphs() {
    const sel = window.getSelection();
    if (!sel || sel.rangeCount === 0) return [];

    const range = sel.getRangeAt(0);

    // If selection is entirely outside the editor, ignore
    if (!editor.contains(range.commonAncestorContainer)) return [];

    // Collect <p> elements touched by the selection
    const paragraphs = new Set();

    // Helper: climb to nearest P inside editor
    const nearestP = (node) => {
      let n = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
      while (n && n !== editor) {
        if (n.tagName === "P") return n;
        n = n.parentElement;
      }
      return null;
    };

    const startP = nearestP(range.startContainer);
    const endP = nearestP(range.endContainer);

    if (startP) paragraphs.add(startP);
    if (endP) paragraphs.add(endP);

    // If selection spans multiple nodes, walk through and grab any P elements intersecting
    const walker = document.createTreeWalker(
      editor,
      NodeFilter.SHOW_ELEMENT,
      {
        acceptNode(node) {
          if (node.tagName === "P") {
            try {
              // Rough intersection test using range comparison
              const nodeRange = document.createRange();
              nodeRange.selectNodeContents(node);
              const endsBefore = range.compareBoundaryPoints(Range.END_TO_START, nodeRange) <= 0;
              const startsAfter = range.compareBoundaryPoints(Range.START_TO_END, nodeRange) >= 0;
              // If not (range ends before node starts) and not (range starts after node ends), they overlap
              if (!(endsBefore || startsAfter)) return NodeFilter.FILTER_ACCEPT;
            } catch {}
          }
          return NodeFilter.FILTER_SKIP;
        }
      }
    );

    while (walker.nextNode()) paragraphs.add(walker.currentNode);

    // If nothing found but caret is inside editor, apply to paragraph at caret
    if (paragraphs.size === 0) {
      const caretP = nearestP(sel.anchorNode);
      if (caretP) paragraphs.add(caretP);
    }

    return Array.from(paragraphs);
  }

  function stripAllowedClasses(p) {
    allowedPClasses.forEach(c => p.classList.remove(c));
  }

  applyBtn.addEventListener("click", () => {
    const cls = pStyle.value;
    const ps = getSelectedParagraphs();

    if (ps.length === 0) {
      setStatus("No paragraph selected (click inside a paragraph or select text).");
      return;
    }

    ps.forEach(p => {
      stripAllowedClasses(p);
      if (cls) p.classList.add(cls);
    });

    setStatus(`Applied “${cls || "none"}” to ${ps.length} paragraph(s).`);
  });

  clearBtn.addEventListener("click", () => {
    const ps = getSelectedParagraphs();
    if (ps.length === 0) {
      setStatus("No paragraph selected.");
      return;
    }
    ps.forEach(stripAllowedClasses);
    setStatus(`Cleared style from ${ps.length} paragraph(s).`);
  });

  // Save to PHP (keeps class attributes)
  saveBtn.addEventListener("click", async () => {
    setStatus("Saving…");

    const payload = {
      filename: filenameEl.value.trim() || "saved.html",
      html: editor.innerHTML
    };

    try {
      const res = await fetch("save.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) throw new Error(data.error || `Save failed (HTTP ${res.status})`);

      setStatus(`Saved to ${data.filename} at ${data.saved_at}`);
    } catch (err) {
      setStatus("Error: " + err.message);
    }
  });
</script>

</body>
</html>
