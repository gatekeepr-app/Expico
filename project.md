# Expico — Project Context

## What is Expico?
Expico is a shared expense management web application built with PHP and MariaDB. It lets groups of people track shared costs, split expenses equally, manage subscriptions, and settle debts.

## Tech Stack
- **Backend**: PHP 8.0, MariaDB 10.4
- **Frontend**: HTML, CSS, vanilla JavaScript
- **Auth**: bcrypt password hashing, PHP sessions

## Core Features

### Groups
- Create groups, invite members, assign Admin/Member roles
- Each group has an optional **settlement deadline** that controls the payment window

### Expenses
- Add shared expenses with equal split among participants
- Category tagging and payment method linking

### Subscriptions
- Track recurring or one-time shared costs (Netflix, Spotify, etc.)
- Billing cycle support (weekly, monthly, yearly, one-time, custom)
- Auto-created deadlines from due dates

### Settlements
- Net balance calculation across expenses, subscriptions, and manual settlements
- **Settlement deadline gating**: after the deadline passes, adding expenses/subscriptions is blocked
- PAY DUE buttons activate when the deadline is reached
- One-click payment settles all dues between two users in a group

### Notifications
- Settlement requests, payment confirmations, and subscription due alerts

## Settlement Deadline Behavior
- **No deadline set**: Everything works normally — add expenses, subscriptions, pay dues anytime
- **Deadline in the future**: Adding expenses/subscriptions is allowed. PAY DUE is blocked until the deadline
- **Deadline today or passed**: Adding expenses/subscriptions is blocked. PAY DUE is active
- Admin can extend the deadline to re-open the group for new expenses

## Database
11 tables: `users`, `groups`, `group_members`, `expenses`, `expenses_participants`, `subscriptions`, `subscription_participants`, `get`, `categories`, `deadlines`, `notifications`, `payment_method`, `settlements`, `is_settled`

## File Structure
```
Expico/
├── auth/              # Authentication handlers
├── config/            # Database config
├── css/               # Stylesheets
├── js/                # JavaScript (split preview, modals)
├── guide/             # Per-file documentation
├── includes/          # header.php, footer.php
├── expenses/          # add, details, edit, list
├── subscriptions/     # add, details, edit, index
├── groups/            # create, details, join, members, pay_due
├── settlements/       # index (global settlement view)
├── deadlines/         # Deadline management
├── notifications/     # Notification center
├── payment_methods/   # Payment method management
├── dashboard.php      # Main dashboard
├── index.php          # Landing page
├── login.php          # Login
├── register.php       # Registration
├── profile.php        # User profile
├── logout.php         # Session destroy
└── expico.sql         # Full database schema + sample data
```
