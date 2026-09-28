<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// The dashboard lives at /oi
$routes->addRedirect('/', 'oi');

$routes->get('oi', 'OiController::index');
$routes->get('oi/json', 'OiController::json');
$routes->get('oi/signals', 'OiController::signals');
$routes->get('oi/metrics', 'OiController::metrics');   // <— new
$routes->get('oi/daywise', 'OiController::daywise');   // NEW daywise table
$routes->get('oi/intraday', 'OiController::intraday'); // NEW addons summary
$routes->get('oi/expiries', 'OiController::expiries');   // NEW
$routes->get('oi/healthz',  'OiController::healthz');    // NEW
$routes->get('price/breakouts', 'PriceController::breakouts');   // ?symbol=NIFTY&tf=5m&lookback=10
$routes->get('oi/track', 'OiController::track'); 
$routes->get('oi/index-eod/export/(:num)', 'OiController::exportIndexEodYear/$1');
$routes->get('oi/cache/purge', 'OiController::purgeIndexCache');

