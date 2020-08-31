@if(count($relationships))
<div class="uk-card-footer">

    @foreach($relationships as $name)
        @if(isset($relationshipsTableNames[$name]))

            <h3>Correlato: @indexLink($name)</h3>

            @include('datatables._table', ['table' => $$name])

        @elseif(! is_null($_item = $relationshipsElements[$name] ?? null))

            <h3>@indexLink($name) :
                <a href="{{ $_item->getShowUrl() }}">{{ $_item->getName() }}</a>
            </h3>

        @endif
    @endforeach

</div>
@endif
