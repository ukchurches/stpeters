# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a church service management and live projection system built in pure PHP with MySQL. It enables church staff to create service templates, project content in real time, manage lectionary dates, and handle announcements.

## Architecture

The application lives in `/home/stpeters/public_html/` and is divided into feature directories. There is no build system — all PHP files are served directly by Apache.

**Database:** Configured in `config.php` (not tracked; copy `config.example.php` to set up). Database name is `stpeters`. Key tables include `saved_services`, `thisyeardates`, and `anglican_calendar`. Newer code uses PDO; older code uses MySQLi directly.

### Key Modules

- **`index.php`** — Landing page with tabbed navigation to all modules
- **`controlservice/`** — Main operator interface; reads from `saved_services` table and writes projection content to flat `.txt` files within this directory (`projectedtext.txt`, `projectedposture.txt`, `projectedfooter.txt`, `projectedmusic.txt`). These files are git-excluded runtime state.
- **`display/`** — Live projection display shown on screen/projector
  - `index.php` — Renders content with auto-fitting font sizes (binary search algorithm)
  - `stream.php` — Server-Sent Events (SSE) endpoint; polls text files every 250ms and pushes updates to the display
- **`createtemplate/`** — Service order editor using `contenteditable` HTML; saves templates as HTML files in `created_templates/`
- **`lectionary/`** — Church of England lectionary calendar viewer; parses date/scripture data from the `anglican_calendar` table
- **`pptupload/`** — Converts PowerPoint files to PNG slides via LibreOffice headless (`soffice`) + `pdftoppm`; tracks conversion status with JSON files
- **`notices/`** — Parses ICS/iCalendar feeds from A Church Near You (ACNY) for announcements
- **`ordersofservice/`** — Static liturgical service order templates (e.g. `communion.php`)
- **`selecttext.php`** — Block-based liturgical text selector with copy/download export
- **`autosizedisplay.php`** — Simplified projection display with dynamic font sizing

### Real-Time Display Flow

1. Operator uses `controlservice/` to select what to project
2. Selected content is written to flat `.txt` files in `display/`
3. `display/stream.php` (SSE) reads those files from `controlservice/`, detects changes via MD5, and pushes updates
4. `display/index.php` receives SSE events and re-renders content with JavaScript

### PPT Conversion Flow

1. User uploads `.pptx` via `pptupload/index.php`
2. PHP spawns background CLI: `soffice --headless --convert-to pdf`, then `pdftoppm`
3. Status tracked in a JSON file; frontend polls for completion
4. Resulting PNGs are served as slides

## Development

No build step is needed for the main application. Edit PHP files and refresh.

**phpMyAdmin** is bundled at `phpmyadmin/` (v5.2.3) as a third-party tool — avoid modifying it. Its own build/test commands are in its `package.json` (Yarn-based).

### Server Requirements

- PHP 7.2.5+ or 8.0+
- MySQL/MariaDB
- Apache with mod_rewrite
- LibreOffice headless (`soffice`) — required only for PPT upload feature
- `pdftoppm` (poppler-utils) — required only for PPT upload feature

### Frontend

- jQuery 3.2.1 (loaded from `assets/`)
- No transpilation or bundling; plain JS
- `cw-booklet.css` — Common Worship liturgical stylesheet
- `style.css` — General site styling
