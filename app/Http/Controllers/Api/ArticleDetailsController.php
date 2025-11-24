<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ArticleDetailsController extends Controller
{
    /**
     * Get enhanced article details with all available information
     */
    public function getDetails(Request $request, $articleId)
    {
        $startTime = microtime(true);
        
        try {
            $cacheKey = "article_details_enhanced_{$articleId}";
            
            $articleDetails = Cache::remember($cacheKey, 1800, function() use ($articleId) {
                // Get basic article info with barcode
                $article = DB::table('ARTICLES as a')
                    ->select([
                        'a.*',
                        'al.ARL_SEARCH_NUMBER as BARCODE_EAN'
                    ])
                    ->leftJoin('ART_LOOKUP as al', function($join) {
                        $join->on('a.ART_ID', '=', 'al.ARL_ART_ID')
                             ->where('al.ARL_TYPE', '=', 'EAN');
                    })
                    ->where('a.ART_ID', $articleId)
                    ->first();
                
                if (!$article) {
                    return null;
                }
                
                // Get article criteria (specifications) - simplified for performance
                $criteriaRaw = DB::table('ARTICLE_CRITERIA as ac')
                    ->select([
                        'ac.ACR_CRI_ID',
                        'ac.ACR_VALUE',
                        'ac.ACR_DES_ID',
                        'ac.ACR_DISPLAY'
                    ])
                    ->where('ac.ACR_ART_ID', $articleId)
                    ->limit(20)
                    ->get();

                // Get criteria names in a separate optimized query
                $criteriaIds = $criteriaRaw->pluck('ACR_CRI_ID')->unique();
                $criteriaNames = [];
                
                if ($criteriaIds->isNotEmpty()) {
                    $namesQuery = DB::table('CRITERIA as c')
                        ->select([
                            'c.CRI_ID',
                            'c.CRI_TYPE', 
                            'dt.TEX_TEXT as CRITERIA_NAME'
                        ])
                        ->leftJoin('TEXT_DESIGNATIONS as td', 'c.CRI_DES_ID', '=', 'td.DES_ID')
                        ->leftJoin('DES_TEXTS as dt', 'td.DES_TEX_ID', '=', 'dt.TEX_ID')
                        ->whereIn('c.CRI_ID', $criteriaIds)
                        ->where('td.DES_LNG_ID', 255)
                        ->get();
                    
                    foreach ($namesQuery as $name) {
                        $criteriaNames[$name->CRI_ID] = [
                            'name' => $name->CRITERIA_NAME,
                            'type' => $name->CRI_TYPE
                        ];
                    }
                }

                // Merge criteria data with names
                $criteria = $criteriaRaw->map(function($criteria) use ($criteriaNames) {
                    $criteria->CRITERIA_NAME = $criteriaNames[$criteria->ACR_CRI_ID]['name'] ?? null;
                    $criteria->CRI_TYPE = $criteriaNames[$criteria->ACR_CRI_ID]['type'] ?? null;
                    return $criteria;
                });
                
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
                    ->limit(5)
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
                    ->limit(5)
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
                            'criteria_name' => $criteria->CRITERIA_NAME ?: "Criteria {$criteria->ACR_CRI_ID}",
                            'criteria_type' => $criteria->CRI_TYPE ?: 'Unknown',
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
                    'cached' => Cache::has($cacheKey)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Enhanced article details error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving article details: ' . $e->getMessage()
            ], 500);
        }
    }
}
