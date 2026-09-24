<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\Buttons\Button;
use IlBronza\CRUD\Tests\TestCase;
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDStoreTrait;
use Illuminate\Http\Request;

class CRUDIndexCreatePopupTest extends TestCase
{
	public function test_standard_create_button_opens_its_get_form_in_an_iframe() : void
	{
		config(['crud.index.createButtonIframe' => true]);

		$controller = new CreatePopupIndexController();
		$controller->addCreateButton();

		$button = $controller->table->buttons[0];

		$this->assertTrue($button->isIframe());
		$this->assertTrue($button->isAjax());
		$this->assertTrue($button->getData()['openiframe']);
		$this->assertSame('GET', $button->getData()['method']);
		$this->assertContains('ib-table-action-button', $button->getHtmlClasses());
	}

	public function test_controller_can_keep_the_standard_create_navigation() : void
	{
		$controller = new class extends CreatePopupIndexController
		{
			public bool $createButtonIframe = false;
		};

		$controller->addCreateButton();
		$button = $controller->table->buttons[0];

		$this->assertFalse($button->isIframe());
		$this->assertFalse($button->isAjax());
		$this->assertArrayNotHasKey('openiframe', $button->getData());
	}

	public function test_iframed_store_closes_without_directly_reloading_the_table() : void
	{
		request()->merge([
			'iframed' => true,
			'callertablename' => 'gadgets-table',
		]);

		$controller = new CreatePopupStoreController();
		$request = request();

		$this->assertTrue($controller->mustCloseIframe($request));

		$html = $controller->iframeCloseResponse($request)->render();

		$this->assertStringContainsString('window.parent.closeModal()', $html);
		$this->assertStringNotContainsString('___reloadTable', $html);
		$this->assertStringNotContainsString('gadgets-table', $html);
	}

	public function test_iframed_store_closes_even_without_a_caller_table() : void
	{
		request()->merge(['iframed' => true]);

		$controller = new CreatePopupStoreController();

		$this->assertTrue($controller->mustCloseIframe(request()));
	}
}

class CreatePopupIndexController
{
	use CRUDIndexTrait;

	public CreatePopupTable $table;

	public function __construct()
	{
		$this->table = new CreatePopupTable();
	}

	public function getCreateNewModelButton() : Button
	{
		return Button::create([
			'href' => '/gadgets/create',
			'text' => 'Crea',
		]);
	}
}

class CreatePopupTable
{
	public array $buttons = [];

	public function addButton(Button $button) : void
	{
		$this->buttons[] = $button;
	}
}

class CreatePopupStoreController
{
	use CRUDStoreTrait;

	public function isIframed() : bool
	{
		return request()->boolean('iframed');
	}

	public function mustCloseIframe(Request $request) : bool
	{
		return $this->shouldCloseIframeAfterStore($request);
	}

	public function iframeCloseResponse(Request $request)
	{
		return $this->closeIframeAfterStore($request);
	}
}
