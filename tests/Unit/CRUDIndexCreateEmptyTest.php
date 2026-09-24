<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\CRUD\Buttons\CreateEmptyButton;
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Tests\TestCase;

class CRUDIndexCreateEmptyTest extends TestCase
{
	public function test_create_empty_is_disabled_when_the_controller_does_not_opt_in() : void
	{
		$controller = new class
		{
			use CRUDIndexTrait;
		};

		$this->assertFalse($controller->hasCreateEmptyButton());
	}

	public function test_button_creates_with_post_without_suppressing_its_own_broadcast() : void
	{
		$button = CreateEmptyButton::create([]);
		$button->setCreateEmptyUrl('/gadgets/create-empty');

		$javascript = $button->renderJsMethod();

		$this->assertStringContainsString("type: 'POST'", $javascript);
		$this->assertStringNotContainsString('ibGetDatatableBroadcastHeaders', $javascript);
	}
}
