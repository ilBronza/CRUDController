<?php

namespace IlBronza\CRUD\Tests\Unit;

use DOMDocument;
use DOMXPath;
use IlBronza\CRUD\Helpers\Forms\TranslatableFormFields;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelFormHelper;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelStorer;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelUpdater;
use IlBronza\CRUD\Models\Casts\TranslatableField;
use IlBronza\CRUD\Tests\TestCase;
use IlBronza\Form\Form;
use IlBronza\Form\Helpers\FieldsetsProvider\CreateFieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\EditFieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\ShowFieldsetsProvider;
use IlBronza\Form\Helpers\FieldsetsProvider\ValidateStoreFieldsetsProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

class TranslatableFormFieldsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.base_locale' => 'it',
            'crud.translatableFields.locales' => ['it' => 'Italiano', 'en' => 'English'],
        ]);
        $route = new Route(['GET'], '/translated-form', static fn () => '');
        $route->name('translated.form');
        request()->setRouteResolver(static fn () => $route);
    }

    private function model(array $attributes = []): Model
    {
        $model = new class extends Model
        {
            protected $table = 'translated_articles';

            protected $guarded = [];

            public $timestamps = false;

            protected $casts = ['title' => TranslatableField::class, 'body' => TranslatableField::class];
        };
        $model->setRawAttributes($attributes, true);

        return $model;
    }

    private function parameters(array $fields = []): FieldsetParametersFile
    {
        return FieldsetParametersFile::makeByParameters([
            'content' => [
                'canBeHidden' => false,
                'fields' => $fields ?: [
                    'title' => ['type' => 'text', 'rules' => 'required|string|max:20', 'label' => 'Titolo', 'id' => 'article-title'],
                    'body' => ['type' => 'textarea', 'rules' => 'nullable|string|max:100', 'label' => 'Testo'],
                    'status' => ['type' => 'text', 'rules' => 'required|in:draft,ready', 'label' => 'Stato'],
                ],
            ],
        ]);
    }

    private function formHelper(Model $model, FieldsetParametersFile $parameters, bool $edit = false): CrudModelFormHelper
    {
        $helper = new class extends CrudModelFormHelper
        {
            public $method = 'POST';

            public bool $editing = false;

            public function initializeFieldsetsProvider(): FieldsetsProvider
            {
                $class = $this->editing ? EditFieldsetsProvider::class : CreateFieldsetsProvider::class;

                return $class::setFieldsetsParametersByFile($this->getFieldsetParametersFile(), $this->getModel());
            }

            public function getCardClasses(): array
            {
                return [];
            }

            public function getTitle(): string
            {
                return 'Test';
            }

            public function getIntro(): string
            {
                return '';
            }
        };
        $helper->editing = $edit;
        $helper->setModel($model);
        $helper->setFieldsetParametersFile($parameters);
        $helper->setFieldsetsProvider();
        $helper->instantiateForm('/translated-form', []);
        $helper->getForm()->name = 'translated-article-form';
        $helper->setFormFieldsets();

        return $helper;
    }

    private function html(Form $form): string
    {
        return $form->_render()->render();
    }

    private function inputs(string $html, string $name): int
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return (new DOMXPath($document))->query('//*[self::input or self::textarea or self::select][@name="'.$name.'"]')->length;
    }

    public function test_create_and_edit_group_only_translated_fields_and_read_raw_values(): void
    {
        request()->attributes->set('frontend.locale', 'en');
        $model = $this->model(['title' => null, 'en_title' => 'English title', 'body' => '0', 'en_body' => '', 'status' => 'draft']);
        $original = $model->getAttributes();

        foreach ([false, true] as $edit) {
            $helper = $this->formHelper($model, $this->parameters(), $edit);
            $provider = $helper->getFieldsetsProvider();
            $fields = $provider->getAllFieldsArray();
            $tabs = $provider->getParametersArray()['content']['fieldsets']['crud_translations'];

            $this->assertSame(['status'], array_keys($provider->getParametersArray()['content']['fields']));
            $this->assertSame(['it', 'en'], array_keys($tabs['fieldsets']));
            $this->assertSame('', $fields['title']['value']);
            $this->assertSame('English title', $fields['en_title']['value']);
            $this->assertSame('0', $fields['body']['value']);
            $this->assertSame('', $fields['en_body']['value']);
            $this->assertSame('Titolo', $fields['en_title']['label']);
            $this->assertSame('en_article-title', $fields['en_title']['id']);
            $this->assertFalse($fields['en_title']['required']);

            $html = $this->html($helper->getForm());
            $this->assertStringContainsString('data-crud-translations=1', $html);
            $this->assertStringContainsString('>Italiano</a>', $html);
            $this->assertStringContainsString('>English</a>', $html);
            $this->assertStringContainsString('value="English title"', $html);
            foreach (['title', 'body', 'en_title', 'en_body', 'status'] as $name) {
                $this->assertSame(1, $this->inputs($html, $name));
            }
            $this->assertSame($original, $model->getAttributes());
            $this->assertFalse($model->isDirty());
        }
    }

    public function test_validation_requires_base_text_and_preserves_translation_limits(): void
    {
        $provider = ValidateStoreFieldsetsProvider::setFieldsetsParametersByFile($this->parameters(), $this->model());
        TranslatableFormFields::configure($provider);
        $rules = $provider->getValidationParameters();

        $this->assertTrue(Validator::make(['title' => 'Italiano', 'status' => 'draft'], $rules)->passes());
        $this->assertTrue(Validator::make(['title' => 'Italiano', 'status' => 'draft', 'en_title' => null], $rules)->passes());
        $this->assertTrue(Validator::make(['title' => 'Italiano', 'status' => 'draft', 'en_title' => ''], $rules)->passes());
        $this->assertTrue(Validator::make(['en_title' => 'English', 'status' => 'draft'], $rules)->errors()->has('title'));
        $this->assertTrue(Validator::make(['title' => 'Italiano', 'status' => 'draft', 'en_title' => str_repeat('x', 21)], $rules)->errors()->has('en_title'));
        $this->assertTrue(Validator::make(['title' => 'Italiano', 'status' => 'draft', 'en_title' => ['invalid']], $rules)->errors()->has('en_title'));
    }

    public function test_standard_store_and_update_persist_explicit_columns_and_keep_omitted_translations(): void
    {
        Schema::create('translated_articles', static function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('en_title')->nullable();
            $table->text('body')->nullable();
            $table->text('en_body')->nullable();
            $table->string('status');
        });
        request()->attributes->set('frontend.locale', 'en');

        $model = CrudModelStorer::saveByRequest($this->model(), $this->parameters(), Request::create('/', 'POST', [
            'title' => 'Italiano', 'en_title' => 'English', 'body' => 'Testo', 'en_body' => 'Text', 'status' => 'draft',
            'fr_title' => 'Not configured',
        ]));
        $this->assertSame('Italiano', $model->fresh()->getRawOriginal('title'));
        $this->assertSame('English', $model->fresh()->getRawOriginal('en_title'));

        CrudModelUpdater::saveByRequest($model, $this->parameters(), Request::create('/', 'PUT', [
            'title' => 'Aggiornato', 'status' => 'ready', 'en_body' => null,
        ]));
        $stored = $model->fresh();
        $this->assertSame('Aggiornato', $stored->getRawOriginal('title'));
        $this->assertSame('English', $stored->getRawOriginal('en_title'));
        $this->assertSame('Testo', $stored->getRawOriginal('body'));
        $this->assertNull($stored->getRawOriginal('en_body'));
        $this->assertSame('ready', $stored->status);
    }

    public function test_untranslated_forms_keep_identical_parameters_and_standard_rendering(): void
    {
        $model = new class extends Model {};
        $model->setRawAttributes(['title' => 'Ordinary title'], true);
        $parameters = $this->parameters();
        $provider = CreateFieldsetsProvider::setFieldsetsParametersByFile($parameters, $model);
        $original = $provider->getParametersArray();
        TranslatableFormFields::configure($provider);
        $this->assertSame($original, $provider->getParametersArray());

        $form = Form::createFromArray(['action' => '/', 'title' => 'Ordinary form', 'name' => 'ordinary-form']);
        $form->setModel($model);
        $provider->setForm($form);
        $provider->setFieldsetsCollectionToForm();
        $before = $this->html($form);
        $extraViews = $form->extraViews;
        TranslatableFormFields::attachToForm($form, $provider);

        $this->assertSame($extraViews, $form->extraViews);
        $this->assertSame(1, $this->inputs($before, 'title'));
        $this->assertSame(0, $this->inputs($before, 'en_title'));
        $this->assertStringNotContainsString('data-crud-translations', $before);
    }

    public function test_show_provider_and_explicit_translation_fields_keep_their_configuration(): void
    {
        $parameters = $this->parameters([
            'title' => ['text' => 'required|string'],
            'en_title' => ['type' => 'text', 'rules' => 'required|string|max:10', 'label' => 'Manual English'],
        ]);
        $show = ShowFieldsetsProvider::setFieldsetsParametersByFile($parameters, $this->model());
        $original = $show->getParametersArray();
        TranslatableFormFields::configure($show);
        $this->assertSame($original, $show->getParametersArray());

        $helper = $this->formHelper($this->model(), $parameters);
        $fields = $helper->getFieldsetsProvider()->getAllFieldsArray();
        $this->assertSame('Manual English', $fields['en_title']['label']);
        $this->assertTrue($fields['en_title']['required']);
        $this->assertSame(1, $this->inputs($this->html($helper->getForm()), 'en_title'));
    }

    public function test_old_input_and_validation_errors_are_available_to_the_translation_tabs(): void
    {
        request()->setLaravelSession(app('session.store'));
        session()->flashInput(['title' => 'Old Italian', 'en_title' => 'Old English']);
        $errors = new ViewErrorBag;
        $errors->put('default', new MessageBag(['en_title' => ['Too long']]));
        view()->share('errors', $errors);

        $form = $this->formHelper($this->model(['title' => 'Stored', 'en_title' => 'Stored English']), $this->parameters())->getForm();
        $html = $this->html($form);

        $this->assertStringContainsString('value="Old Italian"', $html);
        $this->assertStringContainsString('value="Old English"', $html);
        $this->assertStringContainsString('en_title', $form->renderExtraViews('innerBottom'));
        $this->assertStringContainsString('window.UIkit.switcher(navigation).show(errorIndex)', $html);
    }

    public function test_locale_configuration_includes_base_language_and_does_not_reuse_base_defaults(): void
    {
        config(['app.base_locale' => 'fr', 'crud.translatableFields.locales' => ['en', 'de']]);
        $this->assertSame(['fr', 'en', 'de'], array_keys(TranslatableFormFields::locales()));

        $helper = $this->formHelper($this->model(), $this->parameters([
            'title' => ['type' => 'text', 'rules' => 'required|string', 'default' => 'Base default'],
        ]));
        $fields = $helper->getFieldsetsProvider()->getAllFieldsArray();
        $this->assertSame('Base default', $fields['title']['value']);
        $this->assertSame('', $fields['en_title']['value']);
        $this->assertSame('', $fields['de_title']['value']);
        $this->assertArrayNotHasKey('fr_title', $fields);

        config(['crud.translatableFields.locales' => null, 'uikittemplate.availableLanguages' => ['fr', 'en']]);
        $this->assertSame(['fr', 'en'], array_keys(TranslatableFormFields::locales()));
    }

    public function test_nested_fieldsets_are_expanded_once_and_restricted_fields_are_not_exposed(): void
    {
        $parameters = FieldsetParametersFile::makeByParameters([
            'outer' => [
                'fields' => ['status' => ['text' => 'required']],
                'fieldsets' => [
                    'inner' => ['fields' => [
                        'title' => ['type' => 'text', 'rules' => 'required', 'roles' => ['translator']],
                        'body' => ['type' => 'textarea', 'rules' => 'nullable|string', 'disabled' => true],
                    ]],
                ],
            ],
        ]);
        $provider = ValidateStoreFieldsetsProvider::setFieldsetsParametersByFile($parameters, $this->model());
        TranslatableFormFields::configure($provider);
        $first = $provider->getParametersArray();
        TranslatableFormFields::configure($provider);

        $this->assertSame($first, $provider->getParametersArray());
        $this->assertArrayNotHasKey('title', $provider->getValidationParameters());
        $this->assertArrayNotHasKey('en_title', $provider->getValidationParameters());
        $this->assertArrayNotHasKey('body', $provider->getValidationParameters());
        $this->assertArrayNotHasKey('en_body', $provider->getValidationParameters());
        $this->assertArrayNotHasKey('en_title', $provider->getAllFieldsArray());
    }
}
