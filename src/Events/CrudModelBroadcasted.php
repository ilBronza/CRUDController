<?php

namespace IlBronza\CRUD\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;

class CrudModelBroadcasted implements ShouldBroadcast
{
	/** Dispatch the broadcast only after the surrounding transaction commits. */
	public bool $afterCommit = true;
	public string $model;
	public mixed $key;
	public string $text;
	public string $action;
	protected string $channel;

	public function __construct(
		Model $model,
		string $text,
		string $action,
		string $channel
	) {
		// Keep only scalars: queued broadcasts must also work after a force-delete.
		$this->model = $model::class;
		$this->key = $model->getKey();
		$this->text = $text;
		$this->action = $action;
		$this->channel = $channel;
	}

	public function broadcastOn() : array
	{
		return [new PrivateChannel($this->channel)];
	}

	public function broadcastAs() : string
	{
		return 'crud.model.changed';
	}

	public function broadcastWith() : array
	{
		$payload = [
			'model' => $this->model,
			'key' => $this->key,
			'action' => $this->action,
		];

		return [
			...$payload,
			'text' => $this->text,
		];
	}
}
