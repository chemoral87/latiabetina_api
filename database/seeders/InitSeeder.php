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
    // --- PERMISSIONS (real and fictitious) ---
    $permissions = [
      'user-index', 'user-create', 'user-update', 'user-delete',
      'role-index', 'role-create', 'role-update', 'role-delete',
      'permission-index', 'permission-create', 'permission-update', 'permission-delete',
      'organization-index',
      'alpha-special', 'alpha-view', 'beta-manage', 'beta-export',
    ];
    foreach ($permissions as $perm) {
      Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    // --- ROLES ---
    $roleA1 = Role::firstOrCreate(['name' => 'admin-alpha', 'guard_name' => 'web']);
    $roleA2 = Role::firstOrCreate(['name' => 'user-alpha', 'guard_name' => 'web']);
    $roleB1 = Role::firstOrCreate(['name' => 'admin-beta', 'guard_name' => 'web']);
    $roleB2 = Role::firstOrCreate(['name' => 'user-beta', 'guard_name' => 'web']);

    // Assign real and fictitious permissions to demo roles
    $roleA1->syncPermissions(['user-index', 'alpha-special', 'alpha-view']);
    $roleA2->syncPermissions(['user-index', 'alpha-view']);
    $roleB1->syncPermissions(['user-index', 'beta-manage', 'beta-export']);
    $roleB2->syncPermissions(['user-index', 'beta-export']);

    // --- DEMO USERS/PROFILES ONLY IN LOCAL/DEV ---
    if (app()->environment(['local', 'development', 'dev'])) {
      $faker = \Faker\Factory::create();
      $user1 = User::factory()->create([
        'email' => 'alpha@example.com',
        'password' => Hash::make('admin'),
        'name' => $faker->firstName,
        'last_name' => $faker->lastName,
      ]);
      $user2 = User::factory()->create([
        'email' => 'beta@example.com',
        'password' => Hash::make('admin'),
        'name' => $faker->firstName,
        'last_name' => $faker->lastName,
      ]);

      // Create CAM org if not exists (already created above, so just get it)
      $orgCam = Organization::where('short_code', 'CAM')->first();
      if (!$orgCam) {
        $orgCam = Organization::create([
          'name' => 'CAM',
          'short_code' => 'CAM',
          'description' => 'Org CAM',
        ]);
      }

      // Create profile for alpha@example.com in CAM
      $profileCamAlpha = Profile::firstOrCreate([
        'user_id' => $user1->id,
        'org_id' => $orgCam->id,
      ], [
        'favorite' => false,
      ]);

      // Assign two roles to CAM profile
      $profileCamAlpha->assignRole('admin-alpha');
      $profileCamAlpha->assignRole('user-alpha');
    }

    // create roles
    $role1 = Role::create(['name' => 'super', 'guard_name' => 'web']);
    $role1->givePermissionTo([
      'role-index', 'role-create', 'role-update', 'role-delete',
      'user-index', 'user-create', 'user-update', 'user-delete',
      'permission-index', 'permission-create', 'permission-update', 'permission-delete',
      'organization-index',
    ]);

    $managerRole = Role::create(['name' => 'manager', 'guard_name' => 'web']);
    $managerRole->givePermissionTo(['user-index', 'role-index', 'permission-index']);

    Role::create(['name' => 'publisher', 'guard_name' => 'web']);
    Role::create(['name' => 'cashier', 'guard_name' => 'web']);
    Role::create(['name' => 'leader', 'guard_name' => 'web']);
    Role::create(['name' => 'worker', 'guard_name' => 'web']);
    Role::create(['name' => 'auditor', 'guard_name' => 'web']);

    // create demo user
    $user = User::create([
      'name' => 'Sergio',
      'last_name' => 'Morales',
      'second_last_name' => 'Parra',
      'email' => 'chemoral87@hotmail.com',
      'password' => Hash::make('admin'),
    ]);

    // create organizations (idempotent)
    $orgAdmin = Organization::firstOrCreate(
      ['short_code' => 'ADMIN'],
      ['name' => 'ADMIN', 'description' => 'Administrators']
    );
    $orgCam = Organization::firstOrCreate(
      ['short_code' => 'CAM'],
      ['name' => 'CAM', 'description' => 'Org CAM']
    );
    $orgCom = Organization::firstOrCreate(
      ['short_code' => 'COM'],
      ['name' => 'COM', 'description' => 'Org COM']
    );

    // create profiles for each org (idempotent)
    $profileAdmin = Profile::firstOrCreate(
      ['user_id' => $user->id, 'org_id' => $orgAdmin->id],
      ['favorite' => true]
    );
    $profileCam = Profile::firstOrCreate(
      ['user_id' => $user->id, 'org_id' => $orgCam->id],
      ['favorite' => false]
    );
    $profileCom = Profile::firstOrCreate(
      ['user_id' => $user->id, 'org_id' => $orgCom->id],
      ['favorite' => false]
    );

    // assign different roles to each profile
    $profileAdmin->assignRole('super');
    $profileCam->assignRole('manager');
    $profileCom->assignRole('publisher');

  }

}
