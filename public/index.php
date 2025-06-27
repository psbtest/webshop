<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Controllers\HomeController;
use App\Controllers\ProductController;
use App\Controllers\CategoryController;
use App\Controllers\CartController;
use App\Controllers\OrderController;
use App\Controllers\PageController;
use App\Controllers\Admin\AdminController;
use App\Controllers\Admin\AdminProductController;
use App\Controllers\Admin\AdminCategoryController;
use App\Controllers\Admin\AdminOrderController;
use App\Controllers\Admin\AdminPageController;
use App\Controllers\Api\OrderApiController;
use App\Middleware\AdminMiddleware;
use Bramus\Router\Router;

session_start();

$app = new Application();
$router = new Router();

// Public routes
$router->get('/', [HomeController::class, 'index']);
$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/(\d+)', [ProductController::class, 'show']);
$router->get('/categories', [CategoryController::class, 'index']);
$router->get('/categories/(\d+)', [CategoryController::class, 'show']);
$router->get('/pages/([a-zA-Z0-9-_]+)', [PageController::class, 'show']);

// Cart routes
$router->post('/cart/add', [CartController::class, 'add']);
$router->get('/cart', [CartController::class, 'show']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);

// Order routes
$router->post('/checkout', [OrderController::class, 'checkout']);
$router->get('/order/success/(\d+)', [OrderController::class, 'success']);

// Admin routes
$router->mount('/admin', function() use ($router) {
    $router->before('GET|POST|PUT|DELETE', '/.*', [AdminMiddleware::class, 'handle']);
    
    $router->get('/', [AdminController::class, 'dashboard']);
    $router->get('/login', [AdminController::class, 'login']);
    $router->post('/login', [AdminController::class, 'authenticate']);
    $router->post('/logout', [AdminController::class, 'logout']);
    
    // Products
    $router->get('/products', [AdminProductController::class, 'index']);
    $router->get('/products/create', [AdminProductController::class, 'create']);
    $router->post('/products', [AdminProductController::class, 'store']);
    $router->get('/products/(\d+)/edit', [AdminProductController::class, 'edit']);
    $router->put('/products/(\d+)', [AdminProductController::class, 'update']);
    $router->delete('/products/(\d+)', [AdminProductController::class, 'delete']);
    
    // Categories
    $router->get('/categories', [AdminCategoryController::class, 'index']);
    $router->get('/categories/create', [AdminCategoryController::class, 'create']);
    $router->post('/categories', [AdminCategoryController::class, 'store']);
    $router->get('/categories/(\d+)/edit', [AdminCategoryController::class, 'edit']);
    $router->put('/categories/(\d+)', [AdminCategoryController::class, 'update']);
    $router->delete('/categories/(\d+)', [AdminCategoryController::class, 'delete']);
    
    // Orders
    $router->get('/orders', [AdminOrderController::class, 'index']);
    $router->get('/orders/(\d+)', [AdminOrderController::class, 'show']);
    $router->put('/orders/(\d+)/status', [AdminOrderController::class, 'update_status']);
    
    // Pages
    $router->get('/pages', [AdminPageController::class, 'index']);
    $router->get('/pages/create', [AdminPageController::class, 'create']);
    $router->post('/pages', [AdminPageController::class, 'store']);
    $router->get('/pages/(\d+)/edit', [AdminPageController::class, 'edit']);
    $router->put('/pages/(\d+)', [AdminPageController::class, 'update']);
    $router->delete('/pages/(\d+)', [AdminPageController::class, 'delete']);
});

// API routes
$router->mount('/api', function() use ($router) {
    $router->get('/orders', [OrderApiController::class, 'index']);
    $router->get('/orders/(\d+)', [OrderApiController::class, 'show']);
    $router->post('/webhook/orders', [OrderApiController::class, 'webhook']);
});

$router->run();
