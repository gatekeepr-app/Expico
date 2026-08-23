# login.php — Login Page

## What it does
Renders the login form where users enter their email and password. On success they are redirected to the dashboard.

## How it works in code
1. **Session guard**: If already logged in, redirects to `dashboard.php`.
2. **Flash messages**: Reads `$_SESSION["login_error"]` and `$_SESSION["register_success"]` (set by other pages), displays them as alerts, then clears them from the session so they only show once.
3. **Form**: POSTs to `auth/login_process.php` with fields `email` and `password`.
4. **Password toggle**: Includes a "Show" button (handled by `js/app.js`) that toggles the input between `password` and `text`.

## Key details
- Error/success messages are passed via the session (PRG pattern — Post/Redirect/Get) to prevent form resubmission.
- Links to `register.php` for new users.
- `htmlspecialchars()` is used on all output to prevent XSS.

## Flow
```
login.php → user submits form
  → auth/login_process.php
    ├─ Invalid credentials → redirect back to login.php with error
    └─ Valid credentials → redirect to dashboard.php
```
