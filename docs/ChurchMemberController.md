# ChurchMemberController

**File:** `app/Http/Controllers/ChurchMemberController.php`

Manages church members, their tracking logs (bitácora de seguimiento), status changes, and medals. All endpoints require JWT authentication.

---

## Permissions

The controller uses two permission layers to control data access:

| Permission | Scope | Behavior |
|---|---|---|
| `church-member-all` | Organization-wide | User can see members from **all organizations** they have this permission for. Bypasses the "mine" filter. |
| `conso-sheet-index` | Organization-scoped | User can only see members belonging to organizations where they hold this permission. |

### How scoping works

1. **`church-member-all`** — checked first via `hasPermissionTo()` or `getOrgsByPermission()`. If the user has it, the query is filtered by the list of org IDs returned by `getOrgsByPermission('church-member-all')`.
2. **`conso-sheet-index`** — the fallback. Applied through `AppliesOrgPermissionScope::applyOrgPermissionScope()`, which filters by org IDs the user has this permission for.
3. **"Mine" filter** (`?mine=true`) — only available when the user does **not** have `church-member-all`. Filters to members where the user is the creator of the associated `conso_sheet` **or** is listed as a consolidator of the member. Applied on `index()`; `show()` also applies it unconditionally for non-`church-member-all` users. The tracking-log/status/medal/consolidator sub-resources below resolve their member via `findMemberInScope()`, which applies the `church-member-all` / `conso-sheet-index` org scope but **not** the "mine" filter — so a user with org-wide `conso-sheet-index` can act on any member's sub-resources in that org even if they aren't the creator or a consolidator.

> If a user has neither permission for any organization, queries return zero results (`WHERE 1 = 0`).

> Permissions in this app are granted **per organization** through `Profile` (see `User::getOrgsByPermission()`), not via Spatie's direct `user->permissions` relation. Any check that needs to be org-aware (including the consolidator-assignment check below) must go through `getOrgsByPermission()`, not `hasPermissionTo()`/`whereDoesntHave('permissions', ...)`.

### Not found / access denied

`show()` and `findMemberInScope()` (used by every sub-resource action) explicitly check for a missing/out-of-scope record and call `abort(404, '...')` with a clean, user-safe message — they do **not** rely on `findOrFail()`'s default exception message, which would otherwise leak the Eloquent model class and ID (`"No query results for model [...] 29"`). A member outside the caller's org scope is indistinguishable from a non-existent one: both return a plain 404.

---

## Endpoints

### Member CRUD

| Method | Action | Description |
|---|---|---|
| `GET /church-members` | `index` | List all members in scope. Returns last contact datetime and last contacted by. |
| `GET /church-members/{id}` | `show` | Get a single member by ID (within permission scope). Returns 404 (clean message, no model/ID leak) if the member doesn't exist or is outside the caller's org scope. |
| `POST /church-members` | `create` | Create a new member. Auto-sends a WhatsApp welcome message if a cellphone is provided and WhatsApp is configured. |
| `PUT /church-members/{id}` | `update` | Update an existing member. Returns `{success, data}`. |
| `DELETE /church-members/{id}` | `delete` | Soft-delete a member. Returns `{success}`. |

#### `index` query parameters

| Parameter | Type | Description |
|---|---|---|
| `mine` | bool | Filter to members owned/consolidated by the current user (only for non-`church-member-all` users). |
| `status` | string | Filter by status (`ACTIVO`, `NO CONTESTA`, `NO MOLESTAR`, `VISITA`). |
| `org_id` | int | Filter by organization ID. |
| `conso_sheet_id` | int | Filter by consolidation sheet ID. |
| `filter` | string | Free-text search on `name`, `last_name`, `cellphone` (LIKE). |

#### `create` fields

| Field | Required | Notes |
|---|---|---|
| `org_id` | yes | Must exist in `organizations` table. |
| `name` | yes | |
| `last_name` | yes | |
| `second_last_name` | no | |
| `cellphone` | no | Triggers WhatsApp welcome message. |
| `years_old` | no | 0–150 |
| `number_of_children` | no | ≥ 0 |
| `marriage_status` | no | |
| `address` | no | |
| `url_image` | no | Base64 image is compressed and saved to S3. |
| `status` | no | Defaults to `ACTIVO`. Allowed: `ACTIVO`, `NO CONTESTA`, `NO MOLESTAR`, `VISITA`. |
| `conso_sheet_id` | no | |

---

### Tracking Logs (Bitácora de Seguimiento)

| Method | Action | Description |
|---|---|---|
| `GET /church-members/{id}/tracking-logs` | `trackingLogs` | Paginated list of tracking logs for a member. Supports `page`, `itemsPerPage`, `sortBy`, `sortDesc`. |
| `POST /church-members/{id}/tracking-logs` | `storeTrackingLog` | Create a new tracking log entry. Returns the created log (with `creator`), no `success` envelope. |
| `PUT /church-members/{id}/tracking-logs/{logId}` | `updateTrackingLog` | Update an existing tracking log entry. Returns `{success, data}`. |
| `DELETE /church-members/{id}/tracking-logs/{logId}` | `deleteTrackingLog` | Delete a tracking log entry. Returns `{success}`. |

#### `storeTrackingLog` / `updateTrackingLog` fields

| Field | Required | Notes |
|---|---|---|
| `contact_datetime` | yes (create) / sometimes (update) | |
| `medium` | yes (create) / sometimes (update) | `whatsapp`, `llamada`, `presencial`, `sms` |
| `classification` | no | `CONTESTA`, `NO CONTESTA` |
| `description` | no | Max 2000 chars |

---

### Status Management

| Method | Action | Description |
|---|---|---|
| `PUT /church-members/{id}/status` | `updateStatus` | Change a member's status. Creates a status log entry when the status actually changes. Returns `{success, data}`. |
| `GET /church-members/{id}/status-logs` | `statusLogs` | List all status change logs for a member. |

#### `updateStatus` fields

| Field | Required | Notes |
|---|---|---|
| `status` | yes | `ACTIVO`, `NO CONTESTA`, `NO MOLESTAR`, `VISITA` |
| `reason` | no | Max 1000 chars. Explanation for the status change. |

---

### Consolidadores

| Method | Action | Description |
|---|---|---|
| `GET /church-members/{id}/consolidators` | `consolidators` | List the member's assigned consolidators (`id`, `name`, `last_name`, `second_last_name`, `email`). |
| `PUT /church-members/{id}/consolidators` | `syncConsolidators` | Replace the member's consolidators with the given set. Returns `{success, data}` where `data` is the refreshed consolidator list. |

#### `syncConsolidators` payload

```json
{ "consolidator_ids": [15, 16] }
```

| Field | Required | Notes |
|---|---|---|
| `consolidator_ids` | yes | Array of user IDs. Must each exist in `users`. |
| `consolidator_ids.*` | yes | Integer, must reference an existing user. |

- **Double-wrapped payload tolerance** — some clients accidentally send `{"consolidator_ids": {"consolidator_ids": [...]}}`. The endpoint detects this shape and unwraps it before validating, so both the flat and double-wrapped forms are accepted.
- **Eligibility check** — every candidate user must hold the `conso-sheet-index` permission for the **member's own organization** (checked via `User::getOrgsByPermission('conso-sheet-index')`, not a global/direct permission check). If any candidate lacks it, the whole request is rejected with `400 {"error": "Some users do not have the required permission."}` — no partial sync happens.

---

### Medals

| Method | Action | Description |
|---|---|---|
| `GET /church-members/{id}/medals` | `medals` | List all medals for a member. |
| `POST /church-members/{id}/medals` | `storeMedal` | Award a medal to a member. |

#### `storeMedal` fields

| Field | Required | Notes |
|---|---|---|
| `medal` | yes | Max 255 chars. Medal name/identifier. |
| `description` | no | Max 255 chars. |

---

## Response Conventions

Not every endpoint follows the same response shape:

| Shape | Used by |
|---|---|
| `{success: string, data: ...}` | `create`, `update`, `updateStatus`, `updateTrackingLog`, `syncConsolidators` |
| `{success: string}` | `delete`, `deleteTrackingLog` |
| Raw resource (no envelope) | `index`, `show`, `trackingLogs`, `storeTrackingLog`, `statusLogs`, `consolidators` (GET), `medals`, `storeMedal` |
| `{error: string}` | Validation-style failures outside Laravel's default 422 handling, e.g. `syncConsolidators`'s permission check (`400`) |

The frontend's `withNotify()` wrapper auto-toasts any response containing a `success`/`warning`/`error` key, so manual success toasts in calling code would double up for the endpoints in the first two rows above.

## Data Relationships

- A `ChurchMember` belongs to an `Organization` (`org_id`).
- A `ChurchMember` optionally belongs to a `ConsoSheet` (`conso_sheet_id`).
- A `ChurchMember` has many `ChurchMemberTrackingLog` entries (bitácora).
- A `ChurchMember` has many `StatusLog` entries (status change history).
- A `ChurchMember` has many `Medal` entries.
- A `ChurchMember` can have many `Consolidators` (users).

---

## Side Effects

- **WhatsApp welcome message** — On member creation, if `cellphone` is set and WhatsApp config exists, a `SendWhatsAppMessageJob` is dispatched asynchronously. Failures are logged but do not prevent creation.
- **Image handling** — Base64 `url_image` values are compressed via `treatImage()` and stored in S3 via `saveS3Blob()`. Existing images are replaced on update.
