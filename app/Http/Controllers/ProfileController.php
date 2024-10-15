<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;

class ProfileController extends Controller {

  public function index(Request $request, $user_id) {
    $user = User::find($user_id);
    if ($user == null) {
      abort(405, 'User not found');
    }

    $profiles = Profile::where('user_id', $user_id)->with('organization')->with('roles')->with('permissions')->get();
    return $profiles;
  }

  public function show(Request $request, $user_id, $profile_id) {
    $user = User::find($user_id);
    if ($user == null) {
      abort(405, 'User not found');
    }
    $profile = Profile::where('user_id', $user_id)->where('id', $profile_id)->first();

    if ($profile == null) {
      abort(405, 'Profile not found');
    }
    return [
      'id' => $profile->id,
      'organization_name' => $profile->organization_name,
      'organization_short_code' => $profile->organization_short_code,
      'roles' => $profile->roles,
      'direct_permissions' => $profile->getDirectPermissions(),
      // 'roles' => $profile->roles,
      // 'permissions' => $profile->getDirectPermissions(),
    ];
  }

  public function create(Request $request, $user_id) {
    $user = User::find($user_id);
    if ($user == null) {
      abort(405, 'User not found');
    }
    // add profiel to user
    $profile = new Profile();
    $profile->user_id = $user_id;
    $profile->org_id = $request->get('org_id');
    $profile->save();

    return ['success' => __('messa.profile_create'), 'profile' => $profile];
  }

  public function update(Request $request, $user_id, $profile_id) {
    $user = User::find($user_id);
    if ($user == null) {
      abort(405, 'User not found');
    }
    $profile = Profile::where('user_id', $user_id)->where('id', $profile_id)->first();
    if ($profile == null) {
      abort(405, 'Profile not found');
    }

    if ($profile) {
      $role_ids = $request->role_ids;
      $permissions_ids = $request->permissions_ids;
      $profile->roles()->sync($role_ids);
      $profile->permissions()->sync($permissions_ids);
    }

    $profile->save();
    return ['success' => __('messa.profile_update')];
  }

  public function delete(Request $request, $user_id, $profile_id) {
    $user = User::find($user_id);
    if ($user == null) {
      abort(405, 'User not found');
    }
    $profile = Profile::where('user_id', $user_id)->where('id', $profile_id)->first();
    if ($profile == null) {
      abort(405, 'Profile not found');
    }
    $profile->delete();
    return ['success' => __('messa.profile_delete')];
  }

  public function favorite(Request $request, $user_id, $profile_id) {
    $user = User::find($user_id);
    if ($user == null) {
      abort(405, 'User not found');
    }

//set all profiles as favorite false and then set the selected profile as favorite true
    Profile::where('user_id', $user_id)->update(['favorite' => false]);
    Profile::where('user_id', $user_id)->where('id', $profile_id)->update(['favorite' => true]);

    // $profile = Profile::where('user_id', $user_id)->where('id', $profile_id)->first();
    // if ($profile == null) {
    //   abort(405, 'Profile not found');
    // }
    // $profile->favorite = !$profile->favorite;
    // $profile->save();

    return ['success' => __('messa.profile_favorite')];
  }

}
