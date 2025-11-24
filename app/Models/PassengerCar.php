<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassengerCar extends Model
{
    protected $table = 'PASSENGER_CARS';
    protected $primaryKey = 'PC_ID';
    public $timestamps = false;
    
    protected $fillable = [
        'PC_MFA_ID',
        'PC_MS_ID',
        'PC_MODEL_DES',
        'PC_CTM',
        'PC_TYPE'
    ];

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'PC_MFA_ID', 'MFA_ID');
    }

    public function modelSeries()
    {
        return $this->belongsTo(ModelSeries::class, 'PC_MS_ID', 'MS_ID');
    }

    // Get text designation for model name
    public function getModelNameAttribute()
    {
        // This would need to join with TEXT_DESIGNATIONS table
        // For now, return the ID
        return $this->PC_MODEL_DES;
    }
}
