<?php

use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {

    $routes->plugin('TestPlugin', function ($routes) {
        $routes->prefix('admin', function ($routes) {
            $routes->fallbacks('DashedRoute');
        });
    });
};

