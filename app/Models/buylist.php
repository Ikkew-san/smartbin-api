<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buylist extends Model
{
    protected $table = "buylist";
    protected $primaryKey = 'buylist_id';
    public $timestamps = false;
}
