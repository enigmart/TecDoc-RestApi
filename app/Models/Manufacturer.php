<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    protected $table = 'MANUFACTURERS';
    protected $primaryKey = 'MFA_ID';
    public $timestamps = false;
    
    protected $fillable = [
        'MFA_BRAND',
        'MFA_TYPE',
        'MFA_MODELS_COUNT',
        'MFA_SUP_ID'
    ];

    protected $casts = [
        'MFA_TYPE' => 'array'
    ];

    public function modelSeries()
    {
        return $this->hasMany(ModelSeries::class, 'MS_MFA_ID', 'MFA_ID');
    }

    public function passengerCars()
    {
        return $this->hasMany(PassengerCar::class, 'PC_MFA_ID', 'MFA_ID');
    }

    // Scopes for different vehicle types
    public function scopeForCars($query)
    {
        return $query->where('MFA_TYPE', 'LIKE', '%PC%');
    }

    public function scopeForMotorcycles($query)
    {
        return $query->where('MFA_TYPE', 'LIKE', '%Motorcycle%');
    }

    public function scopeForTrucks($query)
    {
        return $query->where('MFA_TYPE', 'LIKE', '%CV%');
    }
}
