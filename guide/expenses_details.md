# expenses/details.php — Expense Details

## What it does
Shows the full details of a single expense: amount, group, date, payer, and a list of participants with their share amounts and settlement status.

## How it works in code
1. **Data**: Joins `expenses`, `groups`, `users`, and `group_members` to verify the current user has access.
2. **Participants**: Lists each participant from `expenses_participants` with their `share_amount` and `is_settled` status.
3. **Delete** (POST): Only the payer can delete their own expense. In a transaction:
   - Unlinks any payment methods from the expense.
   - Deletes `categories`, `expenses_participants`, and the expense itself.
4. **Edit link**: If the current user is the payer, shows an Edit link to `expenses/edit.php`.

## Key details
- Each participant's share is shown as "Settled" (green) or "Pending" (yellow).
- The delete button has a confirmation prompt (`data-confirm`).
- Only the original payer can edit or delete.
