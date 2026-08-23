# profile.php — User Profile

## What it does
Displays the logged-in user's account info (name, email, phone, join date), stats (number of groups, payment methods), their net balance (owe/owed), and links to payment methods and logout.

## How it works in code
1. **Auth check**: Redirects to `login.php` if not logged in.
2. **User data**: Fetches `name`, `email`, `phone_no`, `created_at` from `users`.
3. **Counts**: Queries `group_members` and `payment_method` for group count and payment method count.
4. **Balance calculation**: Uses the same `add_balance` pattern as the dashboard — queries unsettled expenses, subscriptions, and settlements, then sums the user's net position.
5. **HTML**: Shows a summary card with name/email, mini-grid with group/method counts, mini-grid with owe/owed amounts, account info cards (phone, join date), and buttons for payment methods and logout.

## Key details
- No edit-profile functionality — this is a read-only view plus navigation links.
- The balance logic is duplicated from `dashboard.php` (not shared via a function file).
