# expenses/add.php — Add Expense

## What it does
Form to create a new shared expense. The user picks a group, enters the amount, selects participants, and the cost is split equally among them.

## How it works in code

### Form behavior
1. **Group selector**: On change, reloads the page with `?group_id=X` so the member list updates.
2. **Participant checkboxes**: Loaded dynamically based on the selected group. All checked by default.
3. **Split preview**: A live preview (powered by `js/app.js`) shows total, number of participants, and per-person share.

### POST handling
1. Validates title, amount, group access, and at least one participant.
2. In a transaction:
   - Inserts into `expenses` table with `user_id` = current user (the payer).
   - Calculates `share = amount / count(participants)`.
   - Inserts a row into `expenses_participants` for each valid participant (only if they're actually in the group).
   - Optionally creates a `categories` row if a category was entered.
   - Optionally links a `payment_method` to the expense.
3. Redirects to `expenses/details.php?expense_id=X`.

### Additional fields
- **Category** (text): Optional label like "Food", "Transport".
- **Payment Method** (dropdown): Links one of the user's saved payment methods.

## Key details
- The payer (current user) is NOT automatically included in participants — they must be checked.
- Share is always equal split. No custom split support.
- The group reload on selection is a full page reload, not AJAX.
