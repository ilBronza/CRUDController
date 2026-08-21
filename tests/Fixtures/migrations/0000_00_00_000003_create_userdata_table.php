<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// AccountManager\Models\User ha $with = ['userdata']: senza questa tabella
// ogni render del layout esplode.
return new class extends Migration
{
	public function up()
	{
		Schema::create('users__userdata', function (Blueprint $table)
		{
			$table->uuid('id')->primary();
			$table->unsignedBigInteger('user_id');
			$table->string('name')->nullable();
			$table->string('surname')->nullable();
			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down()
	{
		Schema::dropIfExists('users__userdata');
	}
};
