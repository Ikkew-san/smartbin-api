<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $table = "alert";
    protected $primaryKey = 'alert_id';
    const CREATED_AT = 'alert_date';
    const UPDATED_AT = null;  
}
