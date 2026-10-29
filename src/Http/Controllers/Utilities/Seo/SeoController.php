<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\Seo;

use IlBronza\CRUD\Helpers\Seo\SeoFormHelper;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class SeoController extends Controller
{
    protected function parentModel(string $alias, string $key, string $ability)
    {
        // Only registered morph aliases may be resolved from a URL.
        $class = Relation::getMorphedModel($alias);
        abort_unless($class, 404);
        $model = $class::findOrFail($key);
        abort_unless(method_exists($model, 'getSeo'), 404);
        $method = $ability === 'view' ? 'userCanShow' : 'userCanUpdate';
        if (method_exists($model, $method))
            abort_unless($model->$method(auth()->user()), 403);
        else
            Gate::authorize($ability, $model);
        return $model;
    }

    public function index(string $model, string $key)
    {
        $parent = $this->parentModel($model, $key, 'view');
        return view('crud::seo.index', ['model' => $parent, 'records' => $parent->seo()->orderBy('locale')->get()]);
    }

    public function edit(string $model, string $key, string $locale)
    {
        $parent = $this->parentModel($model, $key, 'update');
        return view('crud::seo.edit', ['model' => $parent, 'locale' => $locale]);
    }

    public function update(Request $request, string $model, string $key, string $locale)
    {
        $parent = $this->parentModel($model, $key, 'update');
        SeoFormHelper::save($parent, array_merge($request->input('seo', []), ['locale' => $locale]));
        return redirect(SeoFormHelper::url($parent, 'edit', $locale));
    }
}
