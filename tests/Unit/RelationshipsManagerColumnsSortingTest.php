<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;
use IlBronza\CRUD\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RelationshipsManagerColumnsSortingTest extends TestCase
{
	public function test_relation_table_has_columns_sorting_by_default() : void
	{
		$manager = new class('show', new ColumnsSortingParentModel()) extends RelationshipsManager
		{
			public function getAllRelationsParameters() : array
			{
				return [
					'show' => [
						'relations' => [
							'orders' => [
								'fieldsGroups' => ['base' => ['fields' => []]],
							],
						],
					],
				];
			}
		};

		$relationship = $manager->getRelationships()->get('orders');
		$relationship->setTable();

		$this->assertTrue($relationship->getTable()->hasSorting());
	}

	public function test_relation_table_follows_crud_config_columns_sorting() : void
	{
		config(['crud.realtionshipManagers.columnsSorting' => false]);

		$manager = new class('show', new ColumnsSortingParentModel()) extends RelationshipsManager
		{
			public function getAllRelationsParameters() : array
			{
				return [
					'show' => [
						'relations' => [
							'orders' => [
								'fieldsGroups' => ['base' => ['fields' => []]],
							],
						],
					],
				];
			}
		};

		$relationship = $manager->getRelationships()->get('orders');
		$relationship->setTable();

		$this->assertFalse($relationship->getTable()->hasSorting());
	}

	public function test_relation_parameter_overrides_crud_config_columns_sorting() : void
	{
		config(['crud.realtionshipManagers.columnsSorting' => true]);

		$manager = new class('show', new ColumnsSortingParentModel()) extends RelationshipsManager
		{
			public function getAllRelationsParameters() : array
			{
				return [
					'show' => [
						'relations' => [
							'orders' => [
								'fieldsGroups' => ['base' => ['fields' => []]],
								'columnsSorting' => false,
							],
						],
					],
				];
			}
		};

		$relationship = $manager->getRelationships()->get('orders');
		$relationship->setTable();

		$this->assertFalse($relationship->getTable()->hasSorting());
	}
}

class ColumnsSortingParentModel extends Model
{
	public function orders() : HasMany
	{
		return $this->hasMany(ColumnsSortingRelatedModel::class, 'parent_id');
	}
}

class ColumnsSortingRelatedModel extends Model
{
}
