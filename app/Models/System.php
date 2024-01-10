<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class System extends Model {
  protected $table = "System";
  protected $primaryKey = 'system_id';
  public $timestamps = false;
}