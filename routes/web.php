<?php

use Illuminate\Support\Facades\Route;

Route::get('cache-clear', function()
	{
		cache()->flush();

		return back();
	})
->name('cache.clear');

Route::group([
		'middleware' => ['web'],
		'prefix' => 'concurrent-uri',
		'namespace' => 'IlBronza\CRUD\Http\Controllers'
	],
	function()
	{
		// Route::post('check', 'ConcurrentUriController@check')->name('concurrentUri.check');
		Route::post('tick', 'ConcurrentUriController@tick')->name('concurrentUri.tick');
		Route::post('leave-page', 'ConcurrentUriController@leavePage')->name('concurrentUri.leavePage');
	}
);
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