<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;
use IlBronza\Buttons\Button;
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

	public function test_relation_create_button_uses_its_configured_translated_text() : void
	{
		$manager = new class('show', new CreateButtonParentModel()) extends RelationshipsManager
		{
			public function getAllRelationsParameters() : array
			{
				return [
					'show' => [
						'relations' => [
							'orders' => [
								'hasCreateButton' => true,
								'createButtonText' => 'Aggiungi ordine',
							],
						],
					],
				];
			}
		};

		$relationship = $manager->getRelationships()->get('orders');
		$table = new RelationButtonsTable();
		$relationship->table = $table;
		$relationship->manageTableButtons();

		$this->assertSame('Aggiungi ordine', $table->buttons[0]->getText());
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

class CreateButtonParentModel extends Model
{
	public function orders() : HasMany
	{
		return $this->hasMany(CreateButtonRelatedModel::class, 'parent_id');
	}
}

class CreateButtonRelatedModel extends Model
{
	public function getCreateByRelatedButton(Model $parentModel, Model $relatedModel) : Button
	{
		return Button::create([
			'href' => '#',
			'translatedText' => 'Testo automatico',
		]);
	}
}

class RelationButtonsTable
{
	public array $buttons = [];

	public function addButton(Button $button) : void
	{
		$this->buttons[] = $button;
	}
}
