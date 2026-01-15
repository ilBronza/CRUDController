<?php

namespace IlBronza\CRUD\Traits\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use function session;

trait HelperMessageBagTrait
{
	abstract public function getSubjectModel() : Model;

	public array $messages = [];

	public function addMessage(string $message) : void
	{
		$this->messages[] = $message;

		$this->setMessagesInSession();
	}

	public function setMessagesInSession()
	{
		session()->put(static::getMessagesBagSessionKey($this->getSubjectModel()), $this->messages);
	}

	public function getMessages() : array
	{
		return $this->messages;
	}

	static function getMessagesBag(Model $model) : array
	{
		return session(static::getMessagesBagSessionKey($model), []);
	}

	static function getMessagesBagSessionKey(Model $model) : string
	{
		return Str::slug(static::class . '-' . $model->getKey());
	}

	static function getMessagesBagString(Model $model) : string
	{
		$messages = static::getMessagesBag($model);

		return implode('<br />', $messages);
	}
}