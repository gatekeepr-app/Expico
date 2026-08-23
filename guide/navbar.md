# includes/navbar.php — Mobile Bottom Navigation

## What it does
Renders the bottom navigation bar for mobile screens, plus a floating action button (FAB) that opens a quick-actions sheet.

## How it works in code
1. **Nav links**: Home, Groups, Activity, Profile — with icons and text labels.
2. **Active state**: Matching `$activeNav` gets the `active` class.
3. **FAB (+ button)**: Opens a bottom sheet with quick actions.
4. **Bottom sheet**: Contains links to:
   - Add Expense
   - Create Group
   - Join Group
   - Add Subscription
   - Add Payment Method
   - Logout
5. **Backdrop**: Clicking outside the sheet closes it (handled by `js/app.js`).

## Key details
- Only visible on mobile (hidden on desktop via CSS).
- The bottom sheet is toggled via `data-open-sheet` and `data-bottom-sheet` attributes.
- Each action links directly to the relevant form page.
