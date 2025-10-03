
	<style>
		table.history td{
			font-size: 12px;
		}
	</style>

<table class="history uk-table uk-table-striped uk-table-hover">
	<tr>
		<th>
			Id
		</th>
		<th>
			Data
		</th>
		<th>
			Azione
		</th>
		<th>
			Operatore
		</th>
	@foreach($fields as $field => $parameters)
		<th>
			@if(isset($parameters['translatedFieldName']))
				{!! $parameters['translatedFieldName'] !!}
			@else

				{{ trans('fields.' . ($field ?? null)) }}
			@endif
		</th>
	@endforeach
	</tr>


@foreach($activitiesResult as $activity)
	<tr>
		<td>
			{{ $activity['activity_id'] }}
		</td>
		<td>
			{{ $activity['activity_created_at'] }}
		</td>
		<td>
			{{ trans('crud::crud.activities.' . $activity['activity_description']) }}
		</td>
		<td>
			{{ $activity['activity_causer'] ?? null }}
		</td>

		@foreach($fields as $field => $true)
			<td>
				@if(($activity[$field] ?? null) === false)
					0
				@elseif(isset($activity[$field]))
					@if(is_string($activity[$field]))
						{{ $activity[$field] }}
					@else
						{{ json_encode($activity[$field]) }}
					@endif
				@endif				
			</td>
		@endforeach

	</tr>
@endforeach

</table>

