<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Template Selector</title>

<style>
* {
    box-sizing: border-box;
}

body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    margin: 40px;
}

/* Hide radio buttons */
.tabset > input[type="radio"] {
    position: absolute;
    left: -200vw;
}

/* Tab label */
.tabset > label {
    display: inline-block;
    padding: 10px 18px;
    border: 1px solid #ccc;
    border-bottom: none;
    background: #f5f5f5;
    cursor: pointer;
}

/* Active tab */
.tabset > input:checked + label {
    background: #fff;
    border-bottom: 1px solid #fff;
}

/* Panel container */
.tab-panels {
    border: 1px solid #ccc;
    padding: 20px;
    background: #fff;
}

.tab-panel {
    display: none;
}

/* Show the panel */
#tab1:checked ~ .tab-panels #Template {
    display: block;
}

/* Template list styling */
.template-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.template-list li {
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-bottom: 10px;
    cursor: pointer;
}

.template-list li:hover {
    background: #f7f7f7;
}
</style>
</head>

<body>

<div class="tabset">
    <input type="radio" name="tabset" id="tab1" checked>
    <label for="tab1">Template</label>

    <div class="tab-panels">
        <section id="Template" class="tab-panel">

            <ul class="template-list" id="templateList">
                <li data-template="empty_communion_service.html">Communion Service</li>
                <li data-template="empty_extended_communion.html">Extended Communion</li>
                <li data-template="empty_morning_prayer.html">Morning Prayer</li>
                <li data-template="empty_all_age_worship.html">All-Age Worship</li>
                <li data-template="empty_diy_service.html">DIY Service</li>
            </ul>

        </section>
    </div>
</div>

<script>
  function goToTemplate(filename) {
    if (!filename) return;
    // filename is already safe, e.g. communion_service.txt
    window.location.href = "createtemplate.php?template=" + encodeURIComponent(filename);
  }

  document.querySelectorAll("#templateList li[data-template]")
    .forEach(li => {
      li.addEventListener("click", () => {
        goToTemplate(li.dataset.template);
      });
    });
</script>

</body>
</html>
