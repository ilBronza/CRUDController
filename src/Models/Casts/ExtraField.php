<?php

namespace IlBronza\CRUD\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class ExtraField implements CastsAttributes
{
    public $extraModelClassname;

    /**
     * Come usare ExtraField
     * 
     * ExtraField è una tipologia di casting
     * per le proprietà che vanno salvate su un model esterno
     * con relazione di tipo isA.
     * 
     * Un esempio buono è per ilBronza Client
     * che usa la tabella "clients__clients"
     * e che viene esteso da dei campi
     * in una tabella esterna "clients".
     * 
     * Per utilizzare questi campi
     * viene dichiarato un model di appoggio ClientExtraFields
     * che dichiara un metodo getTable() che ritorna "clients"
     * 
     * Nel model che estende IlBronza Client si dichiarano i castables
     * 
     * es. 
     * protected $castables = [
     *  'rag_soc' => ExtraField::class
     * ];
     * 
     * in questo modo quando si andrà a leggere o scrivere la proprietà
     * questa verrà salvata in una tabella differente
     * 
     * 
     * Se si vuole usare un model diverso
     * va aggiunto un parametro che identifica la classe.
     * Un esempio è quello del model IlBronza Destination che usa Address
     * per conservare gli indirizzi della destination.
     * 
     * i suoi castables saranno quindi:
     * protected $castables = [
     *  'street' => ExtraField::class . ':address'
     * ]
     * 
     * in cui si va ad esplicitare il model (relazione HasOne)
     * su cui si andranno a salvare i valori della proprietà street
     * 
     **/







    public function __construct(string $extraModelClassname = null)
    {
        $this->extraModelClassname = $extraModelClassname;
    }
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        if(! $this->extraModelClassname)
            return $model->getExtraAttribute($key);

        return $model->getCustomExtraAttribute($this->extraModelClassname, $key);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        return $this->_set($model, $key, $value, $attributes);
    }

    public function _set($model, string $key, $value, array $attributes)
    {
        if(! $this->extraModelClassname)
        {
            if(! isset($model->extraFields))
                $model->extraFields = $model->getCachedProjectExtraFieldsModel();

            $model->extraFields->$key = $value;

            return ;
        }

        $extraModelClassname = $this->extraModelClassname;

        if(! $model->$extraModelClassname)
            $model->$extraModelClassname = $model->provideExtraFieldCustomModel($extraModelClassname);

        $model->$extraModelClassname->$key = $value;       
    }
}
