@extends('uikittemplate::app')

@section('content')
	<style>
		table.history td{
			font-size: 12px;
		}
	</style>

<div class="uk-card uk-card-primary uk-card-small history-for-model-field">
	<div class="uk-card-header">
		{{ trans('crud::history.cardTitle', ['model' => $model->getName(), 'field' => trans('fields.' . $field)]) }}
	</div>
	<div class="uk-card-body">

		<table class="history uk-table uk-table-striped uk-table-hover">
			<tr>
				<th>
					{{ trans('crud::history.columnId') }}
				</th>
				<th>
					{{ trans('crud::history.columnDate') }}
				</th>
				<th>
					{{ trans('crud::history.columnAction') }}
				</th>
				<th>
					{{ trans('crud::history.columnOperator') }}
				</th>
				<th>
					{{ trans('crud::history.summaryPreviousValue') }}
				</th>
				<th>
					{{ trans('crud::history.summaryNewValue') }}
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
					@if($activity['old_logged'])
						{{ $activity['old'] }}
					@else
						<span class="uk-text-muted">{{ $activity['old'] }}</span>
					@endif
				</td>
				<td>
					@if($activity['value_logged'])
						{{ $activity['value'] }}
					@else
						<span class="uk-text-muted">{{ $activity['value'] }}</span>
					@endif
				</td>

			</tr>
		@endforeach

		</table>
	</div>

</div>

@endsection
