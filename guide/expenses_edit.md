# expenses/edit.php — Edit Expense

## What it does
Lets the payer edit an existing expense's title, amount, date, category, payment method, and participants.

## How it works in code
1. **Access**: Only the user who created the expense (`e.user_id = user_id`) can edit it.
2. **Pre-fills**: Loads the current expense data and selected participants.
3. **POST handling**:
   - Validates title, amount, date, and at least one participant.
   - In a transaction:
     - Updates the `expenses` row.
     - Deletes all existing `expenses_participants` rows and re-inserts them with recalculated shares.
     - Updates/creates/deletes the `categories` row as needed.
     - Updates the `payment_method` link.
   - Redirects to `expenses/details.php`.

## Key details
- Changing the amount or participant count recalculates all shares equally.
- Category and payment method can be added, changed, or removed.
- Participant list is validated against actual group members (prevents adding non-members).
