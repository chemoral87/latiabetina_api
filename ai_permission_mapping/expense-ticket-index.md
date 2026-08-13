# expense-ticket-index

Permiso `expense-ticket-index` en la API.

## Files

- `routes\api.php` (middleware `permission_org:expense-ticket-index`)

## Routes protected

- `GET /expense-tickets -> ExpenseTicketsController@index`
- `GET /expense-tickets/{id} -> ExpenseTicketsController@show`
- `GET /expenses -> ExpensesController@index`
- `GET /expenses/{id} -> ExpensesController@show`
- `GET /expense-categories -> ExpenseCategoriesController@index`
- `GET /expense-categories/{id} -> ExpenseCategoriesController@show`
- `GET /expense-concepts -> ExpenseConceptsController@index`
- `GET /expense-concepts/{id} -> ExpenseConceptsController@show`

## Enforced by

- `app\Http\Middleware\CheckOrgPermission.php` (middleware `permission_org`)

> Nota: el permiso `expense-ticket-*` cubre todo el módulo de gastos (tickets, líneas de gasto, categorías y conceptos). Los controladores de tickets y gastos aplican scope por organización vía `store->org_id` (whereHas), y `create`/`update` validan que la tienda/ticket pertenezca a una org permitida (403 en caso contrario). Las categorías y conceptos son datos de referencia globales sin scope.
