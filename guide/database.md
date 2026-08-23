# config/database.php — Database Connection

## What it does
Creates a MySQLi connection to the `expico` database and makes it available as `$conn` to every page that includes it.

## How it works in code
1. Defines connection parameters: host (`localhost`), username (`root`), password (empty), database (`expico`).
2. Creates a `new mysqli(...)` connection.
3. If the connection fails, `die()` halts the script with an error message.
4. Sets the character set to `utf8mb4` for proper Unicode support (including the ৳ currency symbol).

## Key details
- Every PHP file that needs the database calls `require_once "config/database.php"` (or `"../config/database.php"` from subdirectories).
- The `$conn` object is used throughout the app for prepared statements via `$conn->prepare()`.
- Credentials are hardcoded — in production these should come from environment variables.
