# css/style.css — Stylesheet

## What it does
Contains all the CSS for the Expico application. Defines the visual design system: colors, typography, layout, and component styles.

## Key design tokens
Based on the file structure and class names used throughout the app:
- **Colors**: Primary buttons, danger buttons, success/warning/negative/positive states.
- **Layout**: `app-shell` (sidebar + main content), `landing-shell` (centered auth pages), `auth-shell`.
- **Components**: Cards (`group-card`, `payment-card`, `transaction-row`, `user-row`), forms (`form-card`, `form-group`), buttons (`primary-button`, `secondary-button`, `danger-button`), badges, alerts, modals, bottom sheets.

## Key classes used across the app
| Class | Used on |
|---|---|
| `app-shell` | Main app layout (sidebar + content) |
| `landing-shell` / `landing-card` | Landing page centered layout |
| `auth-shell` / `auth-card` | Login/register centered layout |
| `summary-card` | Large stat display (dashboard total, group total) |
| `mini-grid` / `mini-card` | Two-column stat cards (owe/owed) |
| `group-card` | Group list items |
| `transaction-row` | Expense/subscription list items |
| `user-row` | Member list items |
| `form-card` / `form-group` | Form layout |
| `primary-button` / `secondary-button` / `danger-button` | Action buttons |
| `badge` / `success` / `warning` | Status indicators |
| `alert` / `error` / `success` | Flash messages |
| `empty-state` | Empty list placeholders |
| `bottom-sheet` / `bottom-sheet-backdrop` | Mobile quick-actions sheet |
| `modal-backdrop` / `modal-card` | Modal dialogs |
| `desktop-sidebar` / `bottom-nav` | Navigation |
