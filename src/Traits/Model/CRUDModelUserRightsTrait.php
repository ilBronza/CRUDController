<?php

namespace IlBronza\CRUD\Traits\Model;

use Auth;
use Exception;
use IlBronza\AccountManager\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

use function config;
use function dd;
use function get_class;

trait CRUDModelUserRightsTrait
{
	static function getStaticBaseUserRightsResult(User $user = null) : ?bool
	{
		if (! $user = Auth::user())
			return false;

		if ($user->isSuperadmin())
			return true;

		if ($user->hasRole('administrator'))
			return true;

//		$roles = $this->getConfigByKey('roles.show.any', []);
//
//		dd($roles);

		//	    if(! $roles = $this->getConfigByKey('roles.show.any', []))
		//		    $roles = config(static::getPackageConfigPrefix() . '.roles', []);
		//
		//	    if(($roles)&&(! $user->hasAnyRole($roles)))
		//		    return false;

		return null;
	}

	public function getBaseUserRightsResult(User $user = null) : ?bool
	{
		if (! $user = Auth::user())
			return false;

		if ($user->isSuperadmin())
			return true;

		if ($user->hasRole('administrator'))
			return true;

			    if(! $roles = $this->getConfigByKey('roles.show.any', []))
				    $roles = config(static::getPackageConfigPrefix() . '.roles', []);

			    if(($roles)&&($user->hasAnyRole($roles)))
				    return true;

		return null;
	}

	public function userCanUpdate(User $user = null)
	{
		if (is_null($user))
			$user = Auth::user();

		if (! is_null($result = $this->getBaseUserRightsResult($user)))
			return $result;

		return $this->user_id == $user->getKey();
	}

	public function userCanDelete(User $user = null)
	{
		if (is_null($user))
			$user = Auth::user();

		if (! is_null($result = $this->getBaseUserRightsResult($user)))
			return $result;

		return $this->user_id == $user->getKey();
	}

	static function userCanCreate(User $user = null)
	{
		if (! is_null($result = static::getStaticBaseUserRightsResult($user)))
			return $result;

		return false;
	}

	public function userCanSee(User $user = null)
	{
		if (Auth::guest())
			return false;

		if (! $user)
			$user = Auth::user();

		try
		{
			/**
			 * if user doesn't have config requried roles, he cnanot see the model
			 * if user has the required roles, he pass this check
			 *
			 * 'contracttype' => [
			 *      'class' => Contracttype::class,
			 *      'roles' => [
			 *          'show' => [
			 *              'any' => ['administrator', 'vnl_2025']
			 *             ]
			 *          ]
			 *      ],
			 *
			 *  declaration example
			 */

			if (! $roles = $this->getConfigByKey('roles.show.any', []))
				$roles = config(static::getPackageConfigPrefix() . '.roles', []);

			if (($roles) && (! $user->hasAnyRole($roles)))
				return false;
		}
		catch (Exception $e)
		{
			/**
			 *
			 * we should use always packaged methods for model
			 */
			Log::critical($e->getMessage() . ' Aggiungi il model package per gestire sta cosa per il model ' . get_class($this));
		}

		return true;
	}

	public function userCanSeeTeaser(User $user = null)
	{
		if (Auth::guest())
			return false;

		return true;
	}

	public function owns(Model $model)
	{
		if (Auth::user()->isSuperadmin())
			return true;

		$owningMethod = $this->getOwningMethod($model);
		if (method_exists($this, $owningMethod))
			return $this->$owningMethod($model);

		if ($model->{$this->getForeignKey()} == $this->getKey())
			return true;

		return false;
	}

	public function getOwningMethod(Model $model)
	{
		return 'owns' . class_basename($model);
	}

}