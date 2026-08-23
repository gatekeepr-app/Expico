# auth/login_process.php — Login Handler

## What it does
Processes the login form submission. Validates credentials, starts a session on success, and redirects.

## How it works in code
1. **Method guard**: Only accepts POST requests. Anything else redirects back to `login.php`.
2. **Input**: Reads `email` and `password` from `$_POST`. Trims the email.
3. **Empty check**: If either field is empty, sets `$_SESSION["login_error"]` and redirects back.
4. **User lookup**: Queries `users` by email. If no match, sets error and redirects.
5. **Password verify**: Uses `password_verify()` against the stored `password_hash`. If it fails, sets error and redirects.
6. **Success**:
   - `session_regenerate_id(true)` — prevents session fixation attacks.
   - Stores `user_id`, `user_name`, `user_email` in the session.
   - Redirects to `dashboard.php`.

## Key details
- Uses prepared statements (`bind_param`) to prevent SQL injection.
- The error message is the same for "user not found" and "wrong password" — this is intentional to avoid leaking whether an email exists.
- Password hashing uses `PASSWORD_DEFAULT` (bcrypt) set during registration.
