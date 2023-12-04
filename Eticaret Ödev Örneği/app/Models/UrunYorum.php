<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UrunYorum extends Model
{
    protected $table= "urun_yorum";
    protected $guarded = [];
    const CREATED_AT = 'olusturma_tarihi';
    const UPDATED_AT = 'guncelleme_tarihi';
    const DELETED_AT='silinme_tarihi';

    public function urun()
    {
        return $this->belongsTo('App\Models\Urun');
    }
}
