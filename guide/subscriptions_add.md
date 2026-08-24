# subscriptions/add.php — Add Subscription

## What it does
Form to create a new subscription (recurring or one-time cost) and split it among participants.

## How it works in code

### Settlement deadline check
When a group is selected, the page checks `groups.settlement_deadline`:
- If deadline is set and `<= today` → form is replaced with "Settlement period active" message
- If deadline is in the future or not set → form is shown normally
- Server-side POST check also blocks submission if deadline has passed

### Form fields
- **Name**: Subscription name (e.g., "Netflix", "Spotify").
- **Amount**: Total cost.
- **Billing Cycle**: One Time, Weekly, Monthly, Yearly, or Custom.
- **Group**: Personal or linked to a group (reload on change to show members).
- **Next Due Date**: When the next payment is due.
- **Category**: Optional label.
- **Payment Method**: Dropdown of user's saved methods.
- **Participants**: Checkboxes (loaded from group members, or just the user for personal).

### POST handling
1. Validates name, amount, group access, participants, and payment method.
2. **Blocks if settlement deadline has passed** for the selected group.
3. In a transaction:
   - Optionally creates a `categories` row.
   - Inserts into `subscriptions`.
   - Inserts into `get` for each participant (links them to the subscription).
   - Inserts into `subscription_participants` with calculated shares.
   - If a due date was provided, creates a `deadlines` row.
4. Redirects to `subscriptions/index.php`.

## Key details
- Share is always equal split.
- The `get` table and `subscription_participants` table are both populated — `get` links users to subscriptions, `subscription_participants` tracks their financial share.
- Deadlines are auto-created from the due date.
- Settlement deadline blocks both the form UI and server-side POST.
