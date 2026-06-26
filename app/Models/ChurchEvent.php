<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChurchEvent extends Model {
  protected $fillable = [
    'name',
    'slug_name',
    'location',
    'description',
    'publish_date',
    'event_date',
    'time_start',
    'url_image',
    'classification',
    'org_id',
    'created_by',
  ];

  protected $hidden = ['url_image'];

  protected $casts = [
    'publish_date' => 'date:Y-m-d',
    'event_date' => 'date:Y-m-d',
    'time_start' => 'datetime:H:i',
  ];

  public function getUrlImageS3Attribute() {
    return permanentUrlS3($this->url_image);
  }

  public function organization() {
    return $this->belongsTo(Organization::class, 'org_id');
  }

  public function creator() {
    return $this->belongsTo(User::class, 'created_by');
  }
}
