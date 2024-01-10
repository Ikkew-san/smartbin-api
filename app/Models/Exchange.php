<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exchange extends Model {
  protected $table = "exchange";
  protected $primaryKey = 'exchange_id';
  const CREATED_AT = 'exchange_created_at';
  const UPDATED_AT = 'exchange_updated_at';
}