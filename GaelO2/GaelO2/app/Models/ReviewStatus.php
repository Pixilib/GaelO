<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewStatus extends Model
{

    protected $table = 'reviews_status';
    protected $primaryKey = ['visit_id', 'study_name'];
    public $incrementing = false;

    protected $guarded = [];

    public function visit(){
        return $this->belongsTo('App\Models\Visit', 'visit_id', 'id');
    }

    //SK : Parceque cle composite (cf https://www.nuomiphp.com/eplan/en/28200.html)
    protected function setKeysForSaveQuery($query)
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
