@if(config('crud.useConcurrentRequestsAlert') && request()->attributes->has('crud.concurrentUri'))
<div id="ib-concurrent-uri" role="status" aria-live="polite">
    <div class="uk-alert uk-alert-warning">
        <p data-concurrent-uri-message>@lang('crud::crud.concurrentUriChecking')</p>
    </div>
</div>
@endif
