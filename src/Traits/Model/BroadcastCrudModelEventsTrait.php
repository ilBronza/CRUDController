<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Database\Eloquent\Model;

/**
 * Completa gli invii di BroadcastCrudTrait per le modifiche fuori dal CRUD.
 * Gli invii esistenti (controller, created e touch) restano attivi:
 * questa prima versione non accorpa né deduplica le notifiche.
 */
trait BroadcastCrudModelEventsTrait
{
	public static function bootBroadcastCrudModelEventsTrait() : void
	{
		static::updated(function(Model $model)
		{
			$model->broadcastCrudAction('updated');
		});

		static::saved(function(Model $model)
		{
			$model->broadcastCrudAction('saved');
		});

		static::deleted(function(Model $model)
		{
			$model->broadcastCrudAction('deleted');
		});

		static::registerModelEvent('trashed', function(Model $model)
		{
			$model->broadcastCrudAction('trashed');
		});

		static::registerModelEvent('restored', function(Model $model)
		{
			$model->broadcastCrudAction('restored');
		});

		static::registerModelEvent('forceDeleted', function(Model $model)
		{
			$model->broadcastCrudAction('forceDeleted');
		});
	}

	/**
	 * Laravel silenzia i listener Eloquent in saveQuietly(). Il realtime deve
	 * comunque seguire la modifica persistita, anche quando il chiamante vuole
	 * evitare observer o activity log.
	 */
	public function saveQuietly(array $options = []) : bool
	{
		$wasExisting = $this->exists;
		$wasDirty = $this->isDirty();
		$saved = parent::saveQuietly($options);

		if($saved && $wasDirty)
			$this->broadcastCrudAction($wasExisting ? 'updated' : 'created');

		return $saved;
	}

	/**
	 * The quiet deletion methods mute the deleted model event, so publish the
	 * corresponding realtime action after the database operation has succeeded.
	 */
	public function deleteQuietly() : ?bool
	{
		$deleted = parent::deleteQuietly();

		if($deleted)
			$this->broadcastCrudAction('deleted');

		return $deleted;
	}

}
