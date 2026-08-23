# subscriptions/details.php — Subscription Details

## What it does
Shows the full details of a subscription: name, amount, group, billing cycle, next due date, payer, and all participants with their shares and settlement status.

## How it works in code
1. **Data**: Joins `subscriptions` with `get`, `payment_method`, `categories`, `groups`, and `users`. Verifies the current user is linked via the `get` table.
2. **Participants**: Lists each from `subscription_participants` with `share_amount` and `is_settled`.
3. **Delete** (POST): Only the subscription creator can delete. In a transaction:
   - Deletes related notifications, deadlines, subscription_participants, get rows, the subscription itself, and any orphaned categories.

## Key details
- The creator (`s.user_id`) is the only one who can edit or delete.
- Edit link goes to `subscriptions/edit.php`.
- Delete has a confirmation prompt.
