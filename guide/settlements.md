# settlements/index.php — Settlements Page

## What it does
The main page for viewing and paying all outstanding dues across all groups. Shows net balances and allows one-click payment.

## How it works in code

### Balance calculation (`get_net_dues`)
Queries three sources of dues:
1. Unsettled `expenses_participants` — who paid vs. who participated.
2. Unsettled `subscription_participants` — same pattern.
3. Unsettled `settlements` — manual settlement records.

For each, it calculates a per-group, per-counterparty net amount. Positive = user owes, negative = user is owed.

### POST actions
| Type | What it does |
|---|---|
| `net` | Pays the full net balance between the user and a counterparty in a specific group. Marks all matching expenses, subscriptions, and settlements as settled. |
| `expense` | Pays a single expense share. |
| `subscription` | Pays a single subscription share. |
| `legacy` | Pays a manual settlement record. |

All actions send a notification to the receiver. Each type checks the group's settlement deadline before allowing payment.

### Settlement deadline behavior
- **No deadline**: PAY DUE buttons are always active
- **Deadline in future**: PAY DUE buttons show "PAY DUE AFTER [date]" and are disabled
- **Deadline today or passed**: PAY DUE buttons are active
- When a group's deadline is extended to a later date, payments become blocked again until that new date

### Display
- **Summary**: "You Owe" and "You Are Owed" totals.
- **Live Split Dues**: Net dues with PAY DUE buttons (gated by settlement deadline).
- **Manual Settlements**: Legacy settlement records that haven't been paid.

## Key details
- The settlement deadline on the group controls when "PAY DUE" buttons are active.
- Notifications are sent to the receiver when a payment is made.
- This is the most feature-rich page — it handles four different payment types.
