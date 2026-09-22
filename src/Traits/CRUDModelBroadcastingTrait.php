<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Database\Eloquent\Model;

trait CRUDModelBroadcastingTrait
{
	/**
	 * Override this in a CRUD controller to customise the text sent to clients.
	 * Return null to suppress the broadcast for a specific model/action.
	 */
	public function getCrudBroadcastText(Model $model, string $action) : ? string
	{
		return $model->getCrudBroadcastText($action);
	}

	/**
	 * Canali di contesto: esistono solo dentro questa richiesta e si sommano
	 * a quelli che il model porta con sé.
	 */
	public function getCrudBroadcastExtraChannels(Model $model, string $action) : array
	{
		if(! method_exists($this, 'getCrudBroadcastChannel'))
			return [];

		$channels = $this->getCrudBroadcastChannel($model, $action);

		return array_values(array_filter(
			is_array($channels) ? $channels : [$channels],
			fn($channel) => ! is_null($channel) && $channel !== ''
		));
	}

	/**
	 * Compatibility helper for controllers that still expose their contextual
	 * channel through getCrudBroadcastChannel().
	 */
	public function getCrudBroadcastChannel(Model $model, string $action) : string | array | null
	{
		return [];
	}

	public function broadcastCrudModel(Model $model, string $action) : void
	{
		// if(! config('crud.broadcast.enabled'))
		// 	return;

		// if(is_null($text = $this->getCrudBroadcastText($model, $action)))
		// 	return;

		// $this->broadcastCrudMessage($model, $text, $action);
	}

	/** Send an explicit free-text message from a custom CRUD action. */
	public function broadcastCrudMessage(Model $model, string $text, string $action = 'updated') : void
	{
		$model->broadcastCrud(
			$action,
			$text,
			$this->getCrudBroadcastExtraChannels($model, $action)
		);
	}
}
