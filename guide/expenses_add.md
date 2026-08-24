# expenses/add.php — Add Expense

## What it does
Form to create a new shared expense. The user picks a group, enters the amount, selects participants, and the cost is split equally among them.

## How it works in code

### Settlement deadline check
When a group is selected, the page checks `groups.settlement_deadline`:
- If deadline is set and `<= today` → form is replaced with "Settlement period active" message
- If deadline is in the future or not set → form is shown normally
- Server-side POST check also blocks submission if deadline has passed

### Form behavior
1. **Group selector**: On change, reloads the page with `?group_id=X` so the member list updates.
2. **Participant checkboxes**: Loaded dynamically based on the selected group. All checked by default.
3. **Split preview**: A live preview (powered by `js/app.js`) shows total, number of participants, and per-person share.

### POST handling
1. Validates title, amount, group access, and at least one participant.
2. **Blocks if settlement deadline has passed** for the selected group.
3. In a transaction:
   - Inserts into `expenses` table with `user_id` = current user (the payer).
   - Calculates `share = amount / count(participants)`.
   - Inserts a row into `expenses_participants` for each valid participant (only if they're actually in the group).
   - Optionally creates a `categories` row if a category was entered.
   - Optionally links a `payment_method` to the expense.
4. Redirects to `expenses/details.php?expense_id=X`.

### Additional fields
- **Category** (text): Optional label like "Food", "Transport".
- **Payment Method** (dropdown): Links one of the user's saved payment methods.

## Key details
- The payer (current user) is NOT automatically included in participants — they must be checked.
- Share is always equal split. No custom split support.
- The group reload on selection is a full page reload, not AJAX.
- Settlement deadline blocks both the form UI and server-side POST.
