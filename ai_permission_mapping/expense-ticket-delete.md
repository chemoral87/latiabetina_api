# expense-ticket-delete

Permiso `expense-ticket-delete` en la API.

## Files

- `routes\api.php` (middleware `permission_org:expense-ticket-delete`)

## Routes protected

- `DELETE /expense-tickets/{id} -> ExpenseTicketsController@delete`
- `DELETE /expenses/{id} -> ExpensesController@delete`
- `DELETE /expense-categories/{id} -> ExpenseCategoriesController@delete`
- `DELETE /expense-concepts/{id} -> ExpenseConceptsController@delete`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: el permiso `expense-ticket-*` cubre todo el módulo de gastos (tickets, líneas de gasto, categorías y conceptos).
