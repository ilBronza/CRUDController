<?php

return [
    //
	/*
	 * Ricerca AJAX per contesto: la chiave (es. production) va passata come `field` nella POST
	 * insieme a `q` (testo cercato). I modelli sono risolti da alias morph, FQCN, o tentativo App\Models\* .
	 *
	 * 'ajaxSearchFields' => [
	 *     'production' => [
	 *         'models' => [
	 *             'product' => ['name', 'description', 'notes'],
	 *             'order' => ['name', 'description'],
	 *         ],
	 *         'per_model_limit' => 20,
	 *         'total_limit' => 100,
	 *     ],
	 * ],
	 */
	'ajaxSearchFields' => [],

	'ajaxSearch' => [
		'per_model_limit' => 20,
		'total_limit' => 100,
	],

	'realtionshipManagers' => [
		'active' => true,
		'selectRowCheckboxes' => false,
		'reloadButton' => false,
		'copyButton' => false,
		'csvButton' => false,
	],

	'cache' => [
		'highlightClickedLinks' => [
			'enabled' => false
		]
	],

	//inside every show controller
	'showEditLink' => true,

	'alertOldFieldsetMethods' => env('CRUD_ALERT_OLD_FIELDSETS_METHOD', true),
    'editFormCardClass' => env('CRUD_EDIT_CARD_CLASS', ''),
    'createFormCardClass' => env('CRUD_CREATE_CARD_CLASS', ''),
    'saveAndNew' => env('CRUD_SAVE_AND_NEW_BUTTON', false),
    'saveAndRefresh' => env('CRUD_SAVE_AND_REFRESH_BUTTON', false),
    'saveAndCopy' => env('CRUD_SAVE_AND_COPY_BUTTON', false),

    'useConcurrentRequestsAlert' => env('CRUD_USE_CONCURRENT_REQUESTS_ALERT', false),

    'concurrentUriPrefix' => env('CRUD_CONCURRENT_URI_PREFIX', 'concurrentUri'),
    'concurrentUriCacheLifetime' => env('CRUD_CONCURRENT_URI_CACHE_LIFETIME', 10),
    'concurrentUriJavascriptTickInterval' => env('CRUD_CONCURRENT_URI_CACHE_LIFETIME', 3000),

    'nestableLeadingId' => 'nest',
	'missingImageUrl' => '/img/no_user.png',

    'meta' => [
    	'mandatoryNames' => [
    		'name',
    		'keywords',
    		'description'
    	]
    ],

    'timelineZoom' => 60, // giorni visibili allo zoom iniziale

	'calendar' => [
		'nextDayThreshold' => '10:00:00',
		'monthTimeline' => [
			'minWidthPercent' => 3,
			'fillOpacity' => 0.35,
			'trackColor' => '#e9ecef',
			'timeColumnWidth' => '3.25rem',
		],
	],
];