<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrudChannelsTable extends Migration
{
	public function up()
	{
		Schema::create('crud__channels', function (Blueprint $table) {
			$table->bigIncrements('id');
			$table->string('name')->unique();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::dropIfExists('crud__channels');
	}
}
