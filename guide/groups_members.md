# groups/members.php — Group Members List

## What it does
Shows all members of a specific group along with each member's net balance (who owes whom).

## How it works in code
1. **Auth check**: Redirects to login if not authenticated.
2. **Access check**: Verifies the current user is a member of the requested group.
3. **Balance calculation**:
   - `add_balance()` is defined locally with a slightly different signature (flat 1D balance array instead of 2D).
   - Queries unsettled `expenses_participants`, `subscription_participants`, and `settlements` for the group.
   - Computes a net balance per member.
4. **HTML**: Lists each member with their avatar initial, name, role, and net balance (positive = they are owed, negative = they owe).

## Key details
- Sorted by role (Admin first), then by name.
- The balance here is group-scoped, not user-scoped — it shows each member's position relative to everyone else in the group.
- If the user doesn't have access, shows "Group not found" with a message.
