<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::create('capital_operations',function(Blueprint $t){$t->id();$t->date('date');$t->string('type');$t->decimal('amount',12,2);$t->boolean('movement')->default(true);$t->timestamps();});} public function down():void{Schema::dropIfExists('capital_operations');} };
