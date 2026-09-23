<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Sale extends Model { protected $fillable=['date','client_id','product_id','quantity','unit_price','total']; protected $casts=['date'=>'date','quantity'=>'integer','unit_price'=>'decimal:2','total'=>'decimal:2']; public function product(){return $this->belongsTo(Product::class);} public function client(){return $this->belongsTo(Client::class);} }
