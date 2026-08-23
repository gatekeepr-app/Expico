# register.php — Registration Page

## What it does
Renders the account creation form. Users provide name, email, phone (optional), password, and password confirmation.

## How it works in code
1. **Session guard**: Redirects to dashboard if already logged in.
2. **Flash error**: Reads `$_SESSION["register_error"]` and displays it, then clears it.
3. **Form**: POSTs to `auth/register_process.php` with fields: `name`, `email`, `phone_no`, `password`, `confirm_password`.
4. **Password toggles**: Both password fields have Show/Hide buttons.

## Key details
- Phone is optional (no `required` attribute).
- Minimum password length is 6 characters (enforced in `register_process.php`).
- Link back to `login.php` for existing users.

## Flow
```
register.php → user submits form
  → auth/register_process.php
    ├─ Validation fails → redirect back to register.php with error
    └─ Account created → redirect to login.php with success message
```
