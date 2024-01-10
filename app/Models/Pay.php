<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pay extends Model {
  protected $table = "pay";
  protected $primaryKey = 'pay_id';
  const CREATED_AT = 'pay_date';
  const UPDATED_AT = null;
}