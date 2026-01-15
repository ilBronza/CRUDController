<?php

namespace IlBronza\CRUD\Traits\IlBronzaPackages;

use function count;

trait IlBronzaHasRolesTrait
{
	public array $roles = [];
	public array $permissions = [];

	/**
	 * Check if the button has roles defined.
	 *
	 * @return bool
	 */
	public function hasRoles() : bool
	{
		return count($this->getRoles()) > 0;
	}

	/**
	 * Get the roles assigned to the resource.
	 *
	 * @return array
	 */
	public function getRoles() : array
	{
		return $this->roles;
	}

	/**
	 * Check if the resource has permissions defined.
	 *
	 * @return bool
	 */
	public function hasPermissions() : bool
	{
		return count($this->getPermissions()) > 0;
	}

	/**
	 * Get the permissions assigned to the button.
	 *
	 * @return array
	 */
	public function getPermissions() : array
	{
		return $this->permissions;
	}
}
