<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CapitalOperation extends Model { protected $fillable=['date','type','amount','movement']; protected $casts=['date'=>'date','amount'=>'decimal:2','movement'=>'boolean']; }
