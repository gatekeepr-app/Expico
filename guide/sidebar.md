# includes/sidebar.php — Desktop Sidebar

## What it does
Renders the vertical navigation sidebar shown on desktop/wide screens. Contains links to Home, Groups, Activity, Profile, and Logout.

## How it works in code
1. **`expico_sidebar_icon($name)`**: Returns inline SVG icons for each nav item (home, groups, activity, profile, logout).
2. **HTML**: An `<aside>` with the brand mark ("E") and icon-only links.
3. **Active state**: The link matching `$activeNav` gets the `active` class.
4. **Spacer**: A `<div class="sidebar-spacer">` pushes the logout link to the bottom.

## Key details
- This is only visible on desktop (hidden on mobile via CSS).
- Mobile uses `navbar.php` instead (bottom navigation).
- No text labels — icons only, with `aria-label` for accessibility.
