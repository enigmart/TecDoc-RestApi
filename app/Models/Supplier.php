<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'SUPPLIERS';
    protected $primaryKey = 'SUP_ID';
    public $timestamps = false;
    
    protected $fillable = [
        'SUP_BRAND',
        'SUP_FULL_NAME',
        'SUP_LOGO_NAME'
    ];

    public function articles()
    {
        return $this->hasMany(Article::class, 'ART_SUP_ID', 'SUP_ID');
    }
}
