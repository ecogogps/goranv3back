<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductStatusController;

// --- Rutas Públicas de Autenticación ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Ruta para crear un administrador (proteger o eliminar en producción)
Route::post('/admin/register', [AuthController::class, 'createAdmin']);

// --- Rutas Públicas de Productos y Categorías ---
// Estas rutas están disponibles sin autenticación para consulta pública
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']); // GET /api/products
    Route::get('/{id}', [ProductController::class, 'show']); // GET /api/products/123
    Route::get('/category/{categoryId}', [ProductController::class, 'byCategory']); // GET /api/products/category/1
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']); // GET /api/categories
    Route::get('/tree', [CategoryController::class, 'tree']); // GET /api/categories/tree (jerarquía)
    Route::get('/{id}', [CategoryController::class, 'show']); // GET /api/categories/1
    Route::get('/{id}/products', [CategoryController::class, 'products']); // GET /api/categories/1/products
});

// --- Rutas Protegidas por Sanctum ---
Route::middleware('auth:sanctum')->group(function () {
    
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/statuses', [ProductStatusController::class, 'index']);
    
    // --- Rutas de Productos y Categorías (Solo usuarios autenticados) ---
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']); // GET /api/products
        Route::get('/{id}', [ProductController::class, 'show']); // GET /api/products/123
        Route::get('/category/{categoryId}', [ProductController::class, 'byCategory']); // GET /api/products/category/1
    });

    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']); // GET /api/categories
        Route::get('/tree', [CategoryController::class, 'tree']); // GET /api/categories/tree (jerarquía)
        Route::get('/{id}', [CategoryController::class, 'show']); // GET /api/categories/1
        Route::get('/{id}/products', [CategoryController::class, 'products']); // GET /api/categories/1/products
    });
    
    // --- Rutas solo para Administradores ---
    Route::prefix('admin')->middleware('auth.admin')->group(function () {
        // Gestión de usuarios
        Route::get('/pending-requests', [AdminController::class, 'pendingRequests']);
        Route::put('/clients/{client}/approve', [AdminController::class, 'approveClient']);
        Route::put('/clients/{client}/reject', [AdminController::class, 'rejectClient']);
        
        // Gestión de productos (solo administradores)
        Route::prefix('products')->group(function () {
            Route::post('/', [ProductController::class, 'store']); // POST /api/admin/products
            Route::put('/{id}', [ProductController::class, 'update']); // PUT /api/admin/products/123
            Route::delete('/{id}', [ProductController::class, 'destroy']); // DELETE /api/admin/products/123
            Route::put('/{id}/status', [ProductController::class, 'updateStatus']); // PUT /api/admin/products/123/status
        });
        
        // Gestión de categorías (solo administradores)
        Route::prefix('categories')->group(function () {
            Route::post('/', [CategoryController::class, 'store']); // POST /api/admin/categories
            Route::put('/{id}', [CategoryController::class, 'update']); // PUT /api/admin/categories/1
            Route::delete('/{id}', [CategoryController::class, 'destroy']); // DELETE /api/admin/categories/1
            Route::put('/{id}/status', [CategoryController::class, 'updateStatus']); // PUT /api/admin/categories/1/status
        });
    });
    
    // --- Rutas para Clientes Autenticados ---
    Route::prefix('client')->middleware('auth.client')->group(function () {
        // Información del cliente
        Route::get('/profile', function(Request $request) {
            return $request->user()->client;
        });

        
        // Carrito de compras
        Route::prefix('cart')->group(function () {
            Route::get('/', [ProductController::class, 'getCart']); // GET /api/client/cart
            Route::post('/add/{productId}', [ProductController::class, 'addToCart']); // POST /api/client/cart/add/123
            Route::put('/update/{productId}', [ProductController::class, 'updateCartItem']); // PUT /api/client/cart/update/123
            Route::delete('/remove/{productId}', [ProductController::class, 'removeFromCart']); // DELETE /api/client/cart/remove/123
        });
    });
    
    // --- Rutas Generales para Usuarios Autenticados ---
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
