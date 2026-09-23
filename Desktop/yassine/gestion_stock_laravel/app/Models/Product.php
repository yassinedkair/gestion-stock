<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model {
 protected $fillable=['name','buy_price','sell_price','stock_quantity'];
 protected $casts=['buy_price'=>'decimal:2','sell_price'=>'decimal:2','stock_quantity'=>'integer'];
 public function purchases(){return $this->hasMany(Purchase::class);}
 public function sales(){return $this->hasMany(Sale::class);}
}
