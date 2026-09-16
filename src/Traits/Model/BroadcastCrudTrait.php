<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\CRUD\Events\CrudModelBroadcasted;
use IlBronza\CRUD\Models\CrudChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait BroadcastCrudTrait
{
	public static function bootBroadcastCrudTrait() : void
	{
		static::created(function(Model $model)
		{
			$model->broadcastCrudAction('created');
		});
	}

	/**
	 * Il touch cambia il timestamp senza passare dal controller: trasmette da sé.
	 */
	public function touch($attribute = null)
	{
		$touched = parent::touch($attribute);

		if($touched)
			$this->broadcastCrudAction('updated');

		return $touched;
	}

	/**
	 * Canali intrinseci del model: dove va pubblicata la modifica
	 * indipendentemente da chi l'ha fatta.
	 */
	public function getCrudBroadcastChannels(string $action) : array
	{
		$method = 'getCrudBroadcastChannelsFor' . Str::studly($action);

		if(! method_exists($this, $method))
			return [$this->getCrudBroadcastChannel()];

		return $this->{$method}();
	}

	public function getCrudBroadcastChannelsForCreated() : array
	{
		return [$this->getCrudBroadcastChannel()];
	}

	public function getCrudBroadcastChannelsForUpdated() : array
	{
		return [$this->getCrudBroadcastChannel()];
	}

	public function getCrudBroadcastChannelsForDeleted() : array
	{
		return [$this->getCrudBroadcastChannel()];
	}

	public function getCrudBroadcastChannelsForForceDeleted() : array
	{
		return [$this->getCrudBroadcastChannel()];
	}

	/**
	 * Canale base del model.
	 */
	public function getCrudBroadcastChannel() : string
	{
		return rtrim(config('crud.broadcast.channel'), '.')
			. '.'
			. $this->getCrudBroadcastSlug();
	}

	/**
	 * Deve restare uguale a quello che il javascript ascolta: datatables lo
	 * costruisce con Str::kebab(class_basename($modelClass)). Da sovrascrivere
	 * quando l'app estende il model con gpc() dandogli un altro nome.
	 */
	public function getCrudBroadcastSlug() : string
	{
		return Str::kebab(class_basename($this));
	}

	/**
	 * Testo mostrato al client. Restituire null per non trasmettere.
	 */
	public function getCrudBroadcastText(string $action) : ? string
	{
		$method = 'getCrudBroadcastTextFor' . Str::studly($action);
		$legacyMethod = 'getCrud' . Str::studly($action) . 'BroadcastText';

		if(method_exists($this, $legacyMethod))
			return $this->{$legacyMethod}();

		if(! method_exists($this, $method))
			return $this->getCrudBroadcastTranslation($action);

		return $this->{$method}();
	}

	public function getCrudBroadcastTextForCreated() : ? string
	{
		return $this->getCrudBroadcastTranslation('created');
	}

	public function getCrudBroadcastTextForUpdated() : ? string
	{
		return $this->getCrudBroadcastTranslation('updated');
	}

	public function getCrudBroadcastTextForDeleted() : ? string
	{
		return $this->getCrudBroadcastTranslation('deleted');
	}

	public function getCrudBroadcastTextForForceDeleted() : ? string
	{
		return $this->getCrudBroadcastTranslation('forceDeleted');
	}

	public function getCrudBroadcastTranslation(string $action) : string
	{
		return trans('crud::broadcastMessages.' . $action, [
			'user' => \Auth::user()?->getName(),
			'model' => $this->getTranslatedClassName(),
			'modelName' => $this->getName()
		]);
	}

	/**
	 * Trasmette con il testo per convenzione.
	 */
	public function broadcastCrudAction(string $action, array $extraChannels = []) : void
	{
		if(! config('crud.broadcast.enabled'))
			return;

		if(is_null($text = $this->getCrudBroadcastText($action)))
			return;

		$this->broadcastCrud($action, $text, $extraChannels);
	}

	/**
	 * Trasmette con un testo esplicito. I canali passati da fuori (il contesto
	 * del controller) si sommano a quelli del model, non li sostituiscono.
	 */
	public function broadcastCrud(string $action, string $text, array $extraChannels = []) : void
	{
		if(! config('crud.broadcast.enabled'))
			return;

		$channels = array_values(
			array_unique([
				...$this->getCrudBroadcastChannels($action),
				...$extraChannels
			])
		);

		foreach($channels as $channel)
			CrudChannel::firstOrCreate([
				'name' => $channel
			]);

		event(new CrudModelBroadcasted($this, $text, $action, $channels));
	}
}
