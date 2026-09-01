<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Http\Resources\RoleShowResource;
use App\Models\Profile;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller {
  // https://codingdriver.com/laravel-user-roles-and-permissions-tutorial-with-example.html
  public function index(Request $request) {
    $query = new Role;
    $itemsPerPage = $request->itemsPerPage;
    $sortBy = $request->get('sortBy');
    $sortDesc = $request->get('sortDesc');
    $filter = $request->get("filter");

    foreach ($request->get('sortBy') as $index => $column) {
      $sortDirection = ($sortDesc[$index] == 'true') ? 'DESC' : 'ASC';
      $query = $query->orderBy($column, $sortDirection);
    }
    if ($filter) {
      $query->where("name", "like", "%" . $filter . "%");
    }

    $roles = $query->with("permissions")->paginate($itemsPerPage);
    return new DataSetResource($roles);
  }

  public function show(Request $request, $id) {
    $userResource = new RoleShowResource(Role::find($id));
    return response()->json($userResource);
  }


  public function distribution(Request $request, $id) {
    $role = Role::findOrFail($id);
    $query = Profile::whereHas('roles', function ($query) use ($role) {
      $query->where('roles.id', $role->id);
    });

    // Filter by organization if org_id is provided
    if ($request->has('org_id') && $request->org_id) {
      $query->where('org_id', $request->org_id);
    }

    $profiles = $query
      ->with([
        'user:id,name,last_name,second_last_name,email,last_login_at',
        'organization:id,name,short_code',
      ])
      ->orderBy('user_id')
      ->get();

    return response()->json([
      'role' => ['id' => $role->id, 'name' => $role->name],
      'profiles' => $profiles,
    ]);
  }
  public function filter(Request $request) {
    $filter = $request->queryText;
    $ids = isset($request->ids) ? $request->ids : [];
    $roles = Role::select("name", "id")
      ->whereNotIn("id", $ids)
      ->where("name", "like", "%" . $filter . "%")
      ->orderBy("name")->paginate(6);
    return $roles->items();
  }

  public function create(Request $request) {
    $this->validate($request, [
      'name' => 'required|unique:roles,name',

    ]);

    $role = Role::create(['name' => $request->input('name'), 'guard_name' => 'api']);
    $role->syncPermissions($request->permissions);
    $role->load('permissions');

    return response()->json([
      'success' => __('messa.role_create'),
      'data' => $role,
    ]);
  }

  public function update(Request $request, $id) {
    $this->validate($request, ['name' => 'required']);
    $role = Role::find($id);
    $role->name = $request->input('name');
    $role->save();
    $role->load('permissions');
    return [
      'success' => __('messa.role_update'),
      'data' => $role,
    ];
  }

  public function children(Request $request, $id) {
    $role = Role::find($id);
    if ($role) {
      $permissions_ids = $request->permissions_ids;
      $role->permissions()->sync($permissions_ids);
    }
    return ['success' => __('messa.role_permission_update')];
  }

  public function addPermission(Request $request, $id) {
    // Accept either `name` as comma-separated string or `names` as array for bulk creation
    // e.g. "song-update, song-delete" -> creates two permissions in one call
    $raw = $request->input('name');
    $namesInput = $request->input('names');

    $names = [];
    if (is_array($namesInput) && !empty($namesInput)) {
      $names = $namesInput;
    } elseif (is_string($raw) && trim($raw) !== '') {
      $names = explode(',', $raw);
    } elseif (is_array($raw) && !empty($raw)) {
      $names = $raw;
    }

    $names = array_map('trim', $names);
    $names = array_filter($names, fn($n) => $n !== '');

    // Deduplicate case-insensitive, preserve original casing of first occurrence
    $seen = [];
    $unique = [];
    foreach ($names as $n) {
      $low = strtolower($n);
      if (!isset($seen[$low])) {
        $seen[$low] = true;
        $unique[] = $n;
      }
    }
    $names = $unique;

    if (empty($names)) {
      return response()->json([
        'message' => 'The name field is required.',
        'errors' => ['name' => ['El campo nombre es requerido.']],
      ], 422);
    }

    // Validate each name
    $validationErrors = [];
    foreach ($names as $idx => $n) {
      if (mb_strlen($n) > 255) {
        $validationErrors[$idx] = 'El nombre no debe exceder 255 caracteres.';
      } elseif (!preg_match('/^[a-z0-9\-_]+$/i', $n)) {
        // Allow typical permission pattern: letters, numbers, dash, underscore
        // If you need other chars, relax this regex
        $validationErrors[$idx] = 'El nombre solo puede contener letras, números, guiones y guiones bajos.';
      }
    }
    if (!empty($validationErrors)) {
      return response()->json([
        'message' => 'Validation failed.',
        'errors' => ['name' => array_values($validationErrors)],
      ], 422);
    }

    $role = Role::findOrFail($id);
    $guard = $role->guard_name ?? 'web';
    $created = [];

    foreach ($names as $name) {
      $permission = Permission::firstOrCreate(['name' => trim($name), 'guard_name' => $guard]);
      if (!$role->hasPermissionTo($permission)) {
        $role->givePermissionTo($permission);
      }
      $created[] = ['id' => $permission->id, 'name' => $permission->name];
    }

    // Backward compatibility: single case still returns `permission`
    if (count($created) === 1) {
      return response()->json([
        'success' => __('messa.role_permission_update'),
        'permission' => $created[0],
        'permissions' => $created,
      ]);
    }

    return response()->json([
      'success' => __('messa.role_permission_update'),
      'permissions' => $created,
      'permission' => $created[0],
    ]);
  }

  public function delete($id) {
    Role::find($id)->delete();
    return ['success' => __('messa.role_delete')];
  }

}

