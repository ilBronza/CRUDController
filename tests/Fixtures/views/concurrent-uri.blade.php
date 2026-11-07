<!doctype html>
<html><head><meta name="csrf-token" content="{{ csrf_token() }}"></head><body>
@include('uikittemplate::utilities.concurrentUri.headerbar')
<form method="post" action="/concurrent-test/operation">@csrf<input name="name" value="draft"><button>Save</button></form>
@include('uikittemplate::utilities.concurrentUri.scripts')
</body></html>
