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
    margin-bottom: 10px;
}

.template-list li a {
    display: block;              /* whole row clickable */
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
}

.template-list li a:hover {
    background: #f7f7f7;
}
</style>
</head>

<body>

<div class="tabset">
    <input type="radio" name="tabset" id="tab1" checked>
    <label for="tab1">Select ...</label>

    <div class="tab-panels">
        <section id="Template" class="tab-panel">

            <ul class="template-list">
                <li>
                    <a href="controlservice">
                        Control the service
                    </a>
                </li>
                <li>
                    <a href="display">
                        Large screen display
                    </a>
                </li>
                <li>
                    <a href="createservice">
                       Create a service
                    </a>
                </li>
                <li>
                    <a href="pptupload">
                       Upload a PPT
                    </a>
                </li>
                <li>
                    <a href="notices">
                        View the notices
                    </a>
                </li>
            </ul>

        </section>
    </div>
</div>

</body>
</html>
