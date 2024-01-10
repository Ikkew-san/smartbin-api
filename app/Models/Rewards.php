<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rewards extends Model
{
    protected $table = "rewards";
    protected $primaryKey = 'rewards_id';
    public $timestamps = false;
}
