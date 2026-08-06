@php
	use IlBronza\CRUD\Helpers\MediaHelpers\MediaCollectionFinderHelper;

	$userCanDelete = $element->userCanDelete();
@endphp

<div class="uk-card uk-card-default uk-card-small media-collection">
	<div class="uk-card-header">
		{{ trans('crud::media.cardTitle', ['model' => $element->getName()]) }}
	</div>
	<div class="uk-card-body">

		@if($medias->isEmpty())
			<p class="uk-text-muted">{{ trans('crud::media.noFiles') }}</p>
		@else
			<table class="media-collection uk-table uk-table-striped uk-table-hover uk-table-small uk-table-middle">
				<tr>
					<th class="uk-table-shrink"></th>
					<th>
						{{ trans('crud::media.columnName') }}
					</th>
					<th>
						{{ trans('crud::media.columnDate') }}
					</th>
					<th>
						{{ trans('crud::media.columnSize') }}
					</th>
					<th>
						{{ trans('crud::media.columnIntegrityCheckedAt') }}
					</th>
					<th class="uk-table-shrink"></th>
					<th class="uk-table-shrink"></th>
					<th class="uk-table-shrink"></th>
					@if($userCanDelete)
						<th class="uk-table-shrink"></th>
					@endif
				</tr>

				@foreach($medias as $media)
					<tr>
						<td>
							@if($thumbUrl = $media->getServeThumbUrl())
								<a href="{{ $media->getServeImageUrl() }}" target="_blank" rel="noopener">
									<img class="media-collection-thumb" src="{{ $thumbUrl }}" alt="{{ $media->file_name }}" width="48" height="48">
								</a>
							@endif
						</td>
						<td>
							{{ $media->file_name }}
						</td>
						<td>
							{{ $media->created_at?->format('d/m/Y H:i') }}
						</td>
						<td>
							{{ round($media->size / 1024) }} KB
						</td>
						<td>
							<span class="media-integrity-checked-at">{{ $media->getIntegrityCheckedAtString() }}</span>
						</td>
						<td>
							<a class="uk-icon-button ib-set-media-integrity-check"
								href="#"
								data-url="{{ $media->getSetIntegrityCheckUrl() }}"
								data-send-ajax-payload="false"
								title="{{ trans('crud::media.setIntegrityCheck') }}">
								<i class="fa-solid fa-clipboard-check"></i>
							</a>
						</td>
						<td>
							<a class="uk-icon-button" href="{{ $media->getServeImageUrl() }}" target="_blank" rel="noopener" title="{{ trans('crud::media.columnPreview') }}">
								<i class="fa-solid fa-up-right-from-square"></i>
							</a>
						</td>
						<td>
							<a class="uk-icon-button" href="{{ $media->getServeImageUrl() }}" download title="{{ trans('crud::media.columnDownload') }}">
								<i class="fa-solid fa-file-arrow-down"></i>
							</a>
						</td>
						@if($userCanDelete)
							<td>
								<a class="uk-icon-button uk-text-danger ib-delete-media"
									href="#"
									data-url="{{ MediaCollectionFinderHelper::getMediaDeleteUrl($element, $media) }}"
									data-returnconfirm="{{ trans('crud::media.deleteConfirm', ['name' => $media->file_name]) }}"
									data-send-ajax-payload="false"
									title="{{ trans('crud::media.columnDelete') }}">
									<i class="fa-solid fa-trash"></i>
								</a>
							</td>
						@endif
					</tr>
				@endforeach

			</table>
		@endif
	</div>

</div>
