<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
   
    public function index(): JsonResponse
    {
        $categories = ProductCategory::orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    
    public function show(int $id): JsonResponse
    {
        $category = ProductCategory::with(['children', 'parent'])
            ->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    
    public function products(int $id): JsonResponse
    {
        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        $products = $category->products()
            ->where('status', 'publish')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'products' => $products
            ]
        ]);
    }

    
    public function tree(): JsonResponse
    {
        // Obtener solo categorías padre (sin parent_id)
        $parentCategories = ProductCategory::whereNull('parent_id')
            ->with($this->getNestedCategoryRelations())
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parentCategories
        ]);
    }

    
    public function treeRecursive(): JsonResponse
    {
        $parentCategories = ProductCategory::whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parentCategories
        ]);
    }

    
    public function treeFlat(): JsonResponse
    {
        $categories = ProductCategory::with(['parent'])
            ->orderBy('parent_id', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $tree = $this->buildTree($categories);

        return response()->json([
            'success' => true,
            'data' => $tree
        ]);
    }

    /**
     * hasta 5 niveles de profundidad
     */
    private function getNestedCategoryRelations(): array
    {
        return [
            'children' => function($query) {
                $query->orderBy('name', 'asc');
            },
            'children.children' => function($query) {
                $query->orderBy('name', 'asc');
            },
            'children.children.children' => function($query) {
                $query->orderBy('name', 'asc');
            },
            'children.children.children.children' => function($query) {
                $query->orderBy('name', 'asc');
            },
            'children.children.children.children.children' => function($query) {
                $query->orderBy('name', 'asc');
            },
        ];
    }

  
    private function buildTree($categories, $parentId = null): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildTree($categories, $category->id);
                if (!empty($children)) {
                    $category->children = $children;
                }
                $tree[] = $category;
            }
        }

        return $tree;
    }

   
    public function treeWithProductCount(): JsonResponse
    {
        $parentCategories = ProductCategory::whereNull('parent_id')
            ->withCount(['products' => function($query) {
                $query->where('status', 'publish');
            }])
            ->with([
                'children' => function($query) {
                    $query->withCount(['products' => function($subQuery) {
                        $subQuery->where('status', 'publish');
                    }])->orderBy('name', 'asc');
                }
            ])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $parentCategories
        ]);
    }
}
