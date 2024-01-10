<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sell extends Model
{
    protected $table = "sell";
    protected $primaryKey = 'sell_id';
    const CREATED_AT = 'sell_date';
    const UPDATED_AT = null;  
}
