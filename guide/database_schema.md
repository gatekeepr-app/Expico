# expico.sql — Database Schema

## What it does
The full MySQL/MariaDB schema for the Expico database. Defines all tables, relationships, indexes, and sample data.

## Tables

### Core entities
| Table | Purpose |
|---|---|
| `users` | User accounts (id, name, email, password_hash, phone, created_at) |
| `groups` | Expense groups (id, name, description, settlement_deadline) |
| `group_members` | Links users to groups with roles (Admin/Member) |

### Financial records
| Table | Purpose |
|---|---|
| `expenses` | Individual expenses (title, amount, date, group, payer) |
| `expenses_participants` | Split shares per participant per expense (share_amount, is_settled) |
| `subscriptions` | Recurring or one-time shared costs (name, amount, billing_cycle, next_due_date) |
| `subscription_participants` | Split shares per participant per subscription |
| `get` | Links users to subscriptions they're part of |
| `settlements` | Manual settlement records (amount, status, paid_by, paid_to) |
| `is_settled` | Links users to settlements they've paid |

### Supporting tables
| Table | Purpose |
|---|---|
| `categories` | Expense/subscription categories (linked to expenses) |
| `deadlines` | Payment due dates for subscriptions |
| `notifications` | User notifications (messages, read status, linked deadlines) |
| `payment_method` | User payment methods (type, account details, default flag, linked expense) |

## Key relationships
```
users ──< group_members >── groups
users ──< expenses ──> groups
expenses ──< expenses_participants >── users
users ──< get >── subscriptions
subscriptions ──< subscription_participants >── users
subscriptions ──> groups
subscriptions ──> categories
subscriptions ──> payment_method
deadlines ──> subscriptions
notifications ──> deadlines
settlements ──> groups, users (paid_by, paid_to)
```

## Sample data
The SQL includes 7 users, 4 groups, 6 expenses, 2 subscriptions, 3 payment methods, and 18 notifications — enough to demo the full app.
