# groups/details.php — Group Details

## What it does
The main group page. Acts as both a group list (when no `group_id` is provided) and a detailed group view (when `group_id` is in the URL).

## How it works in code

### Dual mode
- **No `group_id`**: Shows all groups the user belongs to, with member count and total amount. Links to create/join.
- **With `group_id`**: Shows full group details.

### POST actions (when viewing a specific group)
| Action | Who can do it | What it does |
|---|---|---|
| `leave_group` | Any member | Removes the user from the group. Blocked if there are unsettled dues. |
| `delete_group` | Admin only | Deletes the group and all its data (expenses, subscriptions, settlements, members, notifications) in a transaction. |
| `update_settlement_deadline` | Admin only | Sets or clears the `settlement_deadline` date on the group. |

### Group detail view
- **Summary card**: Group name, total expenses, group ID, settlement deadline.
- **Members (top 4)**: Names and roles.
- **Recent expenses (top 4)**: Title, payer, date, amount.
- **Group subscriptions (top 4)**: Name, billing cycle, payer, amount.
- **Settlement summary (top 4)**: Net dues between the current user and other members.
- **Settlement deadline** (Admin only): Editable via a modal.
- **Leave / Delete buttons**: With confirmation prompts.

### `get_group_balances()` function
Encapsulates the balance logic: queries expenses, subscriptions, and settlements for a group and returns a 2D balance array.

## Key details
- The delete operation cascades through many tables in a transaction with rollback on failure.
- The leave operation checks for pending dues before allowing departure.
- Settlement deadline controls when members can actually pay their dues.
