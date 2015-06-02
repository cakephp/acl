<?php

\Cake\Routing\Router::prefix('admin', function ($routes) {
    $routes->fallbacks('InflectedRoute');
});
