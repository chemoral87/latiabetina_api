# expense-ticket-create

Permiso `expense-ticket-create` en la API.

## Files

- `routes\api.php` (middleware `permission_org:expense-ticket-create`)

## Routes protected

- `POST /expense-tickets -> ExpenseTicketsController@create`
- `POST /expenses -> ExpensesController@create`
- `POST /expense-categories -> ExpenseCategoriesController@create`
- `POST /expense-concepts -> ExpenseConceptsController@create`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: el permiso `expense-ticket-*` cubre todo el módulo de gastos (tickets, líneas de gasto, categorías y conceptos).
