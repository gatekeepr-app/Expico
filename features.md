# Expico — Features

## 1. Authentication & User Management

| Feature | Description |
|---|---|
| Registration | Create account with name, email, password (bcrypt hashed), phone number |
| Login | Email + password authentication with PHP sessions |
| Profile | View and edit profile information |
| Logout | Session destruction and redirect |

## 2. Groups

| Feature | Description |
|---|---|
| Create Group | Name, description, auto-creator as Admin |
| Join Group | Enter group ID to join as Member |
| Group Details | Summary card with total expenses, member count, settlement status |
| Members List | View all members with roles (Admin/Member) |
| Leave Group | Blocked if user has unsettled dues |
| Delete Group | Admin only — cascading delete of all group data in a transaction |
| Settlement Deadline | Admin sets a date that controls when dues can be paid and when expenses/subscriptions can be added |

## 3. Expenses

| Feature | Description |
|---|---|
| Add Expense | Title, amount, date, group, participants — split equally |
| Equal Split | `share = amount / participant_count` — no custom split support |
| Category | Optional text label (Food, Transport, etc.) |
| Payment Method | Link one of the user's saved payment methods |
| Edit Expense | Update title, amount, date, participants |
| Expense Details | View full breakdown: payer, participants, shares, settled status |
| Expense List | List all expenses the user is involved in |
| Deadline Blocking | Cannot add expenses when `settlement_deadline <= today` |

## 4. Subscriptions

| Feature | Description |
|---|---|
| Add Subscription | Name, amount, billing cycle, group, participants |
| Billing Cycles | One Time, Weekly, Monthly, Yearly, Custom |
| Personal or Group | Can be personal (no group) or linked to a group |
| Equal Split | Same equal-split logic as expenses |
| Category | Optional text label |
| Payment Method | Link a saved payment method |
| Auto Deadlines | If a due date is set, a `deadlines` row is created automatically |
| Edit Subscription | Update name, amount, cycle, due date, participants |
| Subscription Details | Full breakdown with participants and settled status |
| Subscription List | List all subscriptions the user is part of |
| Deadline Blocking | Cannot add group subscriptions when `settlement_deadline <= today` |

## 5. Settlements & Payments

| Feature | Description |
|---|---|
| Net Balance Calculation | Aggregates unsettled expenses, subscriptions, and manual settlements into per-counterparty net amounts |
| Settlement Deadline | Controls when PAY DUE buttons activate (`deadline <= today` = active) |
| PAY DUE | Settles ALL dues between two users in a group at once (not per-expense) |
| Payment Types | `net` (full balance), `expense` (single share), `subscription` (single share), `legacy` (manual record) |
| Admin Request Settlement | Sets deadline to today, sends notifications to all members who owe money |
| Manual Settlements | Legacy table for admin-created settlement records (currently unused in UI) |
| Settlements Page | Global view of all dues across all groups with "You Owe" / "You Are Owed" summary |
| Group Pay Due Page | Per-group view showing "Your Dues" and "Group Due Map" |
| Group Due Map | Shows all member-to-member dues, not just the current user's |

## 6. Notifications

| Feature | Description |
|---|---|
| Settlement Request | "Settlement requested for [group]: pay [amount] to [name]" |
| Payment Received | "[Payer] paid you [amount] for [group/expense]" |
| Subscription Due | "[Payer] paid you [amount] for [subscription]" |
| Read/Unread | `is_read` flag with visual indicator |

## 7. Dashboard

| Feature | Description |
|---|---|
| Group Overview | List of user's groups with total amounts and member counts |
| Quick Navigation | Links to groups, expenses, subscriptions, settlements |
| Notifications Center | View unread notifications |

## 8. Payment Methods

| Feature | Description |
|---|---|
| Add Method | Method type (Bkash, etc.) + account details |
| Default Flag | Mark one method as default |
| Link to Expense | Payment method can be linked to a specific expense |

## 9. Deadlines

| Feature | Description |
|---|---|
| Subscription Deadlines | Auto-created from subscription due dates |
| Status Tracking | `upcoming` status on deadline records |
| Notification Link | Deadlines linked to notification system |

---

## Settlement Deadline Behavior Summary

| State | Adding Expenses | Adding Subscriptions | Paying Dues |
|---|---|---|---|
| No deadline set | Allowed | Allowed | Always active |
| Deadline in future | Allowed | Allowed | Blocked until deadline |
| Deadline today or passed | Blocked | Blocked | Active |
| Admin extends deadline | Re-opens | Re-opens | Blocked until new date |
