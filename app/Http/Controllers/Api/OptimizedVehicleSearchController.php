<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Manufacturer;
use App\Models\ModelSeries;
use App\Models\PassengerCar;
use App\Services\TecDocCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptimizedVehicleSearchController extends Controller
{
    /**
     * Get vehicle types with caching
     */
    public function getVehicleTypes()
    {
        $cacheKey = 'vehicle_types';
        $cached = TecDocCacheService::getCachedVehicleData($cacheKey);
        
        if ($cached) {
            return response()->json($cached);
        }

        $vehicleTypes = [
            ['id' => 'PC', 'name' => 'Αυτοκίνητο'],
            ['id' => 'CV', 'name' => 'Φορτηγό'],
            ['id' => 'MC', 'name' => 'Μοτοσικλέτα']
        ];

        TecDocCacheService::cacheVehicleData($cacheKey, $vehicleTypes);
        return response()->json($vehicleTypes);
    }

    /**
     * Get manufacturers with caching and optimization
     */
    public function getManufacturers(Request $request)
    {
        $vehicleType = $request->query('vehicle_type', 'PC');
        $cacheKey = "manufacturers_{$vehicleType}";
        
        $cached = TecDocCacheService::getCachedVehicleData($cacheKey);
        if ($cached) {
            return response()->json($cached);
        }

        // Optimized query with proper indexing
        $manufacturers = Manufacturer::select('MFA_ID', 'MFA_BRAND', 'MFA_MFC_CODE')
            ->when($vehicleType === 'PC', function ($query) {
                return $query->where('MFA_PC_MFA', '>', 0);
            })
            ->when($vehicleType === 'CV', function ($query) {
                return $query->where('MFA_CV_MFA', '>', 0);
            })
            ->when($vehicleType === 'MC', function ($query) {
                return $query->where('MFA_AXL_MFA', '>', 0);
            })
            ->orderBy('MFA_BRAND')
            ->limit(500) // Reasonable limit
            ->get()
            ->map(function ($manufacturer) {
                return [
                    'id' => $manufacturer->MFA_ID,
                    'name' => $manufacturer->MFA_BRAND,
                    'code' => $manufacturer->MFA_MFC_CODE
                ];
            });

        TecDocCacheService::cacheVehicleData($cacheKey, $manufacturers);
        return response()->json($manufacturers);
    }

    /**
     * Get model series with caching
     */
    public function getModelSeries($manufacturerId)
    {
        $cacheKey = "models_{$manufacturerId}";
        $cached = TecDocCacheService::getCachedVehicleData($cacheKey);
        
        if ($cached) {
            return response()->json($cached);
        }

        $models = ModelSeries::select('MOD_ID', 'MOD_SERIES', 'MOD_PCON_START', 'MOD_PCON_END')
            ->where('MOD_MFA_ID', $manufacturerId)
            ->orderBy('MOD_SERIES')
            ->limit(200)
            ->get()
            ->map(function ($model) {
                return [
                    'id' => $model->MOD_ID,
                    'name' => $model->MOD_SERIES,
                    'year_start' => $model->MOD_PCON_START,
                    'year_end' => $model->MOD_PCON_END
                ];
            });

        TecDocCacheService::cacheVehicleData($cacheKey, $models);
        return response()->json($models);
    }

    /**
     * Get vehicle versions with caching
     */
    public function getVehicleVersions($modelSeriesId)
    {
        $cacheKey = "versions_{$modelSeriesId}";
        $cached = TecDocCacheService::getCachedVehicleData($cacheKey);
        
        if ($cached) {
            return response()->json($cached);
        }

        $versions = PassengerCar::select('PC_ID', 'PC_PCON_START', 'PC_PCON_END', 'PC_ENGINE_DES', 'PC_KW_FROM', 'PC_HP_FROM')
            ->where('PC_MOD_ID', $modelSeriesId)
            ->orderBy('PC_PCON_START')
            ->limit(100)
            ->get()
            ->map(function ($version) {
                return [
                    'id' => $version->PC_ID,
                    'name' => trim($version->PC_ENGINE_DES . ' (' . $version->PC_KW_FROM . 'kW / ' . $version->PC_HP_FROM . 'HP)'),
                    'year_start' => $version->PC_PCON_START,
                    'year_end' => $version->PC_PCON_END,
                    'engine' => $version->PC_ENGINE_DES,
                    'power_kw' => $version->PC_KW_FROM,
                    'power_hp' => $version->PC_HP_FROM
                ];
            });

        TecDocCacheService::cacheVehicleData($cacheKey, $versions);
        return response()->json($versions);
    }

    /**
     * Get part categories with caching
     */
    public function getPartCategories()
    {
        $cacheKey = 'part_categories';
        $cached = TecDocCacheService::getCachedVehicleData($cacheKey);
        
        if ($cached) {
            return response()->json($cached);
        }

        $categories = [
            // Engine parts
            ['category' => 'ΦΙΛΤΡΑ', 'parts' => [
                'Air filter', 'Oil filter', 'Fuel filter', 'Cabin filter'
            ]],
            ['category' => 'ΑΝΆΦΛΕΞΗ & ΕΞΑΡΤΉΜΑΤΑ', 'parts' => [
                'Spark plug', 'Glow plug', 'Ignition coil', 'Distributor cap'
            ]],
            ['category' => 'ΙΜΆΝΤΕΣ & ΕΞΑΡΤΉΜΑΤΑ', 'parts' => [
                'Timing belt', 'V-belt', 'Belt tensioner', 'Belt pulley'
            ]],
            ['category' => 'ΜΗΧΑΝΙΚΑ ΕΞΑΡΤΉΜΑΤΑ', 'parts' => [
                'Thermostat', 'Water pump', 'Oil pump', 'Fuel pump'
            ]],
            ['category' => 'ΗΛΕΚΤΡΙΚΑ & ΕΞΑΡΤΉΜΑΤΑ', 'parts' => [
                'Battery', 'Alternator', 'Starter', 'EGR valve'
            ]],
            ['category' => 'ΑΞΕΣΟΥΆΡ', 'parts' => [
                'Wiper blades', 'Light bulbs', 'Mirrors', 'Antennas'
            ]]
        ];

        TecDocCacheService::cacheVehicleData($cacheKey, $categories);
        return response()->json($categories);
    }

    /**
     * Optimized search with aggressive caching and database optimization
     */
    public function searchArticles(Request $request)
    {
        $startTime = microtime(true);
        
        try {
            $vehicleType = $request->input('vehicle_type');
            $manufacturerId = $request->input('manufacturer_id');
            $modelId = $request->input('model_series_id');
            $versionId = $request->input('vehicle_version_id');
            $partCategory = $request->input('part_category');
            $searchTerm = $request->input('search_term');
            $searchType = $request->input('search_type', 'general');
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            $enhanced = $request->input('enhanced', false);

            // Enhanced caching key including search parameters
            $cacheKey = "search_articles_" . md5(serialize([
                $vehicleType, $manufacturerId, $modelId, $versionId, $partCategory, $searchTerm, $searchType, $page, $perPage, $enhanced
            ]));

            $baseFields = [
                'a.ART_ID',
                'a.ART_ARTICLE_NR', 
                'a.ART_SUP_BRAND',
                'a.ART_CTM',
                'a.ART_SUP_ID',
                'al.ARL_SEARCH_NUMBER as BARCODE_EAN'
            ];

            if ($enhanced) {
                $baseFields = array_merge($baseFields, [
                    'a.ART_COMPLETE_DES_ID',
                    'a.ART_DES_ID',
                    'a.ART_PACK_SELFSERVICE',
                    'a.ART_MATERIAL_MARK',
                    'a.ART_REPLACEMENT',
                    'a.ART_ACCESSORY',
                    'a.ART_BATCH_SIZE1',
                    'a.ART_BATCH_SIZE2'
                ]);
            }

            $query = DB::table('ARTICLES as a')
                ->select($baseFields)
                ->leftJoin('ART_LOOKUP as al', function($join) {
                    $join->on('a.ART_ID', '=', 'al.ARL_ART_ID')
                         ->where('al.ARL_TYPE', '=', 'EAN');
                });

            // Add EAN barcode search
            if ($searchTerm && $searchType === 'ean') {
                $query->where('al.ARL_SEARCH_NUMBER', $searchTerm);
            }

            // Add general search term
            if ($searchTerm && $searchType === 'general') {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('a.ART_ARTICLE_NR', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('a.ART_SUP_BRAND', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('al.ARL_SEARCH_NUMBER', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Add vehicle filters
            if ($versionId) {
                $query->join('LINK_ART as la', 'a.ART_ID', '=', 'la.LA_ART_ID')
                      ->join('LINK_ART_PT as lapt', 'la.LA_ID', '=', 'lapt.LAPT_LA_ID')
                      ->join('PASSENGER_CARS as pc', 'lapt.LAPT_PC_ID', '=', 'pc.PC_ID')
                      ->where('pc.PC_ID', $versionId);
            } elseif ($modelId) {
                $query->join('LINK_ART as la', 'a.ART_ID', '=', 'la.LA_ART_ID')
                      ->join('LINK_ART_PT as lapt', 'la.LA_ID', '=', 'lapt.LAPT_LA_ID')
                      ->join('PASSENGER_CARS as pc', 'lapt.LAPT_PC_ID', '=', 'pc.PC_ID')
                      ->where('pc.PC_MS_ID', $modelId);
            } elseif ($manufacturerId) {
                $query->join('LINK_ART as la', 'a.ART_ID', '=', 'la.LA_ART_ID')
                      ->join('LINK_ART_PT as lapt', 'la.LA_ID', '=', 'lapt.LAPT_LA_ID')
                      ->join('PASSENGER_CARS as pc', 'lapt.LAPT_PC_ID', '=', 'pc.PC_ID')
                      ->where('pc.PC_MFA_ID', $manufacturerId);
            }

            // Add part category filter
            $partKeywords = [];
            if ($partCategory) {
                $partKeywords = $this->getPartKeywords($partCategory);
                if (!empty($partKeywords)) {
                    $query->where(function($q) use ($partKeywords) {
                        foreach ($partKeywords as $keyword) {
                            $q->orWhere('a.ART_ARTICLE_NR', 'LIKE', "%{$keyword}%")
                              ->orWhere('a.ART_SUP_BRAND', 'LIKE', "%{$keyword}%");
                        }
                    });
                }
            }

            // Get paginated results
            $articles = $query->paginate($perPage, ['*'], 'page', $page);

            $queryTime = round((microtime(true) - $startTime) * 1000, 2);

            // Prepare response
            $response = $articles->toArray();
            $response['performance'] = [
                'query_time_ms' => $queryTime,
                'cache_key' => $cacheKey,
                'cached' => false,
                'search_type' => $searchType,
                'search_term' => $searchTerm,
                'keywords_used' => $partKeywords
            ];
            $response['debug_info'] = [
                'part_category' => $partCategory,
                'search_term' => $searchTerm,
                'search_type' => $searchType,
                'keywords_used' => $partKeywords,
                'vehicle_info' => [
                    'manufacturer_id' => $manufacturerId,
                    'model_series_id' => $modelId,
                    'vehicle_version_id' => $versionId
                ]
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Search articles error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error searching articles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available brands with optimization
     */
    private function getAvailableBrands($baseQuery, $partKeywords): array
    {
        $brandsQuery = clone $baseQuery;
        
        if (!empty($partKeywords)) {
            $brandsQuery->where(function($q) use ($partKeywords) {
                foreach ($partKeywords as $keyword) {
                    $q->orWhere('ART_ARTICLE_NR', 'LIKE', "%{$keyword}%")
                      ->orWhere('ART_SUP_BRAND', 'LIKE', "%{$keyword}%");
                }
            });
        }

        return $brandsQuery->distinct()
            ->pluck('ART_SUP_BRAND')
            ->filter()
            ->sort()
            ->values()
            ->take(50) // Limit brands for performance
            ->toArray();
    }

    /**
     * Get article details with caching
     */
    public function getArticleDetails($articleId)
    {
        $cached = TecDocCacheService::getCachedArticleDetails($articleId);
        if ($cached) {
            return response()->json($cached);
        }

        $article = Article::with('supplier')
            ->where('ART_ID', $articleId)
            ->first();

        if (!$article) {
            return response()->json(['error' => 'Article not found'], 404);
        }

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
            'performance' => [
                'cached' => false,
                'cache_key' => "article:{$articleId}"
            ],
            'additional_info' => [
                'note' => 'Optimized with Redis caching - Για πλήρεις πληροφορίες συμβατότητας, απαιτείται σύνδεση με τα LINK tables της TecDoc',
                'last_updated' => now()->format('Y-m-d H:i:s')
            ]
        ];

        TecDocCacheService::cacheArticleDetails($articleId, $details);
        return response()->json($details);
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats()
    {
        $stats = TecDocCacheService::getCacheStats();
        return response()->json($stats);
    }

    /**
     * Clear all caches (admin only)
     */
    public function clearCaches()
    {
        TecDocCacheService::clearAllCaches();
        return response()->json(['message' => 'All caches cleared successfully']);
    }

    /**
     * Get search keywords for part categories
     */
    private function getPartKeywords($partCategory): array
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
            'Wiper blades' => ['WIPER', 'BLADE', 'SCHEIBENWISCHER', 'BOSCH'],
            'Ignition coil' => ['IGNITION', 'COIL', 'ZÜNDSPULE'],
            'Water pump' => ['WATER', 'PUMP', 'WASSERPUMPE'],
            'Fuel pump' => ['FUEL', 'PUMP', 'KRAFTSTOFFPUMPE'],
            'Alternator' => ['ALTERNATOR', 'GENERATOR'],
            'Starter' => ['STARTER', 'ANLASSER']
        ];
        
        return $keywordMap[$partCategory] ?? [];
    }

}
