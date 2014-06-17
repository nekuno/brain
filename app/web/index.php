<?php

ini_set('display_errors', 0);

require_once __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../src/app.php';

$app['env'] = 'prod';

require __DIR__.'/../config/prod.php';
require __DIR__.'/../src/controllers.php';
require __DIR__.'/../src/models.php';
require __DIR__.'/../src/routing.php';

$app->run();
