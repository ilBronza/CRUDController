<?php

if(! trait_exists(\Spatie\Activitylog\Models\Concerns\LogsActivity::class)
	&& trait_exists(\Spatie\Activitylog\Traits\LogsActivity::class))
	class_alias(\Spatie\Activitylog\Traits\LogsActivity::class, \Spatie\Activitylog\Models\Concerns\LogsActivity::class);

if(! class_exists(\Spatie\Activitylog\Support\LogOptions::class)
	&& class_exists(\Spatie\Activitylog\LogOptions::class))
	class_alias(\Spatie\Activitylog\LogOptions::class, \Spatie\Activitylog\Support\LogOptions::class);
