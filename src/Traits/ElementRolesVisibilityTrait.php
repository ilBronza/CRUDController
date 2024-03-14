<?php

namespace IlBronza\CRUD\Traits;

use Auth;
use Illuminate\Support\Collection;

trait ElementRolesVisibilityTrait
{
	public null|Collection $enabledRoles = null;

	private function initializeEnabledRoles()
	{
		$this->enabledRoles = collect();
	}

	public function setEnabledRoles(mixed $enabledRoles)
	{
		$this->initializeEnabledRoles();

		$this->addEnabledRoles($enabledRoles);
	}

	public function addEnabledRoles(mixed $enabledRoles)
	{
		foreach($enabledRoles as $enabledRole)
			$this->addEnabledRole($enabledRole);
	}

	public function addEnabledRole(string $enabledRole)
	{
		$this->enabledRoles->push($enabledRole);
	}

	public function getEnabledRoles() : ? Collection
	{
		return $this->enabledRoles;
	}

	public function isEnabledForUserRole() : bool
	{
		if(! $user = Auth::user())
			return false;

		if($user->isSuperadmin())
			return true;

		if(! $enabledRoles = $this->getEnabledRoles())
			return false;

		foreach($enabledRoles as $enabledRole)
			if($user->hasRole($enabledRole))
				return true;

		return false;
	}
}