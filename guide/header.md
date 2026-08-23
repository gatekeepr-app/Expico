# includes/header.php — Page Header

## What it does
Opens the HTML document, includes the sidebar, and renders the top header bar with navigation and user info. Every page includes this file.

## How it works in code
1. **Variables** (set by each page before including):
   - `$pageTitle` — shown in the browser tab and header.
   - `$pageSubtitle` — shown below the title.
   - `$activeNav` — which nav item is highlighted ("home", "groups", "activity", "profile").
   - `$basePath` — relative path prefix ("" for root pages, "../" for subdirectory pages).
   - `$showBack` — if true, shows a back arrow instead of the notification bell.
   - `$backHref` — where the back arrow goes.
2. **HTML head**: Sets charset, viewport, title, and links `css/style.css`.
3. **App shell**: Opens `<div class="app-shell">` and includes `sidebar.php`.
4. **Header bar**:
   - Left: Back arrow (if `$showBack`) or notification bell icon.
   - Center: Page title and subtitle.
   - Right: User avatar (first letter of name) linking to profile.

## Key details
- This is the entry point for the app's visual structure.
- The sidebar and header are shared across all authenticated pages.
- Uses `htmlspecialchars()` on all dynamic output.
