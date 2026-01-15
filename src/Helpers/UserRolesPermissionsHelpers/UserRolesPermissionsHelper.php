<?php

namespace IlBronza\CRUD\Helpers\UserRolesPermissionsHelpers;

use IlBronza\CRUD\Interfaces\CRUDHasRolesInterface;
use Illuminate\Support\Facades\Auth;

class UserRolesPermissionsHelper
{
	static function hasValidItemRoles(CRUDHasRolesInterface $item) : bool
	{
		if(empty($roles = $item->getRoles()))
			return true;

		if(! $user = Auth::user())
			return false;

		if($user->isSuperAdmin())
			return true;

		return $user->hasAnyRole($roles);
	}
}