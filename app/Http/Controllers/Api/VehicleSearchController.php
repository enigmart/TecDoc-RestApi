<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manufacturer;
use App\Models\ModelSeries;
use App\Models\PassengerCar;
use App\Models\Article;
use Illuminate\Http\Request;

class VehicleSearchController extends Controller
{
    /**
     * Get vehicle types
     */
    public function getVehicleTypes()
    {
        return response()->json([
            ['id' => 'PC', 'name' => 'Αυτοκίνητο'],
            ['id' => 'Motorcycle', 'name' => 'Μοτοσικλέτα'],
            ['id' => 'CV', 'name' => 'Φορτηγό']
        ]);
    }

    /**
     * Get manufacturers by vehicle type
     */
    public function getManufacturers(Request $request)
    {
        $vehicleType = $request->get('vehicle_type', 'PC');
        
        $query = Manufacturer::query();
        
        switch ($vehicleType) {
            case 'PC':
                $query->forCars();
                break;
            case 'Motorcycle':
                $query->forMotorcycles();
                break;
            case 'CV':
                $query->forTrucks();
                break;
        }
        
        $manufacturers = $query->orderBy('MFA_BRAND')
            ->limit(500) // Increased limit for more manufacturers
            ->get(['MFA_ID', 'MFA_BRAND', 'MFA_MODELS_COUNT'])
            ->map(function ($manufacturer) {
                return [
                    'id' => $manufacturer->MFA_ID,
                    'name' => $manufacturer->MFA_BRAND,
                    'models_count' => $manufacturer->MFA_MODELS_COUNT
                ];
            });
            
        return response()->json($manufacturers);
    }

    /**
     * Get model series by manufacturer
     */
    public function getModelSeries(Request $request, $manufacturerId)
    {
        $vehicleType = $request->get('vehicle_type', 'PC');
        
        $query = ModelSeries::where('MS_MFA_ID', $manufacturerId);
        
        switch ($vehicleType) {
            case 'PC':
                $query->forCars();
                break;
            case 'Motorcycle':
                $query->forMotorcycles();
                break;
            case 'CV':
                $query->forTrucks();
                break;
        }
        
        $models = $query->with('manufacturer')
            ->limit(100) // Limit models
            ->get(['MS_ID', 'MS_MFA_ID', 'MS_NAME_DES', 'MS_CI_FROM', 'MS_CI_TO'])
            ->map(function ($model) {
                return [
                    'id' => $model->MS_ID,
                    'name' => $model->MS_NAME_DES,
                    'year_start' => $model->MS_CI_FROM,
                    'year_end' => $model->MS_CI_TO
                ];
            });
            
        return response()->json($models);
    }

    /**
     * Get vehicle versions by model series
     */
    public function getVehicleVersions(Request $request, $modelSeriesId)
    {
        $versions = PassengerCar::where('PC_MS_ID', $modelSeriesId)
            ->with(['manufacturer', 'modelSeries'])
            ->limit(50) // Limit versions
            ->get(['PC_ID', 'PC_MFA_ID', 'PC_MS_ID', 'PC_MODEL_DES', 'PC_CTM'])
            ->map(function ($version) {
                return [
                    'id' => $version->PC_ID,
                    'name' => $version->PC_MODEL_DES ?: 'Version ' . $version->PC_ID,
                    'ctm' => $version->PC_CTM
                ];
            });
            
        return response()->json($versions);
    }

    /**
     * Get part categories
     */
    public function getPartCategories()
    {
        $categories = [
            [
                'category' => 'ΦΙΛΤΡΑ',
                'parts' => [
                    'Air filter',
                    'Oil filter',
                    'Fuel filter',
                    'Cabin filter'
                ]
            ],
            [
                'category' => 'ΙΜΑΝΤΕΣ & ΕΞΑΡΤΗΜΑΤΑ',
                'parts' => [
                    'Timing belt',
                    'V-belt',
                    'Spark plug',
                    'Glow plug'
                ]
            ],
            [
                'category' => 'ΜΗΧΑΝΙΚΑ ΕΞΑΡΤΗΜΑΤΑ',
                'parts' => [
                    'Thermostat',
                    'Water pump',
                    'Oil pump',
                    'Fuel pump'
                ]
            ],
            [
                'category' => 'ΗΛΕΚΤΡΙΚΑ & ΕΞΑΡΤΗΜΑΤΑ',
                'parts' => [
                    'Battery',
                    'Alternator',
                    'Starter',
                    'EGR valve'
                ]
            ],
            [
                'category' => 'ΑΞΕΣΟΥΑΡ',
                'parts' => [
                    'Wiper blades',
                    'Light bulbs',
                    'Mirrors',
                    'Antennas'
                ]
            ]
        ];
        
        return response()->json($categories);
    }

    /**
     * Search articles by vehicle and part category
     */
    public function searchArticles(Request $request)
    {
        $request->validate([
            'vehicle_type' => 'required|string',
            'manufacturer_id' => 'required|integer',
            'model_series_id' => 'required|integer',
            'vehicle_version_id' => 'required|integer',
            'part_category' => 'required|string'
        ]);

        // For demo purposes, we'll search based on part category keywords
        // In a real TecDoc implementation, you would use LINK tables and proper relationships
        
        $partKeywords = $this->getPartKeywords($request->part_category);
        
        $query = Article::with('supplier');
        
        // Search in article numbers and brands for part-related keywords
        if (!empty($partKeywords)) {
            $query->where(function($q) use ($partKeywords) {
                foreach ($partKeywords as $keyword) {
                    $q->orWhere('ART_ARTICLE_NR', 'LIKE', '%' . $keyword . '%')
                      ->orWhere('ART_SUP_BRAND', 'LIKE', '%' . $keyword . '%');
                }
            });
        } else {
            // If no specific keywords, show some sample articles from major brands
            $query->whereIn('ART_SUP_BRAND', ['BOSCH', 'MANN', 'MAHLE', 'FEBI', 'SACHS', 'PIERBURG']);
        }
        
        $articles = $query->paginate(50);
        
        // Get unique brands from current results for filtering
        $availableBrands = $query->distinct()
            ->pluck('ART_SUP_BRAND')
            ->filter()
            ->sort()
            ->values();
        
        // Add additional data to response
        $response = $articles->toArray();
        $response['available_brands'] = $availableBrands;
        $response['debug_info'] = [
            'part_category' => $request->part_category,
            'keywords_used' => $partKeywords,
            'vehicle_info' => [
                'manufacturer_id' => $request->manufacturer_id,
                'model_series_id' => $request->model_series_id,
                'vehicle_version_id' => $request->vehicle_version_id
            ]
        ];
            
        return response()->json($response);
    }
    
    /**
     * Get search keywords for part categories
     */
    private function getPartKeywords($partCategory)
    {
        $keywordMap = [
            'Air filter' => ['FILTER', 'AIR', 'LUFT'],
            'Oil filter' => ['FILTER', 'OIL', 'ÖL'],
            'Fuel filter' => ['FILTER', 'FUEL', 'KRAFTSTOFF'],
            'Cabin filter' => ['FILTER', 'CABIN', 'INNEN'],
            'Spark plug' => ['SPARK', 'PLUG', 'ZÜNDKERZE', 'BOSCH'],
            'Timing belt' => ['BELT', 'TIMING', 'ZAHNRIEMEN'],
            'V-belt' => ['BELT', 'KEILRIEMEN'],
            'Thermostat' => ['THERMOSTAT'],
            'Battery' => ['BATTERY', 'BATTERIE', 'VARTA', 'BOSCH'],
            'EGR valve' => ['EGR', 'VALVE'],
            'Glow plug' => ['GLOW', 'PLUG', 'GLÜHKERZE'],
            'Wiper blades' => ['WIPER', 'BLADE', 'SCHEIBENWISCHER', 'BOSCH']
        ];
        
        return $keywordMap[$partCategory] ?? [];
    }

    /**
     * Get detailed information for a specific article
     */
    public function getArticleDetails($articleId)
    {
        $article = Article::with('supplier')
            ->where('ART_ID', $articleId)
            ->first();

        if (!$article) {
            return response()->json(['error' => 'Article not found'], 404);
        }

        // In a real TecDoc implementation, you would join with more tables
        // to get comprehensive article information
        $details = [
            'basic_info' => [
                'id' => $article->ART_ID,
                'article_number' => $article->ART_ARTICLE_NR,
                'brand' => $article->ART_SUP_BRAND,
                'supplier_id' => $article->ART_SUP_ID,
                'complete_description_id' => $article->ART_COMPLETE_DES_ID,
                'ctm' => $article->ART_CTM,
                'description_id' => $article->ART_DES_ID,
                'pack_selfservice' => $article->ART_PACK_SELFSERVICE,
                'material_mark' => $article->ART_MATERIAL_MARK,
                'replacement' => $article->ART_REPLACEMENT,
                'accessory' => $article->ART_ACCESSORY,
                'batch_size1' => $article->ART_BATCH_SIZE1,
                'batch_size2' => $article->ART_BATCH_SIZE2
            ],
            'supplier_info' => $article->supplier ? [
                'id' => $article->supplier->SUP_ID,
                'brand' => $article->supplier->SUP_BRAND,
                'full_name' => $article->supplier->SUP_FULL_NAME,
                'logo_name' => $article->supplier->SUP_LOGO_NAME
            ] : null,
            'additional_info' => [
                'note' => 'Για πλήρεις πληροφορίες συμβατότητας, απαιτείται σύνδεση με τα LINK tables της TecDoc',
                'last_updated' => now()->format('Y-m-d H:i:s')
            ]
        ];

        return response()->json($details);
    }
}
