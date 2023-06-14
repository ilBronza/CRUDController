<!--DEPRECATO A MANETTA-->
<script type="text/javascript">
    console.log('DEPRECATO A MANETTA');
</script>

@if(isset($relationships) && count($relationships))
<div class="uk-card-footer">

    @foreach($relationships as $name)
        @if(isset($relationshipsTableNames[$name]))

            <h3>Correlato: @indexLink($name)</h3>

            {!! $relationshipsTableNames[$name]->renderPortion(); !!}

        @elseif(! is_null($_item = $relationshipsElements[$name] ?? null))

            <h3>@indexLink($name) :
                <a href="{{ $_item->getShowUrl() }}">{{ $_item->getName() }}</a>
            </h3>

        @endif
    @endforeach

</div>
@endif


<!--DEPRECATO A MANETTA-->

@include('crud::uikit.__relationshipsFlat')
{{-- @include('crud::uikit.__relationshipsToggler') --}}
{{-- @include('crud::uikit.__relationshipsSwitcher') --}}
