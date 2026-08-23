# expenses/list.php — Expense List

## What it does
Shows all expenses across the user's groups in a filterable list.

## How it works in code
1. **Data**: Queries all expenses where the user is a member of the group (via `group_members`), ordered by date descending.
2. **Filter pills**: Three client-side filters:
   - **All** — shows everything.
   - **This Month** — shows only expenses from the current month.
   - **Paid by Me** — shows only expenses where the current user is the payer.
3. **Each row**: Shows an icon, title, group name, date, payer name, and amount. Links to `expenses/details.php`.

## Key details
- Filtering is done client-side via `data-filter-item` attributes and `js/app.js`.
- No pagination — loads all expenses at once.
- Links to settlements, deadlines, and add expense in the section header.
