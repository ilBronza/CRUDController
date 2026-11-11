# Accessi simultanei alla stessa pagina

Il controllo è opzionale e si applica ai controller CRUD quando `crud.useConcurrentRequestsAlert` è attivo. La prima scheda che invia un heartbeat ottiene la precedenza. Il server verifica la presenza prima delle richieste POST, PUT, PATCH e DELETE protette dal middleware `CRUDConcurrentUrlAlert`.

## Attivazione e schede dello stesso utente

```dotenv
CRUD_USE_CONCURRENT_REQUESTS_ALERT=true
CRUD_CONCURRENT_URI_CHECK_SAME_USER_TABS=true
```

`concurrentUriCheckSameUserTabs` è `true` per default: due schede dello stesso utente sulla stessa pagina vanno in conflitto; soltanto quella che ha acquisito la precedenza può operare. Le altre possono continuare a leggere e compilare il form, ma non inviare operazioni protette.

Con `CRUD_CONCURRENT_URI_CHECK_SAME_USER_TABS=false`, più schede dello stesso utente possono operare. Ciascuna mantiene una presenza indipendente: chiuderne una non libera le altre. Il conflitto fra utenti differenti rimane attivo in entrambi i casi.

L'intera funzionalità rimane disattivata per default. Se l'applicazione usa una copia pubblicata di `config/crud.php`, aggiornare anche quella configurazione; ricostruire la cache della configurazione dopo le modifiche.

## Parametri

| Configurazione `crud.*` | Variabile `.env` | Default |
| --- | --- | --- |
| `useConcurrentRequestsAlert` | `CRUD_USE_CONCURRENT_REQUESTS_ALERT` | `false` |
| `concurrentUriCheckSameUserTabs` | `CRUD_CONCURRENT_URI_CHECK_SAME_USER_TABS` | `true` |
| `concurrentUriJavascriptTickInterval` | `CRUD_CONCURRENT_URI_JAVASCRIPT_TICK_INTERVAL` | `3000` millisecondi |
| `concurrentUriCacheLifetime` | `CRUD_CONCURRENT_URI_CACHE_LIFETIME` | `10` secondi |
| `concurrentUriOperationLockLifetime` | `CRUD_CONCURRENT_URI_OPERATION_LOCK_LIFETIME` | `120` secondi |
| `concurrentUriCacheStore` | `CRUD_CONCURRENT_URI_CACHE_STORE` | store cache predefinito |
| `concurrentUriPrefix` | `CRUD_CONCURRENT_URI_PREFIX` | `concurrentUri` |
| `concurrentUriScope` | `CRUD_CONCURRENT_URI_SCOPE` | stringa vuota |
| `concurrentUriClientExcludedPaths` | — | `[]` |

Intervallo, durata della presenza e durata del lock devono essere interi positivi. La presenza deve durare almeno tre intervalli di heartbeat. La variabile della durata cache non modifica più l'intervallo JavaScript.

Usare una cache persistente e condivisa fra i processi dell'applicazione, con supporto ai lock atomici. In un'installazione su più server anche la cache e i lock devono essere condivisi fra i server; una cache `file` locale non basta. Gli store `array` sono adatti ai test, non al coordinamento fra processi in produzione. Lo store `null` non è utilizzabile.

Il lock dell'operazione resta acquisito durante l'esecuzione del controller. `concurrentUriOperationLockLifetime` deve superare il timeout massimo delle operazioni sincrone dell'applicazione. I job in coda e il lavoro eseguito dopo la risposta richiedono un coordinamento separato. Un processo interrotto libera il lock alla scadenza configurata.

## Identità della pagina e limiti

La chiave include schema, host, porta, `concurrentUriScope` e il percorso restituito da `request()->path()`, senza query string. Un hash preserva le differenze fra percorsi, ad esempio `a/b` e `a-b`. Per tenant che condividono host e cache, impostare lo scope in un middleware precedente al controllo CRUD e mantenerlo uguale sulle route heartbeat e sulle operazioni.

La presenza è associata alla pagina di provenienza dell'operazione. URL diversi che modificano lo stesso record, ad esempio una lista e un form di modifica, hanno presenze separate. Questo controllo non confronta la versione del record e non rileva modifiche fatte dopo il caricamento dei dati. Dopo un subentro l'avviso invita ad aggiornare la pagina, conservando prima gli inserimenti non salvati. Il controllo di versione al salvataggio è un intervento ulteriore.

## Integrazione delle viste e delle richieste

Aggiornare insieme Crud e UikitTemplate. Le due viste `uikittemplate::utilities.concurrentUri.headerbar` e `scripts` delegano ora alle viste omonime di Crud. Aggiornare eventuali override pubblicati dell'applicazione; eliminare i vecchi script che copiavano la `pageKey` del proprietario. La nuova chiave viene generata nel browser per ogni scheda principale, comprese le schede duplicate, e cambia al ripristino dalla cache di navigazione. Gli iframe dello stesso origin ereditano il contesto della pagina madre, anche quando sono annidati.

Per un layout personalizzato includere:

```blade
@include('crud::utilities.concurrentUri.headerbar')
{{-- Contenuto della pagina --}}
@include('crud::utilities.concurrentUri.scripts')
```

Il layout deve fornire il meta tag `csrf-token`. Il middleware prepara il contesto anche per le pagine HTML restituite da un POST autorizzato e rende le risposte non memorizzabili in cache. Il client deve essere incluso prima delle richieste AJAX della pagina. Il JavaScript integra form HTML, `fetch` e `XMLHttpRequest` (quindi anche jQuery e Dropzone) sulle richieste mutative allo stesso origin. Le integrazioni che catturano questi trasporti prima del caricamento dello script, i worker e gli altri client personalizzati devono trasmettere esplicitamente il contesto.

Gli iframe ereditano dinamicamente credenziali e precedenza dalla madre: non generano una presenza indipendente e non inviano heartbeat o richieste di rilascio proprie. Chiuderli non libera la precedenza della scheda principale. Scadenza, perdita della precedenza e cambio di identità della madre si riflettono anche nei figli. Gli iframe di un altro origin non possono ereditare il contesto. Le chiamate POST delle DataTables possono restare invariate. Anche gli invii nativi `form.submit()` ricevono il contesto prima della partenza.

`concurrentUriClientExcludedPaths` permette all'applicazione di elencare percorsi esatti esterni al CRUD, come logout o preferenze di sessione: il client non blocca queste richieste e non aggiunge credenziali CRUD. Questa opzione non esclude il controllo del middleware sul server.

Gli header sono `X-CRUD-Concurrent-Token` e `X-CRUD-Page-Key`; nei form equivalgono a `_crud_concurrent_token` e `_crud_page_key`. `window.ibConcurrentUri.headers()` restituisce gli header della scheda; `isOwner()` indica la presenza locale valida e `refresh()` richiede un nuovo controllo. Le route personalizzate devono applicare `CRUDConcurrentUrlAlert` se devono imporre lo stesso vincolo. Il contesto è cifrato e autenticato, vincolato all'utente e allo scope della pagina. Non sostituisce autorizzazioni e CSRF già previsti dall'applicazione.

`POST concurrent-uri/tick` e `POST concurrent-uri/leave-page` richiedono autenticazione e i campi `url`, `pageKey` e `token`. Il token proviene dal contesto del layout. Gli stati dell'heartbeat sono `gained`, `kept`, `morePages` e `notAllowed`. La risposta non espone la chiave della scheda proprietaria; `leave-page` restituisce JSON con `released: true` anche se la presenza è già assente.

Una modifica senza precedenza o con presenza scaduta riceve HTTP 409; un lock già occupato restituisce 423. Contesto mancante o non valido produce 422 o 403. Le operazioni non acquisiscono automaticamente una presenza scaduta: serve prima un heartbeat valido. La perdita di rete sospende lo stato operativo locale e il client riprova senza sovrapporre richieste. Alla chiusura usa `pagehide` e un beacon con CSRF; se l'invio non arriva, vale la scadenza della presenza. Cancellare una richiesta di uscita dalla pagina non rilascia la precedenza.

## Verifica

Dal contenitore dei pacchetti:

```sh
python3 .ibtest/bin/ibtest.py Crud --filter ConcurrentUriTest
```

La suite copre utenti distinti, entrambe le impostazioni per le schede dello stesso utente, rinnovo, rilascio, scadenza, input e contesto, blocco delle operazioni e contesa fra istanze di cache indipendenti.

I test del client usano Playwright e un browser reale, con risposte HTTP controllate:

```sh
node --test tests/Browser/concurrent-uri.test.cjs
```

Playwright deve essere disponibile come modulo oppure tramite `PLAYWRIGHT_MODULE_PATH`; `PLAYWRIGHT_CHANNEL=chrome` permette di usare Chrome installato. Senza Playwright questi test sono segnalati come esclusi. I test del browser verificano trasmissione delle credenziali, identità delle schede, avvisi, rete, invio dei form e ripristino dalla cache di navigazione; i test PHP verificano il servizio e le route Laravel.
