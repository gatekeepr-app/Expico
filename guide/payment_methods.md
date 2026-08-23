# payment_methods/index.php — Payment Methods

## What it does
CRUD page for managing the user's payment methods (cards, wallets, cash accounts). Methods can be linked to expenses and marked as default.

## How it works in code

### POST actions
| Action | What it does |
|---|---|
| `add` | Creates a new payment method with type, account details, and default flag. |
| `edit` | Updates an existing payment method. |
| `delete` | Removes a payment method (unlinks from subscriptions first). |
| `default` | Sets a method as the default (clears default from all others first). |

### Display
- **Form**: At the top — type (text), account details (text), default checkbox. Switches between add/edit mode.
- **List**: Each method shows type, masked account details (last 4 chars), default badge, edit button, default button, and delete button.

### `mask_detail()` function
Hides most of the account details, showing only the last 4 characters (e.g., "•••• 4702").

## Key details
- Only one method can be default at a time — setting a new default clears the old one.
- Deleting a method unlinks it from any subscriptions but doesn't delete the subscriptions.
- The page also has a LOGOUT button at the bottom.
- Accessed from the profile page.
