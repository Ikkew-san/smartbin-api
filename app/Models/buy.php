<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buy extends Model
{
    protected $table = "buy";
    protected $primaryKey = 'buy_id';
    const CREATED_AT = 'buy_date';
    const UPDATED_AT = null;  
}
