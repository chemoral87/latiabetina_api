<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Http\Resources\UserShowResource;
use App\Models\User;
use App\Notifications\ResetCodeNotification;
use App\Notifications\UserPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Notification;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller {
  public function index(Request $request) {

    // Log::info($request);

    $query = User::query();
    $itemsPerPage = $request->itemsPerPage;
    $sortBy = $request->get('sortBy');
    $sortDesc = $request->get('sortDesc');
    $filter = $request->get("filter");

    foreach ($request->get('sortBy') as $index => $column) {
      $sortDirection = ($sortDesc[$index] == 'true') ? 'DESC' : 'ASC';
      $query = $query->orderBy($column, $sortDirection);
    }
    if ($filter) { // filter
      $query->where(DB::raw("CONCAT_WS(' ',name, last_name, second_last_name)"), "like", "%" . $filter . "%");
    }
    $users = $query->with("roles")->with("permissions")->paginate($itemsPerPage);
    return new DataSetResource($users);
  }

  public function show(Request $request, $id) {
    $user = User::find($id);
    $userResource = new UserShowResource($user);
    return response()->json($userResource);
  }

  public function create(Request $request) {
    $req = $this->validate($request, [
      'name' => 'required',
      'last_name' => 'required',
      'email' => 'required|unique:users,email',
    ]);

    $random_password = strtoupper(Str::random(5));
    $hashed_random_password = Hash::make($random_password);
    // add password

    //User::create($req + ['password' => Hash::make('admin')]);
    User::create($req + ['password' => $hashed_random_password]);

    // notify password
    Notification::route("mail", $request->get("email"))
      ->notify(new UserPasswordNotification($req + ['password' => $random_password, 'cellphone' => $request->get("cellphone")]));

    return ['success' => __('messa.user_create')];
  }

  public function update(Request $request, $id) {
    $this->validate($request, [
      'name' => 'required',
      'last_name' => 'required',
    ]);
    $user = User::find($id);
    $user->name = $request->name;
    $user->last_name = $request->last_name;
    $user->second_last_name = $request->second_last_name;
    $user->cellphone = $request->cellphone;

    if ($request->password != '') {
      $user->password = bcrypt($request->get('password'));
    }

    $user->save();
    return ['success' => __('messa.user_update')];
  }

  public function filter(Request $request) {
    $filter = $request->queryText;
    $ids = isset($request->ids) ? $request->ids : [];
    $users = User::select("name", "last_name", "email", "id")
      ->whereNotIn("id", $ids)
      ->where(DB::raw("CONCAT_WS(' ',name, last_name, second_last_name)"), "like", "%" . $filter . "%")
      ->orderBy("name")->paginate(7);
    return $users->items();
  }

  public function children(Request $request, $id) {
    $user = User::find($id);
    if ($user) {
      $role_ids = $request->role_ids;
      $permissions_ids = $request->permissions_ids;
      $user->roles()->sync($role_ids);
      $user->permissions()->sync($permissions_ids);
    }

    return ['success' => __('messa.user_roles_update')];
  }

  public function delete($id) {
    if ($id != 1) {
      // admin
      User::find($id)->delete();
    }
    return ['success' => __('messa.user_delete')];
  }

  public function changePassword(Request $request) {

    $user_id = JWTAuth::user()->id;
    $user = User::find($user_id);

    $password = trim($request->get("password"));
    $confirm_password = trim($request->get("confirm_password"));
    if ($password == $confirm_password) {
      $user->password = bcrypt($password);
      $user->save();
      return ['success' => __('messa.user_change')];
    }

  }

  public function register(Request $request) {
    $validatedData = $request->validate([
      'name' => 'required|string',
      'last_name' => 'required|string',
      'email' => 'required|email|unique:users',
      'password' => 'required|min:8|confirmed',

    ]);

    $user = User::create([
      'name' => $validatedData['name'],
      'last_name' => $validatedData['last_name'],
      'second_last_name' => $request->get('second_last_name'),
      'email' => $validatedData['email'],
      'password' => bcrypt($validatedData['password']),
    ]);

    // notify password
    Notification::route("mail", $request->get("email"))
      ->notify(new UserPasswordNotification($request->all()));

    return ['success' => __('messa.user_registration')];
  }

  public function sendResetCode(Request $request) {
    $request->validate([
      'email' => 'required|email|exists:users,email',
    ]);

    $user = User::where('email', $request->email)->first();

    // Generate a unique reset code
    $resetCode = strtoupper(Str::random(5));
    $user->reset_code = $resetCode;
    $user->save();

    // Send reset code to user's email
    $user->notify(new ResetCodeNotification($resetCode));
    // Mail::send('emails.reset_code', ['resetCode' => $resetCode], function ($message) use ($user) {
    //   $message->to($user->email)->subject('Código Cambio Contraseña');
    // });

    return response()->json(['success' => 'El código ha sido enviado a su correo, favor de revisar.'], 200);
  }

  /**
   * Reset user's password using the reset code.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function resetPassword(Request $request) {
    $request->validate([
      'email' => 'required|email|exists:users,email',
      'reset_code' => 'required|string',
      'password' => 'required|string|min:8',
      //   'password' => 'required|string|min:8|confirmed',
    ]);

    $user = User::where('email', $request->email)
      ->where('reset_code', $request->reset_code)
      ->whereNotNull('reset_code') // Add this condition to check if reset_code is not null
      ->where(DB::raw('TRIM(reset_code)'), '<>', '') // Add this condition to check if reset_code is not blank

      ->first();

    if (!$user) {
      return response()->json(['message' => 'Código inválido.'], 422);
    }

    // Update the password and reset code
    $user->password = Hash::make($request->password);
    $user->reset_code = null;
    $user->save();

    return response()->json(['success' => 'Se ha restablecido la contraseña.'], 200);
  }

}
