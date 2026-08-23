# groups/pay_due.php — Pay Due (Group Settlement)

## What it does
Lets the current user pay their outstanding dues within a specific group. Admins can also trigger a settlement request that notifies all members.

## How it works in code

### POST actions
| Action | Who | What it does |
|---|---|---|
| `request_settlement` | Admin only | Sets today as the settlement deadline and sends notifications to all members who owe money. |
| `pay_due` | Any member | Marks all unsettled expenses, subscriptions, and settlements between the user and a specific counterparty as "paid". Sends a notification to the receiver. |

### `pay_due` flow
1. Checks if the group's settlement deadline allows payment (no deadline, or today >= deadline).
2. Calculates the net amount owed to the counterparty.
3. In a transaction:
   - Marks matching `expenses_participants` rows as settled.
   - Marks matching `subscription_participants` rows as settled.
   - Marks matching `settlements` rows as paid.
   - Sends a notification to the counterparty.
4. Redirects back to the same page.

### Display
- **Your Dues**: List of net amounts the user owes (or is owed), with a PAY DUE button for each.
- **Group Due Map**: Shows all dues across all group members (not just the current user's).

## Key details
- The settlement deadline gates when payments can be made.
- Payments settle ALL dues between two users in the group at once (not per-expense).
- The notification system is integrated — both payer and receiver get notified.
