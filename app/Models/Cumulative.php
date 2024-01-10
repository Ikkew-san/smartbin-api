<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cumulative extends Model
{
    protected $table = "cumulative";
    protected $primaryKey = 'cumulative_id';
    const CREATED_AT = 'cumulative_datetime';
    const UPDATED_AT = null;  
}
