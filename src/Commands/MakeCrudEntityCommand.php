<?php

namespace IlBronza\CRUD\Commands;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Models\BaseModel;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function array_key_first;
use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function array_values;
use function base_path;
use function basename;
use function class_basename;
use function count;
use function date;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function implode;
use function is_dir;
use function json_decode;
use function mkdir;
use function preg_match;
use function preg_quote;
use function reset;
use function rtrim;
use function sort;
use function str_repeat;
use function str_replace;
use function strlen;
use function trim;

/**
 * Genera l'intera entità CRUD (model, controllers spezzettati, providers)
 * e stampa i blocchi da incollare in config e routes.
 *
 * Il nome del model è l'unico dato richiesto: cartelle, namespace, chiave di
 * config, tabella e nomi delle route vengono dedotti da lì.
 */
class MakeCrudEntityCommand extends Command
{
	protected $signature = 'crud:make
		{name : Nome del model, es. Vehicle o VehicleType}
		{--package= : Package di destinazione, salta il prompt}
		{--project : Genera nel progetto, salta il prompt}
		{--migration : Genera anche la migration}
		{--force : Sovrascrive i files esistenti}';

	protected $description = 'Crea model, controllers e providers di una entità CRUD e stampa i testi per config e routes';

	/** nomi derivati dal solo nome del model */
	protected array $names = [];

	/** dati dedotti dalla destinazione */
	protected array $target = [];

	protected array $created = [];

	protected array $skipped = [];

	public function handle() : int
	{
		$this->setNames();

		if(! $this->setTarget())
			return static::FAILURE;

		$this->createFiles();

		if($this->option('migration'))
			$this->createMigration();

		$this->printCreatedFiles();
		$this->printConfig();
		$this->printRoutes();

		return static::SUCCESS;
	}

	/**
	 * Tutti i nomi si deducono dal nome del model
	 * VehicleType => vehicleType, VehicleTypes, vehicleTypes, vehicle_types, vehicle-types
	 */
	protected function setNames() : void
	{
		$model = Str::studly($this->argument('name'));

		$this->names = [
			'model' => $model,
			'modelVariable' => Str::camel($model),
			'modelPlural' => Str::plural($model),
			'modelPluralVariable' => Str::camel(Str::plural($model)),
			'configModelClassName' => Str::camel($model),
			'tableSuffix' => Str::snake(Str::plural($model)),
			'urlPrefix' => Str::kebab(Str::plural($model))
		];
	}

	protected function setTarget() : bool
	{
		if($this->option('project'))
			return $this->setProjectTarget();

		if($package = $this->option('package'))
			return $this->setPackageTarget($package);

		$packages = $this->getAvailablePackages();

		$choice = $this->choice(
			'Dove genero ' . $this->names['model'] . '?',
			array_merge(['project'], array_keys($packages)),
			'project'
		);

		if($choice == 'project')
			return $this->setProjectTarget();

		return $this->setPackageTarget($choice);
	}

	/** i packages IlBronza installati, symlinkati o meno */
	protected function getAvailablePackages() : array
	{
		$result = [];

		foreach(glob(base_path('vendor/ilbronza/*'), GLOB_ONLYDIR) as $directory)
			if(file_exists($directory . '/composer.json'))
				$result[basename($directory)] = $directory;

		return $result;
	}

	protected function setProjectTarget() : bool
	{
		$this->target = [
			'mode' => 'project',
			'sourcePath' => base_path('app'),
			'rootNamespace' => rtrim($this->laravel->getNamespace(), '\\'),
			'migrationsPath' => base_path('database/migrations'),
			'configPrefix' => null,
			'table' => $this->names['tableSuffix'],
			'translationPrefix' => 'fields',
			'baseModel' => BaseModel::class,
			'baseController' => CRUD::class,
			'packageClass' => null
		];

		return true;
	}

	protected function setPackageTarget(string $package) : bool
	{
		$packages = $this->getAvailablePackages();

		if(! $directory = $packages[$package] ?? null)
		{
			$this->error("Package [{$package}] non trovato in vendor/ilbronza");
			$this->line('Disponibili: ' . implode(', ', array_keys($packages)));

			return false;
		}

		$composer = json_decode(file_get_contents($directory . '/composer.json'), true);

		if(! $psr4 = $composer['autoload']['psr-4'] ?? null)
		{
			$this->error("Il composer.json di [{$package}] non dichiara autoload.psr-4");

			return false;
		}

		$rootNamespace = rtrim(array_key_first($psr4), '\\');
		$sourcePath = $directory . '/' . trim(reset($psr4), '/');

		if(! $configPrefix = $this->getPackageConfigPrefix($directory))
			return false;

		if(! $baseModel = $this->findClassExtending($sourcePath . '/Models', 'PackagedBaseModel'))
		{
			$this->error("Non trovo il model base del package (extends PackagedBaseModel) in {$sourcePath}/Models");

			return false;
		}

		if(! $baseController = $this->findClassExtending($sourcePath . '/Http/Controllers', 'BasePackageController'))
		{
			$this->error("Non trovo il controller base del package (extends BasePackageController) in {$sourcePath}/Http/Controllers");

			return false;
		}

		if(! $packageClass = $this->findClassWithMethod($sourcePath, 'getController'))
		{
			$this->error("Non trovo la classe helper del package con getController() in {$sourcePath}");

			return false;
		}

		$this->target = [
			'mode' => 'package',
			'sourcePath' => $sourcePath,
			'rootNamespace' => $rootNamespace,
			'migrationsPath' => $directory . '/database/migrations',
			'configPrefix' => $configPrefix,
			'table' => $configPrefix . '__' . $this->names['tableSuffix'],
			'translationPrefix' => $configPrefix . '::fields',
			'baseModel' => $baseModel,
			'baseController' => $baseController,
			'packageClass' => $packageClass
		];

		return true;
	}

	protected function getPackageConfigPrefix(string $directory) : ? string
	{
		$files = glob($directory . '/config/*.php');

		if(! count($files))
		{
			$this->error("Nessun file di config trovato in {$directory}/config");

			return null;
		}

		if(count($files) == 1)
			return basename($files[0], '.php');

		return $this->choice(
			'Quale config uso?',
			array_map(fn($file) => basename($file, '.php'), $files)
		);
	}

	protected function findClassExtending(string $directory, string $parent) : ? string
	{
		return $this->findClassByPattern($directory, '/extends\s+' . preg_quote($parent, '/') . '\b/');
	}

	protected function findClassWithMethod(string $directory, string $method) : ? string
	{
		return $this->findClassByPattern($directory, '/function\s+' . preg_quote($method, '/') . '\s*\(/');
	}

	protected function findClassByPattern(string $directory, string $pattern) : ? string
	{
		foreach(glob($directory . '/*.php') as $file)
		{
			$content = file_get_contents($file);

			if(! preg_match($pattern, $content))
				continue;

			preg_match('/namespace\s+([^;]+);/', $content, $namespace);

			return trim($namespace[1]) . '\\' . basename($file, '.php');
		}

		return null;
	}

	/** stub => [cartella relativa, nome classe] */
	protected function getFilesMap() : array
	{
		$model = $this->names['model'];
		$entityFolder = 'Http/Controllers/' . $this->names['modelPlural'];

		return [
			'Model' => ['Models', $model],
			'CRUD' => [$entityFolder, $model . 'CRUD'],
			'IndexController' => [$entityFolder, $model . 'IndexController'],
			'CreateStoreController' => [$entityFolder, $model . 'CreateStoreController'],
			'EditUpdateController' => [$entityFolder, $model . 'EditUpdateController'],
			'ShowController' => [$entityFolder, $model . 'ShowController'],
			'DestroyController' => [$entityFolder, $model . 'DestroyController'],
			'FieldsGroupParametersFile' => ['Http/Controllers/Providers/FieldsGroups', $model . 'FieldsGroupParametersFile'],
			'EditUpdateFieldsetsParameters' => ['Http/Controllers/Providers/Fieldsets', $model . 'EditUpdateFieldsetsParameters'],
			'CreateStoreFieldsetsParameters' => ['Http/Controllers/Providers/Fieldsets', $model . 'CreateStoreFieldsetsParameters'],
			'ShowFieldsetsParameters' => ['Http/Controllers/Providers/Fieldsets', $model . 'ShowFieldsetsParameters'],
			'RelationManager' => ['Http/Controllers/Providers/RelationshipsManagers', $model . 'RelationManager']
		];
	}

	protected function getNamespaceByFolder(string $folder) : string
	{
		return $this->target['rootNamespace'] . '\\' . str_replace('/', '\\', $folder);
	}

	protected function getClassName(string $folder, string $class) : string
	{
		return $this->getNamespaceByFolder($folder) . '\\' . $class;
	}

	/**
	 * In package i riferimenti passano dal config, in project si punta la classe
	 */
	protected function getParametersFileReference(string $configPath, string $folder, string $class) : string
	{
		if($this->target['mode'] == 'package')
			return "config('{$this->target['configPrefix']}.models.{$this->names['configModelClassName']}.{$configPath}')";

		return $class . '::class';
	}

	protected function getReplacements() : array
	{
		$names = $this->names;
		$model = $names['model'];

		$fieldsGroupClass = $model . 'FieldsGroupParametersFile';
		$createFieldsetsClass = $model . 'CreateStoreFieldsetsParameters';
		$editFieldsetsClass = $model . 'EditUpdateFieldsetsParameters';
		$showFieldsetsClass = $model . 'ShowFieldsetsParameters';
		$relationManagerClass = $model . 'RelationManager';

		$indexFieldsGroup = $this->target['mode'] == 'package'
			? "config('{$this->target['configPrefix']}.models.{$names['configModelClassName']}.fieldsGroupsFiles.index')"
			: $fieldsGroupClass;

		return [
			'{{ model }}' => $model,
			'{{ modelVariable }}' => $names['modelVariable'],
			'{{ table }}' => $this->target['table'],
			'{{ translationPrefix }}' => $this->target['translationPrefix'],

			'{{ baseModelImportLine }}' => $this->getBaseModelImportLine(),
			'{{ baseModelClass }}' => class_basename($this->target['baseModel']),
			'{{ baseControllerImport }}' => $this->target['baseController'],
			'{{ baseControllerClass }}' => class_basename($this->target['baseController']),

			'{{ crudExtraImports }}' => $this->getCrudExtraImports(),
			'{{ crudModelBinding }}' => $this->getCrudModelBinding(),

			'{{ indexExtraImports }}' => $this->getExtraImports([
				['Http/Controllers/Providers/FieldsGroups', $fieldsGroupClass]
			]),
			'{{ createExtraImports }}' => $this->getExtraImports([
				['Http/Controllers/Providers/Fieldsets', $createFieldsetsClass]
			]),
			'{{ editExtraImports }}' => $this->getExtraImports([
				['Http/Controllers/Providers/Fieldsets', $editFieldsetsClass]
			]),
			'{{ showExtraImports }}' => $this->getExtraImports([
				['Http/Controllers/Providers/Fieldsets', $showFieldsetsClass],
				['Http/Controllers/Providers/RelationshipsManagers', $relationManagerClass]
			]),

			'{{ indexFieldsGroup }}' => $indexFieldsGroup,
			'{{ createParametersFile }}' => $this->getParametersFileReference('parametersFiles.create', 'Http/Controllers/Providers/Fieldsets', $createFieldsetsClass),
			'{{ editParametersFile }}' => $this->getParametersFileReference('parametersFiles.edit', 'Http/Controllers/Providers/Fieldsets', $editFieldsetsClass),
			'{{ showParametersFile }}' => $this->getParametersFileReference('parametersFiles.show', 'Http/Controllers/Providers/Fieldsets', $showFieldsetsClass),
			'{{ relationshipsManagerClass }}' => $this->getParametersFileReference('relationshipsManagerClasses.show', 'Http/Controllers/Providers/RelationshipsManagers', $relationManagerClass),

			'{{ migrationKey }}' => $this->target['mode'] == 'package' ? "\t\t\t\$table->uuid('id')->primary();" : "\t\t\t\$table->id();"
		];
	}

	/** in package il model base sta nello stesso namespace, l'import non serve */
	protected function getBaseModelImportLine() : string
	{
		if($this->getNamespaceByFolder('Models') == $this->getNamespace($this->target['baseModel']))
			return '';

		return "\nuse " . $this->target['baseModel'] . ";\n";
	}

	protected function getNamespace(string $class) : string
	{
		return trim(implode('\\', array_slice(explode('\\', $class), 0, -1)), '\\');
	}

	protected function getCrudExtraImports() : string
	{
		if($this->target['mode'] == 'package')
			return '';

		return 'use ' . $this->getClassName('Models', $this->names['model']) . ";\n";
	}

	protected function getCrudModelBinding() : string
	{
		if($this->target['mode'] == 'package')
			return "\tpublic \$configModelClassName = '{$this->names['configModelClassName']}';";

		return "\tpublic \$modelClass = {$this->names['model']}::class;";
	}

	/** in package i providers si raggiungono dal config, quindi non servono imports */
	protected function getExtraImports(array $classes) : string
	{
		if($this->target['mode'] == 'package')
			return '';

		$result = '';

		foreach($classes as $class)
			$result .= 'use ' . $this->getClassName($class[0], $class[1]) . ";\n";

		return $result;
	}

	protected function createFiles() : void
	{
		$replacements = $this->getReplacements();

		foreach($this->getFilesMap() as $stub => $destination)
		{
			[$folder, $class] = $destination;

			$path = $this->target['sourcePath'] . '/' . $folder . '/' . $class . '.php';

			$content = str_replace(
				array_keys($replacements),
				array_values($replacements),
				$this->getStub($stub)
			);

			$content = str_replace('{{ namespace }}', $this->getNamespaceByFolder($folder), $content);

			$this->putFile($path, $content);
		}
	}

	protected function createMigration() : void
	{
		$replacements = $this->getReplacements();

		$fileName = date('Y_m_d_His') . '_create_' . $this->target['table'] . '_table.php';

		$this->putFile(
			$this->target['migrationsPath'] . '/' . $fileName,
			str_replace(array_keys($replacements), array_values($replacements), $this->getStub('migration'))
		);
	}

	protected function getStub(string $name) : string
	{
		return file_get_contents(__DIR__ . '/Stubs/CrudEntity/' . $name . '.stub');
	}

	protected function putFile(string $path, string $content) : void
	{
		if(file_exists($path) && (! $this->option('force')))
		{
			$this->skipped[] = $path;

			return;
		}

		if(! is_dir($directory = dirname($path)))
			mkdir($directory, 0755, true);

		file_put_contents($path, $content);

		$this->created[] = $path;
	}

	protected function printCreatedFiles() : void
	{
		if(count($this->created))
		{
			$this->newLine();
			$this->info('Files creati');

			foreach($this->created as $path)
				$this->line('  <fg=green>' . str_replace(base_path() . '/', '', $path) . '</>');
		}

		if(! count($this->skipped))
			return;

		$this->newLine();
		$this->warn('Files già esistenti, saltati (--force per sovrascrivere)');

		foreach($this->skipped as $path)
			$this->line('  <fg=yellow>' . str_replace(base_path() . '/', '', $path) . '</>');
	}

	protected function printConfig() : void
	{
		if($this->target['mode'] == 'project')
			return;

		$names = $this->names;
		$model = $names['model'];
		$key = $names['configModelClassName'];

		$this->printTitle("Da incollare in config/{$this->target['configPrefix']}.php, dentro 'models'");

		$this->line("        '{$key}' => [");
		$this->line("            'class' => {$model}::class,");
		$this->line("            'table' => '{$this->target['table']}',");
		$this->line("            'fieldsGroupsFiles' => [");
		$this->line("                'index' => {$model}FieldsGroupParametersFile::class");
		$this->line("            ],");
		$this->line("            'parametersFiles' => [");
		$this->line("                'create' => {$model}CreateStoreFieldsetsParameters::class,");
		$this->line("                'edit' => {$model}EditUpdateFieldsetsParameters::class,");
		$this->line("                'show' => {$model}ShowFieldsetsParameters::class");
		$this->line("            ],");
		$this->line("            'relationshipsManagerClasses' => [");
		$this->line("                'show' => {$model}RelationManager::class");
		$this->line("            ],");
		$this->line("            'controllers' => [");
		$this->line("                'index' => {$model}IndexController::class,");
		$this->line("                'create' => {$model}CreateStoreController::class,");
		$this->line("                'store' => {$model}CreateStoreController::class,");
		$this->line("                'show' => {$model}ShowController::class,");
		$this->line("                'edit' => {$model}EditUpdateController::class,");
		$this->line("                'update' => {$model}EditUpdateController::class,");
		$this->line("                'destroy' => {$model}DestroyController::class,");
		$this->line("            ]");
		$this->line("        ],");

		$this->printTitle("Use da aggiungere in cima a config/{$this->target['configPrefix']}.php");

		foreach($this->getConfigImports() as $import)
			$this->line("use {$import};");
	}

	protected function getConfigImports() : array
	{
		$model = $this->names['model'];
		$entityFolder = 'Http/Controllers/' . $this->names['modelPlural'];

		$imports = [
			$this->getClassName('Models', $model),
			$this->getClassName($entityFolder, $model . 'IndexController'),
			$this->getClassName($entityFolder, $model . 'CreateStoreController'),
			$this->getClassName($entityFolder, $model . 'ShowController'),
			$this->getClassName($entityFolder, $model . 'EditUpdateController'),
			$this->getClassName($entityFolder, $model . 'DestroyController'),
			$this->getClassName('Http/Controllers/Providers/FieldsGroups', $model . 'FieldsGroupParametersFile'),
			$this->getClassName('Http/Controllers/Providers/Fieldsets', $model . 'CreateStoreFieldsetsParameters'),
			$this->getClassName('Http/Controllers/Providers/Fieldsets', $model . 'EditUpdateFieldsetsParameters'),
			$this->getClassName('Http/Controllers/Providers/Fieldsets', $model . 'ShowFieldsetsParameters'),
			$this->getClassName('Http/Controllers/Providers/RelationshipsManagers', $model . 'RelationManager')
		];

		sort($imports);

		return $imports;
	}

	protected function printRoutes() : void
	{
		$names = $this->names;
		$variable = $names['modelVariable'];
		$routeName = $names['modelPluralVariable'];

		$destination = $this->target['mode'] == 'package'
			? "routes/{$this->target['configPrefix']}.php"
			: 'routes/web.php';

		$this->printTitle("Da incollare in {$destination}");

		$this->line("Route::group(['prefix' => '{$names['urlPrefix']}'], function()");
		$this->line("{");
		$this->line("\tRoute::get('', [" . $this->getRouteController('index') . ", 'index'])->name('{$routeName}.index');");
		$this->line("\tRoute::get('create', [" . $this->getRouteController('create') . ", 'create'])->name('{$routeName}.create');");
		$this->line("\tRoute::post('', [" . $this->getRouteController('store') . ", 'store'])->name('{$routeName}.store');");
		$this->line("");
		$this->line("\tRoute::get('{{$variable}}', [" . $this->getRouteController('show') . ", 'show'])->name('{$routeName}.show');");
		$this->line("\tRoute::get('{{$variable}}/edit', [" . $this->getRouteController('edit') . ", 'edit'])->name('{$routeName}.edit');");
		$this->line("\tRoute::put('{{$variable}}', [" . $this->getRouteController('update') . ", 'update'])->name('{$routeName}.update');");
		$this->line("");
		$this->line("\tRoute::delete('{{$variable}}/delete', [" . $this->getRouteController('destroy') . ", 'destroy'])->name('{$routeName}.destroy');");
		$this->line("});");

		$this->printTitle("Use da aggiungere in cima a {$destination}");

		foreach($this->getRoutesImports() as $import)
			$this->line("use {$import};");

		$this->newLine();
	}

	protected function getRouteController(string $action) : string
	{
		if($this->target['mode'] == 'package')
			return class_basename($this->target['packageClass']) . "::getController('{$this->names['configModelClassName']}', '{$action}')";

		$controllers = [
			'index' => 'IndexController',
			'create' => 'CreateStoreController',
			'store' => 'CreateStoreController',
			'show' => 'ShowController',
			'edit' => 'EditUpdateController',
			'update' => 'EditUpdateController',
			'destroy' => 'DestroyController'
		];

		return $this->names['model'] . $controllers[$action] . '::class';
	}

	protected function getRoutesImports() : array
	{
		if($this->target['mode'] == 'package')
			return [$this->target['packageClass']];

		$model = $this->names['model'];
		$entityFolder = 'Http/Controllers/' . $this->names['modelPlural'];

		return [
			$this->getClassName($entityFolder, $model . 'CreateStoreController'),
			$this->getClassName($entityFolder, $model . 'DestroyController'),
			$this->getClassName($entityFolder, $model . 'EditUpdateController'),
			$this->getClassName($entityFolder, $model . 'IndexController'),
			$this->getClassName($entityFolder, $model . 'ShowController')
		];
	}

	protected function printTitle(string $title) : void
	{
		$this->newLine();
		$this->info($title);
		$this->line(str_repeat('-', strlen($title)));
	}
}
