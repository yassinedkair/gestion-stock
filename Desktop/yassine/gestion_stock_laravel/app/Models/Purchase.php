<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Purchase extends Model { protected $fillable=['date','supplier_id','product_id','quantity','unit_price','total']; protected $casts=['date'=>'date','quantity'=>'integer','unit_price'=>'decimal:2','total'=>'decimal:2']; public function product(){return $this->belongsTo(Product::class);} public function supplier(){return $this->belongsTo(Supplier::class);} }
