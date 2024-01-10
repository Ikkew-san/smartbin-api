<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Smartbin extends Model {
  protected $table = "smartbin";
  protected $primaryKey = 'smartbin_id';
  public $timestamps = false;
}