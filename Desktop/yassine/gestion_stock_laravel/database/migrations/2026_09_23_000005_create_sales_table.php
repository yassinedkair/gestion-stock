<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::create('sales',function(Blueprint $t){$t->id();$t->date('date');$t->foreignId('client_id')->constrained()->restrictOnDelete();$t->foreignId('product_id')->constrained()->restrictOnDelete();$t->unsignedInteger('quantity');$t->decimal('unit_price',12,2);$t->decimal('total',12,2);$t->timestamps();});} public function down():void{Schema::dropIfExists('sales');} };
