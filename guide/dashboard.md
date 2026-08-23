# dashboard.php — Main Dashboard

## What it does
The home screen after login. Shows a summary of the user's financial state: total expenses this month, how much they owe, how much they are owed, their groups, and recent expenses.

## How it works in code

### Authentication
- Checks `$_SESSION["user_id"]`. If missing, redirects to `login.php`.

### Helper functions defined locally
| Function | Purpose |
|---|---|
| `money($value)` | Formats a number as ৳X,XXX.XX (Bangladeshi Taka) |
| `scalar_query($conn, $sql, ...)` | Runs a query and returns a single scalar value |
| `add_balance(&$balances, $payer_id, $receiver_id, $amount)` | Updates a 2D balance ledger — positive means payer owes receiver |
| `add_group_balance(&$group_balances, ...)` | Same as above but scoped per group |

### Data loading (all via prepared statements)
1. **Total expenses this month**: Sums `amount` from `expenses` where the user is a member and the date falls in the current month/year.
2. **Subscriptions this month**: Sums `amount` from subscriptions linked to the user via the `get` table, filtered to current month.
3. **Expense balances**: Queries unsettled `expenses_participants` rows and feeds them into `add_balance`.
4. **Subscription balances**: Same for `subscription_participants`.
5. **Settlement balances**: Queries unsettled `settlements` rows.
6. **You owe / You are owed**: Sums the user's row in the group balance ledger.
7. **Groups (top 4)**: Fetches groups the user belongs to, counts members, sums total expenses+subscriptions per group.
8. **Recent expenses (top 6)**: Fetches the latest expenses with group name and payer name.

### HTML output
- **Summary card**: Shows total expenses for the month.
- **Mini grid**: Two cards — "You Owe" (red) and "You Are Owed" (green).
- **Your Groups**: List of up to 4 group cards with member count, total amount, and user's balance.
- **Recent Expenses**: List of up to 6 expense rows with title, group, date, and amount.

### Includes
- `includes/header.php` (which pulls in `sidebar.php`)
- `includes/footer.php` (which pulls in `navbar.php` and `app.js`)

## Key details
- The balance calculation is net-based: opposite dues between two users cancel out.
- Currency symbol is ৳ (Bangladeshi Taka).
- The page uses the `money()` helper everywhere for consistent formatting.
