<?php

namespace IlBronza\CRUD\Tests\Feature;

use App\Models\TranslatableCommandArticle;
use IlBronza\CRUD\Commands\TranslatableFieldsCommand;
use IlBronza\CRUD\Helpers\ModelHelpers\TranslatableModelFinder;
use IlBronza\CRUD\Tests\TestCase;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Tester\CommandTester;

class TranslatableFieldsCommandTest extends TestCase
{
    private string $migrationDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrationDirectory = sys_get_temp_dir().'/crud-translations-'.bin2hex(random_bytes(8));
        $this->app->useDatabasePath($this->migrationDirectory);
        config([
            'app.base_locale' => 'it',
            'crud.translatableFields.locales' => ['it' => 'Italiano', 'en' => 'English', 'fr' => 'Français'],
        ]);

        Schema::create('translation_command_articles', static function (Blueprint $table) {
            $table->id();
            $table->string('title', 83)->default('Base title')->unique();
            $table->text('body');
            $table->string('en_title', 83)->nullable();
        });
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->migrationDirectory);
        Relation::morphMap([], false);
        parent::tearDown();
    }

    private function runCommand(array $arguments = [], ?string $answer = null): CommandTester
    {
        $console = new ConsoleApplication($this->app, $this->app['events'], $this->app->version());
        $command = new TranslatableFieldsCommand;
        $console->add($command);
        $tester = new CommandTester($command);

        if ($answer !== null) {
            $tester->setInputs([$answer]);
        }

        $tester->execute($arguments, ['interactive' => $answer !== null]);

        return $tester;
    }

    private function migrations(): array
    {
        return glob(database_path('migrations/*.php')) ?: [];
    }

    public function test_check_reports_only_missing_columns_without_writing_or_altering_schema(): void
    {
        $this->assertArrayHasKey('crud:translations', Artisan::all());
        $tester = $this->runCommand(['model' => TranslatableCommandArticle::class, '--check' => true]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('fr_title', $tester->getDisplay());
        $this->assertStringContainsString('en_body', $tester->getDisplay());
        $this->assertStringContainsString('fr_body', $tester->getDisplay());
        $this->assertStringNotContainsString('en_title', $tester->getDisplay());
        $this->assertSame([], $this->migrations());
        $this->assertFalse(Schema::hasColumn('translation_command_articles', 'fr_title'));
    }

    public function test_generated_migration_runs_and_rolls_back_preserving_existing_columns_and_data(): void
    {
        DB::table('translation_command_articles')->insert(['title' => 'Italiano', 'body' => 'Testo', 'en_title' => 'English']);
        $tester = $this->runCommand(['model' => TranslatableCommandArticle::class, '--generate' => true]);

        $this->assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        $this->assertCount(1, $this->migrations());
        $this->assertFalse(Schema::hasColumn('translation_command_articles', 'fr_title'));

        $path = $this->migrations()[0];
        $source = File::get($path);
        $this->assertStringNotContainsString('config(', $source);
        $this->assertStringNotContainsString('TranslatableColumnDefinition', $source);
        $this->assertStringNotContainsString('->unique()', $source);
        $this->assertStringNotContainsString('->default(', $source);

        // The generated migration must be stable when languages change after generation.
        config(['crud.translatableFields.locales' => ['de']]);
        $migration = require $path;
        $migration->up();
        $columns = array_column(Schema::getColumns('translation_command_articles'), null, 'name');
        $this->assertTrue($columns['fr_title']['nullable']);
        $this->assertNull($columns['fr_title']['default']);
        $this->assertSame($columns['title']['type'], $columns['fr_title']['type']);
        $this->assertSame($columns['body']['type'], $columns['en_body']['type']);
        $this->assertArrayNotHasKey('de_title', $columns);
        $this->assertSame('English', DB::table('translation_command_articles')->value('en_title'));
        $this->assertNull(DB::table('translation_command_articles')->value('fr_title'));

        config(['crud.translatableFields.locales' => ['it', 'en', 'fr']]);
        $this->assertSame(0, $this->runCommand(['model' => TranslatableCommandArticle::class, '--check' => true])->getStatusCode());
        $this->assertSame(0, $this->runCommand(['model' => TranslatableCommandArticle::class, '--generate' => true])->getStatusCode());
        $this->assertCount(1, $this->migrations());

        $migration->down();
        $this->assertFalse(Schema::hasColumn('translation_command_articles', 'fr_title'));
        $this->assertFalse(Schema::hasColumn('translation_command_articles', 'en_body'));
        $this->assertTrue(Schema::hasColumn('translation_command_articles', 'en_title'));
        $this->assertSame('Italiano', DB::table('translation_command_articles')->value('title'));
    }

    public function test_omitting_model_discovers_and_prompts_for_translatable_models(): void
    {
        $finder = new TranslatableModelFinder;
        $models = $finder->find();
        $this->assertContains(TranslatableCommandArticle::class, $models);
        $this->assertNotContains(\App\Models\TranslatableCommandArticleBase::class, $models);
        $this->assertNotContains(\App\Models\User::class, $models);

        $tester = $this->runCommand(['--generate' => true], TranslatableCommandArticle::class);
        $this->assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        $this->assertStringContainsString('Which model should be checked?', $tester->getDisplay());
        $this->assertCount(1, $this->migrations());
    }

    public function test_non_interactive_invocations_require_a_model_and_do_not_choose_one_implicitly(): void
    {
        $tester = $this->runCommand(['--generate' => true]);
        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString(TranslatableCommandArticle::class, $tester->getDisplay());
        $this->assertStringContainsString('Specify a model when running non-interactively.', $tester->getDisplay());
        $this->assertSame([], $this->migrations());
    }

    public function test_duplicate_generation_is_rejected_until_existing_migration_is_applied(): void
    {
        $arguments = ['model' => TranslatableCommandArticle::class, '--generate' => true];
        $this->assertSame(0, $this->runCommand($arguments)->getStatusCode());
        $tester = $this->runCommand($arguments);
        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('already exists', $tester->getDisplay());
        $this->assertCount(1, $this->migrations());

        config(['crud.translatableFields.locales' => ['it', 'en', 'fr', 'de']]);
        $this->assertSame(1, $this->runCommand($arguments)->getStatusCode());
        $this->assertCount(1, $this->migrations());
    }

    public function test_morph_alias_and_shared_form_locale_fallback_are_supported(): void
    {
        Relation::morphMap(['translated-article' => TranslatableCommandArticle::class]);
        config(['crud.translatableFields.locales' => null, 'uikittemplate.availableLanguages' => ['it', 'en']]);
        $tester = $this->runCommand(['model' => 'translated-article', '--generate' => true]);
        $this->assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        $source = File::get($this->migrations()[0]);
        $this->assertStringContainsString("text('en_body')", $source);
        $this->assertStringNotContainsString('fr_title', $source);
        $this->assertStringNotContainsString('it_title', $source);
    }

    public function test_model_connection_is_used_for_inspection_and_written_into_the_migration(): void
    {
        config(['database.connections.translations' => config('database.connections.testing')]);
        $schema = DB::connection('translations')->getSchemaBuilder();
        $schema->create('translation_command_articles', static function (Blueprint $table) {
            $table->string('title');
            $table->text('body');
        });

        $tester = $this->runCommand(['model' => ConnectedTranslatableCommandArticle::class, '--generate' => true]);
        $this->assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        $path = $this->migrations()[0];
        $this->assertStringContainsString("protected \$connection = 'translations';", File::get($path));
        $migration = require $path;
        $migration->up();
        $this->assertTrue($schema->hasColumn('translation_command_articles', 'en_body'));
        $this->assertFalse(Schema::hasColumn('translation_command_articles', 'en_body'));
        $migration->down();
        $this->assertFalse($schema->hasColumn('translation_command_articles', 'en_body'));
    }

    public function test_missing_base_column_and_invalid_model_do_not_produce_migrations(): void
    {
        Schema::table('translation_command_articles', static fn (Blueprint $table) => $table->dropColumn('body'));
        $tester = $this->runCommand(['model' => TranslatableCommandArticle::class, '--generate' => true]);
        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Base column [translation_command_articles.body] does not exist', $tester->getDisplay());
        $tester = $this->runCommand(['model' => \stdClass::class, '--generate' => true]);
        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('not an instantiable Eloquent model', $tester->getDisplay());
        $this->assertSame([], $this->migrations());
    }
}

class ConnectedTranslatableCommandArticle extends TranslatableCommandArticle
{
    protected $connection = 'translations';
}
