<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Events\CrudModelBroadcasted;
use IlBronza\CRUD\Models\CrudChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait CRUDModelBroadcastingTrait
{
	/**
	 * Override this in a CRUD controller to customise the text sent to clients.
	 * Return null to suppress the broadcast for a specific model/action.
	 */
	public function getCrudBroadcastText(Model $model, string $action) : ?string
	{
		$parameters = [
			'user' => \Auth::user()?->getName(),
			'model' => $model->getTranslatedClassName(),
			'modelName' => $model->getName()
		];

		return trans('crud::broadcastMessages.' . $action, $parameters);
	}

	/**
	 * Override this when a controller needs a more specific private channel.
	 * Restituire un nome singolo, un array di nomi, oppure null per non inviare.
	 *
	 * The default keeps model classes out of the channel name: a fully qualified
	 * PHP class contains backslashes, which are not suitable for broadcasters.
	 */
	public function getCrudBroadcastChannel(Model $model, string $action) : string | array | null
	{
		if(method_exists($model, 'getCrudBroadcastChannel'))
			return $model->getCrudBroadcastChannel();

		return rtrim(config('crud.broadcast.channel'), '.')
			. '.'
			. Str::kebab(class_basename($model));
	}

	public function broadcastCrudModel(Model $model, string $action) : void
	{
		if(! config('crud.broadcast.enabled'))
			return;

		if(is_null($text = $this->getCrudBroadcastText($model, $action)))
			return;

		$this->broadcastCrudMessage($model, $text, $action);
	}

	/** Send an explicit free-text message from a custom CRUD action. */
	public function broadcastCrudMessage(Model $model, string $text, string $action = 'updated') : void
	{
		if(! config('crud.broadcast.enabled'))
			return;

		$channels = Arr::wrap($this->getCrudBroadcastChannel($model, $action));

		if(method_exists($model, 'broadcastCrud'))
		{
			$model->broadcastCrud($action, $text, $channels);

			return;
		}

		if(! $channels)
			return;

		foreach($channels as $channel)
			CrudChannel::firstOrCreate([
				'name' => $channel,
			]);

		event(new CrudModelBroadcasted($model, $text, $action, $channels));
	}
}
