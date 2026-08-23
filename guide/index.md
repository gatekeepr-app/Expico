# index.php — Landing Page

## What it does
This is the first page users see. It shows the Expico branding, a short tagline, and two buttons: LOGIN and CREATE ACCOUNT.

## How it works in code
1. **Session check**: If the user is already logged in (`$_SESSION["user_id"]` exists), it redirects them straight to the dashboard. No reason to show the landing page to someone already signed in.
2. **HTML output**: Renders a centered card with the brand mark ("E"), the title "EXPICO", a one-line description, and two links pointing to `login.php` and `register.php`.

## Key details
- Uses `css/style.css` for styling.
- No database calls — purely static output.
- Class `landing-shell` / `landing-card` handle the full-screen centered layout.

## Flow
```
User opens app → index.php
  ├─ Already logged in? → dashboard.php
  └─ Not logged in? → shows landing card with Login / Create Account buttons
```
