# deadlines/index.php — Deadlines Page

## What it does
Shows upcoming subscription payment deadlines. Users can advance recurring deadlines or mark one-time deadlines as paid.

## How it works in code

### POST action
When a user clicks "ADVANCE DUE DATE" or "MARK PAID":
1. Fetches the deadline and its linked subscription.
2. If the billing cycle is weekly/monthly/yearly, advances the due date by one cycle and resets status to "upcoming".
3. If one-time (or other), marks the deadline as "paid".
4. Marks related notifications as read.

### Display
- Lists all unpaid deadlines for the user's subscriptions, sorted by due date.
- Each row shows: date block (day + month), subscription name, amount, days until due (or overdue), and a status badge.
- Badges: red for overdue, yellow for due within 3 days, default for later.
- Button text: "ADVANCE DUE DATE" for recurring, "MARK PAID" for one-time.

## Key details
- Deadlines are linked to subscriptions via `subscription_id`.
- The deadline advancement updates both `deadlines` and `subscriptions.next_due_date`.
- Overdue deadlines show "X days overdue" in the description.
