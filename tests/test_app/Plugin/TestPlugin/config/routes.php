<?php

\Cake\Routing\Router::plugin('TestPlugin', function ($routes) {
    $routes->prefix('admin', function ($routes) {
        $routes->fallbacks('DashedRoute');
    });
});
