<?php

namespace IlBronza\CRUD\ResourceFilters;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class ResourceFilterController extends Controller
{
    public function edit(ResourceFilters $filters, string $resource)
    {
        $entry = $filters->resource($resource);
        $form = $entry->helper->buildForm(route('crud.resourceFilters.update', [
            'resource' => $resource,
            'iframed' => request()->boolean('iframed') ? 1 : null,
        ]), $entry->label(), $entry->definitions, $filters->state()[$resource] ?? []);
        return view('crud::resource-filters.edit', [
            'form' => $form,
            'resource' => $resource,
            'active' => isset($filters->state()[$resource]),
            'hasActiveFilters' => $filters->state() !== [],
        ]);
    }

    public function update(Request $request, ResourceFilters $filters, string $resource)
    {
        try {
            $filters->save($resource, $request->input());
        } catch (ValidationException $exception) {
            if ($request->expectsJson())
                throw $exception;

            return redirect()->route('crud.resourceFilters.edit', [
                'resource' => $resource,
                'iframed' => $request->boolean('iframed') ? 1 : null,
            ], 303)->withErrors($exception->errors());
        }
        return $this->updated($request, $resource);
    }

    public function reset(Request $request, ResourceFilters $filters, string $resource)
    {
        $filters->save($resource, []);
        return $this->updated($request, $resource);
    }

    public function resetAll(Request $request, ResourceFilters $filters)
    {
        $filters->resetAll();
        return $this->updated($request);
    }

    private function updated(Request $request, ?string $resource = null)
    {
        if ($request->expectsJson())
            return response()->noContent();
        if ($request->boolean('iframed'))
            return view('crud::resource-filters.updated', compact('resource'));
        return back(303);
    }
}
