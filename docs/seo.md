# SEO per entità e lingua

Eseguire `php artisan migrate`. Aggiungere `IlBronza\CRUD\Traits\Model\HasSeo` al modello Eloquent (in alternativa al vecchio `CRUDHasMetaTrait`). La relazione `seo()` è una morphMany con un record per lingua; supporta chiavi numeriche, UUID e ULID.

```php
$product->seo()->updateOrCreate(['locale' => 'it-IT'], [
    'title' => 'Titolo SEO',
    'description' => 'Descrizione della pagina',
    'og_image' => 'https://example.com/images/product.jpg',
]);
$product->unsetRelation('seo'); // se la relazione era già stata caricata
$meta = $product->getMetaTags('it-IT');
```

Nel layout, dentro head:

```blade
@include('crud::seo.meta', ['model' => $product, 'locale' => app()->getLocale()])
```

Impostare separatamente `<html lang="{{ app()->getLocale() }}">`. Per liste usare `with('seo')` per evitare query per ogni entità.

Personalizzare `getDefaultSeoTitle()`, `getDefaultSeoDescription()` e `getDefaultSeoUrl(?string $locale = null)` sul modello. Il titolo usa altrimenti title/name; descrizione e URL non vengono dedotti. Gli override Open Graph e Twitter ereditano titolo e descrizione SEO. I valori vuoti non vengono renderizzati. Usare URL assoluti pubblici per canonical e immagini.

Non viene effettuato fallback tra lingue diverse. `og:locale` viene emesso solo per locale con lingua e regione (es. it-IT), mentre locale brevi come it restano validi per la selezione dei dati. Hreflang richiede gli URL delle traduzioni effettivamente pubblicate e va gestito dal layout dell'applicazione. La presenza di un record SEO non certifica la pubblicazione di una traduzione.

I record SEO non vengono eliminati automaticamente alla cancellazione del modello: l'applicazione può gestire la pulizia secondo le proprie regole di soft delete e ripristino.

## Route e fetcher

Registrare gli alias dei modelli abilitati tramite `Relation::morphMap(['product' => Product::class])`. Le route richiedono autenticazione e controllano `userCanShow`/`userCanUpdate` sul padre, oppure le policy Laravel `view`/`update`.

- `GET /crud/seo/{model}/{key}` (`crud.seo.index`): elenco delle lingue, caricabile via fetcher.
- `GET /crud/seo/{model}/{key}/{locale}/edit` (`crud.seo.edit`): form della lingua, anche se il record non esiste ancora.
- `PUT /crud/seo/{model}/{key}/{locale}` (`crud.seo.update`): creazione/aggiornamento validato.

Le risposte HTML sono frammenti integrabili nel layout o nei fetcher.

```php
use IlBronza\CRUD\Helpers\Seo\SeoFormHelper;

SeoFormHelper::addFetcher($form, $product); // padre già salvato
// Oppure: $table->addFetcherByUrl(SeoFormHelper::url($product));
```

## Campi nei form create/edit

Dentro il form esistente, senza introdurre un altro tag form:

```blade
{!! \IlBronza\CRUD\Helpers\Seo\SeoFormHelper::render($product ?? null, app()->getLocale())->render() !!}
```

I campi sono raggruppati sotto `seo[...]`. `SeoFormHelper::fields()` espone anche le definizioni di tipo e regole per integrazioni personalizzate.

Nel controller create/update, escludere `seo` dai dati del padre. Dopo il salvataggio del padre chiamare l'helper nella stessa transazione, dopo aver verificato i permessi:

```php
DB::transaction(function () use ($request, $product) {
    // Validare e salvare i campi del padre.
    $product->save();
    if ($request->has('seo')) {
        SeoFormHelper::save($product, $request->input('seo'));
    }
});
```

L'helper invalida la relazione caricata dopo il salvataggio. La route di aggiornamento fissa la lingua dal percorso e ignora un eventuale locale differente nel payload.

## Sitemap multilingua

`SeoUrlResolver` risolve il canonical usato sia da `HasSeo::getMetaTags()` sia da `SeoSitemapAdapter`. È risolvibile dal container per i meta; per personalizzarlo nella sitemap passare la stessa istanza all'adapter.

I modelli partecipanti implementano `IlBronza\CRUD\Interfaces\Sitemapable`:

```php
public function getSitemapUrls(): iterable
{
    // La lista deve provenire dalle traduzioni pubblicate, non dai record SEO.
    foreach ($this->publishedLocales() as $locale) {
        yield $locale => $this->getDefaultSeoUrl($locale);
    }
}

public function isSitemapIndexable(): bool
{
    return $this->isPublished() && ! $this->isPrivate();
}

public function getSitemapLastModified(): ?\DateTimeInterface
{
    return $this->updated_at; // includere eventuali modifiche SEO significative
}
```

`publishedLocales()`, `isPublished()` e `isPrivate()` sono esempi di logica dell'applicazione, da implementare secondo il dominio. Ogni URL deve essere assoluto HTTP(S), senza fragment. Usare gli stessi locale dei record SEO; gli underscore vengono convertiti in trattini solo nell'output hreflang.

Registrare le sorgenti implementando `IlBronza\CRUD\Interfaces\SitemapProvider`:

```php
class ProductSitemapProvider implements \IlBronza\CRUD\Interfaces\SitemapProvider
{
    public function items(): iterable
    {
        return Product::query()->where('published', true)->with('seo')->lazyById(500);
    }
}
```

Generazione e salvataggio a carico dell'applicazione (nessuna route pubblica aggiunta automaticamente):

```php
$resolver = app(\IlBronza\CRUD\Seo\SeoUrlResolver::class);
$generator = new \IlBronza\CRUD\Sitemap\SitemapGenerator(
    new \IlBronza\CRUD\Sitemap\SeoSitemapAdapter($resolver)
);
$urls = [];
foreach ($generator->documents([new ProductSitemapProvider]) as $index => $xml) {
    $filename = 'sitemap-' . ($index + 1) . '.xml';
    // Salvare $xml nello storage pubblico scelto.
    $urls[] = url($filename);
}
$indexXml = $generator->index($urls);
```

Ogni traduzione ammessa include gli alternate reciproci e se stessa. `noindex`, `none` e canonical diversi dall'URL pubblico escludono la traduzione anche dagli alternate. Il confronto canonical è esatto: gli URL devono avere una normalizzazione coerente. Le sorgenti senza `HasSeo` possono implementare lo stesso contratto senza metadati SEO.

Il generatore limita ogni documento a 50.000 URL e 50 MiB non compressi, esegue escaping XML e deduplica globalmente gli URL (vince la prima voce). La memoria mantiene il documento corrente e gli URL già incontrati. Pubblicazione atomica, pulizia dei file precedenti e scheduling restano responsabilità dell'applicazione. Con sorgenti vuote non vengono prodotti urlset.

Verifica autonoma: `php tests/Standalone/sitemap.php`.
