<?php

\Cake\Routing\Router::prefix('Admin', function ($routes) {
    $routes->fallbacks('DashedRoute');
});
