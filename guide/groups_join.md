# groups/join.php — Join Group

## What it does
Lets the user join an existing group by entering its numeric Group ID.

## How it works in code
1. **Auth check**: Redirects to login if not authenticated.
2. **POST handling**:
   - Reads `group_id` from the form.
   - Checks if the group exists in the `groups` table.
   - Checks if the user is already a member (prevents duplicates).
   - If valid, inserts the user into `group_members` with role `"Member"`.
   - Redirects to `details.php?group_id=X`.
3. **Form**: Single input for Group ID (number field, min 1).

## Key details
- No invite link or code — users must know the numeric group ID.
- New members always get the "Member" role (not Admin).
- Error messages: "No group exists with that ID" or "You are already a member of this group."
