# notifications/index.php — Notifications Page

## What it does
Shows all notifications for the user (reminders, payment confirmations, settlement requests). Also auto-generates notifications for upcoming deadlines and unsettled dues.

## How it works in code

### Auto-generated notifications
On every page load, the system creates notifications for:
1. **Upcoming deadlines**: Subscriptions due within 3 days (including overdue).
2. **Unsettled expense shares**: "You owe X ৳Y for Z."
3. **Unsettled subscription shares**: Same pattern.

The `add_notification()` helper deduplicates by checking if the same message already exists for the user.

### POST/GET actions
| Action | What it does |
|---|---|
| `GET ?open=X` | Opens a notification, marks it as read, redirects to the relevant page (deadlines, settlements, or dashboard). |
| `POST notification_id=X` | Marks a single notification as read. |
| `POST action=mark_all_read` | Marks all of the user's notifications as read. |

### `notification_action_page()` function
Determines where to redirect when a notification is tapped:
- Has `deadline_id` → deadlines page.
- Contains "settlement" or "owe" or "paid" → settlements page.
- Default → dashboard.

### Display
- Sorted by unread first, then by date descending.
- Each card shows: "New reminder" or "Update", read/unread badge, message, timestamp, and "Tap to open".
- "Mark all read" button in the header.

## Key details
- Notifications are generated on page load, not in real-time.
- The deduplication prevents duplicate notifications from accumulating.
- Notifications link to the most relevant page based on their content.
