# Fix: relazioni BelongsToMany con pivot in soft delete

Brief di implementazione. Contiene tutto il contesto necessario: non serve
conoscere la conversazione da cui nasce.

## Obiettivo

Rendere corrette le riassociazioni sulle relazioni `belongsToMany` la cui
tabella pivot usa il soft delete. Oggi falliscono in silenzio: il form dichiara
di aver salvato, nessuna eccezione, nessuna query di scrittura, la riga resta
cancellata.

## Il bug, nel dettaglio

Leggere questa sezione per intero prima di toccare il codice. Senza il
meccanismo esatto, la tentazione di "semplificare" tornando a `sync()` è forte
e reintroduce il difetto.

Una relazione con pivot soft-deleting si scrive tipicamente così:

```php
public function boards()
{
	return $this->belongsToMany(Board::class, 'board_drives')
		->whereNull('board_drives.deleted_at')
		->using(BoardDrive::class);
}
```

Quel `whereNull()` è un vincolo sulla **query Eloquent** della relazione. Non
viene registrato in `$pivotWheres`, `$pivotWhereIns` o `$pivotWhereNulls`, che
si popolano solo tramite `wherePivot()`, `wherePivotIn()`, `wherePivotNull()`.

`BelongsToMany::sync()` non usa la query Eloquent. Legge i pivot esistenti con
`getCurrentlyAttachedPivots()` → `newPivotQuery()`, che è un query builder
grezzo sulla tabella pivot vincolato al solo parent più gli eventuali
`wherePivot*`. Quindi **vede anche le righe soft-deleted**.

Conseguenza, riassociando un elemento precedentemente scollegato:

| passo di `sync()` | esito |
|---|---|
| `$current` da `newPivotQuery()` | contiene la riga cancellata |
| `array_diff($current, $ids)` | vuoto, nessun detach |
| `attachNew()`: `in_array($id, $current)` | `true`, **niente attach** |
| ramo `elseif`: `count($attributes) > 0` | `false`, niente `updateExistingPivot` |

Zero scritture, zero eccezioni. In lettura invece il `whereNull()` si applica,
quindi l'elemento resta invisibile.

## Portata

`CrudModelStoringHelper::relateBelongsToManyElements()` prova già a gestire i
pivot soft-deleting (righe ~157-177), ma copre solo la rimozione e poi cade
comunque su `sync()`, quindi la riassociazione resta rotta.

In più il rilevamento usa `class_uses()`, che **non è ricorsivo**.
`IlBronza\CRUD\Models\BasePivotModel` dichiara `use SoftDeletes`, ma
`class_uses()` su una sua sottoclasse restituisce solo i trait propri. Verifica
sui pivot esistenti nei pacchetti:

| classe | rilevata da `class_uses()` |
|---|---|
| `Operators\OperatorContracttype` | no |
| `Operators\ClientOperator` | no |
| `Products\SellableSupplier` | no |
| `Products\ProductRelation` | no |
| `courses\CoursesPackageBasePivotModel` | no |
| `Products\AccessoryProduct` | sì, ridichiara `use SoftDeletes` |
| `Products\AccessoryTypeProduct` | sì, ridichiara `use SoftDeletes` |

Cinque su sette saltano anche la gestione parziale esistente.

Secondo punto d'ingresso: `CrudModelAssociatorHelper::processAssociation()`,
ramo `BelongsToMany` (~riga 110), che di soft delete non sa nulla. Va sistemato
anch'esso, altrimenti il flusso "associa" resta rotto.

## Decisioni già prese

Sono state discusse e chiuse. **Non rimetterle in discussione**, implementale.

**D1 — Il rilevamento avviene sul model pivot, non sullo schema.**
`class_uses_recursive($relation->getPivotClass())`. Niente `Schema::hasColumn()`
come criterio di comportamento: una tabella con `deleted_at` ma senza `using()`
dichiarato ha quasi certamente anche la lettura rotta (nessuno ha scritto il
filtro nella relazione), quindi sistemarne la scrittura produrrebbe un bug
diverso e più subdolo, non una feature funzionante.

**D2 — Lo schema si ispeziona solo per far esplodere, mai per gestire.**
Nel ramo scoperto (`getPivotClass()` non usa SoftDeletes) si controlla se la
tabella ha `deleted_at`. Se ce l'ha, si lancia un'eccezione immediata.

**D3 — Errore rognoso, subito, anche e soprattutto in produzione.**
Niente `Log::warning`, niente flag di config, niente rollout morbido. Richiesta
esplicita del committente: un problema che non si vede è peggio di un problema
che blocca. Un `throw new \Exception` con messaggio esplicito, in italiano,
coerente con lo stile già presente nel file (vedi
`CrudModelStoringHelper::relateHasOneThroughElements()`).

**D4 — Riuso della riga, non storico.**
Riassociare fa `restore()` sulla riga esistente, non inserisce una riga nuova.
Una riga per coppia, `deleted_at` che va e torna a `null`. Lo storico delle
associazioni è già coperto da `LogsActivity` su `BasePivotModel`.

**D5 — Operazioni per-model, non mass update.**
Usare `$pivot->restore()` e `$pivot->delete()` su istanze del pivot, non
`->update(['deleted_at' => ...])` sul query builder. I mass update non fanno
scattare i model events, quindi Activitylog non registra nulla e `updated_at`
non si muove: è l'incoerenza che ha oggi il codice esistente (riga ~171).
Sono N query invece di una, ma N qui è nell'ordine delle unità.

**D6 — Convenzione da fissare.**
Pivot con soft delete ⇒ `using()` obbligatorio con una classe che estende
`BasePivotModel`. È questa convenzione che D2 rende autoimponente.

## Cosa implementare

### 1. Il trait condiviso

Nuovo file `src/Helpers/ModelManagers/Traits/SyncsSoftDeletingPivotsTrait.php`,
accanto a `ModelManagersSettersAndGettersTraits.php`.

```php
<?php

namespace IlBronza\CRUD\Helpers\ModelManagers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

trait SyncsSoftDeletingPivotsTrait
{
	static array $pivotTablesWithDeletedAt = [];

	public function pivotUsesSoftDeletes(BelongsToMany $relation) : bool
	{
		if(in_array(SoftDeletes::class, class_uses_recursive($relation->getPivotClass())))
			return true;

		$this->assertPivotTableHasNoDeletedAt($relation);

		return false;
	}

	// Nessun using() dichiarato ma la tabella ha deleted_at: la relazione e' rotta
	// in scrittura e quasi certamente anche in lettura. Si rompe subito e rumorosamente.
	private function assertPivotTableHasNoDeletedAt(BelongsToMany $relation)
	{
		$table = $relation->getTable();

		if(! array_key_exists($table, static::$pivotTablesWithDeletedAt))
			static::$pivotTablesWithDeletedAt[$table] = Schema::connection(
				$relation->getParent()->getConnectionName()
			)->hasColumn($table, 'deleted_at');

		if(! static::$pivotTablesWithDeletedAt[$table])
			return;

		throw new \Exception('La tabella pivot ' . $table . ' ha la colonna deleted_at ma la relazione ' . get_class($relation->getParent()) . ' non dichiara using() con una classe che estende BasePivotModel. Dichiaralo: senza, le riassociazioni falliscono in silenzio.');
	}

	public function syncSoftDeletingPivot(BelongsToMany $relation, array|Collection $toRelate)
	{
		$toRelate = collect($toRelate)->map(
			fn($value) => $value instanceof Model ? $value->getKey() : $value
		)->filter();

		$relatedKey = $relation->getRelatedPivotKeyName();

		$pivots = $relation->newPivotQuery()
			->orderBy($relation->newPivot()->getKeyName())
			->get()
			->map(fn($record) => $relation->newPivot((array) $record, true))
			->groupBy($relatedKey);

		foreach($pivots as $key => $keyPivots)
		{
			$alive = $keyPivots->reject->trashed();

			if(! $toRelate->contains($key))
			{
				foreach($alive as $pivot)
					$pivot->delete();

				continue;
			}

			if($alive->isNotEmpty())
				continue;

			$keyPivots->last()->restore();
		}

		foreach($toRelate->diff($pivots->keys()) as $key)
			$relation->attach($key);
	}
}
```

Note sulle API usate, tutte pubbliche e verificate su Laravel 9:

- `getPivotClass()` restituisce `Pivot::class` quando non c'è `using()`, quindi
  il ramo D2 scatta correttamente
- `newPivotQuery()` è il builder grezzo sulla tabella pivot, già vincolato al
  parent e rispettoso di eventuali `wherePivot()`. Vede tutto, cancellati
  compresi: è esattamente ciò che ci serve
- `newPivot($attributes, $exists = true)` costruisce l'istanza con la tabella e
  la connessione giuste. Non usare `$pivotClass::where(...)` statico: un pivot
  che non dichiara `$table` cadrebbe sul fallback
  `Str::singular(class_basename())` e cercherebbe `board_drive` invece di
  `board_drives`
- l'istanza così costruita ha `id` fra gli attributi, quindi `AsPivot::delete()`
  passa da `parent::delete()` → soft delete con eventi, non dalla DELETE fisica
  del ramo senza chiave
- `attach()` eredita `withTimestamps()`, `withPivotValue()`, connessione e
  classe pivot custom: non reimplementare l'insert a mano

### 2. `CrudModelStoringHelper`

Usare il trait. Sostituire `relateBelongsToManyElements()` con:

```php
private function relateBelongsToManyElements(string $relationshipMethod, $toRelate)
{
	if((is_string($toRelate))||(is_null($toRelate)))
		$toRelate = [$toRelate];

	$relation = $this->getModel()->{$relationshipMethod}();

	if($this->pivotUsesSoftDeletes($relation))
		return $this->syncSoftDeletingPivot($relation, $toRelate);

	if((count($toRelate) == 1)&&($toRelate[0] == null))
		return $relation->sync([]);

	$relation->sync($toRelate);
}
```

Il blocco esistente righe ~157-177 (quello con `class_uses()`, `$pivotToRemove`
e il mass update su `deleted_at`) va **rimosso**: è interamente sostituito.
Verificare se l'import di `Carbon` resta usato altrove nel file prima di
toglierlo.

Nota: `syncSoftDeletingPivot()` gestisce da sé il caso `[null]` (il `filter()`
lo svuota, `whereNotIn` logico cancella tutto, nessun attach), quindi il ramo
`sync([])` resta solo sul percorso non-soft.

### 3. `CrudModelAssociatorHelper`

Usare lo stesso trait. Nel ramo `BelongsToMany` di `processAssociation()`
(~riga 110):

```php
if($relationTypeName == 'BelongsToMany')
{
	$relation = $this->getModel()->{$this->getRelation()}();

	if($this->pivotUsesSoftDeletes($relation))
		$this->syncSoftDeletingPivot($relation, $this->getValues());
	else
		$relation->sync($this->getValues());

	return $this->sendAssociationEvent();
}
```

Attenzione: qui `getValues()` è tipizzato `array|Collection` e nel ramo
`HasMany` contiene **istanze di Model**, non chiavi. Per questo
`syncSoftDeletingPivot()` normalizza in ingresso. Verificare comunque cosa
arriva realmente da questo percorso prima di dare per buona la normalizzazione.

## Casi limite da rispettare

- **`$toRelate` vuoto o `[null]`** → tutte le righe vive vanno soft-deleted,
  nessun attach. È il "deseleziona tutto".
- **Righe duplicate per la stessa coppia** (una viva e una cancellata, o più
  cancellate). Non dovrebbero esistere con questa logica, ma possono venire da
  dati storici. Regola: se esiste già una riga viva non si tocca niente; se non
  ce n'è, si ripristina **una sola** riga, l'ultima per chiave primaria, e le
  altre restano cancellate. Mai ripristinarle tutte: la relazione
  restituirebbe l'elemento N volte.
- **Nome della colonna di soft delete.** Il trait dà per scontato `deleted_at`
  solo nel controllo di schema di D2. Nelle operazioni si passa sempre da
  `restore()` / `delete()` del model, che usano `getDeletedAtColumn()`: non
  hardcodare il nome lì.
- **Pivot su connessione diversa dal parent.** `Schema::connection()` prende il
  nome da `$relation->getParent()->getConnectionName()`. Non usare
  `Schema::hasColumn()` sulla connessione di default.

## Cosa non fare

- Non chiamare `sync()` né `detach()` sui pivot soft-deleting, per nessun
  motivo. `detach()` con `pivotWheres`/`pivotWhereIns`/`pivotWhereNulls` non
  vuoti salta `detachUsingCustomClass()` e finisce su
  `newPivotQuery()->delete()`, cioè una DELETE fisica che bypassa il soft
  delete.
- Non introdurre `try`/`catch` né valori di default attorno al controllo di
  schema. Se la connessione non risponde deve esplodere.
- Non rendere l'eccezione di D2 disattivabile da config.
- Non aggiungere parametri o rami "per il futuro" (es. semantica storico
  configurabile per pivot). Si aggiungono quando il caso si presenta davvero.

## Convenzioni di stile

Indentazione a tab, graffe a capo (Allman), nullable con spazio (`? string`),
spazio dopo `!` (`if(! $value)`), `if` a riga singola senza graffe quando il
corpo è una riga, concatenazione con `.` invece dell'interpolazione, commenti
solo dove servono e in italiano.

## Verifica

Non esiste una suite che copra questo percorso. Verificare a mano su un
progetto che usa il pacchetto:

1. Ciclo associa → dissocia → riassocia su una relazione con pivot
   soft-deleting. La riga deve tornare viva, con lo stesso `id` di prima, e
   **non** deve comparire una riga nuova.
2. Controllare l'activity log: associazione e dissociazione devono risultare
   registrate. Se non lo sono, D5 non è stato rispettato e da qualche parte è
   rimasto un mass update.
3. Deselezionare tutto e salvare: tutte le righe soft-deleted, nessuna DELETE
   fisica. Confrontare il conteggio delle righe in tabella prima e dopo.
4. Salvare una relazione `belongsToMany` **senza** pivot soft-deleting: deve
   continuare a passare da `sync()` senza cambi di comportamento.
5. Creare al volo una tabella pivot con `deleted_at` e una relazione senza
   `using()`: il salvataggio deve lanciare l'eccezione di D2 con il nome tabella
   corretto nel messaggio.
6. Verificare che il ramo D2 non aggiunga una query di schema a ogni salvataggio
   ripetuto nella stessa richiesta (la cache statica deve reggere).

## Fuori scope, ma da coordinare

**Progetto Sael.** `App\Models\Drive` ha un metodo `relateBoards()` che
implementa a mano questa stessa logica, intercettato da
`CrudModelStoringHelper::associateRelationshipsByType()` prima dello `sync()`
standard. Una volta che il fix è nel pacchetto e verificato, quel metodo va
**rimosso**, altrimenti resta un doppione da mantenere. È in un altro
repository: non toccarlo da qui, segnalarlo e basta.

**Migrazione a `wherePivotNull()`.** Con il pacchetto che non chiama più
`sync()`/`detach()` sui pivot soft-deleting, il rischio di DELETE fisica
sparisce e `->wherePivotNull('deleted_at')` diventa preferibile a
`->whereNull('nome_tabella.deleted_at')`: niente nome tabella hardcodato e
coerenza fra ciò che legge la relazione e ciò che legge `newPivotQuery()`, che
è la divergenza da cui nasce tutto questo. È un cambio nelle relazioni dei
progetti, non nel pacchetto: valutarlo dopo, separatamente.
