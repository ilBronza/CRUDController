<?php

namespace IlBronza\CRUD\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use IlBronza\CRUD\Tests\Fixtures\Models\MediaGadget;
use IlBronza\CRUD\Tests\TestCase;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class InteractsWithMediaTest extends TestCase
{
	public function test_get_main_media_uses_an_already_loaded_relation_without_querying() : void
	{
		$firstMedia = new Media([
			'id' => 1,
			'collection_name' => 'images',
			'created_at' => '2026-01-01 12:00:00'
		]);
		$lastMedia = new Media([
			'id' => 2,
			'collection_name' => 'images',
			'created_at' => '2026-01-02 12:00:00'
		]);
		$model = (new MediaGadget)->setRelation('media', new Collection([
			$lastMedia,
			$firstMedia
		]));

		DB::enableQueryLog();
		DB::flushQueryLog();

		$this->assertSame($firstMedia, $model->getMainMedia('images'));
		$this->assertSame([], DB::getQueryLog());
	}

	public function test_get_main_media_does_not_query_for_an_empty_loaded_relation() : void
	{
		$model = (new MediaGadget)->setRelation('media', new Collection);

		DB::enableQueryLog();
		DB::flushQueryLog();

		$this->assertNull($model->getMainMedia('images'));
		$this->assertSame([], DB::getQueryLog());
	}

	public function test_get_main_media_queries_when_the_relation_is_not_loaded() : void
	{
		$model = MediaGadget::create(['name' => 'Media gadget']);
		$this->insertMedia($model, '2026-01-01 12:00:00');
		$this->insertMedia($model, '2026-01-02 12:00:00');

		DB::enableQueryLog();
		DB::flushQueryLog();

		$media = $model->getMainMedia('images');

		$this->assertNotNull($media);
		$this->assertSame('2026-01-01 12:00:00', $media->created_at->format('Y-m-d H:i:s'));
		$this->assertCount(1, DB::getQueryLog());
	}

	private function insertMedia(MediaGadget $model, string $createdAt) : void
	{
		DB::table('media')->insert([
			'model_type' => $model->getMorphClass(),
			'model_id' => $model->getKey(),
			'collection_name' => 'images',
			'name' => 'image',
			'file_name' => 'image.jpg',
			'disk' => 'public',
			'size' => 1,
			'manipulations' => '[]',
			'custom_properties' => '[]',
			'generated_conversions' => '[]',
			'responsive_images' => '[]',
			'created_at' => $createdAt,
			'updated_at' => $createdAt
		]);
	}
}
