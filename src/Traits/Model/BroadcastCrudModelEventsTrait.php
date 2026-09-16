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

		static::deleted(function(Model $model)
		{
			$action = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
				? 'forceDeleted'
				: 'deleted';

			$model->broadcastCrudAction($action);
		});
	}
}
