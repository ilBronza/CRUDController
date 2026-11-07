@if(config('crud.useConcurrentRequestsAlert') && request()->attributes->has('crud.concurrentUri'))
@once
<script>
window.ibConcurrentUriConfig = @json(request()->attributes->get('crud.concurrentUri'));
</script>
@include('crud::utilities.concurrentUri.client')
@endonce
@endif
