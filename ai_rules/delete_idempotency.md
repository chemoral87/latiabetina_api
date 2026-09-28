# Rule: DELETE endpoints must be idempotent

A `delete($id)` must return success whether or not the row still exists.
Calling it twice in a row must not error. The client retries, double-clicks,
or a background row-animation keeps a stale id in the DOM — none of that
should produce a 404/405/500.

## Canonical pattern

```php
public function delete($id) {
    $model = Model::find($id);   // NOT findOrFail
    if ($model) {
        $model->delete();        // side effects (detach/child delete) go INSIDE the guard
    }
    return ['success' => __('messa.{resource}_delete')];
}
```

- `find` (returns `null` when gone) — never `findOrFail`.
- Guard the dereference: `if ($model) { ... }`. `find($id)->delete()` on a
  missing row is a null dereference → 500, not idempotent.
- Never `abort(404|405)` for a missing id on DELETE.
- Side effects (`detach()`, cascade deletes) run only when the row was found.
- Response is identical on first and second call: `{ success: "..." }`, HTTP 200.

## Anti-patterns (all break idempotency)

| Pattern | Second call does |
|---------|------------------|
| `Model::findOrFail($id)->delete()` | 404 |
| `Model::find($id)->delete()` | 500 (null deref) |
| `$x = Model::find($id); $x->delete();` | 500 (null deref) |
| `if (!$x) abort(405, ...)` | 405 |
| `$relation->findOrFail($childId)` | 404 |

## Controllers to fix (audit 2026-09-28)

| Controller | Line | Current | Fix |
|------------|------|---------|-----|
| `AuditoriumController` | 130 | `findOrFail` | `find` + guard |
| `ChurchMemberController` | 270 | `findOrFail` | `find` + guard |
| `ChurchMemberTrackingLogController` | 201 | `trackingLogs()->findOrFail` | `find` + guard for log only |
| `ExpenseTicketsController` | 113 | `abort(405)` when null | drop abort, return success |
| `ExpenseConceptsController` | 70 | `abort(405)` when null | guard + `detach` inside |
| `ExpensesController` | 135 | `abort(405)` when null | drop abort, return success |
| `ExpenseCategoriesController` | 96 | `abort(405)` when null | guard + `detach` inside |
| `PermissionController` | 123 | `find($id)->delete()` | guard null |
| `OrganizationController` | 73 | `find()` then `->delete()` | guard null |
| `RoleController` | 193 | `find($id)->delete()` | guard null |
| `StoreController` | 70 | `find()` then `->delete()` | guard null |
| `UserController` | 120 | `find($id)->delete()` | guard null |
| `ProfileController` | 77 | `abort(405)` when null | drop aborts, return success |

Already compliant: `ConsoSheetController::delete` (line 155).

## Notes

- `ProfileController::delete` and `ChurchMemberTrackingLogController::deleteTrackingLog`
  keep their 403 ownership check — a wrong owner is an authorization error, not
  an idempotency one; the check must stay before the "already gone" path.
- This is about HTTP semantics, not soft deletes. Soft-deleted rows are
  invisible to `find`, so the guard handles them the same way.
- Do NOT change `show`/`update` to `find` — those legitimately 404 on missing
  rows. Idempotency applies to DELETE only.
