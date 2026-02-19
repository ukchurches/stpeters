<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Selectable Blocks → Introduction</title>

  <style>
    body {
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      margin: 24px;
      line-height: 1.4;
    }

    .toolbar {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
      margin: 14px 0 18px;
    }

    button {
      padding: 10px 14px;
      border: 1px solid #ddd;
      border-radius: 10px;
      background: #fff;
      cursor: pointer;
      font-size: 14px;
    }
    button:hover { filter: brightness(0.98); }

    .status { font-size: 13px; opacity: 0.75; }

    .block {
      border: 1px solid #ddd;
      border-radius: 12px;
      padding: 14px 16px;
      margin: 14px 0;
      cursor: pointer;
      user-select: none;
      transition: transform 120ms ease, box-shadow 120ms ease, background 120ms ease;
    }

    .block:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    }

    .block.selected {
      border-color: #2b6cff;
      background: rgba(43,108,255,0.08);
      box-shadow: 0 0 0 3px rgba(43,108,255,0.25);
    }

    .block.mandatory { border-style: dashed; }
    .block.mandatory::before {
      content: "Mandatory";
      font-size: 11px;
      opacity: 0.6;
      float: left;
    }
 .block.or { border-style: dashed; }
    .block.or::before {
      content: "or ...";
      font-size: 11px;
      opacity: 0.6;
      float: left;
    }
.block.seasonal { border-style: dashed; }
    .block.seasonal::before {
      content: "seasonal";
      font-size: 11px;
      opacity: 0.6;
      float: left;
    }
.block.optional { border-style: dashed; }
    .block.optional::before {
      content: "optional";
      font-size: 11px;
      opacity: 0.6;
      float: left;
    }
.block.either { border-style: dashed; }
    .block.either::before {
      content: "either";
      font-size: 11px;
      opacity: 0.6;
      float: left;
    }	
	
	
	
    .block p { margin: 0 0 0.6em; }
    .block p:last-child { margin-bottom: 0; }

    textarea {
      width: 100%;
      min-height: 220px;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 12px;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
      font-size: 13px;
      line-height: 1.35;
      margin-top: 10px;
    }

    .preview {
      margin-top: 10px;
      border: 1px solid #ddd;
      border-radius: 12px;
      padding: 14px 16px;
      background: #fafafa;
    }

    .preview h2 { margin-top: 0; }
  </style>
</head>
<body>

  <h1>Introduction</h1>
  <p>Click blocks to toggle selection. Mandatory blocks are always included. Export the selected blocks to HTML below.</p>

  <div class="toolbar">
    <button id="btnExportHtml" type="button">Export HTML</button>
    <button id="btnCopyHtml" type="button">Copy HTML</button>
    <button id="btnDownloadHtml" type="button">Download HTML</button>
    <button id="btnClear" type="button">Clear optional selection</button>
    <span class="status" id="status"></span>
  </div>


  <div class="block selected" tabindex="0" data-id="block1" >

<p class="p-response ">Almighty God,<br>
to whom all hearts are open,<br>
all desires known,<br>
and from whom no secrets are hidden:<br>
cleanse the thoughts of our hearts<br>
by the inspiration of your Holy Spirit,<br>
that we may perfectly love you,<br>
and worthily magnify your holy name;<br>
through Christ our Lord.<br>
Amen.
</p>
  </div>



  <h2>Exported HTML</h2>
  <textarea id="htmlOut" spellcheck="false" placeholder="Click “Export HTML”…"></textarea>

  <div class="preview" id="preview">
    <h2>Preview</h2>
    <div id="previewInner">(nothing yet)</div>
  </div>

  <script>
    const blocks = Array.from(document.querySelectorAll('.block'));
    const htmlOut = document.getElementById('htmlOut');
    const previewInner = document.getElementById('previewInner');
    const statusEl = document.getElementById('status');

    function isMandatory(el) {
      return el.dataset.mandatory === '1';
    }

    function setStatus(msg) {
      statusEl.textContent = msg;
      if (msg) setTimeout(() => statusEl.textContent = '', 1500);
    }

    // Toggle selection (multi-select by default; mandatory cannot be deselected)
    for (const el of blocks) {
      el.addEventListener('click', () => {
        if (isMandatory(el)) return;
        el.classList.toggle('selected');
      });

      el.addEventListener('keydown', (e) => {
        if ((e.key === 'Enter' || e.key === ' ') && !isMandatory(el)) {
          e.preventDefault();
          el.classList.toggle('selected');
        }
      });
    }

    function getSelectedBlocksInDomOrder() {
      return blocks.filter(el => el.classList.contains('selected'));
    }

    // Build export HTML as a standalone fragment (no <html><head> etc)
  function buildExportHtml() {
  const selected = getSelectedBlocksInDomOrder();

  const wrapper = document.createElement('div');
  wrapper.className = 'exported-content';

  for (const el of selected) {
    const outBlock = document.createElement('div');
    outBlock.className = 'content-block';
    outBlock.setAttribute('data-id', el.dataset.id || '');

    if (isMandatory(el)) outBlock.setAttribute('data-mandatory', '1');

    // Copy paragraphs, preserving classes/attributes/inline HTML
    const ps = Array.from(el.querySelectorAll('p'));
    for (const p of ps) {
      const clonedP = p.cloneNode(true); // keeps class="" etc
      clonedP.removeAttribute('id');     // optional
      outBlock.appendChild(clonedP);
    }

    wrapper.appendChild(outBlock);
  }

  return wrapper.outerHTML;
}

	
	
	
	
	
	

    function renderExport() {
      const html = buildExportHtml();
      htmlOut.value = html;
      previewInner.innerHTML = html;
      setStatus('Exported');
      return html;
    }

    async function copyToClipboard(text) {
      try {
        await navigator.clipboard.writeText(text);
        setStatus('Copied');
      } catch {
        setStatus('Clipboard blocked by browser');
      }
    }

    function downloadText(filename, text) {
      const blob = new Blob([text], { type: 'text/html;charset=utf-8' });
      const url = URL.createObjectURL(blob);

      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();

      URL.revokeObjectURL(url);
      setStatus('Downloaded');
    }

    // Buttons
    document.getElementById('btnExportHtml').addEventListener('click', () => {
      renderExport();
    });

    document.getElementById('btnCopyHtml').addEventListener('click', async () => {
      const html = htmlOut.value.trim() || renderExport();
      await copyToClipboard(html);
    });

    document.getElementById('btnDownloadHtml').addEventListener('click', () => {
      const html = htmlOut.value.trim() || renderExport();
      const fullDoc =
`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Export</title>
</head>
<body>
${html}
</body>
</html>`;
      downloadText('export.html', fullDoc);
    });

    document.getElementById('btnClear').addEventListener('click', () => {
      for (const el of blocks) {
        if (!isMandatory(el)) el.classList.remove('selected');
      }
      setStatus('Cleared');
    });

    // Initial export once so you can see it immediately
    renderExport();
  </script>

</body>
</html>
