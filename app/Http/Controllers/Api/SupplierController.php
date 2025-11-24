<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers
     */
    public function index(Request $request)
    {
        $query = Supplier::query();
        
        // Search by brand name
        if ($request->has('brand')) {
            $query->where('SUP_BRAND', 'LIKE', '%' . $request->brand . '%');
        }
        
        // Search by full name
        if ($request->has('name')) {
            $query->where('SUP_FULL_NAME', 'LIKE', '%' . $request->name . '%');
        }
        
        $perPage = $request->get('per_page', 20);
        $suppliers = $query->paginate($perPage);
        
        return response()->json($suppliers);
    }

    /**
     * Display the specified supplier
     */
    public function show(string $id)
    {
        $supplier = Supplier::find($id);
        
        if (!$supplier) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }
        
        return response()->json($supplier);
    }

    /**
     * Get supplier with their articles
     */
    public function withArticles(string $id, Request $request)
    {
        $supplier = Supplier::with(['articles' => function($query) use ($request) {
            if ($request->has('article_nr')) {
                $query->where('ART_ARTICLE_NR', 'LIKE', '%' . $request->article_nr . '%');
            }
            $query->limit($request->get('limit', 50));
        }])->find($id);
        
        if (!$supplier) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }
        
        return response()->json($supplier);
    }

    /**
     * Search suppliers by brand
     */
    public function searchByBrand(Request $request)
    {
        $request->validate([
            'brand' => 'required|string|min:2'
        ]);
        
        $suppliers = Supplier::where('SUP_BRAND', 'LIKE', '%' . $request->brand . '%')
            ->limit(20)
            ->get();
            
        return response()->json($suppliers);
    }
}
