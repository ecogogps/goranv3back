<?php

namespace App\Services;

use Automattic\WooCommerce\Client;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WooCommerceSyncService
{
    protected $woocommerce;

    public function __construct()
    {
        $this->woocommerce = new Client(
            config('woocommerce.store_url'),
            config('woocommerce.client_key'),
            config('woocommerce.client_secret'),
            [
                'version' => config('woocommerce.api_version'),
                'timeout' => 30,
            ]
        );
    }

    /**
     * Get the total count of products and categories from WooCommerce.
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        try {
            // Method 1: Try to get count from headers first
            $this->woocommerce->get('products', ['per_page' => 1, 'status' => 'publish']);
            $productHeaders = $this->woocommerce->http->getResponse()->getHeaders();
            $productCount = isset($productHeaders['X-WP-Total']) ? (int) $productHeaders['X-WP-Total'][0] : null;

            $this->woocommerce->get('products/categories', ['per_page' => 1]);
            $categoryHeaders = $this->woocommerce->http->getResponse()->getHeaders();
            $categoryCount = isset($categoryHeaders['X-WP-Total']) ? (int) $categoryHeaders['X-WP-Total'][0] : null;

            // Method 2: If headers don't work, count manually by fetching all items
            if ($productCount === null) {
                echo "X-WP-Total header not available for products, counting manually...\n";
                $productCount = $this->countProductsManually();
            }

            if ($categoryCount === null) {
                echo "X-WP-Total header not available for categories, counting manually...\n";
                $categoryCount = $this->countCategoriesManually();
            }

            return [
                'products' => $productCount,
                'categories' => $categoryCount,
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to get WooCommerce counts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Count products manually by fetching all pages
     */
    private function countProductsManually(): int
    {
        $totalCount = 0;
        $page = 1;
        $perPage = 100;

        do {
            $products = $this->woocommerce->get('products', [
                'per_page' => $perPage,
                'page' => $page,
                'status' => 'publish'
            ]);
            
            $totalCount += count($products);
            $page++;
            
            // Safety break to avoid infinite loops
            if ($page > 50) { // Max 5000 products
                Log::warning('Product count stopped at page 50 to prevent infinite loop');
                break;
            }
            
        } while (count($products) === $perPage);

        return $totalCount;
    }

    /**
     * Count categories manually by fetching all pages
     */
    private function countCategoriesManually(): int
    {
        $totalCount = 0;
        $page = 1;
        $perPage = 100;

        do {
            $categories = $this->woocommerce->get('products/categories', [
                'per_page' => $perPage,
                'page' => $page
            ]);
            
            $totalCount += count($categories);
            $page++;
            
            // Safety break to avoid infinite loops
            if ($page > 20) { // Max 2000 categories should be enough
                Log::warning('Category count stopped at page 20 to prevent infinite loop');
                break;
            }
            
        } while (count($categories) === $perPage);

        return $totalCount;
    }

    /**
     * Sync all data with enhanced options
     */
    public function syncAll(
        ?callable $progressCallback = null,
        bool $syncCategories = true,
        bool $syncProducts = true,
        bool $force = false,
        int $batchSize = 50
    ): void {
        Log::info('Starting WooCommerce synchronization...', [
            'sync_categories' => $syncCategories,
            'sync_products' => $syncProducts,
            'force' => $force,
            'batch_size' => $batchSize
        ]);

        DB::beginTransaction();
        try {
            if ($syncCategories) {
                $this->syncCategories($progressCallback, $force, $batchSize);
            }

            if ($syncProducts) {
                $this->syncProducts($progressCallback, $force, $batchSize);
            }

            DB::commit();
            Log::info('WooCommerce synchronization completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('WooCommerce synchronization failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync categories with enhanced progress tracking
     */
    protected function syncCategories(?callable $progressCallback = null, bool $force = false, int $batchSize = 50): void
    {
        Log::info('Syncing WooCommerce categories...');
        $page = 1;
        $perPage = min($batchSize, 100); // WooCommerce API max is 100

        // Prepare sync parameters
        $params = [
            'per_page' => $perPage,
            'orderby' => 'id',
            'order' => 'asc'
        ];

        // Add date filter if not forcing full sync
        if (!$force) {
            $lastSync = $this->getLastSyncTime('categories');
            if ($lastSync) {
                $params['modified_after'] = $lastSync->toISOString();
            }
        }

        do {
            try {
                $params['page'] = $page;
                $categories = $this->woocommerce->get('products/categories', $params);

                if (empty($categories)) {
                    break;
                }

                foreach ($categories as $wooCategory) {
                    try {
                        $this->syncSingleCategory($wooCategory);

                        if ($progressCallback) {
                            $progressCallback('categories', 'processed', [
                                'name' => $wooCategory->name,
                                'id' => $wooCategory->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to sync category {$wooCategory->id}: " . $e->getMessage());
                        
                        if ($progressCallback) {
                            $progressCallback('categories', 'error', [
                                'name' => $wooCategory->name ?? "Category {$wooCategory->id}",
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                $page++;
                
                // Pequeña pausa para no sobrecargar la API
                if (count($categories) === $perPage) {
                    usleep(250000); // 250ms
                }
                
            } catch (\Exception $e) {
                Log::error("Failed to fetch categories page {$page}: " . $e->getMessage());
                throw $e;
            }
            
        } while (count($categories) === $perPage);

        $this->updateLastSyncTime('categories');
        Log::info('WooCommerce categories synced.');
    }

    /**
     * Sync products with enhanced progress tracking
     */
    protected function syncProducts(?callable $progressCallback = null, bool $force = false, int $batchSize = 50): void
    {
        Log::info('Syncing WooCommerce products...');
        $page = 1;
        $perPage = min($batchSize, 100); // WooCommerce API max is 100

        // Prepare sync parameters
        $params = [
            'per_page' => $perPage,
            'status' => 'publish',
            'orderby' => 'id',
            'order' => 'asc'
        ];

        // Add date filter if not forcing full sync
        if (!$force) {
            $lastSync = $this->getLastSyncTime('products');
            if ($lastSync) {
                $params['modified_after'] = $lastSync->toISOString();
            }
        }

        do {
            try {
                $params['page'] = $page;
                $products = $this->woocommerce->get('products', $params);

                if (empty($products)) {
                    break;
                }

                foreach ($products as $wooProduct) {
                    try {
                        $this->syncSingleProduct($wooProduct);

                        if ($progressCallback) {
                            $progressCallback('products', 'processed', [
                                'name' => $wooProduct->name,
                                'id' => $wooProduct->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error("Failed to sync product {$wooProduct->id}: " . $e->getMessage());
                        
                        if ($progressCallback) {
                            $progressCallback('products', 'error', [
                                'name' => $wooProduct->name ?? "Product {$wooProduct->id}",
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                $page++;
                
                // Pequeña pausa para no sobrecargar la API
                if (count($products) === $perPage) {
                    usleep(250000); // 250ms
                }
                
            } catch (\Exception $e) {
                Log::error("Failed to fetch products page {$page}: " . $e->getMessage());
                throw $e;
            }
            
        } while (count($products) === $perPage);

        $this->updateLastSyncTime('products');
        Log::info('WooCommerce products synced.');
    }

    /**
     * Sync a single category
     */
    protected function syncSingleCategory(object $wooCategory): void
    {
        ProductCategory::updateOrCreate(
            ['woocommerce_id' => $wooCategory->id],
            [
                'name' => $wooCategory->name,
                'slug' => $wooCategory->slug,
                'parent_id' => $wooCategory->parent > 0 
                    ? ProductCategory::where('woocommerce_id', $wooCategory->parent)->first()?->id 
                    : null,
                'description' => $wooCategory->description ?? null,
                'display' => $wooCategory->display ?? 'default',
                'image' => isset($wooCategory->image->src) ? $wooCategory->image->src : null,
                'count' => $wooCategory->count ?? 0,
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * Sync a single product
     */
    protected function syncSingleProduct(object $wooProduct): void
    {
        // Process images
        $images = collect($wooProduct->images)->map(function ($image) {
            return [
                'id' => $image->id,
                'src' => $image->src,
                'name' => $image->name,
                'alt' => $image->alt
            ];
        })->toArray();

        // Handle price
        $price = !empty($wooProduct->price) ? (float) $wooProduct->price : null;
        $regularPrice = !empty($wooProduct->regular_price) ? (float) $wooProduct->regular_price : null;
        $salePrice = !empty($wooProduct->sale_price) ? (float) $wooProduct->sale_price : null;

        // Handle attributes
        $attributes = collect($wooProduct->attributes)->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'options' => $attribute->options,
                'visible' => $attribute->visible,
                'variation' => $attribute->variation
            ];
        })->toArray();

        $product = Product::updateOrCreate(
            ['woocommerce_id' => $wooProduct->id],
            [
                'name' => $wooProduct->name,
                'slug' => $wooProduct->slug,
                'type' => $wooProduct->type,
                'status' => $wooProduct->status,
                'featured' => $wooProduct->featured ?? false,
                'catalog_visibility' => $wooProduct->catalog_visibility ?? 'visible',
                'price' => $price,
                'regular_price' => $regularPrice,
                'sale_price' => $salePrice,
                'on_sale' => $wooProduct->on_sale ?? false,
                'stock_status' => $wooProduct->stock_status ?? 'instock',
                'stock_quantity' => $wooProduct->stock_quantity,
                'manage_stock' => $wooProduct->manage_stock ?? false,
                'sku' => $wooProduct->sku,
                'weight' => $wooProduct->weight,
                'dimensions' => [
                    'length' => $wooProduct->dimensions->length ?? null,
                    'width' => $wooProduct->dimensions->width ?? null,
                    'height' => $wooProduct->dimensions->height ?? null,
                ],
                'short_description' => $wooProduct->short_description,
                'description' => $wooProduct->description,
                'images' => $images,
                'attributes' => $attributes,
                'total_sales' => $wooProduct->total_sales ?? 0,
                'tax_status' => $wooProduct->tax_status ?? 'taxable',
                'tax_class' => $wooProduct->tax_class ?? '',
                'last_synced_at' => now(),
            ]
        );

        // Sync product categories
        $this->syncProductCategories($product, $wooProduct->categories);
    }

    /**
     * Sync product categories relationship
     */
    protected function syncProductCategories(Product $product, array $wooCategories): void
    {
        $categoryIds = [];
        foreach ($wooCategories as $wooCategory) {
            $localCategory = ProductCategory::where('woocommerce_id', $wooCategory->id)->first();
            if ($localCategory) {
                $categoryIds[] = $localCategory->id;
            }
        }
        $product->categories()->sync($categoryIds);
    }

    /**
     * Get last sync time for a specific type
     */
    protected function getLastSyncTime(string $type): ?Carbon
    {
        // You might want to store this in a settings table or cache
        // For now, returning null to always do full sync
        return null;
    }

    /**
     * Update last sync time for a specific type
     */
    protected function updateLastSyncTime(string $type): void
    {
        // Store the sync time in a settings table or cache
        // cache()->put("woocommerce_last_sync_{$type}", now(), 3600);
    }
}
