<?php

namespace IlBronza\CRUD\Tests\Feature;

use IlBronza\CRUD\Tests\Fixtures\Models\Gadget;
use IlBronza\CRUD\Tests\TestCase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DeleteMediaControllerTest extends TestCase
{
	protected function setUp() : void
	{
		parent::setUp();

		$this->actingAsSuperadmin();
	}

	public function test_get_delete_media_url_uses_the_standard_route() : void
	{
		$gadget = $this->createGadget('Cacciavite');

		$this->assertSame(
			route('crud.media.destroy', [
				'modelClass' => $gadget->getMorphClass(),
				'modelKey' => $gadget->getKey(),
				'mediaKey' => 123,
				'iframed' => true,
			]),
			$gadget->getDeleteMediaUrlByKey(123, ['iframed' => true])
		);
	}

	public function test_it_deletes_media_belonging_to_the_requested_model() : void
	{
		$gadget = $this->createGadget('Cacciavite');
		$media = $this->createMedia($gadget);

		$response = $this->delete(route('crud.media.destroy', [
			'modelClass' => $gadget->getMorphClass(),
			'modelKey' => $gadget->getKey(),
			'mediaKey' => $media->getKey(),
		]));

		$response->assertOk()->assertJson(['success' => true]);
		$this->assertDatabaseMissing('media', ['id' => $media->getKey()]);
	}

	public function test_it_does_not_delete_media_belonging_to_another_model() : void
	{
		$gadget = $this->createGadget('Cacciavite');
		$otherGadget = $this->createGadget('Martello');
		$media = $this->createMedia($otherGadget);

		$response = $this->delete(route('crud.media.destroy', [
			'modelClass' => $gadget->getMorphClass(),
			'modelKey' => $gadget->getKey(),
			'mediaKey' => $media->getKey(),
		]));

		$response->assertForbidden();
		$this->assertDatabaseHas('media', ['id' => $media->getKey()]);
	}

	private function createGadget(string $name) : Gadget
	{
		return Gadget::create([
			'name' => $name,
			'description' => 'Descrizione',
		]);
	}

	private function createMedia(Gadget $gadget) : Media
	{
		return Media::create([
			'model_type' => $gadget->getMorphClass(),
			'model_id' => $gadget->getKey(),
			'collection_name' => 'images',
			'name' => 'image',
			'file_name' => 'image.jpg',
			'disk' => 'public',
			'size' => 1,
			'manipulations' => [],
			'custom_properties' => [],
			'generated_conversions' => [],
			'responsive_images' => [],
		]);
	}
}
