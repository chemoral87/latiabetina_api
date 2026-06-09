<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChurchEvent extends Model {
  protected $fillable = [
    'name',
    'slug_name',
    'location',
    'description',
    'start_date',
    'end_date',
    'time_start',
    'url_image',
    'org_id',
    'created_by',
  ];

  protected $hidden = ['url_image'];

  protected $casts = [
    'start_date' => 'date:Y-m-d',
    'end_date' => 'date:Y-m-d',
    'time_start' => 'datetime:H:i',
  ];

  public function getUrlImageS3Attribute() {
    return temporaryUrlS3($this->url_image);
  }

  public function organization() {
    return $this->belongsTo(Organization::class, 'org_id');
  }

  public function creator() {
    return $this->belongsTo(User::class, 'created_by');
  }
}
