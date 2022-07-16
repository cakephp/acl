<?php

use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {

    $routes->prefix('Admin', function ($routes) {
        $routes->fallbacks('DashedRoute');
    });
};
