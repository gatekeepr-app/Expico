# subscriptions/edit.php — Edit Subscription

## What it does
Lets the subscription creator update name, amount, billing cycle, group, due date, category, payment method, and participants.

## How it works in code
1. **Access**: Only the creator (`s.user_id = user_id`) can edit.
2. **Pre-fills**: Loads current subscription data and selected participants.
3. **POST handling**:
   - Validates name, amount, group access, participants.
   - In a transaction:
     - Updates/creates/deletes `categories` as needed.
     - Updates the `subscriptions` row.
     - Deletes and re-inserts `subscription_participants` and `get` rows.
     - Updates or creates `deadlines` based on the due date.
   - Redirects to `subscriptions/details.php`.

## Key details
- Changing the group reloads the participant list (via URL parameter).
- Due date changes update or create deadlines accordingly.
- If due date is cleared, related deadlines and their notifications are deleted.
