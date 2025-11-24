<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Manufacturer;
use App\Models\ModelSeries;
use App\Models\PassengerCar;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VehicleController extends BaseController
{
    /**
     * Get vehicle types
     */
    public function getVehicleTypes(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $vehicleTypes = [
            ['id' => 'PC', 'name' => 'Αυτοκίνητο'],
            ['id' => 'CV', 'name' => 'Φορτηγό'],
            ['id' => 'MC', 'name' => 'Μοτοσικλέτα']
        ];

        if ($this->isFullMode($request)) {
            // Add extra data in full mode
            $vehicleTypes = array_map(function ($type) {
                return array_merge($type, [
                    'description' => $this->getVehicleTypeDescription($type['id']),
                    'manufacturers_count' => $this->getManufacturersCount($type['id']),
                    'supported' => true
                ]);
            }, $vehicleTypes);
        }

        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $response = $this->apiResponse(
            $vehicleTypes,
            'Vehicle types retrieved successfully',
            true,
            200,
            ['mode' => $this->getDataMode($request)],
            ['query_time_ms' => $queryTime, 'cached' => false]
        );

        $this->addCacheHeaders($response, 7200); // Cache for 2 hours
        return $response;
    }

    /**
     * Get manufacturers by vehicle type
     */
    public function getManufacturers(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $vehicleType = $request->get('vehicle_type', 'PC');
        $isFullMode = $this->isFullMode($request);
        
        $query = Manufacturer::query();
        
        switch ($vehicleType) {
            case 'PC':
                $query->forCars();
                break;
            case 'MC':
                $query->forMotorcycles();
                break;
            case 'CV':
                $query->forTrucks();
                break;
        }
        
        $manufacturers = $query->orderBy('MFA_BRAND')
            ->limit(500)
            ->get(['MFA_ID', 'MFA_BRAND', 'MFA_MODELS_COUNT'])
            ->map(function ($manufacturer) use ($isFullMode) {
                $data = [
                    'id' => $manufacturer->MFA_ID,
                    'name' => $manufacturer->MFA_BRAND,
                    'models_count' => $manufacturer->MFA_MODELS_COUNT
                ];

                if ($isFullMode) {
                    $data = array_merge($data, [
                        'country' => $this->getManufacturerCountry($manufacturer->MFA_BRAND),
                        'founded_year' => $this->getManufacturerFoundedYear($manufacturer->MFA_BRAND),
                        'website' => $this->getManufacturerWebsite($manufacturer->MFA_BRAND),
                        'logo_url' => $this->getManufacturerLogo($manufacturer->MFA_BRAND),
                        'is_active' => true
                    ]);
                }

                return $data;
            });

        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $response = $this->apiResponse(
            $manufacturers,
            'Manufacturers retrieved successfully',
            true,
            200,
            [
                'vehicle_type' => $vehicleType,
                'mode' => $this->getDataMode($request),
                'total_count' => $manufacturers->count()
            ],
            ['query_time_ms' => $queryTime, 'cached' => false]
        );

        $this->addCacheHeaders($response, 3600); // Cache for 1 hour
        return $response;
    }

    /**
     * Get model series by manufacturer
     */
    public function getModelSeries(Request $request, $manufacturerId): JsonResponse
    {
        $startTime = microtime(true);
        $vehicleType = $request->get('vehicle_type', 'PC');
        $isFullMode = $this->isFullMode($request);
        
        $query = ModelSeries::where('MS_MFA_ID', $manufacturerId);
        
        switch ($vehicleType) {
            case 'PC':
                $query->forCars();
                break;
            case 'MC':
                $query->forMotorcycles();
                break;
            case 'CV':
                $query->forTrucks();
                break;
        }
        
        $models = $query->with('manufacturer')
            ->limit(100)
            ->get(['MS_ID', 'MS_MFA_ID', 'MS_NAME_DES', 'MS_CI_FROM', 'MS_CI_TO'])
            ->map(function ($model) use ($isFullMode) {
                $data = [
                    'id' => $model->MS_ID,
                    'name' => $model->MS_NAME_DES,
                    'year_start' => $model->MS_CI_FROM,
                    'year_end' => $model->MS_CI_TO
                ];

                if ($isFullMode) {
                    $data = array_merge($data, [
                        'manufacturer' => [
                            'id' => $model->MS_MFA_ID,
                            'name' => $model->manufacturer->MFA_BRAND ?? 'Unknown'
                        ],
                        'production_years' => $this->getProductionYearsRange($model->MS_CI_FROM, $model->MS_CI_TO),
                        'body_types' => $this->getModelBodyTypes($model->MS_ID),
                        'engine_types' => $this->getModelEngineTypes($model->MS_ID),
                        'versions_count' => $this->getModelVersionsCount($model->MS_ID)
                    ]);
                }

                return $data;
            });

        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $response = $this->apiResponse(
            $models,
            'Model series retrieved successfully',
            true,
            200,
            [
                'manufacturer_id' => $manufacturerId,
                'vehicle_type' => $vehicleType,
                'mode' => $this->getDataMode($request),
                'total_count' => $models->count()
            ],
            ['query_time_ms' => $queryTime, 'cached' => false]
        );

        $this->addCacheHeaders($response, 3600);
        return $response;
    }

    /**
     * Get vehicle versions by model series
     */
    public function getVehicleVersions(Request $request, $modelSeriesId): JsonResponse
    {
        $startTime = microtime(true);
        $isFullMode = $this->isFullMode($request);
        
        $versions = PassengerCar::where('PC_MS_ID', $modelSeriesId)
            ->with(['manufacturer', 'modelSeries'])
            ->limit(50)
            ->get(['PC_ID', 'PC_MFA_ID', 'PC_MS_ID', 'PC_MODEL_DES', 'PC_CTM'])
            ->map(function ($version) use ($isFullMode) {
                $data = [
                    'id' => $version->PC_ID,
                    'name' => $version->PC_MODEL_DES ?: 'Version ' . $version->PC_ID,
                    'ctm' => $version->PC_CTM
                ];

                if ($isFullMode) {
                    $data = array_merge($data, [
                        'manufacturer' => [
                            'id' => $version->PC_MFA_ID,
                            'name' => $version->manufacturer->MFA_BRAND ?? 'Unknown'
                        ],
                        'model_series' => [
                            'id' => $version->PC_MS_ID,
                            'name' => $version->modelSeries->MS_NAME_DES ?? 'Unknown'
                        ],
                        'specifications' => $this->getVehicleSpecifications($version->PC_ID),
                        'compatible_parts_count' => $this->getCompatiblePartsCount($version->PC_ID)
                    ]);
                }

                return $data;
            });

        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $response = $this->apiResponse(
            $versions,
            'Vehicle versions retrieved successfully',
            true,
            200,
            [
                'model_series_id' => $modelSeriesId,
                'mode' => $this->getDataMode($request),
                'total_count' => $versions->count()
            ],
            ['query_time_ms' => $queryTime, 'cached' => false]
        );

        $this->addCacheHeaders($response, 3600);
        return $response;
    }

    // Helper methods for full mode data
    private function getVehicleTypeDescription($typeId): string
    {
        return match($typeId) {
            'PC' => 'Επιβατικά αυτοκίνητα και ελαφρά οχήματα',
            'CV' => 'Φορτηγά και επαγγελματικά οχήματα',
            'MC' => 'Μοτοσικλέτες και δίκυκλα',
            default => 'Άλλος τύπος οχήματος'
        };
    }

    private function getManufacturersCount($vehicleType): int
    {
        $query = Manufacturer::query();
        switch ($vehicleType) {
            case 'PC': $query->forCars(); break;
            case 'MC': $query->forMotorcycles(); break;
            case 'CV': $query->forTrucks(); break;
        }
        return $query->count();
    }

    private function getManufacturerCountry($brand): ?string
    {
        // Simplified mapping - in real app, this would be from database
        $countries = [
            'BMW' => 'Germany', 'MERCEDES-BENZ' => 'Germany', 'AUDI' => 'Germany',
            'TOYOTA' => 'Japan', 'HONDA' => 'Japan', 'NISSAN' => 'Japan',
            'FORD' => 'USA', 'CHEVROLET' => 'USA', 'CHRYSLER' => 'USA',
            'PEUGEOT' => 'France', 'RENAULT' => 'France', 'CITROËN' => 'France'
        ];
        return $countries[$brand] ?? null;
    }

    private function getManufacturerFoundedYear($brand): ?int
    {
        $years = [
            'BMW' => 1916, 'MERCEDES-BENZ' => 1926, 'AUDI' => 1909,
            'TOYOTA' => 1937, 'HONDA' => 1948, 'NISSAN' => 1933,
            'FORD' => 1903, 'CHEVROLET' => 1911, 'PEUGEOT' => 1810
        ];
        return $years[$brand] ?? null;
    }

    private function getManufacturerWebsite($brand): ?string
    {
        return 'https://www.' . strtolower(str_replace(['-', ' '], '', $brand)) . '.com';
    }

    private function getManufacturerLogo($brand): ?string
    {
        return 'https://logos.example.com/' . strtolower(str_replace(['-', ' '], '_', $brand)) . '.png';
    }

    private function getProductionYearsRange($from, $to): string
    {
        if ($to) {
            return "$from - $to";
        }
        return "$from - Present";
    }

    private function getModelBodyTypes($modelId): array
    {
        return ['Sedan', 'Hatchback', 'SUV']; // Simplified
    }

    private function getModelEngineTypes($modelId): array
    {
        return ['Petrol', 'Diesel', 'Hybrid']; // Simplified
    }

    private function getModelVersionsCount($modelId): int
    {
        return PassengerCar::where('PC_MS_ID', $modelId)->count();
    }

    private function getVehicleSpecifications($vehicleId): array
    {
        return [
            'engine_capacity' => '2.0L',
            'power_hp' => 150,
            'fuel_type' => 'Petrol',
            'transmission' => 'Manual'
        ]; // Simplified
    }

    private function getCompatiblePartsCount($vehicleId): int
    {
        return rand(500, 2000); // Simplified
    }
}
