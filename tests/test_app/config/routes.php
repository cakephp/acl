<?php

\Cake\Routing\Router::scope('/', function ($routes) {
    $routes->fallbacks('InflectedRoute');
});

\Cake\Routing\Router::prefix('admin', function ($routes) {
    $routes->plugin('TestPlugin', function ($routes) {
        $routes->fallbacks('InflectedRoute');
    });
    $routes->fallbacks('InflectedRoute');
});
