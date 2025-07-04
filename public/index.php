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

// Middleware function
function applyMiddleware(callable $middleware) {
    return function() use ($middleware) {
        $middleware();
    };
}

// Public routes
$router->get('/', function() {
    $controller = new HomeController();
    $controller->index();
});

// Products Routes
$router->get('/products', function() {
    $controller = new ProductController();
    $controller->index();
});

$router->get('/products/(\d+)', function($id) {
    $controller = new ProductController();
    $controller->show((int) $id);
});

// Categories Routes
$router->get('/categories', function() {
    $controller = new CategoryController();
    $controller->index();
});

$router->get('/categories/(\d+)', function($id) {
    $controller = new CategoryController();
    $controller->show((int) $id);
});

// Cart Routes
$router->post('/cart/add', function() {
    $controller = new CartController();
    $controller->add();
});

$router->get('/cart', function() {
    $controller = new CartController();
    $controller->show();
});

// Order Routes
$router->get('/checkout', function() {
    $controller = new OrderController();
    $controller->checkout();
});

$router->post('/checkout', function() {
    $controller = new OrderController();
    $controller->processCheckout();
});

// Pages Route
$router->get('/pages/([a-zA-Z0-9-_]+)', function($slug) {
    $controller = new PageController();
    $controller->show($slug);
});


$router->mount('/admin', function() use ($router) {
    // Authentication routes without middleware
    $router->get('/login', function() {
        $controller = new AdminController();
        $controller->login();
    });

    $router->post('/login', function() {
        $controller = new AdminController();
        $controller->authenticate();
    });

    $router->get('/logout', function() {
        $controller = new AdminController();
        $controller->logout();
    });

    // Apply middleware to other admin routes
    $router->before('GET|POST|PUT|DELETE', '/.*', [AdminMiddleware::class, 'handle']);

    // Dashboard
    $router->get('/', function() {
        $controller = new AdminController();
        $controller->dashboard();
    });

    // Products
    $router->get('/products', function() {
        $controller = new AdminProductController();
        $controller->index();
    });

    $router->get('/products/create', function() {
        $controller = new AdminProductController();
        $controller->create();
    });

    $router->post('/products', function() {
        $controller = new AdminProductController();
        $controller->store();
    });

    $router->get('/products/(\d+)/edit', function($id) {
        $controller = new AdminProductController();
        $controller->edit((int)$id);
    });

    $router->put('/products/(\d+)', function($id) {
        $controller = new AdminProductController();
        $controller->update((int)$id);
    });

    $router->delete('/products/(\d+)', function($id) {
        $controller = new AdminProductController();
        $controller->delete((int)$id);
    });

    // Categories
    $router->get('/categories', function() {
        $controller = new AdminCategoryController();
        $controller->index();
    });

    $router->get('/categories/create', function() {
        $controller = new AdminCategoryController();
        $controller->create();
    });

    $router->post('/categories', function() {
        $controller = new AdminCategoryController();
        $controller->store();
    });

    $router->get('/categories/(\d+)/edit', function($id) {
        $controller = new AdminCategoryController();
        $controller->edit((int)$id);
    });

    $router->put('/categories/(\d+)', function($id) {
        $controller = new AdminCategoryController();
        $controller->update((int)$id);
    });

    $router->delete('/categories/(\d+)', function($id) {
        $controller = new AdminCategoryController();
        $controller->delete((int)$id);
    });

    // Orders
    $router->get('/orders', function() {
        $controller = new AdminOrderController();
        $controller->index();
    });

    $router->get('/orders/(\d+)', function($id) {
        $controller = new AdminOrderController();
        $controller->show((int)$id);
    });

    // Pages
    $router->get('/pages', function() {
        $controller = new AdminPageController();
        $controller->index();
    });

    $router->get('/pages/create', function() {
        $controller = new AdminPageController();
        $controller->create();
    });

    $router->post('/pages', function() {
        $controller = new AdminPageController();
        $controller->store();
    });

    $router->get('/pages/(\d+)/edit', function($id) {
        $controller = new AdminPageController();
        $controller->edit((int)$id);
    });

    $router->put('/pages/(\d+)', function($id) {
        $controller = new AdminPageController();
        $controller->update((int)$id);
    });

    $router->delete('/pages/(\d+)', function($id) {
        $controller = new AdminPageController();
        $controller->delete((int)$id);
    });
});

// API Routes
$router->mount('/api', function() use ($router) {
    $router->get('/orders', function() {
        $controller = new OrderApiController();
        $controller->index();
    });
});

$router->run();
