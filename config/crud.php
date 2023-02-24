<?php

return [
    //
    'alertOldFieldsetMethods' => env('CRUD_ALERT_OLD_FIELDSETS_METHOD', true),
    'editFormCardClass' => env('CRUD_EDIT_CARD_CLASS', ''),
    'createFormCardClass' => env('CRUD_CREATE_CARD_CLASS', ''),
    'saveAndNew' => env('CRUD_SAVE_AND_NEW_BUTTON', true),
    'saveAndRefresh' => env('CRUD_SAVE_AND_REFRESH_BUTTON', true),

    'useConcurrentRequestsAlert' => env('CRUD_USE_CONCURRENT_REQUESTS_ALERT', false),


    'concurrentUriPrefix' => env('CRUD_CONCURRENT_URI_PREFIX', 'concurrentUri'),
    'concurrentUriCacheLifetime' => env('CRUD_CONCURRENT_URI_CACHE_LIFETIME', 10),
    'concurrentUriJavascriptTickInterval' => env('CRUD_CONCURRENT_URI_CACHE_LIFETIME', 3000),

    'nestableLeadingId' => 'nest',

    'meta' => [
    	'mandatoryNames' => [
    		'name',
    		'keywords',
    		'description'
    	]
    ]
];