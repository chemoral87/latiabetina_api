# expense-ticket-update

Permiso `expense-ticket-update` en la API.

## Files

- `routes\api.php` (middleware `permission_org:expense-ticket-update`)

## Routes protected

- `PUT /expense-tickets/{id} -> ExpenseTicketsController@update`
- `PUT /expenses/{id} -> ExpensesController@update`
- `PUT /expense-categories/{id} -> ExpenseCategoriesController@update`
- `PUT /expense-concepts/{id} -> ExpenseConceptsController@update`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: el permiso `expense-ticket-*` cubre todo el módulo de gastos (tickets, líneas de gasto, categorías y conceptos).
