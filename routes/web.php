<?php

use Illuminate\Support\Facades\Route;

Route::group([
		'middleware' => ['web'],
		'prefix' => 'files-manager',
		'namespace' => 'IlBronza\CRUD\Http\Controllers'
	],
	function()
	{
		Route::get('show-media/{media}', 'ShowMediaController@show')->name('media.show');
	}
);