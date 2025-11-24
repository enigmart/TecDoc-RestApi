<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelSeries extends Model
{
    protected $table = 'MODELS_SERIES';
    protected $primaryKey = 'MS_ID';
    public $timestamps = false;
    
    protected $fillable = [
        'MS_MFA_ID',
        'MS_NAME_DES',
        'MS_CI_FROM',
        'MS_CI_TO',
        'MS_TYPE'
    ];

    protected $casts = [
        'MS_CI_FROM' => 'date',
        'MS_CI_TO' => 'date',
        'MS_TYPE' => 'array'
    ];

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'MS_MFA_ID', 'MFA_ID');
    }

    public function passengerCars()
    {
        return $this->hasMany(PassengerCar::class, 'PC_MS_ID', 'MS_ID');
    }

    // Scopes for different vehicle types
    public function scopeForCars($query)
    {
        return $query->where('MS_TYPE', 'LIKE', '%PC%');
    }

    public function scopeForMotorcycles($query)
    {
        return $query->where('MS_TYPE', 'LIKE', '%Motorcycle%');
    }

    public function scopeForTrucks($query)
    {
        return $query->where('MS_TYPE', 'LIKE', '%CV%');
    }
}
