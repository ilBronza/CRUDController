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
	public ?array $origin;
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
		$this->origin = $this->getOriginFromRequest();
	}

	/**
	 * Keep the browser-origin metadata on the event itself: broadcasts may be
	 * queued, where the originating HTTP request is no longer available.
	 */
	protected function getOriginFromRequest() : ?array
	{
		$browserTabId = request()->header('X-IB-Datatable-Browser-Tab-Id');
		$tableId = request()->header('X-IB-Datatable-Table-Id');

		if((! $browserTabId) || (! $tableId))
			return null;

		return [
			'browserTabId' => $browserTabId,
			'tableId' => $tableId,
		];
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

		if($this->origin)
			$payload['origin'] = $this->origin;

		return [
			...$payload,
			'text' => $this->text,
		];
	}
}
