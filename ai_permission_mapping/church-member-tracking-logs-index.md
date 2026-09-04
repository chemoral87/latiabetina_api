# church-member-tracking-logs-index

**File:** `routes/api.php`

## Routes protected

| Method | URI | Controller@Method |
|--------|-----|-------------------|
| GET | `/church-member/tracking-logs` | `ChurchMemberController@trackingLogsIndex` |

## Enforced by

- Middleware: `permission_org:church-member-tracking-logs-index`
- Checks `User::getOrgsByPermission('church-member-tracking-logs-index')` — returns array of org_ids where user holds this permission
- If user has `church-member-all` permission, applies org scope based on permitted orgs from `getOrgsByPermission('church-member-all')`
- If no orgs found for the permission, returns empty result set (`WHERE 1 = 0`)

## Response on failure

```json
{
  "error": "No tienes permiso para realizar esta acción. Se requiere el permiso: church-member-tracking-logs-index"
}
```

Status: `403 Forbidden`

## Notes

- This endpoint returns the **current user's own tracking logs** (where `created_by = current_user_id`), filtered by org scope
- Uses `permission_org:` middleware (org-aware) instead of Spatie's `permission:` middleware
- Supports query params: `page`, `itemsPerPage`, `sortBy`, `sortDesc`, `filter`, `medium`, `date_from`, `date_to`