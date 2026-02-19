<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

<title>Live Projection Display</title>

<style>
  html, body {
	font-family: "EB Garamond", Garamond, serif;

    height: 100%;
    margin: 0;
    background: #fff;
    overflow: hidden;
  }

  body {
    -webkit-user-select: none;
    user-select: none;
    -webkit-touch-callout: none;
    touch-action: manipulation;
  }

  #stage {
    position: fixed;
    inset: 0;
    padding: 8px;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .layer {
    position: absolute;

    padding: 8px;
    box-sizing: border-box;

    white-space: pre-line;
    color: #000;
    background: #fff;

    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;

    -webkit-font-smoothing: antialiased;
    text-rendering: geometricPrecision;

    opacity: 0;
    transition: opacity 220ms ease-in-out;
  }

  .layer.visible { opacity: 1; }

  .text {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
  }


</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<div id="stage">
  <div id="layerA" class="layer visible"><div class="text" id="textA"></div></div>
  <div id="layerB" class="layer"><div class="text" id="textB"></div></div>
</div>



<script>
  // HARD FONT LIMITS (set these)
  const MAX_FONT_SIZE = 40; // px
  const MIN_FONT_SIZE = 3;  // px

  function requestFullscreen() {
    const el = document.documentElement;
    if (el.requestFullscreen) el.requestFullscreen();
    else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
  }

  document.addEventListener('click', () => requestFullscreen(), { passive: true });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'f' || e.key === 'F') requestFullscreen();
  });

  function fitTextToBox($textEl, $boxEl, opts = {}) {
    const min = opts.min || MIN_FONT_SIZE;
    const max = opts.max || MAX_FONT_SIZE;
    const stepTimeout = opts.timeoutMs || 12;

    const box = $boxEl[0];
    const text = $textEl[0];
    if (!box || !text) return;

    let lo = min, hi = max, best = min;

    $textEl.css({
      "word-break": "break-word",
      "overflow-wrap": "anywhere"
    });

    const fits = (size) => {
      $textEl.css("font-size", size + "px");
      void text.offsetHeight;
      return (text.scrollHeight <= box.clientHeight) && (text.scrollWidth <= box.clientWidth);
    };

    const start = performance.now();
    while (lo <= hi) {
      if (performance.now() - start > stepTimeout) break;

      const mid = Math.floor((lo + hi) / 2);
      if (fits(mid)) {
        best = mid;
        lo = mid + 1;
      } else {
        hi = mid - 1;
      }
    }

    // Final clamp (belt & braces)
    best = Math.max(min, Math.min(max, best));
    $textEl.css("font-size", best + "px");
  }

  let fitTimer = null;
  function scheduleRefit() {
    clearTimeout(fitTimer);
    fitTimer = setTimeout(() => {
      const usingA = $('#layerA').hasClass('visible');
      const $layer = usingA ? $('#layerA') : $('#layerB');
      const $text  = usingA ? $('#textA')  : $('#textB');

      fitTextToBox($text, $layer, {
        min: MIN_FONT_SIZE,
        max: MAX_FONT_SIZE,
        timeoutMs: 18
      });
    }, 80);
  }

  window.addEventListener('resize', scheduleRefit);
  window.addEventListener('orientationchange', scheduleRefit);

  function extractText(dataStr) {
    try {
      const obj = JSON.parse(dataStr);
      return (obj && typeof obj.text === 'string') ? obj.text : dataStr;
    } catch {
      return dataStr;
    }
  }

  function updateProjected(newTextRaw) {
    const newText = extractText(newTextRaw);

    const $layerA = $('#layerA'), $layerB = $('#layerB');
    const $textA  = $('#textA'),  $textB  = $('#textB');

    const aVisible = $layerA.hasClass('visible');

    const $showLayer = aVisible ? $layerB : $layerA;
    const $hideLayer = aVisible ? $layerA : $layerB;
    const $showText  = aVisible ? $textB  : $textA;

    $showText.text(newText);

    fitTextToBox($showText, $showLayer, {
      min: MIN_FONT_SIZE,
      max: MAX_FONT_SIZE,
      timeoutMs: 18
    });

    $showLayer.addClass('visible');
    $hideLayer.removeClass('visible');
  }

  const source = new EventSource('stream.php');

  source.addEventListener('projectedtext', function (e) {
    updateProjected(e.data);
  });

  source.onerror = function () {
    console.log('SSE connection problem (will auto-reconnect).');
  };

  scheduleRefit();
</script>

</body>
</html>
