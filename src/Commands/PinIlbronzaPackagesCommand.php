<?php

namespace IlBronza\CRUD\Commands;

use Illuminate\Console\Command;
use RuntimeException;

class PinIlbronzaPackagesCommand extends Command
{
	protected $signature = 'crud:pin-ilbronza-packages
		{version? : Version constraint to apply (defaults to config crud.packagesVersion)}
		{--dry-run : Show changes without writing composer.json}';

	protected $description = 'Set every ilbronza/* package in the host composer.json to a chosen version constraint';

	public function handle(): int
	{
		$version = $this->argument('version') ?: config('crud.packagesVersion');

		if (! is_string($version) || $version === '') {
			throw new RuntimeException(
				'No version given. Pass {version} or set crud.packagesVersion / ILBRONZA_PACKAGES_VERSION.'
			);
		}

		$path = base_path('composer.json');

		if (! is_file($path)) {
			throw new RuntimeException("composer.json not found at [{$path}].");
		}

		$raw = file_get_contents($path);

		if ($raw === false) {
			throw new RuntimeException("Unable to read [{$path}].");
		}

		$composer = json_decode($raw, true);

		if (! is_array($composer)) {
			throw new RuntimeException('Invalid composer.json: ' . json_last_error_msg());
		}

		$changed = [];

		foreach (['require', 'require-dev'] as $section) {
			if (! isset($composer[$section]) || ! is_array($composer[$section])) {
				continue;
			}

			foreach ($composer[$section] as $package => $current) {
				if (! is_string($package) || ! str_starts_with($package, 'ilbronza/')) {
					continue;
				}

				if ($current === $version) {
					continue;
				}

				$changed[] = [
					'section' => $section,
					'package' => $package,
					'from' => $current,
					'to' => $version,
				];

				$composer[$section][$package] = $version;
			}
		}

		if ($changed === []) {
			$this->info("No ilbronza/* requires to change (already at [{$version}]).");

			return self::SUCCESS;
		}

		$this->table(
			['Section', 'Package', 'From', 'To'],
			array_map(fn (array $row) => [
				$row['section'],
				$row['package'],
				$row['from'],
				$row['to'],
			], $changed)
		);

		if ($this->option('dry-run')) {
			$this->warn('Dry-run only: composer.json was not modified.');

			return self::SUCCESS;
		}

		$encoded = json_encode(
			$composer,
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
		) . "\n";

		if (file_put_contents($path, $encoded) === false) {
			throw new RuntimeException("Unable to write [{$path}].");
		}

		$this->info('Updated composer.json (' . count($changed) . ' package(s) → ' . $version . ').');
		$this->line('Next: <fg=yellow>composer update "ilbronza/*"</>');

		return self::SUCCESS;
	}
}
