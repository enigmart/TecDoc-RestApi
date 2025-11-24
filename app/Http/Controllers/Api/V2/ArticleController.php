<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ArticleController extends BaseController
{
    /**
     * Get part categories
     */
    public function getPartCategories(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $isFullMode = $this->isFullMode($request);
        
        $categories = [
            [
                'id' => 'filters',
                'name' => 'ΦΙΛΤΡΑ',
                'parts' => ['Air filter', 'Oil filter', 'Fuel filter', 'Cabin filter']
            ],
            [
                'id' => 'belts',
                'name' => 'ΙΜΑΝΤΕΣ & ΕΞΑΡΤΗΜΑΤΑ',
                'parts' => ['Timing belt', 'V-belt', 'Spark plug', 'Glow plug']
            ],
            [
                'id' => 'mechanical',
                'name' => 'ΜΗΧΑΝΙΚΑ ΕΞΑΡΤΗΜΑΤΑ',
                'parts' => ['Thermostat', 'Water pump', 'Oil pump', 'Fuel pump']
            ],
            [
                'id' => 'electrical',
                'name' => 'ΗΛΕΚΤΡΙΚΑ & ΕΞΑΡΤΗΜΑΤΑ',
                'parts' => ['Battery', 'Alternator', 'Starter', 'EGR valve']
            ],
            [
                'id' => 'accessories',
                'name' => 'ΑΞΕΣΟΥΑΡ',
                'parts' => ['Wiper blades', 'Light bulbs', 'Mirrors', 'Antennas']
            ]
        ];

        if ($isFullMode) {
            $categories = array_map(function ($category) {
                return array_merge($category, [
                    'description' => $this->getCategoryDescription($category['id']),
                    'parts_count' => count($category['parts']),
                    'popular_brands' => $this->getPopularBrandsForCategory($category['id']),
                    'price_range' => $this->getPriceRangeForCategory($category['id'])
                ]);
            }, $categories);
        }

        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $response = $this->apiResponse(
            $categories,
            'Part categories retrieved successfully',
            true,
            200,
            ['mode' => $this->getDataMode($request)],
            ['query_time_ms' => $queryTime, 'cached' => false]
        );

        $this->addCacheHeaders($response, 7200);
        return $response;
    }

    /**
     * Search articles
     */
    public function searchArticles(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $validator = Validator::make($request->all(), [
            'vehicle_type' => 'required|in:PC,CV,MC',
            'manufacturer_id' => 'required|integer',
            'model_series_id' => 'required|integer',
            'vehicle_version_id' => 'required|integer',
            'part_category' => 'required|string',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                'VALIDATION_ERROR',
                $validator->errors()->toArray()
            );
        }

        $perPage = $request->input('per_page', 30);
        $isFullMode = $this->isFullMode($request);
        $partCategory = $request->input('part_category');
        
        // Get keywords for search
        $keywords = $this->getPartCategoryKeywords($partCategory);
        
        $query = Article::query();
        
        // Add keyword search
        if (!empty($keywords)) {
            $query->where(function ($q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $q->orWhere('ART_ARTICLE_NR', 'LIKE', "%{$keyword}%")
                      ->orWhere('ART_SUP_BRAND', 'LIKE', "%{$keyword}%");
                }
            });
        }

        $articles = $query->orderBy('ART_SUP_BRAND')
            ->orderBy('ART_ARTICLE_NR')
            ->paginate($perPage);

        // Transform articles data
        $transformedArticles = $articles->getCollection()->map(function ($article) use ($isFullMode) {
            $data = [
                'id' => $article->ART_ID,
                'article_number' => $article->ART_ARTICLE_NR,
                'brand' => $article->ART_SUP_BRAND,
                'supplier_id' => $article->ART_SUP_ID,
                'ctm' => $article->ART_CTM
            ];

            if ($isFullMode) {
                $data = array_merge($data, [
                    'complete_description_id' => $article->ART_COMPLETE_DES_ID,
                    'description_id' => $article->ART_DES_ID,
                    'pack_selfservice' => (bool) $article->ART_PACK_SELFSERVICE,
                    'material_mark' => (bool) $article->ART_MATERIAL_MARK,
                    'replacement' => (bool) $article->ART_REPLACEMENT,
                    'accessory' => (bool) $article->ART_ACCESSORY,
                    'batch_size1' => $article->ART_BATCH_SIZE1,
                    'batch_size2' => $article->ART_BATCH_SIZE2,
                    'specifications' => $this->getArticleSpecifications($article->ART_ID),
                    'compatibility' => $this->getArticleCompatibility($article->ART_ID),
                    'images' => $this->getArticleImages($article->ART_ID),
                    'price_info' => $this->getArticlePriceInfo($article->ART_ID)
                ]);
            }

            return $data;
        });

        // Get available brands for filtering
        $availableBrands = $this->getAvailableBrands($query);

        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $response = $this->paginatedResponse(
            $articles->setCollection($transformedArticles),
            [
                'query_time_ms' => $queryTime,
                'cached' => false,
                'keywords_used' => $keywords
            ]
        );

        // Add available brands to response
        $responseData = $response->getData(true);
        $responseData['meta']['available_brands'] = $availableBrands;
        $responseData['meta']['search_criteria'] = [
            'vehicle_type' => $request->input('vehicle_type'),
            'manufacturer_id' => $request->input('manufacturer_id'),
            'model_series_id' => $request->input('model_series_id'),
            'vehicle_version_id' => $request->input('vehicle_version_id'),
            'part_category' => $partCategory,
            'mode' => $this->getDataMode($request)
        ];

        $this->addCacheHeaders($response, 1800); // Cache for 30 minutes
        return response()->json($responseData, $response->getStatusCode());
    }

    /**
     * Get article details
     */
    public function getArticleDetails(Request $request, $articleId): JsonResponse
    {
        $startTime = microtime(true);
        $isFullMode = $this->isFullMode($request);
        
        $article = Article::with('supplier')->find($articleId);
        
        if (!$article) {
            return $this->errorResponse(
                'Article not found',
                404,
                'ARTICLE_NOT_FOUND'
            );
        }

        $data = [
            'id' => $article->ART_ID,
            'article_number' => $article->ART_ARTICLE_NR,
            'brand' => $article->ART_SUP_BRAND,
            'supplier' => [
                'id' => $article->ART_SUP_ID,
                'name' => $article->supplier->SUP_BRAND ?? $article->ART_SUP_BRAND
            ],
            'ctm' => $article->ART_CTM
        ];

        if ($isFullMode) {
            $data = array_merge($data, [
                'complete_description_id' => $article->ART_COMPLETE_DES_ID,
                'description_id' => $article->ART_DES_ID,
                'pack_selfservice' => (bool) $article->ART_PACK_SELFSERVICE,
                'material_mark' => (bool) $article->ART_MATERIAL_MARK,
                'replacement' => (bool) $article->ART_REPLACEMENT,
                'accessory' => (bool) $article->ART_ACCESSORY,
                'batch_size1' => $article->ART_BATCH_SIZE1,
                'batch_size2' => $article->ART_BATCH_SIZE2,
                'specifications' => $this->getArticleSpecifications($article->ART_ID),
                'compatibility' => $this->getArticleCompatibility($article->ART_ID),
                'images' => $this->getArticleImages($article->ART_ID),
                'price_info' => $this->getArticlePriceInfo($article->ART_ID),
                'related_articles' => $this->getRelatedArticles($article->ART_ID),
                'installation_notes' => $this->getInstallationNotes($article->ART_ID)
            ]);
        }

        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        
        $response = $this->apiResponse(
            $data,
            'Article details retrieved successfully',
            true,
            200,
            ['mode' => $this->getDataMode($request)],
            ['query_time_ms' => $queryTime, 'cached' => false]
        );

        $this->addCacheHeaders($response, 3600);
        return $response;
    }

    // Helper methods
    private function getPartCategoryKeywords($category): array
    {
        $keywords = [
            'Air filter' => ['FILTER', 'AIR', 'ΦΙΛ'],
            'Oil filter' => ['FILTER', 'OIL', 'ΟΛ'],
            'Fuel filter' => ['FILTER', 'FUEL', 'ΚΑΥΣ'],
            'Cabin filter' => ['FILTER', 'CABIN', 'ΚΑΜΠ'],
            'Timing belt' => ['BELT', 'TIMING', 'ΧΡΟΝ'],
            'V-belt' => ['BELT', 'V-BELT'],
            'Spark plug' => ['SPARK', 'PLUG', 'ΜΠΟΥΖ'],
            'Thermostat' => ['THERMOSTAT', 'ΘΕΡΜ'],
            'Water pump' => ['PUMP', 'WATER', 'ΑΝΤΛ'],
            'Battery' => ['BATTERY', 'ΜΠΑΤ'],
            'Alternator' => ['ALTERNATOR', 'ΔΥΝΑΜ']
        ];
        
        return $keywords[$category] ?? [$category];
    }

    private function getAvailableBrands($query): array
    {
        return $query->distinct()
            ->pluck('ART_SUP_BRAND')
            ->filter()
            ->sort()
            ->values()
            ->take(50)
            ->toArray();
    }

    private function getCategoryDescription($categoryId): string
    {
        $descriptions = [
            'filters' => 'Φίλτρα για καθαρισμό αέρα, λαδιού, καυσίμων και καμπίνας',
            'belts' => 'Ιμάντες χρονισμού, βοηθητικών συστημάτων και εξαρτήματα ανάφλεξης',
            'mechanical' => 'Μηχανικά εξαρτήματα κινητήρα και συστημάτων',
            'electrical' => 'Ηλεκτρικά εξαρτήματα και συστήματα φόρτισης',
            'accessories' => 'Αξεσουάρ και εξαρτήματα εξωτερικής εμφάνισης'
        ];
        
        return $descriptions[$categoryId] ?? 'Κατηγορία ανταλλακτικών';
    }

    private function getPopularBrandsForCategory($categoryId): array
    {
        return ['BOSCH', 'MANN-FILTER', 'MAHLE', 'FEBI', 'SACHS'];
    }

    private function getPriceRangeForCategory($categoryId): array
    {
        return ['min' => 5.00, 'max' => 150.00, 'currency' => 'EUR'];
    }

    private function getArticleSpecifications($articleId): array
    {
        return [
            'weight' => '0.5 kg',
            'dimensions' => '10x5x3 cm',
            'material' => 'Steel/Plastic',
            'warranty_months' => 24
        ];
    }

    private function getArticleCompatibility($articleId): array
    {
        return [
            'compatible_vehicles' => 150,
            'vehicle_types' => ['PC'],
            'years_from' => 2010,
            'years_to' => 2024
        ];
    }

    private function getArticleImages($articleId): array
    {
        return [
            'thumbnail' => "https://images.example.com/articles/{$articleId}_thumb.jpg",
            'medium' => "https://images.example.com/articles/{$articleId}_medium.jpg",
            'large' => "https://images.example.com/articles/{$articleId}_large.jpg"
        ];
    }

    private function getArticlePriceInfo($articleId): array
    {
        return [
            'retail_price' => rand(10, 200),
            'wholesale_price' => rand(5, 150),
            'currency' => 'EUR',
            'vat_included' => true,
            'discount_available' => rand(0, 1) === 1
        ];
    }

    private function getRelatedArticles($articleId): array
    {
        return [
            ['id' => $articleId + 1, 'article_number' => 'REL001', 'brand' => 'BOSCH'],
            ['id' => $articleId + 2, 'article_number' => 'REL002', 'brand' => 'MANN']
        ];
    }

    private function getInstallationNotes($articleId): array
    {
        return [
            'difficulty_level' => 'Medium',
            'estimated_time' => '30-45 minutes',
            'tools_required' => ['Wrench set', 'Screwdriver'],
            'special_notes' => 'Ensure engine is cool before installation'
        ];
    }
}
