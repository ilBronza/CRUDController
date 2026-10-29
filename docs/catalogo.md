# Catalogo filtrabile

Il catalogo mostra una query Eloquent come griglia di schede, con filtri laterali,
ricerca, ordinamento, conteggio e paginazione sul database. Non registra route:
l'applicazione decide URL, middleware, policy e perimetro consultabile.

## Contratti e responsabilità

- `CatalogItemInterface`: il modello restituisce un `CatalogItem` con chiave stabile,
  titolo, descrizione, immagine, URL e badge. Nessun nome di colonna è implicito.
- `CatalogDefinitionInterface`: definisce query, filtri, ordinamenti, ordinamento
  predefinito, dimensione pagina, view della scheda e relazioni da precaricare.
- `CatalogParametersFile`: implementazione base della definizione. Converte gli
  array di `getFiltersParameters()` in oggetti filtro; si può anche sovrascrivere
  `filters()` restituendo direttamente istanze di `CatalogFilterInterface`.
- `CatalogFilterInterface`: separa controllo, normalizzazione/validazione,
  predicati SQL e selezioni attive.
- `CatalogOptionsProviderInterface`: ricava le opzioni dalla query di base ricevuta.

La definizione appartiene al singolo catalogo. Lo stesso modello può avere più
cataloghi con perimetri, filtri e schede diversi. Creare definizioni e filtri per
richiesta; non registrarli come singleton con stato condiviso tra utenti.

## Esempio completo: documenti

L'esempio presuppone una tabella `documents` con `id`, `tenant_id`, `title`,
`reference`, `summary`, `status`, `amount` e `approved`.

```php
namespace App\Models;

use IlBronza\CRUD\Catalog\CatalogItem;
use IlBronza\CRUD\Interfaces\CatalogItemInterface;
use Illuminate\Database\Eloquent\Model;

class Document extends Model implements CatalogItemInterface
{
    public function toCatalogItem(): CatalogItem
    {
        return new CatalogItem(
            key: $this->getKey(),
            title: $this->title ?? $this->reference ?? '',
            description: $this->summary,
            badges: [$this->status],
        );
    }
}
```

La chiave identifica stabilmente l'elemento. `image` e `url` sono URL opzionali;
la scheda standard accetta HTTP(S), percorsi relativi alla radice e anchor. Le
stringhe vengono sottoposte a escaping da Blade; i badge sono un elenco di stringhe.
Titolo vuoto e dati opzionali mancanti hanno una rappresentazione standard.

```php
namespace App\Providers\Documents;

use App\Models\Document;
use IlBronza\CRUD\Providers\CatalogParametersFile;
use Illuminate\Database\Eloquent\Builder;

class DocumentCatalogParameters extends CatalogParametersFile
{
    public function __construct(private int $tenantId) {}

    public function query(): Builder
    {
        return Document::query()->where('tenant_id', $this->tenantId);
    }

    public function getFiltersParameters(): array
    {
        return [
            'search' => [
                'type' => 'text', 'label' => 'Cerca',
                'columns' => ['title', 'reference'], 'maxLength' => 200,
            ],
            'status' => [
                'type' => 'multiple', 'label' => 'Stato', 'column' => 'status',
                'options' => ['draft' => 'Bozza', 'sent' => 'Inviato'],
            ],
            'amount' => [
                'type' => 'number-range', 'label' => 'Importo',
                'column' => 'amount', 'min' => 0, 'step' => '0.01',
            ],
            'approved' => [
                'type' => 'boolean', 'label' => 'Approvato', 'column' => 'approved',
            ],
        ];
    }

    public function sorts(): array
    {
        return [
            'title' => ['label' => 'Titolo', 'column' => 'title'],
            'amount_desc' => ['label' => 'Importo decrescente', 'column' => 'amount', 'direction' => 'desc'],
        ];
    }

    public function defaultSort(): string { return 'title'; }
    public function perPage(): int { return 24; }
    public function with(): array { return []; }
}
```

Il controller dell'applicazione costruisce il catalogo dopo le proprie verifiche
di autorizzazione. Nell'esempio esiste una policy `DocumentPolicy::viewAny` e
l'utente autenticato contiene il tenant autorizzato.

```php
namespace App\Http\Controllers;

use App\Models\Document;
use App\Providers\Documents\DocumentCatalogParameters;
use IlBronza\CRUD\Catalog\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DocumentCatalogController extends Controller
{
    public function __invoke(Request $request)
    {
        Gate::authorize('viewAny', Document::class);

        $catalog = Catalog::make(
            new DocumentCatalogParameters((int) $request->user()->tenant_id),
            $request,
        );

        return view('documents.catalog', compact('catalog'));
    }
}
```

```php
// routes/web.php dell'applicazione
use App\Http\Controllers\DocumentCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/documents/catalog', DocumentCatalogController::class)
    ->middleware('auth')->name('documents.catalog');
// Dichiarare questa route prima di eventuali /documents/{document} non vincolate.
```

```blade
{{-- resources/views/documents/catalog.blade.php, dentro il layout UIkit del progetto --}}
<x-crud::catalog :catalog="$catalog" />
```

Il componente include una sola volta stili e script, come gli altri comportamenti
Blade del package. Non richiede una compilazione frontend aggiuntiva. Il layout
ospite carica UIkit. I controlli sono HTML GET con classi UIkit: non istanziano i
form CRUD di modifica, che hanno stato del modello, old input e comportamenti di
salvataggio differenti. Un controllo FormField del progetto può essere integrato
tramite una view del filtro, conservando nomi degli input e stato del catalogo.

## Parametri e semantica dei filtri

La chiave dell'array è il nome pubblico nell'URL, separato dalla colonna. Dichiarare
un solo target fra `column`, `columns` e `scope`. Il tipo può essere un alias
standard o il nome completo di una classe che implementa `CatalogFilterInterface`.
Classi, colonne, scope, ordinamenti e view provengono esclusivamente dal codice di
configurazione, mai dai valori della richiesta.

| Tipo | Valore nell'URL | Comportamento |
| --- | --- | --- |
| `text` | `filters[search]=report` | Ricerca LIKE su `columns` (oppure `column`), OR tra colonne; `%`, `_`, `!` sono letterali |
| `multiple` | `filters[status][]=draft` | OR tra valori, deduplicazione e verifica delle opzioni ammesse |
| `number-range` | `filters[amount][min]=0&filters[amount][max]=100` | Estremi inclusivi, anche uno solo; minimo ≤ massimo |
| `boolean` | `filters[approved]=0` oppure `1` | Tutti / sì / no; `0` resta un valore attivo |

Filtri diversi si combinano in AND. `null`, stringa vuota e selezione vuota non
filtrano. La ricerca viene ripulita dagli spazi iniziali/finali. Il confronto di
maiuscole e accenti dipende dal database e dalla sua collation.

Limiti standard: testo di 200 caratteri (`maxLength`), 100 selezioni
(`maxSelections`), pagina tra 1 e 1.000.000, dimensione pagina configurata tra 1 e
1.000. Non si può scegliere liberamente la dimensione pagina tramite URL. `min`
e `max` del filtro numerico sono validati anche sul server; `step` è un suggerimento
per il controllo HTML. Gli input non validi producono HTTP 422, JSON nelle richieste
AJAX e una pagina con errori nelle richieste HTML, senza redirect ciclici.

### Accordion dei filtri laterali

Ogni filtro ha un accordion indipendente. Il parametro `accordion` ne configura
lo stato iniziale:

| Valore | Comportamento |
| --- | --- |
| `'open'` (predefinito) | Parzialmente aperto: l'intero contenuto del `details` ha un'altezza massima di 18rem e scorre |
| `'full-open'` | Aperto completamente, con tutti i campi e le opzioni visibili senza limite di altezza |
| `'closed'` | Chiuso, con il solo titolo visibile |
| `false` | Controllo senza accordion |

```php
'status' => [
    'type' => 'multiple', 'label' => 'Stato', 'column' => 'status',
    'options' => ['draft' => 'Bozza', 'sent' => 'Inviato'],
    'accordion' => 'full-open',
],
```

Il limite iniziale si applica al corpo dell'accordion, anche quando una view
personalizzata contiene più gruppi di campi. Si può personalizzare con la variabile
CSS `--crud-catalog-filter-height`. «Mostra tutto» espande l'intero contenuto;
il titolo apre e chiude il filtro. Dopo l'interazione con questi comandi il filtro
alterna solo apertura completa e chiusura; non torna all'apertura parziale.
In apertura completa è disponibile anche «Chiudi filtro» in fondo al contenuto.
La chiusura non cancella le selezioni. Stato e posizione
di scorrimento restano invariati durante filtri, ordinamento, paginazione e
navigazione avanti/indietro via AJAX. Un caricamento completo riparte dallo stato
configurato. Senza JavaScript l'apertura e la chiusura funzionano con `<details>`
e rispettano l'altezza iniziale configurata.

Anche i controlli personalizzati possono restituire `accordion` da `control()`.
La view riceve `$inAccordion`: può usarlo per nascondere visivamente un'etichetta
duplicata, mantenendola associata all'input. Le liste con classe
`crud-catalog-options` dentro l'accordion non hanno limiti propri: scorre il corpo
del filtro. Le view personalizzate devono evitare limiti di altezza inline sulle
liste interne, che impedirebbero di mostrarle completamente.

## Scope e filtri personalizzati

Uno scope locale riceve **un solo argomento**, il valore normalizzato: stringa,
array di stringhe, array con `min`/`max`, oppure booleano. Non deve sostituire o
eseguire la query. Le scelte multiple devono rispettare la semantica OR.

```php
// Nel modello con relazione tags() già definita:
public function scopeWithAnyTags(Builder $query, array $ids): void
{
    $query->whereHas('tags', fn (Builder $tags) => $tags->whereKey($ids));
}

// Nel parameters file:
'tags' => [
    'type' => 'multiple', 'label' => 'Etichette',
    'scope' => 'withAnyTags', 'options' => TagCatalogOptions::class,
],
```

`TagCatalogOptions` va implementato nell'applicazione per ottenere etichette
collegate agli elementi consultabili. Il motore verifica che `scope` identifichi
uno scope locale Eloquent: nomi di metodi del builder come `delete` sono rifiutati.

Per un comportamento riutilizzabile si può estendere un filtro standard:

```php
use IlBronza\CRUD\Catalog\Filters\BooleanFilter;
use Illuminate\Database\Eloquent\Builder;

class HasAttachmentsFilter extends BooleanFilter
{
    protected function applyValue(Builder $query, mixed $value): void
    {
        if ($value) $query->whereHas('attachments');
        else $query->whereDoesntHave('attachments');
    }
}

// Nel parameters file, su un modello che espone attachments():
'attachments' => ['type' => HasAttachmentsFilter::class, 'label' => 'Con allegati'],
```

Le classi create dall'array ricevono `__construct(string $name, array $parameters)`.
Per dipendenze ulteriori si possono passare istanze costruite dall'applicazione.
Per controlli e valori nuovi, implementare direttamente `CatalogFilterInterface`:
`control()` restituisce almeno `view`, `key`, `label`; `normalize()` restituisce
`null` se inattivo oppure solleva `ValidationException`; `apply()` aggiunge
predicati; `selections()` restituisce etichette e, per singoli valori multipli,
la chiave `value` da rimuovere.

Ogni filtro viene applicato in un gruppo WHERE separato sulla query originale:
gli OR restano nel proprio gruppo. Le estensioni devono aggiungere predicati
(anche `whereHas`), senza eseguire query di scrittura, sostituire il builder o
rimuovere il perimetro autorizzato. Join, select, ordinamenti e eager loading
vanno definiti nella query iniziale / definizione, non nel gruppo del filtro.

## Opzioni dinamiche e perimetro

`options` può essere una mappa `valore => etichetta`, un provider, oppure il nome
completo di un provider risolto dal container Laravel.

```php
use IlBronza\CRUD\Interfaces\CatalogOptionsProviderInterface;
use Illuminate\Database\Eloquent\Builder;

class DocumentStatusOptions implements CatalogOptionsProviderInterface
{
    public function options(Builder $baseQuery): array
    {
        $labels = ['draft' => 'Bozza', 'sent' => 'Inviato'];
        return $baseQuery->reorder()->distinct()->pluck('status')
            ->mapWithKeys(fn ($status) => [$status => $labels[$status] ?? $status])->all();
    }
}
```

Il provider riceve una copia della query di base, **prima dei filtri selezionati**:
le opzioni rimangono stabili mentre si filtra. Deve conservarne i vincoli; non deve
iniziare una query globale sulle entità correlate. Le mappe statiche sono adatte
a enum pubblici; dati dipendenti da tenant/permessi richiedono il provider. Non
sono previsti conteggi delle singole opzioni nella prima versione.

Perimetro e autorizzazioni obbligatorie vanno in `query()`, ricostruiti a ogni
richiesta dal contesto autorizzato. Usare parametri di route, utente o dipendenze
esplicite. Se un contesto autorizzato deve viaggiare nella query string, dichiararlo
nel terzo argomento di `make()`:

```php
$catalog = Catalog::make($definition, $request, ['project' => $authorizedProject->getKey()]);
```

Questi parametri scalari restano nel form, nei link, nell'azzeramento e nella
paginazione. `filters`, `sort` e `page` sono riservati. L'applicazione valida e
autorizza il contesto a ogni richiesta; un campo hidden non è un'autorizzazione.
Parametri extra non dichiarati non vengono propagati dal catalogo.

## Scheda personalizzata e stile

Sovrascrivere `cardView()` nella definizione:

```php
public function cardView(): string { return 'documents.catalog-card'; }
public function with(): array { return ['owner']; } // se toCatalogItem() usa owner
```

```blade
{{-- resources/views/documents/catalog-card.blade.php --}}
<article class="uk-card uk-card-default uk-card-body" data-catalog-item="{{ $item->key }}">
    <h3>{{ $item->title }}</h3>
    @if($item->description)<p>{{ $item->description }}</p>@endif
    @foreach($item->badges as $badge)<span class="uk-label">{{ $badge }}</span>@endforeach
</article>
```

La view riceve `$item` (`CatalogItem`) e `$catalog`; il modello non restituisce
nomi di view. Solo la pagina corrente viene convertita in DTO. Il rendering della
scheda è condiviso da documenti, clienti e altri domini. I test includono due
cataloghi separati: `DocumentCatalogParameters` e `CustomerCatalogParameters`.

Anche ogni filtro può dichiarare `view` per sostituire il controllo; la view riceve
`$control`, `$value` e `$inputId`. Mantenere nomi `filters[chiave]` compatibili con
il normalizzatore. Classi CSS e variabili `--crud-catalog-sidebar-width` e
`--crud-catalog-gap` consentono personalizzazione senza cambiare i template.

## Navigazione e limiti della prima versione

- Un modello Eloquent e una query di base per catalogo; nessuna unione tra tipi
  diversi. La paginazione esegue conteggio e lettura della pagina sul database:
  “una query” indica una definizione di insieme, non un solo round trip SQL.
- Ogni ordinamento aggiunge la chiave primaria come spareggio. La query di base
  deve restituire una riga per modello; usare `whereHas` per filtrare relazioni
  senza duplicazioni. Query aggregate e join che duplicano modelli richiedono una
  definizione appropriata nell'applicazione.
- Un catalogo per pagina: `filters`, `sort`, `page` condividono lo stato nell'URL.
- GET e paginazione funzionano senza JavaScript. Il form non contiene la pagina,
  quindi inviare filtri o ordinamento riparte dalla prima. Senza JavaScript,
  scegliere l'ordinamento e premere “Applica filtri”.
- Con JavaScript, checkbox e select aggiornano subito; ricerca e intervalli si
  applicano con Invio o “Applica filtri”. Fetch sostituisce il componente estratto
  dalla risposta HTML e aggiorna la cronologia. Indietro/Avanti ripristinano lo
  stato. Annullamento delle richieste e numero di sequenza impediscono risposte
  obsolete; anche modificare un input annulla una richiesta ancora in corso.
- “Azzera filtri” mantiene ordinamento e contesto, rimuove filtri e pagina. Ogni
  valore multiplo attivo è rimovibile separatamente.
- Sotto 960px i filtri diventano un pannello espandibile con stato ARIA e chiusura
  tramite Escape. Senza JavaScript rimangono visibili e utilizzabili.
- Gli errori di caricamento lasciano visibili risultati e controlli correnti,
  mostrando un messaggio. Le schede personalizzate aggiunte via AJAX devono usare
  listener delegati; gli script inclusi nella risposta non vengono rieseguiti.
- L'integrazione non modifica index, datatable o route CRUD esistenti.

## Verifica

```bash
# Dalla cartella pacchetti, nell'ambiente previsto da AGENTS.md:
python3 .ibtest/bin/ibtest.py Crud --filter CatalogTest
python3 .ibtest/bin/ibtest.py Crud

# Test JavaScript senza dipendenze aggiuntive:
node --test Crud/tests/Browser/catalog.test.cjs
```
