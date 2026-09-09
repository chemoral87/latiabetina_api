<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DataSetResource extends JsonResource {

  public function toArray($request) {
    return [
      'data' => $this->items(),
      'total' => $this->total(),
      'itemsPerPage' => (int) $this->perPage(),
    ];
    // return parent::toArray($request);
  }
}
