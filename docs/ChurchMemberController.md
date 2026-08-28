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
3. **"Mine" filter** (`?mine=true`) — only available when the user does **not** have `church-member-all`. Filters to members where the user is the creator of the associated `conso_sheet` **or** is listed as a consolidator of the member.

> If a user has neither permission for any organization, queries return zero results (`WHERE 1 = 0`).

---

## Endpoints

### Member CRUD

| Method | Action | Description |
|---|---|---|
| `GET /church-members` | `index` | List all members in scope. Returns last contact datetime and last contacted by. |
| `GET /church-members/{id}` | `show` | Get a single member by ID (within permission scope). |
| `POST /church-members` | `create` | Create a new member. Auto-sends a WhatsApp welcome message if a cellphone is provided and WhatsApp is configured. |
| `PUT /church-members/{id}` | `update` | Update an existing member. |
| `DELETE /church-members/{id}` | `delete` | Soft-delete a member. |

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
| `POST /church-members/{id}/tracking-logs` | `storeTrackingLog` | Create a new tracking log entry. |
| `PUT /church-members/{id}/tracking-logs/{logId}` | `updateTrackingLog` | Update an existing tracking log entry. |
| `DELETE /church-members/{id}/tracking-logs/{logId}` | `deleteTrackingLog` | Delete a tracking log entry. |

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
| `PUT /church-members/{id}/status` | `updateStatus` | Change a member's status. Creates a status log entry when the status actually changes. |
| `GET /church-members/{id}/status-logs` | `statusLogs` | List all status change logs for a member. |

#### `updateStatus` fields

| Field | Required | Notes |
|---|---|---|
| `status` | yes | `ACTIVO`, `NO CONTESTA`, `NO MOLESTAR`, `VISITA` |
| `reason` | no | Max 1000 chars. Explanation for the status change. |

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
