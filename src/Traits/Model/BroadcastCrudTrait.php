<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\CRUD\Events\CrudModelBroadcasted;
use IlBronza\CRUD\Models\CrudChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait BroadcastCrudTrait
{
	public static function bootBroadcastCrudTrait() : void
	{
		static::created(function(Model $model)
		{
			$model->broadcastCrudCreated();
		});
	}

	/**
	 * Touch the model as usual and notify listeners when the timestamp changed.
	 */
	public function touch($attribute = null)
	{
		$touched = parent::touch($attribute);

		if($touched)
			$this->broadcastCrudTouched();

		return $touched;
	}

	/**
	 * Kept as a dedicated hook for backwards-compatible model customisation.
	 */
	public function getCrudCreatedBroadcastText() : ?string
	{
		return $this->getCrudModelBroadcastText('created');
	}

	/**
	 * Kept as a dedicated hook for backwards-compatible model customisation.
	 */
	public function getCrudTouchedBroadcastText() : ?string
	{
		return $this->getCrudModelBroadcastText('updated');
	}

	protected function getCrudModelBroadcastText(string $action) : string
	{
		return trans('crud::broadcastMessages.' . $action, [
			'user' => \Auth::user()?->getName(),
			'model' => $this->getTranslatedClassName(),
			'modelName' => $this->getName(),
		]);
	}

	/**
	 * Canali intrinseci del model: dove va pubblicata la modifica
	 * indipendentemente da chi l'ha fatta.
	 */
	public function getCrudBroadcastChannels(string $action) : array
	{
		$method = 'getCrudBroadcastChannelsFor' . Str::studly($action);

		$channels = method_exists($this, $method)
			? $this->{$method}()
			: Arr::wrap($this->getCrudBroadcastChannel());

		return $this->normalizeCrudBroadcastChannels($channels);
	}

	public function getCrudBroadcastChannelsForCreated() : array
	{
		return Arr::wrap($this->getCrudCreatedBroadcastChannel());
	}

	public function getCrudBroadcastChannelsForUpdated() : array
	{
		return Arr::wrap($this->getCrudTouchedBroadcastChannel());
	}

	public function getCrudBroadcastChannelsForDeleted() : array
	{
		return Arr::wrap($this->getCrudBroadcastChannel());
	}

	public function getCrudBroadcastChannelsForForceDeleted() : array
	{
		return Arr::wrap($this->getCrudBroadcastChannel());
	}

	public function getCrudBroadcastChannelsForArchived() : array
	{
		return Arr::wrap($this->getCrudBroadcastChannel());
	}

	/**
	 * Canale base del model.
	 */
	public function getCrudBroadcastChannel() : string | array | null
	{
		return rtrim(config('crud.broadcast.channel'), '.')
			. '.'
			. $this->getCrudBroadcastSlug();
	}

	/**
	 * Legacy action-specific hooks retained for existing model overrides.
	 */
	public function getCrudCreatedBroadcastChannel() : string | array | null
	{
		return $this->getCrudBroadcastChannel();
	}

	public function getCrudTouchedBroadcastChannel() : string | array | null
	{
		return $this->getCrudBroadcastChannel();
	}

	/**
	 * Da sovrascrivere quando l'app estende il model con gpc() dandogli un
	 * altro nome: il canale deve restare quello che il javascript ascolta.
	 */
	public function getCrudBroadcastSlug() : string
	{
		return Str::kebab(class_basename($this));
	}

	/**
	 * I canali passati da fuori (il contesto del controller) si sommano a
	 * quelli del model, non li sostituiscono.
	 */
	public function broadcastCrud(string $action, string $text, array $extraChannels = []) : void
	{
		if(! config('crud.broadcast.enabled'))
			return;

		$channels = $this->normalizeCrudBroadcastChannels([
			...$this->getCrudBroadcastChannels($action),
			...$extraChannels
		]);

		if(! $channels)
			return;

		foreach($channels as $channel)
			CrudChannel::firstOrCreate([
				'name' => $channel,
			]);

		event(new CrudModelBroadcasted($this, $text, $action, $channels));
	}

	public function broadcastCrudCreated() : void
	{
		if(! config('crud.broadcast.enabled'))
			return;

		if(is_null($text = $this->getCrudCreatedBroadcastText()))
			return;

		$this->broadcastCrud('created', $text);
	}

	public function broadcastCrudTouched() : void
	{
		if(! config('crud.broadcast.enabled'))
			return;

		if(is_null($text = $this->getCrudTouchedBroadcastText()))
			return;

		$this->broadcastCrud('updated', $text);
	}

	protected function normalizeCrudBroadcastChannels(array $channels) : array
	{
		return array_values(array_unique(array_filter(
			$channels,
			fn(mixed $channel) => is_string($channel) && trim($channel) !== ''
		)));
	}
}
