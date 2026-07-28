<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\CRUD\Events\CrudModelBroadcasted;
use IlBronza\CRUD\Models\CrudChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait BroadcastCreateTrait
{
	public static function bootBroadcastCreateTrait() : void
	{
		static::created(function(Model $model)
		{
			$model->broadcastCrudCreated();
		});
	}

	/**
	 * Override in the model and return null to suppress the created broadcast.
	 */
	public function getCrudCreatedBroadcastText() : ?string
	{
		return trans('crud::broadcastMessages.created', [
			'user' => \Auth::user()?->getName(),
			'model' => $this->getTranslatedClassName(),
			'modelName' => $this->getName(),
		]);
	}

	/** Override in the model when it needs a more specific private channel. */
	public function getCrudCreatedBroadcastChannel() : ?string
	{
		return rtrim(config('crud.broadcast.channel'), '.')
			. '.'
			. Str::kebab(class_basename($this));
	}

	public function broadcastCrudCreated() : void
	{
		if(! config('crud.broadcast.enabled'))
			return;

		if(is_null($text = $this->getCrudCreatedBroadcastText()))
			return;

		if(! $channel = $this->getCrudCreatedBroadcastChannel())
			return;

		CrudChannel::firstOrCreate([
			'name' => $channel,
		]);

		event(new CrudModelBroadcasted($this, $text, 'created', $channel));
	}
}
