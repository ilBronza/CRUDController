# Filtri globali di consultazione

Un utente autenticato può selezionare ordini, tipi di contratto, operatori e clienti dalla navbar. La selezione vale sulle pagine e sulle richieste successive della stessa sessione web. Due schede condividono la selezione al caricamento successivo. Due sessioni dello stesso account possono usare selezioni diverse.

## Abilitazione

Installare le versioni coordinate di Crud e dei package utilizzati dal progetto. I provider di Products, Operators e Clients registrano ciascuno i default delle proprie risorse, disabilitate inizialmente. Crud funziona anche con il registro vuoto. Per attivare soltanto gli ordini, creare `config/resource-filters.php` nell'applicazione.

```php
<?php

return [
    'resources' => [
        'orders' => [
            'enabled' => true,
        ],
    ],
];
```

Il [file completo del Planner](examples/resource-filters/resource-filters.php) è copiabile nello stesso percorso e abilita le quattro risorse. In un progetto che usa la cache di configurazione, rigenerarla con `php artisan config:cache` dopo la modifica. La funzionalità usa la sessione Laravel e i pacchetti già impiegati per l'interfaccia. Non richiede migrazioni.

| Chiave di sessione | Package e modello | Helper | Scope di dominio |
| --- | --- | --- | --- |
| `orders` | Products · Order | OrderFilters | OrderFilterScope |
| `contracttypes` | Operators · Contracttype | ContracttypeFilters | ContracttypeFilterScope |
| `operators` | Operators · Operator | OperatorFilters | OperatorFilterScope |
| `clients` | Clients · Client | ClientFilters | ClientFilterScope |

I modelli sono nel namespace `IlBronza\<Package>\Models`. Helper e scope sono in `IlBronza\<Package>\ResourceFilters`. Tutti e quattro offrono inizialmente un unico criterio `ids`, una selezione multipla sulla chiave primaria. Le opzioni mostrano il nome restituito da `getName()`, con la chiave come ripiego. L'helper operatori precarica `user.userdata` per i nomi.

Ogni risorsa accetta `model`, `helper`, `scope`, `label`, `enabled` e, facoltativamente, `ability` per richiedere una capacità Laravel Gate. Il provider dell'entità integra i default sotto la singola chiave. Il progetto può quindi sostituire anche solo `helper` mantenendo le altre proprietà e risorse. La configurazione contiene nomi di classe e valori serializzabili. Crud risolve il modello effettivo con `gpc()` dove disponibile durante la richiesta. Le query sul modello del package e sulle sue sottoclassi riconoscono la risorsa configurata. Il nome in sessione rimane, per esempio, `orders`.

## Menu, header e operazioni dell'utente

Il default presenta una voce per risorsa nella navbar `default`. L'utente apre il form in un iframe con la lightbox di Buttons, modifica le selezioni e preme **Applica filtri**. Il salvataggio ricarica la pagina contenitore. Il numero sulla voce conta i criteri compilati per la risorsa. Il riepilogo richiudibile nell'header conta tutti i criteri e mostra etichette, valori, pulsanti di modifica e azzeramento per risorsa. La X accanto a **Filtri attivi** azzera tutte le risorse con un clic anche quando il riepilogo è chiuso. Anche il form offre le azioni di azzeramento applicabili.

- Per usare un'altra navbar, impostare `navbar` al suo nome. Se il progetto sostituisce `menu.serviceProviders`, includere il servizio esistente `CRUDMenuUtilities`.
- Con `navbar => false` l'header offre le voci di apertura già prima della prima selezione. Con `header_view => null` e navbar abilitata le operazioni restano disponibili dalla navbar e dal popup.
- Per cambiare il riepilogo impostare `header_view` a una view del progetto. Riceve `summaries`, `buttons`, `showResourceButtons` e `count`. UikitTemplate richiama i servizi elencati in `uikittemplate.headerServices` dopo l'header del progetto sulle pagine autenticate esterne all'iframe. Conservare `IlBronza\CRUD\ResourceFilters\ResourceFilters::class` nell'elenco per utilizzare questo aggancio.
- Per un diverso raggruppamento o un'apertura personalizzata, si possono estendere `ResourceFilters::menuButtons()` e `button()` in una sottoclasse registrata come servizio **scoped** nel container al posto di `ResourceFilters::class`. Il form è raggiungibile anche con `route('crud.resourceFilters.edit', $chiave)`. Menu usa il proprio caricamento dei servizi per navbar normale, progressiva e offcanvas. Crud prepara bottoni, form Form/FormField e riepilogo.

Le route usano il gruppo `web`, autenticazione e protezione CSRF sulle mutazioni. Il prefisso è `/crud/resource-filters`.

| Metodo e percorso | Operazione |
| --- | --- |
| GET `/{resource}` | Apre il form con i valori salvati. |
| PUT `/{resource}` | Sostituisce tutti i criteri della sola risorsa. |
| DELETE `/{resource}` | Azzera la risorsa. |
| DELETE sul prefisso | Azzera il namespace dei filtri. |

La risposta a una mutazione JSON valida è HTTP 204. Le mutazioni usano il blocco di sessione Laravel per serializzare gli aggiornamenti concorrenti dei filtri. Usare uno store con lock atomici condivisi tra i processi e sessioni lato server per offrire questa garanzia. Se nello stesso momento altre route modificano la medesima sessione, Laravel permette di estendere il blocco a tutte le richieste web con `session.block`.

## Definire i criteri di progetto

Crud risolve le definizioni una volta per risorsa e richiesta chiamando prima `getFilters()` e poi `transformFilters($filters)`. La stessa lista governa campi FormField, tipi, valori ammessi e criteri conservati. `buildForm()` riceve la lista trasformata. Definire le opzioni con un callable nell'helper permette di effettuare la query solo quando necessaria. Il callable riceve `null` per l'elenco dell'editor oppure la lista delle chiavi da verificare o descrivere. Restituisce una mappa chiave → etichetta.

### Sostituire tutti i criteri

Questo helper espone solo il nome esatto dell'ordine. Salvare la classe in `app/ResourceFilters/OrderNameFilters.php`.

```php
<?php

namespace App\ResourceFilters;

use IlBronza\Products\ResourceFilters\OrderFilters;

class OrderNameFilters extends OrderFilters
{
    public function getFilters(): array
    {
        return [
            'name' => [
                'type' => 'text',
                'label' => 'Nome esatto',
                'rules' => [
                    'max:120',
                ],
            ],
        ];
    }
}
```

### Aggiungere un criterio

Questo helper mantiene la selezione per ID e aggiunge la distinzione commesse/sottocommesse già usata dal Planner. Il valore vuoto offre entrambe, `0` seleziona le commesse, `1` le sottocommesse.

```php
<?php

namespace App\ResourceFilters;

use IlBronza\Products\ResourceFilters\OrderFilters;

class PlannerOrderFilters extends OrderFilters
{
    public function transformFilters(array $filters): array
    {
        $filters = parent::transformFilters($filters);
        $filters['only_suborders'] = [
            'type' => 'select',
            'label' => 'Tipo di commessa',
            'cast' => 'boolean',
            'options' => fn (?array $selected) => [
                0 => 'Commesse',
                1 => 'Sottocommesse',
            ],
        ];

        return $filters;
    }
}
```

### Rimuovere un criterio

Questa variante conserva `only_suborders` dall'esempio precedente e rimuove `ids`. Alla richiesta successiva Crud ripulisce anche gli ID eventualmente già salvati.

```php
<?php

namespace App\ResourceFilters;

class OrderKindFilters extends PlannerOrderFilters
{
    public function transformFilters(array $filters): array
    {
        $filters = parent::transformFilters($filters);
        unset($filters['ids']);

        return $filters;
    }
}
```

### Dare significato ai nuovi criteri nella query

Lo scope seguente supporta tutte e tre le varianti. Il metodo del padre tratta `ids`, quando presente. Il nome e il tipo di commessa hanno ciascuno una condizione esplicita. Il test di presenza di `only_suborders` include il valore booleano falso.

```php
<?php

namespace App\ResourceFilters;

use IlBronza\Products\ResourceFilters\OrderFilterScope;
use Illuminate\Database\Eloquent\Builder;

class PlannerOrderScope extends OrderFilterScope
{
    public function constrain(Builder $query, array $values): void
    {
        parent::constrain($query, $values);

        if (isset($values['name'])) {
            $query->where($query->qualifyColumn('name'), $values['name']);
        }

        if (array_key_exists('only_suborders', $values)) {
            $parent = $query->qualifyColumn('parent_id');
            if ($values['only_suborders']) {
                $query->whereNotNull($parent);
            } else {
                $query->whereNull($parent);
            }
        }
    }
}
```

Nell'elemento `resources.orders` della configurazione d'esempio, scegliere l'helper desiderato e lo scope associato.

```php
'orders' => [
    'enabled' => true,
    'helper' => \App\ResourceFilters\PlannerOrderFilters::class,
    'scope' => \App\ResourceFilters\PlannerOrderScope::class,
],
```

## Stato, visibilità e query

Il middleware avvia il contesto dopo l'apertura della sessione web e prima del binding dei modelli delle route. Il servizio scoped legge e salva soltanto valori scalari o liste in `crud.resource_filters.<risorsa>.<criterio>` e libera lo stato al termine della richiesta. Il boot del modello registra una sola volta lo scope Eloquent identificato da `SessionResourceScope::class`. I modelli che usano `CRUDModelTrait` lo ereditano. A un altro modello Eloquent si può aggiungere `IlBronza\CRUD\ResourceFilters\HasResourceFilters`.

- Ogni invio sostituisce la risorsa indicata. Campo omesso, `null`, stringa vuota e lista vuota rimuovono il criterio. I cast supportati sono `string` (default), `integer` e `boolean`. Zero e falso si conservano. Le liste vengono deduplicate e ordinate. Un errore di validazione conserva integralmente la selezione precedente.
- Le chiavi accettate sono quelle definite dagli helper abilitati per l'utente. Cambi di configurazione, regole, tipi o permessi di accesso alla risorsa portano alla pulizia dei criteri obsoleti alla richiesta successiva. Dopo un errore HTML il form riapre la selezione salvata e mostra gli errori. Le opzioni basate sui modelli sono verificate al salvataggio, mantenendo gli altri global scope, `userCanShow()` quando presente e la policy `view` quando registrata. Un progetto che limita la visibilità con condizioni aggiuntive del controller deve riportarle anche nell'override di `optionsQuery()`, a partire da `parent::optionsQuery()`.
- Un identificativo salvato che diventa invisibile rimane una restrizione fino alla modifica o al reset. Il riepilogo indica un elemento non disponibile. Le query applicative conservano i propri controlli di accesso. L'apertura del form offre tutte le alternative ancora visibili.
- Criteri diversi si compongono in AND. Più valori di `ids` ammettono qualunque ID nell'insieme. Crud raggruppa le condizioni di ogni scope per preservare i vincoli esterni anche quando il progetto introduce un OR.
- Sono consultazioni le richieste HTTP sicure, gli indici CRUD in POST e `crud.ajaxSearch`. Inserire ulteriori endpoint POST di sola lettura in `read_routes`. Usare `except_routes` per escludere route nominate, anche con pattern Laravel. Un dettaglio o un form di modifica in GET può rispondere 404 per un modello escluso. Le mutazioni ordinarie operano con gli scope e i permessi applicativi consueti. Una GET applicativa che esegue manutenzione richiede esclusione esplicita o conversione a una mutazione.
- Richieste senza sessione web autenticata, console e worker partono neutri. Durante job sincroni e comandi Artisan lanciati dentro la richiesta, gli eventi di Laravel sospendono il contesto fino al ritorno. Un import eseguito direttamente dentro una GET senza questi meccanismi deve utilizzare una route esclusa. Per codice di progetto `app(ResourceFilters::class)->active()` restituisce i criteri effettivi della consultazione.

Per costruire le opzioni sulla risorsa stessa, l'helper usa l'esclusione mirata seguente. Gli altri global scope del modello continuano ad applicarsi, incluse cancellazione logica, archiviazione e restrizioni di visibilità definite dal progetto. Questo esempio restituisce un builder, al quale applicare anche le verifiche di visibilità delle opzioni quando necessarie.

```php
use IlBronza\CRUD\ResourceFilters\SessionResourceScope;
use IlBronza\Products\Models\Order;

$query = Order::gpc()::query()->withoutGlobalScope(SessionResourceScope::class);
```

## Timeline del Planner

Uno scope su Client limita `Client::query()` e il caricamento della relazione `order.client`. L'ordine già selezionato può allora avere una relazione vuota. Se l'unità da mostrare è una riga, occorre selezionare esplicitamente le righe che hanno un ordine, cliente o incarico ammesso.

I controller per ordini e per tipologie in Operators usano l'adattatore `OperatorTimelineRows::constrain()` sul builder delle righe. Per il progetto si usa il modello locale, che mantiene etichette e tooltip delle barre.

```php
use App\Models\ProjectSpecific\Orderrows\OperatorOrderrow;
use IlBronza\Operators\ResourceFilters\OperatorTimelineRows;

$rows = OperatorTimelineRows::constrain(OperatorOrderrow::query())
    ->with([
        'order.client',
        'sellable.target',
        'sellableSupplier.supplier.target',
    ])
    ->get();
```

Con un filtro clienti l'adattatore richiede `order.client`, con il solo filtro ordini richiede `order`. Per operatori e tipi di contratto attraversa `sellableSupplier.supplier`, seleziona il target polimorfo `OperatorContracttype::gpc()` e verifica le relazioni `operator` e/o `contracttype`. I relativi scope restringono le sottoquery. Quando la consultazione è priva di filtri, l'adattatore lascia intatta la query. La timeline ordini costruisce i gruppi dagli item sopravvissuti. Quella per tipologie passa anche `requireAssignmentGroup: true`. In una consultazione filtrata richiede sia operatore sia contratto sulla riga, come nella costruzione dei sottogruppi. Può mantenere gruppi senza item.

Il Planner sovrascrive entrambi i metodi che caricano gli item. La [patch applicativa](examples/resource-filters/planner.patch) adatta quei due metodi preservando la distinzione commesse/sottocommesse. Rimuove anche il `delete()` dal getter del nome dell'incarico e usa il getter ereditato che tollera una relazione vuota. Per le etichette degli ordini usa la relazione cliente precaricata al posto della mappa statica. Applicare questi interventi **prima** di abilitare i filtri nel Planner.

Dalla radice del Planner, con il checkout locale dei package nella posizione indicata, i comandi seguenti controllano e applicano l'esempio.

```bash
git apply --check /Volumes/Lavoro/pacchetti/Crud/docs/examples/resource-filters/planner.patch
git apply /Volumes/Lavoro/pacchetti/Crud/docs/examples/resource-filters/planner.patch
cp /Volumes/Lavoro/pacchetti/Crud/docs/examples/resource-filters/resource-filters.php config/resource-filters.php
```

Il controllo di applicabilità è stato eseguito contro il checkout locale. L'applicazione della patch e la prova autenticata delle due timeline con i dati del Planner restano a carico del progetto.

## Cache e limiti d'integrazione

Crud calcola un'impronta dai filtri normalizzati, utente, sessione, lingua, configurazione delle risorse, abilitazioni effettive e contesto di lettura. Le chiavi preesistenti mantengono i propri elementi di pagina, tabella e autorizzazione. A parità di contesto un riordino della medesima selezione produce la stessa impronta.

- Gli indici CRUD confrontano il contesto prima del riutilizzo dello slot invalidabile dell'indice. Il percorso che recupera solo gli ID passa da Eloquent quando la funzione è abilitata. I dati precaricati delle DataTable hanno una chiave coerente con lo stesso contesto.
- Le chiavi dei consueti helper di cache del modello CRUD incorporano il contesto. Le raccolte globali degli indici client e di tutti gli ordini vengono invece escluse dal percorso filtrato perché usavano una sola voce condivisa.
- Menu usa il contesto nelle cache HTML di menu e navbar e nella chiave browser del caricamento progressivo. Il reset torna a un contesto valido senza svuotare la cache applicativa.
- I due percorsi timeline esaminati caricano le righe per richiesta. Lo stato temporale del widget nel browser riguarda la finestra di date. La ricerca dell’azienda proprietaria in Clients esclude lo scope di consultazione, perché crea l’azienda se assente. L'accesso a `sellable_class` in Products evita la persistenza del valore derivato da una relazione soggetta ai filtri.
- SQL diretto, `DB::table()`, query su modelli privi del trait e cache personalizzate dell'applicazione richiedono un adattamento quando restituiscono risorse filtrabili. Per una cache di consultazione aggiungere `ResourceFilters::currentCacheContext()` alle componenti di visibilità già usate, o evitare il riuso su quel percorso. I filtri di colonna delle singole DataTable mantengono il loro significato locale.

## Verifiche

`Crud/tests/Feature/ResourceFiltersTest.php` verifica il percorso HTTP, il binding, autorizzazione e CSRF, valori falsi e zero, due sessioni dello stesso utente, visibilità, override, reset, job sincrono, cache di indice e menu e composizione dell'header. `Operators/tests/Feature/ResourceFilterTimelineTest.php` usa modelli dei package e relazioni polimorfe reali su SQLite, confronta righe e gruppi, verifica il merge di un singolo override e controlla che la consultazione non scriva un dato derivato né duplichi l’azienda proprietaria.

I test mirati si eseguono dalla radice dei package.

```bash
python3 .ibtest/bin/ibtest.py Crud --filter ResourceFiltersTest
python3 .ibtest/bin/ibtest.py Operators --filter ResourceFilterTimelineTest
```

Verifica del 13 novembre 2026 con il runner del workspace. I 9 test mirati passano con 139 asserzioni. Nelle suite complete, Operators (16), Menu (51) e UikitTemplate (72) completano tutti i test. Crud (226) conserva sei fallimenti preesistenti e quattro skip legati a `DESCRIBE` su SQLite. Clients (30) conserva otto errori nelle fixture media e categorie. Products (23) conserva due errori nei test allergeni. Datatables (132) segnala un errore del binding `translator`, riprodotto anche con la classe e il test precedenti alla modifica. Il runner segnala anche deprecazioni.

La verifica visiva in Chrome a 1440 e 390 px ha usato dati di prova isolati con il layout e la lightbox reali, incluso il menu progressivo desktop. Prima dell'adozione nel Planner verificare anche utenti con i ruoli del progetto, viste commesse/sottocommesse, gruppi e tooltip sulle due timeline autenticate e gli eventuali endpoint di lettura personalizzati.
