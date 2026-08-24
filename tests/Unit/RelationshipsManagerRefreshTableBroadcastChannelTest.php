<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;
use IlBronza\CRUD\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RelationshipsManagerRefreshTableBroadcastChannelTest extends TestCase
{
	public function test_relation_table_uses_its_configured_refresh_broadcast_channel() : void
	{
		$manager = new class('show', new RefreshTableChannelParentModel()) extends RelationshipsManager
		{
			public function getAllRelationsParameters() : array
			{
				return [
					'show' => [
						'relations' => [
							'orders' => [
								'fieldsGroups' => ['base' => ['fields' => []]],
								'refreshTableBroadcastChannel' => 'channel.tables.orders.refresh',
							],
						],
					],
				];
			}
		};

		$relationship = $manager->getRelationships()->get('orders');
		$relationship->setTable();

		$this->assertSame(
			'channel.tables.orders.refresh',
			$relationship->getTable()->getRefreshTableBroadcastChannel()
		);
	}
}

class RefreshTableChannelParentModel extends Model
{
	public function orders() : HasMany
	{
		return $this->hasMany(RefreshTableChannelRelatedModel::class, 'parent_id');
	}
}

class RefreshTableChannelRelatedModel extends Model
{
}
