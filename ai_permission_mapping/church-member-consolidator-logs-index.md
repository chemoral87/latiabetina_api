# church-member-consolidator-logs-index

**File:** `routes/api.php`

## Routes protected

| Method | URI | Controller@Method |
|--------|-----|-------------------|
| GET | `/church-member/consolidator-logs` | `ChurchMemberController@consolidatorLogsIndex` |

## Enforced by

- Middleware: `permission_org:church-member-consolidator-logs-index`
- Checks `User::getOrgsByPermission('church-member-consolidator-logs-index')` — returns array of org_ids where user holds this permission
- If user has `church-member-all` permission, applies org scope based on permitted orgs from `getOrgsByPermission('church-member-all')`
- If no orgs found for the permission, returns empty result set (`WHERE 1 = 0`)

## Response on failure

```json
{
  "error": "No tienes permiso para realizar esta acción. Se requiere el permiso: church-member-consolidator-logs-index"
}
```

Status: `403 Forbidden`

## Notes

- This endpoint returns **all members' consolidator logs** (assigned/unassigned actions), filtered by org scope
- Uses `permission_org:` middleware (org-aware) instead of Spatie's `permission:` middleware
- Supports query params: `page`, `itemsPerPage`, `sortBy`, `sortDesc`, `filter`, `action`