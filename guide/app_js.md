# js/app.js — Client-Side JavaScript

## What it does
Handles all interactive UI behavior: password toggles, bottom sheet, modals, confirmation dialogs, filter pills, and the live split preview.

## Features

### 1. Password toggle
```js
document.querySelectorAll("[data-toggle-password]")
```
Toggles password input visibility between `text` and `password`. Updates button text ("Show"/"Hide") and `aria-pressed`.

### 2. Bottom sheet (mobile quick actions)
```js
const sheet = document.querySelector("[data-bottom-sheet]");
```
- `data-open-sheet` buttons open the sheet.
- Clicking the backdrop closes it.
- Escape key closes it.
- Adds/removes `open` class on both sheet and backdrop.

### 3. Modals
```js
document.querySelectorAll("[data-open-modal]")
```
- `data-open-modal="X"` buttons open the modal with `data-modal="X"`.
- Clicking the backdrop or the `×` button closes it.
- Escape key closes all open modals.
- Auto-focuses the first input when opened.

### 4. Confirmation dialogs
```js
document.querySelectorAll("[data-confirm]")
```
Any element with `data-confirm="message"` shows a `confirm()` dialog before proceeding. If cancelled, the default action (form submit, link navigation) is prevented.

### 5. Filter pills
```js
document.querySelectorAll("[data-filter]")
```
Client-side filtering for expense lists. Toggles `active` class on pills and shows/hides items based on `data-filter-item` attribute matching.

### 6. Live split preview
```js
const splitAmount = document.querySelector("[data-split-amount]");
const splitChecks = document.querySelectorAll("[data-split-participant]");
```
Calculates and displays the per-person split in real-time:
- **Total**: The amount input value.
- **Split between**: Number of checked participants.
- **Each pays**: Total / count.
Updates on every `input` event on the amount field and every `change` event on participant checkboxes.

## Key details
- No frameworks — pure vanilla JavaScript.
- All interactivity is attribute-driven (`data-*` attributes).
- The split preview runs on page load to initialize the display.
