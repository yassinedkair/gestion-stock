<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::create('products',function(Blueprint $t){$t->id();$t->string('name');$t->decimal('buy_price',12,2)->default(0);$t->decimal('sell_price',12,2)->default(0);$t->unsignedInteger('stock_quantity')->default(0);$t->timestamps();});} public function down():void{Schema::dropIfExists('products');} };
