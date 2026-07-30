<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\CRUD\Events\CrudModelBroadcasted;
use IlBronza\CRUD\Models\CrudChannel;
use Illuminate\Support\Str;

trait BroadcastTouchTrait
{
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
	 * Override in the model and return null to suppress its touch broadcast.
	 */
	public function getCrudTouchedBroadcastText() : ?string
	{
		return trans('crud::broadcastMessages.updated', [
			'user' => \Auth::user()?->getName(),
			'model' => $this->getTranslatedClassName(),
			'modelName' => $this->getName(),
		]);
	}

	/** Override in the model when it needs a more specific private channel. */
	public function getCrudTouchedBroadcastChannel() : ?string
	{
		return rtrim(config('crud.broadcast.channel'), '.')
			. '.'
			. Str::kebab(class_basename($this));
	}

	public function broadcastCrudTouched() : void
	{
		if(! config('crud.broadcast.enabled'))
			return;

		if(is_null($text = $this->getCrudTouchedBroadcastText()))
			return;

		if(! $channel = $this->getCrudTouchedBroadcastChannel())
			return;

		CrudChannel::firstOrCreate([
			'name' => $channel,
		]);

		event(new CrudModelBroadcasted($this, $text, 'updated', $channel));
	}
}
