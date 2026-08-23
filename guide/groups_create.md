# groups/create.php — Create Group

## What it does
Lets the user create a new expense group. On submission, the group is created and the user is added as its Admin.

## How it works in code
1. **Auth check**: Redirects to login if not authenticated.
2. **POST handling**:
   - Reads `group_name` and `description` from the form.
   - Validates that `group_name` is not empty.
   - Inserts into `groups` table.
   - Inserts the current user into `group_members` with role `"Admin"`.
   - Redirects to `details.php?group_id=X` for the new group.
3. **Form**: Simple form with group name (text) and description (textarea).

## Key details
- The creator automatically becomes the Admin.
- Group ID is returned via `$conn->insert_id` after the insert.
- There's no group limit per user.
