<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $table = 'ARTICLES';
    protected $primaryKey = 'ART_ID';
    public $timestamps = false;
    
    protected $fillable = [
        'ART_ARTICLE_NR',
        'ART_SUP_ID',
        'ART_SUP_BRAND',
        'ART_COMPLETE_DES_ID',
        'ART_CTM',
        'ART_DES_ID',
        'ART_PACK_SELFSERVICE',
        'ART_MATERIAL_MARK',
        'ART_REPLACEMENT',
        'ART_ACCESSORY',
        'ART_BATCH_SIZE1',
        'ART_BATCH_SIZE2'
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'ART_SUP_ID', 'SUP_ID');
    }
}
