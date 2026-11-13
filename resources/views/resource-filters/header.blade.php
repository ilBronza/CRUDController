<div class="uk-flex uk-flex-top uk-margin-small uk-padding-small uk-background-muted">
    @if($count)
        @include('crud::resource-filters.reset', [
            'iconOnly' => true,
        ])
    @endif
    <details class="uk-flex-1">
        <summary>{{ $count ? __('crud::resource-filters.active', ['count' => $count]) : __('crud::resource-filters.edit') }}</summary>
        @if($showResourceButtons)
            @foreach($buttons as $key => $button)
                @unless(isset($summaries[$key]))
                    {!! $button->render() !!}
                @endunless
            @endforeach
        @endif
        <ul class="uk-list">
            @foreach($summaries as $key => $summary)
                <li>
                    {!! $buttons[$key]->render() !!}
                    @foreach($summary as $criterion)
                        <span>{{ $criterion['label'] }} · {{ $criterion['selection'] }}</span>
                    @endforeach
                    @include('crud::resource-filters.reset', ['resource' => $key])
                </li>
            @endforeach
        </ul>
    </details>
</div>
