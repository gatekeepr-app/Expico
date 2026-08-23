# logout.php — Logout

## What it does
Destroys the current session and redirects the user to the login page.

## How it works in code
1. `session_start()` — starts or resumes the session.
2. `$_SESSION = []` — clears all session data.
3. `session_destroy()` — removes the session file on the server.
4. `header("Location: login.php")` — sends the redirect.
5. `exit()` — stops execution immediately.

## Key details
- No HTML output at all — this is a pure server-side redirect.
- After logout, any attempt to access a protected page (dashboard, profile, etc.) will redirect back to `login.php` because those pages check `$_SESSION["user_id"]`.
