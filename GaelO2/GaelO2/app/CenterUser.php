<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CenterUser extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'center_user';
    protected $primaryKey = ['user_id', 'center_code'];
    public $incrementing = false;

    //SK : Parceque cle composite (cf https://www.nuomiphp.com/eplan/en/28200.html)
    protected function setKeysForSaveQuery(Builder $query)
    {
        $keys = $this->getKeyName();
        if(!is_array($keys)){
            return parent::setKeysForSaveQuery($query);
        }

        foreach($keys as $keyName){
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    //SK : Parceque cle composite
    protected function getKeyForSaveQuery($keyName = null)
    {
        if(is_null($keyName)){
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }
}
