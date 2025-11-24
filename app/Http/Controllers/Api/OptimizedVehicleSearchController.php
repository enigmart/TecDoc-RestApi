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
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 20);
            $enhanced = $request->input('enhanced', false);

            // Enhanced caching key
            $cacheKey = "search_articles_" . md5(serialize([
                $vehicleType, $manufacturerId, $modelId, $versionId, $partCategory, $page, $perPage, $enhanced
            ]));

            $searchResults = Cache::remember($cacheKey, config('cache.search_ttl'), function() use (
                $vehicleType, $manufacturerId, $modelId, $versionId, $partCategory, $page, $perPage, $enhanced
            ) {
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
                if ($partCategory) {
                    $partKeywords = $this->getPartCategoryKeywords($partCategory);
                    if (!empty($partKeywords)) {
                        $query->where(function($q) use ($partKeywords) {
                            foreach ($partKeywords as $keyword) {
                                $q->orWhere('a.ART_ARTICLE_NR', 'LIKE', "%{$keyword}%")
                                  ->orWhere('a.ART_SUP_BRAND', 'LIKE', "%{$keyword}%");
                            }
                        });
                    }
                }

                return $query->paginate($perPage, ['*'], 'page', $page);
            });

        // Get paginated results with reduced page size for better performance
        $articles = $query->paginate(30); // Reduced from 50 to 30

        // Get available brands for filtering (cache separately)
        $brandsKey = TecDocCacheService::getBrandsCacheKey($searchData);
        $availableBrands = TecDocCacheService::getCachedBrands($brandsKey);
        
        if (!$availableBrands) {
            $availableBrands = $this->getAvailableBrands($query, $partKeywords);
            TecDocCacheService::cacheBrands($brandsKey, $availableBrands);
        }

        $queryTime = round((microtime(true) - $startTime) * 1000, 2);

        // Prepare response
        $response = $articles->toArray();
        $response['available_brands'] = $availableBrands;
        $response['performance'] = [
            'query_time_ms' => $queryTime,
            'cache_key' => $cacheKey,
            'cached' => false,
            'keywords_used' => $partKeywords
        ];
        $response['debug_info'] = [
            'part_category' => $request->part_category,
            'keywords_used' => $partKeywords,
            'vehicle_info' => [
                'manufacturer_id' => $request->manufacturer_id,
                'model_series_id' => $request->model_series_id,
                'vehicle_version_id' => $request->vehicle_version_id
            ]
        ];

        // Cache the results for future requests
        TecDocCacheService::cacheSearchResults($cacheKey, $response);

        return response()->json($response);
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

    /**
     * Get enhanced article details with all available information
     */
    public function getArticleDetails(Request $request, $articleId)
    {
        $startTime = microtime(true);
        
        try {
            $cacheKey = "article_details_enhanced_{$articleId}";
            
            $articleDetails = Cache::remember($cacheKey, 3600, function() use ($articleId) {
                // Get basic article info
                $article = DB::table('ARTICLES')
                    ->where('ART_ID', $articleId)
                    ->first();
                
                if (!$article) {
                    return null;
                }
                
                // Get article criteria (specifications)
                $criteria = DB::table('ARTICLE_CRITERIA as ac')
                    ->select([
                        'ac.ACR_CRI_ID',
                        'ac.ACR_VALUE',
                        'ac.ACR_DES_ID',
                        'ac.ACR_DISPLAY'
                    ])
                    ->where('ac.ACR_ART_ID', $articleId)
                    ->get();
                
                // Get media information (images, documents)
                $media = DB::table('ART_MEDIA_INFO as ami')
                    ->select([
                        'ami.ART_MEDIA_FILE_NAME',
                        'ami.ART_MEDIA_TYPE',
                        'ami.ART_MEDIA_CONTENT_TYPE',
                        'ami.ART_MEDIA_WIDTH',
                        'ami.ART_MEDIA_HEIGHT',
                        'ami.ART_MEDIA_HIPPERLINK as ART_MEDIA_HYPERLINK'
                    ])
                    ->where('ami.ART_MEDIA_ART_ID', $articleId)
                    ->limit(10)
                    ->get();
                
                // Get superseded/replacement info
                $replacements = DB::table('SUPERSEDED_ARTICLES as sa')
                    ->select([
                        'sa.SUA_NEW_ART_ID',
                        'sa.SUA_NUMBER',
                        'new_art.ART_ARTICLE_NR as NEW_ARTICLE_NR',
                        'new_art.ART_SUP_BRAND as NEW_BRAND'
                    ])
                    ->leftJoin('ARTICLES as new_art', 'sa.SUA_NEW_ART_ID', '=', 'new_art.ART_ID')
                    ->where('sa.SUA_ART_ID', $articleId)
                    ->get();
                
                // Get replaced by (articles that this one replaces)
                $replacedBy = DB::table('SUPERSEDED_ARTICLES as sa')
                    ->select([
                        'sa.SUA_ART_ID as OLD_ART_ID',
                        'sa.SUA_NUMBER as OLD_NUMBER',
                        'old_art.ART_ARTICLE_NR as OLD_ARTICLE_NR',
                        'old_art.ART_SUP_BRAND as OLD_BRAND'
                    ])
                    ->leftJoin('ARTICLES as old_art', 'sa.SUA_ART_ID', '=', 'old_art.ART_ID')
                    ->where('sa.SUA_NEW_ART_ID', $articleId)
                    ->get();
                
                return [
                    'article' => $article,
                    'criteria' => $criteria,
                    'media' => $media,
                    'replacements' => $replacements,
                    'replaced_by' => $replacedBy
                ];
            });
            
            if (!$articleDetails || !$articleDetails['article']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article not found'
                ], 404);
            }
            
            $queryTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return response()->json([
                'success' => true,
                'message' => 'Article details retrieved successfully',
                'data' => [
                    'basic_info' => $articleDetails['article'],
                    'specifications' => $articleDetails['criteria']->map(function($criteria) {
                        return [
                            'criteria_id' => $criteria->ACR_CRI_ID,
                            'value' => $criteria->ACR_VALUE,
                            'description_id' => $criteria->ACR_DES_ID,
                            'display' => $criteria->ACR_DISPLAY
                        ];
                    }),
                    'media' => $articleDetails['media']->map(function($media) {
                        return [
                            'filename' => $media->ART_MEDIA_FILE_NAME,
                            'type' => $media->ART_MEDIA_TYPE,
                            'content_type' => $media->ART_MEDIA_CONTENT_TYPE,
                            'dimensions' => [
                                'width' => $media->ART_MEDIA_WIDTH,
                                'height' => $media->ART_MEDIA_HEIGHT
                            ],
                            'hyperlink' => $media->ART_MEDIA_HYPERLINK
                        ];
                    }),
                    'replacements' => [
                        'replaces' => $articleDetails['replacements']->map(function($replacement) {
                            return [
                                'new_article_id' => $replacement->SUA_NEW_ART_ID,
                                'new_article_number' => $replacement->NEW_ARTICLE_NR,
                                'new_brand' => $replacement->NEW_BRAND,
                                'superseded_number' => $replacement->SUA_NUMBER
                            ];
                        }),
                        'replaced_by' => $articleDetails['replaced_by']->map(function($old) {
                            return [
                                'old_article_id' => $old->OLD_ART_ID,
                                'old_article_number' => $old->OLD_ARTICLE_NR,
                                'old_brand' => $old->OLD_BRAND,
                                'old_number' => $old->OLD_NUMBER
                            ];
                        })
                    ]
                ],
                'performance' => [
                    'query_time_ms' => $queryTime,
                    'cached' => false
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Enhanced article details error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving article details'
            ], 500);
        }
    }
}
