# includes/footer.php — Page Footer

## What it does
Closes the HTML structure opened by `header.php`. Includes the mobile navbar and loads `app.js`.

## How it works in code
1. Closes `</main>` and `</div>` (the app shell wrappers).
2. Includes `navbar.php` (mobile bottom nav).
3. Loads `js/app.js`.
4. Closes `</body></html>`.

## Key details
- Every page ends with `include "footer.php"` (or `"../includes/footer.php"`).
- The order is: header opens HTML → page content → footer closes HTML + loads JS.
- `app.js` is loaded at the end of the body for faster page rendering.
