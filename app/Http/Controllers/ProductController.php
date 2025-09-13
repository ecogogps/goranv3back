<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 100);
        $search = $request->get('search');
        
        $query = Product::with(['categories', 'statuses'])
            ->where('status', 'publish');

        if ($search) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('product_categories.id', $request->get('category_id'));
            });
        }
        
        $products = $query->orderBy('name', 'asc')->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }


    public function show(int $id): JsonResponse
    {
        
        $product = Product::with(['categories', 'statuses'])
            ->where('id', $id)
            ->where('status', 'publish')
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }


    public function byCategory(int $categoryId, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 100);
        
        $products = Product::with(['categories', 'statuses'])
            ->whereHas('categories', function ($query) use ($categoryId) {
                $query->where('product_categories.id', $categoryId);
            })
            ->where('status', 'publish')
            ->orderBy('name', 'asc')
            ->paginate($perPage);

        return response()->json([
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }
}

