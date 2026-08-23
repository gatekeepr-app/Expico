# subscriptions/index.php — Subscription List

## What it does
Lists all subscriptions the user is linked to (via the `get` table), showing billing cycle, amount, group, share, next due date, and participant count.

## How it works in code
1. **Query**: Joins `subscriptions` with `get`, `payment_method`, `categories`, `groups`, `users`, and `subscription_participants`.
2. **Filter**: Only shows subscriptions where `gu.user_id = current user`.
3. **Ordering**: Sorted by `next_due_date` (nulls last).
4. **Each card**: Shows name, billing cycle, category, group, total amount, user's share, next due date, and number of participants.

## Key details
- Subscriptions can be personal (no group) or group-shared.
- The `get` table is the link between users and subscriptions they're part of.
- "Add" link in the header goes to `subscriptions/add.php`.
