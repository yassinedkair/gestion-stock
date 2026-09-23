<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Charge extends Model { protected $fillable=['date','description','amount']; protected $casts=['date'=>'date','amount'=>'decimal:2']; }
