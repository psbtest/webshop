<?php

// Category routes
$router->get('/categories', 'CategoryController@index');
$router->get('/categories/{slug}', 'CategoryController@show');

// Cart routes
$router->get('/cart', 'CartController@show');
$router->post('/cart/add', 'CartController@add');
$router->post('/cart/update', 'CartController@update');
$router->post('/cart/remove', 'CartController@remove');
$router->delete('/cart/clear', 'CartController@clear');
$router->get('/cart/count', 'CartController@count');

// Product routes (if not already defined)
$router->get('/products', 'ProductController@index');
$router->get('/products/{slug}', 'ProductController@show');
$router->get('/products/search', 'ProductController@search');

// API routes
$router->get('/api/search/suggestions', 'Api\SearchApiController@suggestions');
$router->get('/api/cart/status', 'Api\CartApiController@status');

// Checkout routes
$router->get('/checkout', 'OrderController@checkout');
$router->post('/checkout', 'OrderController@processCheckout');
$router->get('/orders/success/{id}', 'OrderController@success');
$router->get('/orders/failed/{id}', 'OrderController@failed');

