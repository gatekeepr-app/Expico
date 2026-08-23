# auth/register_process.php — Registration Handler

## What it does
Processes the registration form. Validates input, checks for duplicate emails, hashes the password, inserts the new user, and redirects to login.

## How it works in code
1. **Method guard**: Only accepts POST.
2. **Input**: Reads `name`, `email`, `phone_no`, `password`, `confirm_password`. Trims name, email, phone.
3. **Required fields check**: Name, email, and password must not be empty.
4. **Email validation**: Uses `filter_var($email, FILTER_VALIDATE_EMAIL)`.
5. **Password match**: Checks `password === confirm_password`.
6. **Password length**: Must be at least 6 characters.
7. **Duplicate check**: Queries `users` by email. If exists, shows error.
8. **Hash password**: `password_hash($password, PASSWORD_DEFAULT)` — generates a bcrypt hash.
9. **Generate user ID**: Queries `MAX(user_id) + 1` (manual auto-increment).
10. **Insert**: Runs `INSERT INTO users` with the generated ID, name, email, hash, and phone.
11. **Success**: Sets `$_SESSION["register_success"]` and redirects to `login.php`.

## Key details
- All validation errors redirect back to `register.php` with the error stored in the session.
- The user ID is generated manually (`MAX + 1`) rather than using `AUTO_INCREMENT` — this works but isn't race-condition safe.
- Phone number is optional and can be empty.
