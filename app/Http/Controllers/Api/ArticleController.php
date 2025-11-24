<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    /**
     * Display a listing of articles with pagination and search
     */
    public function index(Request $request)
    {
        $query = Article::with('supplier');
        
        // Search by article number
        if ($request->has('article_nr')) {
            $query->where('ART_ARTICLE_NR', 'LIKE', '%' . $request->article_nr . '%');
        }
        
        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('ART_SUP_ID', $request->supplier_id);
        }
        
        // Filter by brand
        if ($request->has('brand')) {
            $query->where('ART_SUP_BRAND', 'LIKE', '%' . $request->brand . '%');
        }
        
        $perPage = $request->get('per_page', 15);
        $articles = $query->paginate($perPage);
        
        return response()->json($articles);
    }

    /**
     * Display the specified article
     */
    public function show(string $id)
    {
        $article = Article::with('supplier')->find($id);
        
        if (!$article) {
            return response()->json(['error' => 'Article not found'], 404);
        }
        
        return response()->json($article);
    }

    /**
     * Search articles by article number
     */
    public function searchByNumber(Request $request)
    {
        $request->validate([
            'article_nr' => 'required|string|min:3'
        ]);
        
        $articles = Article::with('supplier')
            ->where('ART_ARTICLE_NR', 'LIKE', '%' . $request->article_nr . '%')
            ->limit(50)
            ->get();
            
        return response()->json($articles);
    }

    /**
     * Get articles by supplier
     */
    public function bySupplier(string $supplierId)
    {
        $articles = Article::with('supplier')
            ->where('ART_SUP_ID', $supplierId)
            ->paginate(20);
            
        return response()->json($articles);
    }
}
