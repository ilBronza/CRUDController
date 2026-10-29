<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\CRUD\Tests\TestCase;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelFormHelper;
use IlBronza\CRUD\Tests\Fixtures\Controllers\GadgetCRUD;
use IlBronza\CRUD\Tests\Fixtures\Models\Gadget;
use IlBronza\Form\Form;
use IlBronza\Form\Helpers\FieldsetsProvider\CreateFieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use IlBronza\UikitTemplate\Traits\CollapsibleElementTrait;
use Illuminate\Routing\Route;

class CollapsibleFormTest extends TestCase
{
	public function test_high_level_parameters_file_and_controller_options_configure_the_form() : void
	{
		$route = new Route(['GET'], '/form-configuration', static fn () => '');
		$route->name('form.configuration');
		request()->setRouteResolver(static fn () => $route);

		$parametersFile = FieldsetParametersFile::makeByParameters([
			'general' => [
				'fields' => [
					'name' => ['text' => 'nullable']
				]
			]
		]);
		$parametersFile->formParameters = [
			'collapsible' => true,
			'collapsedInitially' => true,
			'collapseStateKey' => 'parameters-file-key'
		];

		$helper = $this->makeFormHelper($parametersFile);
		$form = $helper->instantiateForm('/test', []);

		$this->assertTrue($form->isCollapsible());
		$this->assertTrue($form->isCollapsedInitially());
		$this->assertSame('parameters-file-key', $form->getCollapseStateKey());

		$form = $this->makeFormHelper($parametersFile)->instantiateForm('/test', [
			'collapsible' => false,
			'collapsedInitially' => false,
			'collapseStateKey' => 'controller-key'
		]);

		$this->assertFalse($form->isCollapsible());
		$this->assertFalse($form->isCollapsedInitially());
		$this->assertSame('controller-key', $form->getCollapseStateKey());

		$controller = new class extends GadgetCRUD
		{
			public $allowedMethods = ['create'];

			public array $formParameters = [
				'collapsible' => true,
				'collapsedInitially' => false
			];

			public ?string $collapseStateKey = 'controller-property-key';
		};
		$controller->setModel(new Gadget());

		$this->assertSame($controller->formParameters, $controller->getFormParameters());
		$this->assertSame(true, $controller->provideFormDefaultSettings()['collapsible']);
		$this->assertSame(false, $controller->provideFormDefaultSettings()['collapsedInitially']);
		$this->assertSame('controller-property-key', $controller->provideFormDefaultSettings()['collapseStateKey']);
	}

	public function test_iframed_form_hides_the_list_link_and_closes_its_parent_on_cancel() : void
	{
		$route = new Route(['GET'], '/iframed-form', static fn () => '');
		$route->name('form.iframed');
		request()->setRouteResolver(static fn () => $route);
		request()->merge(['iframed' => true]);

		$controller = new class extends GadgetCRUD
		{
			public $allowedMethods = ['index'];

			public function getIndexUrl()
			{
				return '/gadgets';
			}
		};
		$controller->setModel(new Gadget());

		$this->assertArrayNotHasKey('backToListUrl', $controller->provideFormDefaultSettings());

		$parametersFile = FieldsetParametersFile::makeByParameters([
			'general' => [
				'fields' => [
					'name' => ['text' => 'nullable']
				]
			]
		]);

		$form = $this->makeFormHelper($parametersFile)->instantiateForm('/test', [
			'backToListUrl' => '/gadgets',
			'cancelHref' => '/previous-page'
		]);

		$this->assertFalse($form->getBackToListUrl());
		$this->assertSame(CrudModelFormHelper::IFRAME_CANCEL_HREF, $form->getCancelHref());

		$html = $form->_render()->render();

		$this->assertStringContainsString('href="#crud-close-parent-overlay"', $html);
		$this->assertStringContainsString('window.__crudCloseParentOverlay', $html);
	}

	private function makeFormHelper(FieldsetParametersFile $parametersFile) : CrudModelFormHelper
	{
		$helper = new class extends CrudModelFormHelper
		{
			public $method = 'POST';

			public function initializeFieldsetsProvider() : FieldsetsProvider
			{
				return CreateFieldsetsProvider::setFieldsetsParametersByFile(
					$this->getFieldsetParametersFile(),
					$this->getModel()
				);
			}

			public function getCardClasses() : array
			{
				return [];
			}

			public function getTitle() : string
			{
				return 'Test form';
			}

			public function getIntro() : string
			{
				return '';
			}
		};

		$helper->setModel(new Gadget());
		$helper->setFieldsetParametersFile($parametersFile);
		$helper->setFieldsetsProvider();

		return $helper;
	}

	public function test_trait_can_be_used_by_a_generic_object() : void
	{
		$element = new class
		{
			use CollapsibleElementTrait;
		};

		$this->assertFalse($element->isCollapsible());
		$this->assertFalse($element->setCollapsedInitially()->isCollapsedInitially());

		$element->setCollapsible();

		$this->assertTrue($element->isCollapsedInitially());
		$this->assertMatchesRegularExpression('/^[A-Za-z][A-Za-z0-9_-]*$/', $element->getCollapseTargetId());
		$this->assertSame('.' . $element->getCollapseTargetHtmlClass(), $element->getCollapseTargetSelector());
	}

	public function test_collapsible_form_renders_standard_toggle_and_hidden_target() : void
	{
		$form = Form::createFromArray([
			'action' => '/test',
			'card' => true,
			'collapsible' => true,
			'collapsedInitially' => true,
			'title' => 'Collapsible form'
		]);

		$html = $form->_render()->render();

		$this->assertStringContainsString('ib-collapsible-toggle', $html);
		$this->assertStringContainsString('data-collapse-state-key="ilbronza:collapse:', $html);
		$this->assertStringContainsString('sessionStorage.getItem', $html);
		$this->assertStringContainsString('aria-controls="' . $form->getCollapseTargetId() . '"', $html);
		$this->assertStringContainsString(
			'uk-toggle="target: .' . $form->getCollapseTargetHtmlClass() . ', .' . $form->getCollapseToggleStateClass() . '; cls: uk-hidden"',
			$html
		);
		$this->assertMatchesRegularExpression(
			'/id="' . preg_quote($form->getCollapseTargetId(), '/') . '"\s+class="uk-card-body[^"]*uk-hidden[^"]*"/',
			$html
		);
		$this->assertStringNotContainsString('class="uk-card-footer ib-collapsible-content', $html);
		$this->assertGreaterThan(strpos($html, '<form'), strpos($html, 'ib-collapsible-toggle'));
	}

	public function test_non_collapsible_form_keeps_content_visible_and_renders_no_toggle() : void
	{
		$form = Form::createFromArray([
			'action' => '/test',
			'collapsedInitially' => true,
			'title' => 'Regular form'
		]);

		$html = $form->_render()->render();

		$this->assertStringNotContainsString('ib-collapsible-toggle', $html);
		$this->assertStringNotContainsString('ib-collapsible-content uk-hidden', $html);
	}

	public function test_collapsible_form_without_card_renders_the_same_standard_toggle() : void
	{
		$form = Form::createFromArray([
			'action' => '/test',
			'collapsible' => true,
			'title' => 'Form without card'
		]);

		$html = $form->_render()->render();

		$this->assertStringContainsString('ib-collapsible-toggle', $html);
		$this->assertStringContainsString('aria-controls="' . $form->getId() . '"', $html);
		$this->assertMatchesRegularExpression(
			'/<form[^>]*class="[^"]*' . preg_quote($form->getCollapseTargetHtmlClass(), '/') . '[^"]*"/s',
			$html
		);
		$this->assertStringNotContainsString('ib-collapsible-content uk-hidden', $html);
	}

	public function test_collapsible_fieldset_persists_its_state_per_route_and_name() : void
	{
		$route = new Route(['GET'], '/form-configuration', static fn () => '');
		$route->name('form.configuration');
		request()->setRouteResolver(static fn () => $route);

		$form = Form::createFromArray([
			'action' => '/test'
		]);
		$fieldset = $form->addFormFieldset('advanced-options', [
			'canBeHidden' => true
		]);

		$this->assertSame(
			'ilbronza:fieldset-collapse:form.configuration:advanced-options',
			$fieldset->getCollapseStateKey()
		);

		$html = $form->_render()->render();

		$this->assertStringContainsString(
			'data-collapse-state-key="ilbronza:fieldset-collapse:form.configuration:advanced-options"',
			$html
		);
		$this->assertStringContainsString('sessionStorage.getItem(stateKey)', $html);
		$this->assertStringContainsString('sessionStorage.setItem(', $html);
	}
}
