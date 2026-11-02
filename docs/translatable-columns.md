# Colonne per TranslatableField

`crud:translations` controlla le colonne dei campi con cast
`IlBronza\CRUD\Models\Casts\TranslatableField`, inclusi i cast ereditati, e genera
una migration per quelle mancanti.

```bash
# Mostra una lista dei modelli traducibili e permette di sceglierne uno.
php artisan crud:translations --generate

# Modello esplicito: FQCN, nome sotto App\Models oppure alias morph.
php artisan crud:translations 'App\Models\Product' --generate

# Solo controllo: exit code 1 se mancano colonne, 0 se sono presenti tutte.
php artisan crud:translations 'App\Models\Product' --check
```

Senza `--generate` il comando esegue soltanto il controllo. Con `--no-interaction`
il modello deve essere specificato: non viene scelto implicitamente.

La lista comprende i modelli concreti sotto `Models` nel namespace
dell'applicazione, quelli registrati in `*.models.*.class` nelle configurazioni
dei pacchetti e quelli nella morph map. Mostra soltanto i modelli con almeno un
cast `TranslatableField`. Per includere modelli in altre posizioni:

```php
// config/crud.php
'translatableFields' => [
    'locales' => ['it' => 'Italiano', 'en' => 'English', 'fr' => 'Français'],
    'models' => [\Acme\Catalog\Product::class],
],
```

Le lingue sono le stesse dei form: `crud.translatableFields.locales`, con fallback
su `uikittemplate.availableLanguages`. La lingua base è `app.base_locale`
(default `it`) e mantiene il campo originale. Per `title` si aggiungono quindi
`en_title`, `fr_title`, ecc.; le colonne già presenti vengono conservate.

Il comando legge lo schema sulla connessione del modello e replica il tipo delle
colonne testuali: `char`, `varchar`, `tinytext`, `text`, `mediumtext`, `longtext`.
Conserva lunghezza, charset e collation quando disponibili. Le traduzioni sono
sempre nullable e non ereditano default, indici, vincoli unique o espressioni
generate del campo base. Per tipi non supportati il comando segnala l'errore
senza scrivere una migration incompleta. SQLite non conserva la lunghezza dei
`varchar`: il comando può copiare soltanto le informazioni presenti nello schema.

La tabella e i campi base devono già esistere. Prima della generazione, applicare
le migration già presenti: il comando riconosce eventuali sovrapposizioni con
altre migration generate da `crud:translations`, ma non interpreta il PHP delle
migration scritte manualmente.

La migration viene salvata in `database/migrations` dell'applicazione e non viene
eseguita dal comando. Contiene colonne, tipi e connessione espliciti; successive
modifiche alla configurazione delle lingue non ne cambiano il comportamento.
Il rollback rimuove soltanto le colonne elencate nella migration.

```bash
php artisan migrate
```
