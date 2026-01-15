@extends('uikittemplate::app')

@section('content')
	<style>
		table.history td{
			font-size: 12px;
		}
	</style>

<div class="uk-card uk-card-primary uk-card-small history-for-model-field">
	<div class="uk-card-header">
		{{ $model->getName() }} - Storico del campo {{ trans('fields.' . $field) }}
	</div>
	<div class="uk-card-body">
		
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
				<th>
					{{ trans('fields.oldValue') }} - {{ trans('fields.' . $field) }}
				</th>
				<th>
					{{ trans('fields.' . $field) }}
				</th>
				<th>
					Json
				</th>
			</tr>


		@foreach($activities as $activity)
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
				<td>
					{{ $activity['old'] ?? null }}
				</td>
				<td>
					{{ $activity['value'] ?? null }}
				</td>
				<td>
					{{ $activity['json'] ?? null }}
				</td>

			</tr>
		@endforeach

		</table>
	</div>
	
</div>

@endsection