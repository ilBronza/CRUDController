<?php

use IlBronza\CRUD\Http\Controllers\Utilities\AjaxSearchController;
use IlBronza\CRUD\Http\Controllers\Utilities\Archive\ArchiveController;
use IlBronza\CRUD\Http\Controllers\Utilities\History\HistoryController;
use IlBronza\CRUD\Http\Controllers\Utilities\History\HistoryByFieldController;
use IlBronza\CRUD\Http\Controllers\Utilities\Media\DeleteMediaController;
use IlBronza\CRUD\Http\Controllers\Utilities\Media\MediaCollectionController;
use IlBronza\CRUD\Http\Controllers\Utilities\Media\SetMediaIntegrityCheckController;
use IlBronza\CRUD\Http\Controllers\Utilities\Relationships\AssociateBelongsToManyController;
use Illuminate\Support\Facades\Route;


Route::get('close-iframe', function(Request $request)
{
	return view('crud::utilities.messages.closeIframe', [
		'closeMessage' => __('crud::messages.success'),
		'reloadAllTables' => true
	]);
})->name('iframe.close');



Route::get('cache-clear', function()
	{
		cache()->flush();

		return back();
	})
->name('cache.clear');

Route::get('/server-cache-clear', function() {

    $exitCode = Artisan::call('cache:clear');
    echo $exitCode;

    $exitCode = Artisan::call('config:cache');
    echo $exitCode;

    return ;
})->name('servercache.clear');

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

		Route::get('collection/class/{model}/key/{key}/collection/{collection}', [MediaCollectionController::class, 'show'])->name('media.collection');

		Route::delete('collection/class/{model}/key/{key}/media/{media}', [MediaCollectionController::class, 'destroy'])->name('media.collection.destroy');

		Route::post('media/{media}/set-integrity-check', [SetMediaIntegrityCheckController::class, 'setIntegrityCheck'])->name('media.setIntegrityCheck');
	}
);

Route::group([
		'middleware' => ['web'],
		'prefix' => 'history',
		'as' => 'history.',
		'routeTranslationPrefix' => 'crud::routes.',
	],
	function()
	{
		Route::get('show/class/{model}/key/{key}', [HistoryController::class, 'show'])->name('show');
		Route::get('show-by-field/class/{model}/key/{key}/field/{field}', [HistoryByFieldController::class, 'show'])->name('historyByField');
	}
);

Route::group([
		'middleware' => ['web'],
		'prefix' => 'crud/archiver',
		'as' => 'crud.',
	],
	function()
	{
		Route::put('archive/class/{model}/key/{key}', [ArchiveController::class, 'archive'])->name('archive');
		Route::put('unarchive/class/{model}/key/{key}', [ArchiveController::class, 'unarchive'])->name('unarchive');
	}
);

Route::post('crud/ajax-search', AjaxSearchController::class)
	->middleware('web')
	->name('crud.ajaxSearch');

Route::delete('crud/media/model-class/{modelClass}/model-key/{modelKey}/media-key/{mediaKey}', [DeleteMediaController::class, 'destroy'])
	->middleware('web')
	->name('crud.media.destroy');

Route::get('crud/relationships/model/{model}/key/{key}/relation/{relation}/associate', [AssociateBelongsToManyController::class, 'index'])
	->middleware('web')
	->name('crud.relationships.associate');

Route::post('crud/relationships/model/{model}/key/{key}/relation/{relation}/associate', [AssociateBelongsToManyController::class, 'store'])
	->middleware('web');
