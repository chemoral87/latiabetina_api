<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InitSeeder extends Seeder {
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run() {

    // create permissions
    Permission::create(['name' => 'user-index']);
    Permission::create(['name' => 'user-create']);
    Permission::create(['name' => 'user-update']);
    Permission::create(['name' => 'user-delete']);

    Permission::create(['name' => 'role-index']);
    Permission::create(['name' => 'role-create']);
    Permission::create(['name' => 'role-update']);
    Permission::create(['name' => 'role-delete']);

    Permission::create(['name' => 'permission-index']);
    Permission::create(['name' => 'permission-create']);
    Permission::create(['name' => 'permission-update']);
    Permission::create(['name' => 'permission-delete']);

    // create role
    $role1 = Role::create(['name' => 'super']);
    $role1->givePermissionTo('role-index');
    $role1->givePermissionTo('role-create');
    $role1->givePermissionTo('role-update');
    $role1->givePermissionTo('role-delete');

    $role1->givePermissionTo('user-index');
    $role1->givePermissionTo('user-create');
    $role1->givePermissionTo('user-update');
    $role1->givePermissionTo('user-delete');

    $role1->givePermissionTo('permission-index');
    $role1->givePermissionTo('permission-create');
    $role1->givePermissionTo('permission-update');
    $role1->givePermissionTo('permission-delete');

    Role::create(['name' => 'publisher']);
    Role::create(['name' => 'cashier']);
    Role::create(['name' => 'leader']);
    Role::create(['name' => 'worker']);
    Role::create(['name' => 'auditor']);

    // create demo users
    $user = User::create([
      'name' => 'Sergio',
      'last_name' => 'Morales',
      'second_last_name' => 'Parra',
      'email' => 'chemoral87@hotmail.com',
      'password' => Hash::make('admin'),
    ]);

    $organization = Organization::create([
      'name' => 'admin',
      'short_code' => 'admin',
      'description' => 'administrators',
    ]);

    $profile = Profile::create([
      'user_id' => $user->id, // Relaciona con el usuario creado
      'org_id' => $organization->id, // Relaciona con la organización creada
      'favorite' => true, // Establece el valor de 'favorite'
    ]);

    $profile->roles()->sync([$role1->id]);

  }

}
