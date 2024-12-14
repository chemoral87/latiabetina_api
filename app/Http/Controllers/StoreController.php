<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Models\Store;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class StoreController extends Controller {

  public function index(Request $request) {
    $filter = $request->get("filter");
    $query = queryServerSide($request, Store::query());
    if ($filter) {
      $query->where("name", "like", "%" . $filter . "%");
    }
    $stores = $query->paginate($request->get('itemsPerPage'));
    return new DataSetResource($stores);
  }

  public function show($id) {
    $store = Store::where("id", $id)->first();
    if ($store == null) {
      abort(405, 'Store not found');
    }
    return response()->json($store);
  }

  public function create(Request $request) {
    $userId = JWTAuth::user()->id;
    $store = Store::create([
      'org_id' => $request->get('org_id'),
      'name' => $request->get('name'),
      'address' => $request->get('address'),
      'city' => $request->get('city'),
      'state' => $request->get('state'),
      'zip' => $request->get('zip'),
      'country' => $request->get('country'),
      'phone' => $request->get('phone'),
      'latitude' => $request->get('latitude'),
      'longitude' => $request->get('longitude'),
      'created_by' => $userId,
      'updated_by' => $userId,
    ]);
    return ['success' => __('messa.store_create')];
  }

  public function update(Request $request, $id) {
    $userId = JWTAuth::user()->id;
    $store = Store::find($id);

    $store->name = $request->get('name');
    $store->address = $request->get('address');
    $store->city = $request->get('city');
    $store->state = $request->get('state');
    $store->zip = $request->get('zip');
    $store->country = $request->get('country');
    $store->phone = $request->get('phone');
    $store->latitude = $request->get('latitude');
    $store->longitude = $request->get('longitude');

    $store->updated_by = $userId;
    $store->save();
    return ['success' => __('messa.store_update')];
  }

  public function delete($id) {
    $store = Store::find($id);
    $store->delete();
    return ['success' => __('messa.store_delete')];
  }

}
