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

## Integrazione con UikitTemplate

Nei layout UikitTemplate, il modello con `HasSeo` viene letto automaticamente
quando è passato come `FrontendContext::page`:

```php
$frontendContext = new \IlBronza\UikitTemplate\Frontend\FrontendContext(
    locale: $contentLocale,
    title: 'Titolo generico della pagina',
    page: $product,
);
```

Il titolo SEO, la descrizione, i robots, il canonical, Open Graph e Twitter card
arrivano al head nella lingua del contesto. Non è necessario ripassare gli stessi
dati in `meta`, né includere un altro partial SEO. La lingua del traduttore
applicativo resta invariata.

Per pagine senza contesto frontend, registrare il modello nel controller prima
del rendering:

```php
$head = app('uikittemplate')->head()->setSeoModel($product, $contentLocale);

// Gli override del controller hanno precedenza, anche se impostati prima del modello.
$head->setTitle('Titolo specifico')
    ->setDescription('Descrizione specifica')->setRobots('index, follow')
    ->setCanonical($canonicalUrl)
    ->setOpenGraphTitle('Titolo social')->setOpenGraphImage($shareImageUrl)
    ->setTwitterTitle('Titolo Twitter')
    ->setHrefLink('en', 'https://example.com/en/custom-slug');
$head->addJsonLd($articleSchema);
$head->setJsonLdProperty('headline', 'Titolo per i dati strutturati');
```

Il modello fornisce i default; `FrontendContext::meta` e i setter del controller
possono sovrascriverli. `setMeta($product->getMetaTags($contentLocale))` continua a
essere supportato quando si preferisce passare esplicitamente l'array completo.
Il renderer comune emette una sola copia di titolo, canonical e metadati.

Gli override sono indipendenti e limitati alla richiesta: non modificano i record
SEO. Cambiare `description` non cambia anche le descrizioni Open Graph o Twitter.
Sono disponibili setter dedicati per tutti i campi del trait, come
`setOpenGraphDescription/Url/Type/Image/ImageAlt/Locale` e
`setTwitterCard/Title/Description/Image/ImageAlt`, oltre a
`setOpenGraphSiteName`, `setTwitterSite` e `setTwitterCreator`.
Per ulteriori proprietà usare `setOpenGraphProperty($property, $value)` e
`setTwitterProperty($property, $value)`. Un valore `null` su titolo, descrizione,
robots o proprietà social omette quel tag, anche quando il modello lo fornisce.
`setHrefLink($locale, $url)` modifica un solo alternate; `null` rimuove quella
lingua e lascia le altre invariate. I setter di gruppo vanno chiamati prima delle
correzioni puntuali dello stesso gruppo.

Se né modello né controller/config forniscono un canonical, UikitTemplate usa
l'URL assoluto corrente senza query string. Gli alternate non forniti vengono
generati prefissando il canonical con le lingue di
`uikittemplate.availableLanguages`, sostituendo l'eventuale prefisso già presente.
Il fallback si applica solo al head: `SeoUrlResolver` rimane indipendente dalla
richiesta, anche quando viene usato per generare sitemap.

Per slug differenti tra lingue, lingua base senza prefisso o traduzioni non tutte
disponibili, passare la mappa reale in `FrontendContext::languageUrls` oppure
`$head->setHrefLinks($languageUrls)`. `setHrefLinks([])` disattiva gli alternate.
La presenza di un record SEO non certifica la pubblicazione di una traduzione.

Nei layout personalizzati rimane disponibile il partial, da includere dentro head:

```blade
@include('crud::seo.meta', ['model' => $product, 'locale' => $contentLocale])
```

Con UikitTemplate il partial usa lo stesso renderer del head. Senza gli helper
UikitTemplate mantiene il rendering autonomo dei metadati del trait.
Impostare separatamente `<html lang="{{ $contentLocale }}">`; il layout frontend
usa già la lingua di `FrontendContext`. Per liste usare `with('seo')` per evitare
query per ogni entità.

Personalizzare `getDefaultSeoTitle()`, `getDefaultSeoDescription()` e `getDefaultSeoUrl(?string $locale = null)` sul modello. Il titolo usa altrimenti title/name; descrizione e URL non vengono dedotti. Gli override Open Graph e Twitter ereditano titolo e descrizione SEO. I valori vuoti non vengono renderizzati. Usare URL assoluti pubblici per canonical e immagini.

Non viene effettuato fallback tra lingue diverse. Senza locale esplicito, il trait
usa l'attributo di richiesta `frontend.locale`, con fallback su `app()->getLocale()`.
`og:locale` viene emesso solo per locale con lingua e regione (es. it-IT), mentre
locale brevi come it restano validi per la selezione dei dati.

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

public function getSitemapChangeFrequency(): ?\IlBronza\CRUD\Enums\SitemapChangeFrequency
{
    return \IlBronza\CRUD\Enums\SitemapChangeFrequency::Weekly; // o null per omettere
}

public function getSitemapPriority(): ?float
{
    return 0.8; // 0.0–1.0, o null per omettere
}
```

`publishedLocales()`, `isPublished()` e `isPrivate()` sono esempi di logica dell'applicazione, da implementare secondo il dominio. Ogni URL deve essere assoluto HTTP(S), senza fragment. Usare gli stessi locale dei record SEO; gli underscore vengono convertiti in trattini nella normalizzazione del locale. `changefreq` e `priority` sono opzionali nel protocollo.

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

Registrare i provider in `config/crud.php` (array `driver` oppure classi `SitemapProvider`) e generare i file con Artisan:

```php
// config/crud.php
'sitemap' => [
    'url_resolver' => [App\Support\FrontendPage::class, 'url'], // opzionale per driver routes
    'providers' => [
        'static' => [
            'driver' => 'routes',
            'routes' => [
                'home' => ['changefreq' => 'daily', 'priority' => 1.0],
                'about' => ['changefreq' => 'weekly', 'priority' => 0.8],
            ],
        ],
        'products' => [
            'driver' => 'eloquent',
            'model' => App\Models\Product::class,
            'scopes' => ['visible'],
            'changefreq' => 'weekly',
            'priority' => 0.9,
            // opzionale: altre query eloquenti nello stesso urlset
            // 'extra' => [['model' => ..., 'scopes' => [...], 'where_has' => [...], 'url_model' => 'product', 'url_query' => ['variant' => 'slug']]],
        ],
    ],
],
```

I model usati dal driver `eloquent` devono esporre `getPublicUrl($locale, $query = [])` oppure `getDefaultSeoUrl($locale)`.

```bash
php artisan sitemap:generate
# oppure
php artisan sitemap:generate --path=/absolute/path/sitemap.xml
```

Con `group_by_locale` attivo (default) ogni provider viene risolto per locale e scritto come `sitemap-{locale}-{name}.xml`; `sitemap.xml` è l'index. Senza locale (lista vuota) viene scritto solo un index vuoto. Writer, `SitemapPage`, `PublicUrlSitemapItem` e i driver config sono nel package.

Di default la sitemap non emette `xhtml:link` hreflang: gli alternate appartengono all'`<head>` HTML. Per ripristinare gli alternate in XML impostare `crud.sitemap.include_hreflang_alternates` oppure passare `includeHreflangAlternates: true` a `SitemapGenerator`. `noindex`, `none` e canonical diversi dall'URL pubblico escludono comunque la traduzione dal documento. Il confronto canonical è esatto: gli URL devono avere una normalizzazione coerente. Le sorgenti senza `HasSeo` possono implementare lo stesso contratto senza metadati SEO.

Il generatore limita ogni documento a 50.000 URL e 50 MiB non compressi, esegue escaping XML e deduplica globalmente gli URL (vince la prima voce). La memoria mantiene il documento corrente e gli URL già incontrati. Con sorgenti vuote non vengono prodotti urlset figli.

Verifica autonoma: `php tests/Standalone/sitemap.php`.
