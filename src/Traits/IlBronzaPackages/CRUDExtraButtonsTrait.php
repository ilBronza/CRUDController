<?php

namespace IlBronza\CRUD\Traits\IlBronzaPackages;

use IlBronza\Buttons\Button;
use IlBronza\Menu\Navbar;
use Illuminate\Support\Str;

trait CRUDExtraButtonsTrait
{
	public ? Navbar $buttonsNavbar = null;

	abstract public function getName() : ? string;
	abstract public function getId() : ? string;

	public function getButtonsNavbarName() : string
	{
		return "navbar" . Str::slug($this->getId());
	}

	public function setButtonsNavbar()
	{
		$this->buttonsNavbar = app('menu')->getIndependentNavbarByName(
			$this->getButtonsNavbarName()
		);

		return $this->buttonsNavbar;
	}

	public function getButtonsNavbar() : Navbar
	{
		if($this->buttonsNavbar)
			return $this->buttonsNavbar;

		return $this->setButtonsNavbar();
	}

	public function hasButtonsNavbar() : bool
	{
		return !! $this->buttonsNavbar;
	}

	public function addNavbarButton(Button $button) : Navbar
	{
		$buttonsNavbar = $this->getButtonsNavbar();

		$buttonsNavbar->addButton($button);

		return $buttonsNavbar;
	}
}