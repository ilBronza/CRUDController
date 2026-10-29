<?php

namespace IlBronza\CRUD\Providers\RelationshipsManager;

/** A named content section shared by the Flat, Switcher and Toggler layouts. */
interface ManagerSection
{
	public function getName() : string;

	public function getCardTitle();

	public function getToggleId();

	public function prepare() : void;

	public function render();
}
