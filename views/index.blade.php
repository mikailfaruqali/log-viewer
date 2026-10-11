<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ $logViewer->currentFile() ? $logViewer->currentFile() . ' · ' : '' }}Logs</title>
    <link
        rel="icon"
        type="image/svg+xml"
        href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Crect width='512' height='512' rx='96' fill='%23249658'/%3E%3Cg fill='%23fff'%3E%3Crect x='136' y='150' width='240' height='44' rx='22'/%3E%3Crect x='136' y='234' width='176' height='44' rx='22'/%3E%3Crect x='136' y='318' width='208' height='44' rx='22'/%3E%3C/g%3E%3C/svg%3E"
    />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap"
        rel="stylesheet"
    />
    <style>
        :root {
            --bg: #fafafa;
            --surface: #fff;
            --subtle: #f4f4f5;
            --border: #e4e4e7;
            --border-strong: #d4d4d8;
            --text: #18181b;
            --text-soft: #3f3f46;
            --muted: #71717a;
            --faint: #a1a1aa;
            --ink: #18181b;
            --ink-hover: #3f3f46;
            --brand: #249658;
            --brand-bg: #f0fdf4;

            --success: #16a34a;
            --success-bg: #f0fdf4;
            --success-line: #bbf7d0;
            --warning: #d97706;
            --warning-bg: #fffbeb;
            --warning-line: #fde68a;
            --error: #dc2626;
            --error-bg: #fef2f2;
            --error-line: #fecaca;
            --info: #2563eb;
            --info-bg: #eff6ff;
            --info-line: #bfdbfe;
            --debug: #71717a;
            --debug-bg: #f4f4f5;
            --debug-line: #e4e4e7;

            --sidebar-w: 272px;
            --topbar-h: 60px;
            --radius: 10px;
            --radius-sm: 6px;
            --shadow: 0 1px 2px rgba(24, 24, 27, .04), 0 1px 3px rgba(24, 24, 27, .06);
            --shadow-lg: 0 10px 30px rgba(24, 24, 27, .12), 0 2px 6px rgba(24, 24, 27, .06);
            --sans: 'Geist', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            --mono: 'Geist Mono', ui-monospace, 'SFMono-Regular', Consolas, monospace;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
        }

        html.is-locked {
            overflow: hidden;
        }

        body {
            min-height: 100vh;
            min-height: 100dvh;
            background: var(--bg);
            color: var(--text);
            font-family: var(--sans);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        h1,
        h2,
        p {
            margin: 0;
        }

        button,
        input {
            font: inherit;
            color: inherit;
        }

        :focus-visible {
            outline: 2px solid var(--brand);
            outline-offset: 2px;
        }

        .i {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .tone-error { --tone: var(--error); --tone-bg: var(--error-bg); --tone-line: var(--error-line); }
        .tone-warning { --tone: var(--warning); --tone-bg: var(--warning-bg); --tone-line: var(--warning-line); }
        .tone-info { --tone: var(--info); --tone-bg: var(--info-bg); --tone-line: var(--info-line); }
        .tone-debug { --tone: var(--debug); --tone-bg: var(--debug-bg); --tone-line: var(--debug-line); }
        .tone-success { --tone: var(--success); --tone-bg: var(--success-bg); --tone-line: var(--success-line); }

        /* Layout: the document scrolls; the sidebar and top bar stay pinned. */
        .layout {
            display: grid;
            grid-template-columns: var(--sidebar-w) minmax(0, 1fr);
            min-height: 100vh;
            min-height: 100dvh;
        }

        /* Sidebar */
        .sidebar {
            position: sticky;
            top: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh;
            background: var(--surface);
            border-right: 1px solid var(--border);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
            height: var(--topbar-h);
            padding: 0 18px;
            border-bottom: 1px solid var(--border);
            font-size: 15px;
            font-weight: 600;
            letter-spacing: -.01em;
        }

        .brand__mark {
            display: block;
            width: 28px;
            height: 28px;
        }

        .sidebar__head {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 16px 18px 8px;
        }

        .sidebar__title {
            color: var(--muted);
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 20px;
            padding: 0 7px;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--surface);
            color: var(--text-soft);
            font-size: 12px;
            font-weight: 500;
            line-height: 1;
            font-variant-numeric: tabular-nums;
        }

        .files {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 0 10px 16px;
        }

        .file {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            margin-bottom: 2px;
            border-radius: var(--radius-sm);
            color: var(--text-soft);
            transition: background-color .15s, color .15s;
        }

        .file:hover {
            background: var(--subtle);
            color: var(--text);
        }

        .file.is-active {
            background: var(--brand-bg);
            color: var(--text);
        }

        .file.is-active::before {
            content: '';
            position: absolute;
            top: 8px;
            bottom: 8px;
            left: 0;
            width: 3px;
            border-radius: 3px;
            background: var(--brand);
        }

        .file .i {
            color: var(--faint);
        }

        .file.is-active .i {
            color: var(--brand);
        }

        .file__body {
            flex: 1;
            min-width: 0;
        }

        .file__name {
            display: block;
            overflow: hidden;
            font-family: var(--mono);
            font-size: 12.5px;
            font-weight: 500;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .file__meta {
            display: block;
            color: var(--faint);
            font-size: 11.5px;
        }

        .sidebar__empty {
            padding: 24px 18px;
            color: var(--muted);
            font-size: 13px;
        }

        /* Top bar */
        .main {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255, 255, 255, .85);
            border-bottom: 1px solid var(--border);
            backdrop-filter: saturate(180%) blur(8px);
            -webkit-backdrop-filter: saturate(180%) blur(8px);
        }

        .topbar__inner {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: var(--topbar-h);
            padding: 0 24px;
        }

        .topbar__title {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            font-family: var(--mono);
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .topbar__title.is-placeholder {
            color: var(--muted);
            font-family: var(--sans);
        }

        .topbar__actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search > .i {
            position: absolute;
            left: 10px;
            width: 14px;
            height: 14px;
            color: var(--faint);
            pointer-events: none;
        }

        .search__input {
            width: 280px;
            height: 34px;
            padding: 0 34px 0 32px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface);
            font-size: 13px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .search__input::placeholder {
            color: var(--faint);
        }

        .search__input:focus {
            border-color: var(--border-strong);
            box-shadow: 0 0 0 3px rgba(36, 150, 88, .12);
        }

        .search__input::-webkit-search-cancel-button {
            display: none;
        }

        .search__clear {
            position: absolute;
            right: 4px;
        }

        .kbd {
            position: absolute;
            right: 8px;
            padding: 0 5px;
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--faint);
            font-family: var(--mono);
            font-size: 11px;
            line-height: 17px;
            pointer-events: none;
        }

        .search__input:focus ~ .kbd {
            display: none;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            height: 34px;
            padding: 0 14px;
            border: 1px solid transparent;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: background-color .15s, border-color .15s, color .15s;
        }

        .btn .i {
            width: 14px;
            height: 14px;
        }

        .btn-dark {
            background: var(--ink);
            color: #fff;
        }

        .btn-dark:hover {
            background: var(--ink-hover);
        }

        .btn-danger {
            background: var(--error);
            color: #fff;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-outline {
            background: var(--surface);
            border-color: var(--border);
            color: var(--text-soft);
        }

        .btn-outline:hover {
            border-color: var(--border-strong);
            color: var(--text);
        }

        .btn-outline.is-danger:hover {
            border-color: var(--error-line);
            background: var(--error-bg);
            color: var(--error);
        }

        .btn-icon {
            width: 34px;
            padding: 0;
        }

        .btn-ghost {
            width: 28px;
            height: 28px;
            padding: 0;
            background: transparent;
            color: var(--muted);
        }

        .btn-ghost:hover {
            background: var(--subtle);
            color: var(--text);
        }

        .btn-ghost.is-copied,
        .btn-copy.is-copied {
            color: var(--success);
        }

        .btn-copy {
            height: 26px;
            padding: 0 8px;
            border-color: var(--border);
            background: var(--surface);
            color: var(--muted);
            font-size: 12px;
        }

        .btn-copy:hover {
            border-color: var(--border-strong);
            color: var(--text);
        }

        .btn-copy .i {
            width: 13px;
            height: 13px;
        }

        .menu-btn {
            display: none;
        }

        /* Page */
        .content {
            flex: 1;
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            padding: 28px 24px 48px;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 11px 14px;
            margin-bottom: 20px;
            border: 1px solid var(--tone-line);
            border-radius: var(--radius-sm);
            background: var(--tone-bg);
            color: var(--tone);
            font-weight: 500;
            overflow-wrap: anywhere;
        }

        .alert .i {
            margin-top: 2px;
        }

        /* Summary */
        .summary {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 20px 22px;
            margin-bottom: 28px;
        }

        .summary__main {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .summary__title {
            overflow: hidden;
            font-family: var(--mono);
            font-size: 16px;
            font-weight: 500;
            letter-spacing: -.01em;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .summary__text {
            margin-top: 2px;
            color: var(--muted);
        }

        .chip {
            display: grid;
            flex-shrink: 0;
            place-items: center;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--brand-bg);
            color: var(--brand);
            box-shadow: inset 0 0 0 1px var(--success-line);
        }

        .chip .i {
            width: 20px;
            height: 20px;
        }

        .stats {
            display: flex;
            gap: 4px;
        }

        .stat {
            min-width: 84px;
            padding: 6px 12px;
            border: 1px solid transparent;
            border-radius: var(--radius-sm);
            transition: background-color .15s, border-color .15s;
        }

        .stat:hover {
            background: var(--subtle);
        }

        .stat.is-active {
            border-color: var(--tone-line);
            background: var(--tone-bg);
        }

        .stat__value {
            font-family: var(--mono);
            font-size: 22px;
            font-weight: 500;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .stat__value.is-zero {
            color: var(--faint);
        }

        .stat__label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
        }

        .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--tone, var(--faint));
        }

        /* List */
        .list-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin: 0 2px 10px;
        }

        .list-head__title {
            font-size: 14px;
            font-weight: 600;
        }

        .list-head__text {
            color: var(--muted);
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .list-head__clear {
            margin-left: auto;
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
        }

        .list-head__clear:hover {
            color: var(--text);
        }

        .list {
            overflow: hidden;
        }

        .list > * + * {
            border-top: 1px solid var(--border);
        }

        /* Entry */
        .entry > summary {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 12px 12px 16px;
            list-style: none;
            cursor: pointer;
            box-shadow: inset 3px 0 0 var(--tone);
            transition: background-color .15s;
        }

        .entry > summary::-webkit-details-marker {
            display: none;
        }

        .entry > summary:hover,
        .entry[open] > summary {
            background: #fcfcfd;
        }

        .entry__body {
            flex: 1;
            min-width: 0;
        }

        .entry__meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .level {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            height: 22px;
            padding: 0 8px;
            border: 1px solid var(--tone-line);
            border-radius: 999px;
            background: var(--tone-bg);
            color: var(--tone);
            font-size: 11.5px;
            font-weight: 500;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .level .i {
            width: 12px;
            height: 12px;
            stroke-width: 2.5;
        }

        .time {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--muted);
            font-family: var(--mono);
            font-size: 12px;
            white-space: nowrap;
        }

        .time .i {
            width: 13px;
            height: 13px;
            color: var(--faint);
        }

        .env {
            height: 20px;
            padding: 0 6px;
            border: 1px solid var(--border);
            border-radius: 4px;
            background: var(--subtle);
            color: var(--text-soft);
            font-family: var(--mono);
            font-size: 11.5px;
            line-height: 18px;
        }

        .entry__message {
            display: -webkit-box;
            overflow: hidden;
            color: var(--text);
            font-weight: 500;
            line-height: 1.55;
            overflow-wrap: anywhere;
            white-space: pre-line;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .entry__tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .tag {
            display: inline-flex;
            max-width: 100%;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface);
            font-family: var(--mono);
            font-size: 11.5px;
            line-height: 20px;
        }

        .tag__key {
            flex-shrink: 0;
            padding: 0 6px;
            border-right: 1px solid var(--border);
            background: var(--subtle);
            color: var(--muted);
        }

        .tag__value {
            min-width: 0;
            max-width: 320px;
            overflow: hidden;
            padding: 0 7px;
            color: var(--text-soft);
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .entry__actions {
            display: flex;
            align-items: center;
            gap: 2px;
            flex-shrink: 0;
        }

        .caret {
            color: var(--faint);
            transition: transform .2s;
        }

        .entry[open] .caret {
            transform: rotate(180deg);
        }

        .entry > summary:hover .caret {
            color: var(--text);
        }

        /* Entry detail */
        .detail {
            padding: 4px 16px 16px 19px;
            box-shadow: inset 3px 0 0 var(--tone);
            background: #fcfcfd;
        }

        .section + .section {
            margin-top: 14px;
        }

        .section__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 6px;
        }

        .section__title {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
            color: var(--muted);
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .04em;
            text-transform: uppercase;
            overflow-wrap: anywhere;
        }

        .section__title .i {
            width: 13px;
            height: 13px;
        }

        .code {
            margin: 0;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--text-soft);
            font-family: var(--mono);
            font-size: 12.5px;
            line-height: 1.65;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            tab-size: 4;
        }

        .trace-exception { color: #b91c1c; font-weight: 500; }
        .trace-caused { color: #b45309; font-weight: 500; }
        .trace-at { color: var(--muted); }
        .trace-frame { color: var(--text-soft); }
        .trace-vendor { color: var(--faint); }

        .json-key { color: #1d4ed8; }
        .json-str { color: #15803d; }
        .json-num { color: #c2410c; }
        .json-bool { color: #7c3aed; font-weight: 500; }
        .json-null { color: var(--faint); font-style: italic; }
        .json-punct { color: var(--faint); }

        mark {
            padding: 0 2px;
            border-radius: 3px;
            background: #fef08a;
            color: inherit;
        }

        /* Pagination */
        .pager {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 14px;
        }

        .pager__text {
            color: var(--muted);
            font-size: 13px;
            font-variant-numeric: tabular-nums;
        }

        .pager__actions {
            display: flex;
            gap: 8px;
        }

        .btn[aria-disabled='true'] {
            opacity: .45;
            pointer-events: none;
        }

        /* Empty */
        .empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 56px 20px;
            color: var(--muted);
            text-align: center;
        }

        .empty__icon {
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            margin-bottom: 6px;
            border-radius: 50%;
            background: var(--subtle);
            color: var(--faint);
        }

        .empty__title {
            color: var(--text);
            font-weight: 600;
        }

        /* Dialog */
        .dialog {
            width: min(420px, calc(100vw - 32px));
            padding: 0;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface);
            color: var(--text);
            box-shadow: var(--shadow-lg);
        }

        .dialog::backdrop {
            background: rgba(24, 24, 27, .35);
            backdrop-filter: blur(2px);
        }

        .dialog__body {
            display: flex;
            gap: 14px;
            padding: 22px 22px 18px;
        }

        .dialog__icon {
            display: grid;
            flex-shrink: 0;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--error-bg);
            color: var(--error);
            box-shadow: inset 0 0 0 1px var(--error-line);
        }

        .dialog__title {
            font-size: 15px;
            font-weight: 600;
        }

        .dialog__text {
            margin-top: 4px;
            color: var(--muted);
            overflow-wrap: anywhere;
        }

        code {
            padding: 1px 5px;
            border-radius: 4px;
            background: var(--subtle);
            color: var(--text);
            font-family: var(--mono);
            font-size: 12.5px;
        }

        .dialog__actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 14px 22px;
            border-top: 1px solid var(--border);
            background: var(--bg);
            border-radius: 0 0 var(--radius) var(--radius);
        }

        /* Footer */
        footer {
            padding: 0 0 24px;
            color: var(--faint);
            font-size: 12px;
            text-align: center;
        }

        footer a {
            color: var(--muted);
        }

        footer a:hover {
            color: var(--text);
        }

        .backdrop {
            display: none;
        }

        @media (max-width: 1100px) {
            .search__input {
                width: 220px;
            }
        }

        @media (max-width: 900px) {
            .layout {
                display: block;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                z-index: 40;
                width: min(var(--sidebar-w), 85vw);
                box-shadow: var(--shadow-lg);
                transform: translateX(-105%);
                transition: transform .2s ease;
                visibility: hidden;
            }

            .sidebar.is-open {
                transform: none;
                visibility: visible;
            }

            .backdrop {
                position: fixed;
                inset: 0;
                z-index: 30;
                background: rgba(24, 24, 27, .3);
            }

            .backdrop.is-open {
                display: block;
            }

            .menu-btn {
                display: inline-flex;
            }
        }

        @media (max-width: 640px) {
            .topbar__inner {
                flex-wrap: wrap;
                gap: 8px;
                padding: 10px 14px;
            }

            .search {
                order: 3;
                flex-basis: 100%;
            }

            .search__input {
                width: 100%;
            }

            .kbd {
                display: none;
            }

            .content {
                padding: 18px 14px 40px;
            }

            .summary {
                padding: 16px;
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                width: 100%;
                padding-top: 12px;
                border-top: 1px solid var(--border);
            }

            .stat {
                min-width: 0;
                padding: 6px 8px;
            }

            .stat__value {
                font-size: 18px;
            }

            .entry > summary {
                gap: 8px;
                padding: 12px 10px 12px 14px;
            }

            .detail {
                padding: 4px 12px 14px 15px;
            }

            .tag__value {
                max-width: 160px;
            }

            .time .i {
                display: none;
            }

            .pager__text {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <svg width="0" height="0" style="position: absolute" aria-hidden="true">
        <symbol id="i-app" viewBox="0 0 512 512">
            <rect width="512" height="512" rx="96" fill="#249658" />
            <g fill="#fff">
                <rect x="136" y="150" width="240" height="44" rx="22" />
                <rect x="136" y="234" width="176" height="44" rx="22" />
                <rect x="136" y="318" width="208" height="44" rx="22" />
            </g>
        </symbol>
        <symbol id="i-file" viewBox="0 0 24 24">
            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
            <path d="M14 2v4a2 2 0 0 0 2 2h4" />
            <path d="M16 13H8" />
            <path d="M16 17H8" />
        </symbol>
        <symbol id="i-search" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="8" />
            <path d="m21 21-4.3-4.3" />
        </symbol>
        <symbol id="i-x" viewBox="0 0 24 24">
            <path d="M18 6 6 18" />
            <path d="m6 6 12 12" />
        </symbol>
        <symbol id="i-download" viewBox="0 0 24 24">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
            <path d="m7 10 5 5 5-5" />
            <path d="M12 15V3" />
        </symbol>
        <symbol id="i-trash" viewBox="0 0 24 24">
            <path d="M3 6h18" />
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
        </symbol>
        <symbol id="i-down" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6" /></symbol>
        <symbol id="i-left" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6" /></symbol>
        <symbol id="i-right" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" /></symbol>
        <symbol id="i-copy" viewBox="0 0 24 24">
            <rect x="9" y="9" width="13" height="13" rx="2" />
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
        </symbol>
        <symbol id="i-check" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5" /></symbol>
        <symbol id="i-menu" viewBox="0 0 24 24">
            <path d="M4 6h16" />
            <path d="M4 12h16" />
            <path d="M4 18h16" />
        </symbol>
        <symbol id="i-error" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <path d="m15 9-6 6" />
            <path d="m9 9 6 6" />
        </symbol>
        <symbol id="i-warning" viewBox="0 0 24 24">
            <path d="M12 9v4" />
            <path d="M12 17h.01" />
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
        </symbol>
        <symbol id="i-info" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <path d="M12 16v-4" />
            <path d="M12 8h.01" />
        </symbol>
        <symbol id="i-debug" viewBox="0 0 24 24">
            <path d="m16 18 6-6-6-6" />
            <path d="m8 6-6 6 6 6" />
        </symbol>
        <symbol id="i-success" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <path d="m9 12 2 2 4-4" />
        </symbol>
        <symbol id="i-layers" viewBox="0 0 24 24">
            <path d="M12 2 2 7l10 5 10-5-10-5Z" />
            <path d="m2 17 10 5 10-5" />
            <path d="m2 12 10 5 10-5" />
        </symbol>
        <symbol id="i-inbox" viewBox="0 0 24 24">
            <path d="M22 12h-6l-2 3h-4l-2-3H2" />
            <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
        </symbol>
        <symbol id="i-message" viewBox="0 0 24 24">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
        </symbol>
        <symbol id="i-braces" viewBox="0 0 24 24">
            <path d="M8 3H7a2 2 0 0 0-2 2v5a2 2 0 0 1-2 2 2 2 0 0 1 2 2v5c0 1.1.9 2 2 2h1" />
            <path d="M16 21h1a2 2 0 0 0 2-2v-5c0-1.1.9-2 2-2a2 2 0 0 1-2-2V5a2 2 0 0 0-2-2h-1" />
        </symbol>
        <symbol id="i-clock" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
        </symbol>
    </svg>

    <div class="layout">
        <aside class="sidebar" id="sidebar" aria-label="Log files">
            <a href="{{ $logViewer->indexUrl() }}" class="brand">
                <svg class="brand__mark" aria-hidden="true"><use href="#i-app" /></svg>
                Logs
            </a>

            <div class="sidebar__head">
                <span class="sidebar__title">Files</span>
                <span class="count">{{ $logViewer->fileCount() }}</span>
            </div>

            <nav class="files">
                @forelse ($logViewer->files() as $file)
                    <a
                        href="{{ $file['url'] }}"
                        @class(['file', 'is-active' => $file['active']])
                        title="{{ $file['name'] }}"
                        @if ($file['active']) aria-current="page" @endif
                    >
                        <svg class="i"><use href="#i-file" /></svg>
                        <span class="file__body">
                            <span class="file__name">{{ $file['name'] }}</span>
                            <span class="file__meta">{{ $file['size'] }} · {{ $file['modified'] }}</span>
                        </span>
                    </a>
                @empty
                    <p class="sidebar__empty">No log files found.</p>
                @endforelse
            </nav>
        </aside>

        <div class="backdrop" id="backdrop"></div>

        <div class="main">
            <header class="topbar">
                <div class="topbar__inner">
                    <button
                        type="button"
                        class="btn btn-outline btn-icon menu-btn"
                        id="menuButton"
                        title="Files"
                        aria-controls="sidebar"
                        aria-expanded="false"
                    >
                        <svg class="i"><use href="#i-menu" /></svg>
                    </button>

                    @if ($logViewer->hasCurrentFile())
                        <span
                            class="topbar__title"
                            title="{{ $logViewer->currentFile() }}"
                        >{{ $logViewer->currentFile() }}</span>

                        <form action="{{ $logViewer->indexUrl() }}" method="GET" class="search" role="search">
                            <svg class="i"><use href="#i-search" /></svg>
                            <input type="hidden" name="file" value="{{ $logViewer->currentFile() }}" />
                            @if ($logViewer->level())
                                <input type="hidden" name="level" value="{{ $logViewer->level() }}" />
                            @endif
                            <input
                                type="search"
                                name="search"
                                id="searchInput"
                                class="search__input"
                                value="{{ $logViewer->search() }}"
                                placeholder="Search this log…"
                                autocomplete="off"
                                aria-label="Search log entries"
                            />
                            @if ($logViewer->search())
                                <a
                                    href="{{ $logViewer->clearSearchUrl() }}"
                                    class="btn btn-ghost search__clear"
                                    title="Clear search"
                                >
                                    <svg class="i"><use href="#i-x" /></svg>
                                </a>
                            @else
                                <span class="kbd" aria-hidden="true">/</span>
                            @endif
                        </form>

                        <div class="topbar__actions">
                            <a
                                href="{{ $logViewer->downloadUrl() }}"
                                class="btn btn-outline btn-icon"
                                title="Download log"
                            >
                                <svg class="i"><use href="#i-download" /></svg>
                            </a>
                            <button
                                type="button"
                                class="btn btn-outline btn-icon is-danger"
                                id="deleteButton"
                                title="Delete log"
                            >
                                <svg class="i"><use href="#i-trash" /></svg>
                            </button>
                        </div>
                    @else
                        <span class="topbar__title is-placeholder">Select a log file</span>
                    @endif
                </div>
            </header>

            <main class="content">
                @if ($flash = $logViewer->flash())
                    <div class="alert tone-{{ $flash['tone'] }}" role="status">
                        <svg class="i"><use href="#i-{{ $flash['tone'] }}" /></svg>
                        <span>{{ $flash['message'] }}</span>
                    </div>
                @endif

                @if ($logViewer->hasCurrentFile())
                    <section class="card summary">
                        <div class="summary__main">
                            <span class="chip"
                                ><svg class="i"><use href="#i-layers" /></svg
                            ></span>
                            <div style="min-width: 0">
                                <h1 class="summary__title" title="{{ $logViewer->currentFile() }}">
                                    {{ $logViewer->currentFile() }}
                                </h1>
                                <p class="summary__text">{{ $logViewer->currentFileMeta() }}</p>
                            </div>
                        </div>

                        <nav class="stats" aria-label="Filter by level">
                            @foreach ($logViewer->stats() as $stat)
                                <a
                                    href="{{ $stat['url'] }}"
                                    @class(['stat', 'tone-' . $stat['tone'], 'is-active' => $stat['active']])
                                    title="{{ $stat['active'] ? 'Show all levels' : 'Show only ' . strtolower($stat['label']) }}"
                                >
                                    <div @class(['stat__value', 'is-zero' => $stat['value'] === 0])>
                                        {{ number_format($stat['value']) }}
                                    </div>
                                    <div class="stat__label"><span class="dot"></span>{{ $stat['label'] }}</div>
                                </a>
                            @endforeach
                        </nav>
                    </section>

                    <div class="list-head">
                        <h2 class="list-head__title">{{ $logViewer->listTitle() }}</h2>
                        <span class="list-head__text">{{ $logViewer->resultText() }}</span>
                        @if ($logViewer->isFiltered())
                            <a href="{{ $logViewer->clearFiltersUrl() }}" class="list-head__clear">Clear filters</a>
                        @endif
                    </div>
                @endif

                <div class="card list">
                    @forelse ($logViewer->entries() as $index => $entry)
                        @php($tone = $entry->levelGroup())
                        <details class="entry tone-{{ $tone }}" id="entry-{{ $index }}">
                            <summary>
                                <div class="entry__body">
                                    <div class="entry__meta">
                                        <span class="level">
                                            <svg class="i"><use href="#i-{{ $tone }}" /></svg>{{ $entry->level }}
                                        </span>
                                        <span class="time" title="{{ $entry->timestamp }}">
                                            <svg class="i"><use href="#i-clock" /></svg
                                            >{{ $entry->getFormattedTimestamp() }}
                                        </span>
                                        @if ($entry->environment !== 'production')
                                            <span class="env">{{ $entry->environment }}</span>
                                        @endif
                                    </div>

                                    <div class="entry__message">
                                        {!! $entry->highlightSearchTerm($entry->message, $logViewer->search()) !!}
                                    </div>

                                    @if ($entry->hasContext())
                                        <div class="entry__tags">
                                            @foreach ($entry->getContext() as $key => $value)
                                                <span class="tag" title="{{ $key }}: {{ $value }}">
                                                    <span class="tag__key">{{ $key }}</span>
                                                    <span class="tag__value">{!! $entry->highlightSearchTerm($value, $logViewer->search()) !!}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="entry__actions">
                                    <button
                                        type="button"
                                        class="btn btn-ghost"
                                        data-copy-entry="entry-{{ $index }}"
                                        title="Copy entry"
                                    >
                                        <svg class="i"><use href="#i-copy" /></svg>
                                    </button>
                                    <svg class="i caret" aria-hidden="true"><use href="#i-down" /></svg>
                                </div>
                            </summary>

                            <div class="detail">
                                <div class="section">
                                    <div class="section__head">
                                        <span class="section__title"><svg class="i"><use href="#i-message" /></svg>Message</span>
                                        <button
                                            type="button"
                                            class="btn btn-copy"
                                            data-copy="entry-{{ $index }}-message"
                                        >
                                            <svg class="i"><use href="#i-copy" /></svg>Copy
                                        </button>
                                    </div>
                                    <pre
                                        class="code"
                                        id="entry-{{ $index }}-message"
                                        data-copy-label="Message"
                                    >{{ $entry->message }}</pre>
                                </div>

                                @if ($entry->hasContext())
                                    <div class="section">
                                        <div class="section__head">
                                            <span class="section__title"><svg class="i"><use href="#i-braces" /></svg>Context</span>
                                            <button
                                                type="button"
                                                class="btn btn-copy"
                                                data-copy="entry-{{ $index }}-context"
                                            >
                                                <svg class="i"><use href="#i-copy" /></svg>Copy
                                            </button>
                                        </div>
                                        <pre
                                            class="code"
                                            id="entry-{{ $index }}-context"
                                            data-copy-label="Context"
                                        >{!! $logViewer->json($entry->getContext()) !!}</pre>
                                    </div>
                                @endif

                                @foreach ($entry->getLongContext() as $label => $value)
                                    <div class="section">
                                        <div class="section__head">
                                            <span class="section__title"><svg class="i"><use href="#i-braces" /></svg>{{ $label }}</span>
                                            <button
                                                type="button"
                                                class="btn btn-copy"
                                                data-copy="entry-{{ $index }}-section-{{ $loop->index }}"
                                            >
                                                <svg class="i"><use href="#i-copy" /></svg>Copy
                                            </button>
                                        </div>
                                        @if (is_array($value))
                                            <pre
                                                class="code"
                                                id="entry-{{ $index }}-section-{{ $loop->index }}"
                                                data-copy-label="{{ $label }}"
                                            >{!! $logViewer->json($value) !!}</pre>
                                        @else
                                            <pre
                                                class="code"
                                                id="entry-{{ $index }}-section-{{ $loop->index }}"
                                                data-copy-label="{{ $label }}"
                                            >{{ $value }}</pre>
                                        @endif
                                    </div>
                                @endforeach

                                @if ($entry->hasStackTrace())
                                    <div class="section">
                                        <div class="section__head">
                                            <span class="section__title"><svg class="i"><use href="#i-error" /></svg>Stack trace</span>
                                            <button
                                                type="button"
                                                class="btn btn-copy"
                                                data-copy="entry-{{ $index }}-trace"
                                            >
                                                <svg class="i"><use href="#i-copy" /></svg>Copy
                                            </button>
                                        </div>
                                        <pre
                                            class="code"
                                            id="entry-{{ $index }}-trace"
                                            data-copy-label="Stack trace"
                                        >{!! $logViewer->stackTrace($entry->stackTrace) !!}</pre>
                                    </div>
                                @endif

                                @if ($entry->hasExtra())
                                    <div class="section">
                                        <div class="section__head">
                                            <span class="section__title"><svg class="i"><use href="#i-braces" /></svg>Extra</span>
                                            <button
                                                type="button"
                                                class="btn btn-copy"
                                                data-copy="entry-{{ $index }}-extra"
                                            >
                                                <svg class="i"><use href="#i-copy" /></svg>Copy
                                            </button>
                                        </div>
                                        <pre
                                            class="code"
                                            id="entry-{{ $index }}-extra"
                                            data-copy-label="Extra"
                                        >{{ $entry->extra }}</pre>
                                    </div>
                                @endif
                            </div>
                        </details>
                    @empty
                        @php($empty = $logViewer->emptyState())
                        <div class="empty">
                            <span class="empty__icon"
                                ><svg class="i"><use href="#i-inbox" /></svg
                            ></span>
                            <p class="empty__title">{{ $empty['title'] }}</p>
                            <p>{{ $empty['text'] }}</p>
                        </div>
                    @endforelse
                </div>

                @if ($logViewer->hasPages())
                    <nav class="pager" aria-label="Pagination">
                        <span class="pager__text">{{ $logViewer->pageSummary() }}</span>
                        <div class="pager__actions">
                            <a
                                href="{{ $logViewer->previousPageUrl() ?? '#' }}"
                                class="btn btn-outline"
                                @if (! $logViewer->previousPageUrl()) aria-disabled="true" tabindex="-1" @endif
                            >
                                <svg class="i"><use href="#i-left" /></svg>Newer
                            </a>
                            <a
                                href="{{ $logViewer->nextPageUrl() ?? '#' }}"
                                class="btn btn-outline"
                                @if (! $logViewer->nextPageUrl()) aria-disabled="true" tabindex="-1" @endif
                            >
                                Older<svg class="i"><use href="#i-right" /></svg>
                            </a>
                        </div>
                    </nav>
                @endif
            </main>

            <footer>
                &copy; {{ date('Y') }} &middot;
                <a href="https://snawbar.com" target="_blank" rel="noopener">Snawbar</a>
            </footer>
        </div>
    </div>

    @if ($logViewer->hasCurrentFile())
        <dialog class="dialog" id="deleteDialog" aria-labelledby="deleteDialogTitle">
            <form action="{{ $logViewer->deleteUrl() }}" method="POST">
                @csrf
                @method('DELETE')
                <input type="hidden" name="file" value="{{ $logViewer->currentFile() }}" />

                <div class="dialog__body">
                    <span class="dialog__icon"
                        ><svg class="i"><use href="#i-trash" /></svg
                    ></span>
                    <div style="min-width: 0">
                        <h2 class="dialog__title" id="deleteDialogTitle">Delete log file?</h2>
                        <p class="dialog__text">
                            <code>{{ $logViewer->currentFile() }}</code> will be permanently removed. This cannot be
                            undone.
                        </p>
                    </div>
                </div>

                <div class="dialog__actions">
                    <button type="button" class="btn btn-outline" data-close-dialog>Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <svg class="i"><use href="#i-trash" /></svg>Delete
                    </button>
                </div>
            </form>
        </dialog>
    @endif

    <script>
        (() => {
            const root = document.documentElement;
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('backdrop');
            const menuButton = document.getElementById('menuButton');

            const setDrawer = (open) => {
                sidebar.classList.toggle('is-open', open);
                backdrop.classList.toggle('is-open', open);
                root.classList.toggle('is-locked', open);
                menuButton.setAttribute('aria-expanded', String(open));
            };

            menuButton.addEventListener('click', () => setDrawer(!sidebar.classList.contains('is-open')));
            backdrop.addEventListener('click', () => setDrawer(false));
            window
                .matchMedia('(min-width: 901px)')
                .addEventListener('change', (event) => event.matches && setDrawer(false));

            // Keep the active file visible inside the sidebar list.
            sidebar.querySelector('.file.is-active')?.scrollIntoView({ block: 'nearest' });

            // Copy helpers (clipboard API needs HTTPS, so fall back to execCommand).
            const writeClipboard = (text) => {
                if (navigator.clipboard && window.isSecureContext) {
                    return navigator.clipboard.writeText(text);
                }

                return new Promise((resolve, reject) => {
                    const area = document.createElement('textarea');
                    area.value = text;
                    area.setAttribute('readonly', '');
                    area.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
                    document.body.appendChild(area);
                    area.select();
                    const copied = document.execCommand('copy');
                    area.remove();
                    copied ? resolve() : reject(new Error('Copy failed'));
                });
            };

            const flashCopied = (button) => {
                const icon = button.querySelector('use');
                icon?.setAttribute('href', '#i-check');
                button.classList.add('is-copied');
                clearTimeout(button.copyTimer);
                button.copyTimer = setTimeout(() => {
                    icon?.setAttribute('href', '#i-copy');
                    button.classList.remove('is-copied');
                }, 1500);
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-copy], [data-copy-entry]');

                if (!button) {
                    return;
                }

                // Buttons live inside <summary>; don't toggle the entry.
                event.preventDefault();
                event.stopPropagation();

                let text = '';

                if (button.dataset.copy) {
                    text = document.getElementById(button.dataset.copy)?.textContent ?? '';
                } else {
                    const entry = document.getElementById(button.dataset.copyEntry);
                    const meta = entry?.querySelector('.entry__meta')?.innerText.replace(/\s+/g, ' ').trim() ?? '';

                    text = [
                        meta,
                        ...Array.from(entry?.querySelectorAll('[data-copy-label]') ?? []).map(
                            (block) => `${block.dataset.copyLabel.toUpperCase()}:\n${block.textContent}`,
                        ),
                    ].join('\n\n');
                }

                writeClipboard(text)
                    .then(() => flashCopied(button))
                    .catch(() => {});
            });

            // Search: "/" focuses the box, Escape leaves it.
            const searchInput = document.getElementById('searchInput');

            document.addEventListener('keydown', (event) => {
                const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName ?? '');

                if (event.key === '/' && !typing && searchInput) {
                    event.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                } else if (event.key === 'Escape') {
                    if (document.activeElement === searchInput) {
                        searchInput.blur();
                    }

                    setDrawer(false);
                }
            });

            // Delete confirmation.
            const deleteDialog = document.getElementById('deleteDialog');

            document.getElementById('deleteButton')?.addEventListener('click', () => {
                if (typeof deleteDialog?.showModal === 'function') {
                    deleteDialog.showModal();
                } else if (confirm('Delete this log file? This cannot be undone.')) {
                    deleteDialog?.querySelector('form')?.submit();
                }
            });

            deleteDialog?.querySelector('[data-close-dialog]')?.addEventListener('click', () => deleteDialog.close());
            deleteDialog?.addEventListener('click', (event) => event.target === deleteDialog && deleteDialog.close());
        })();
    </script>
</body>
</html>
