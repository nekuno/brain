<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;

// replace with mechanism to retrieve EntityManager in your app
$app = require __DIR__.'/../src/app.php';
$entityManager = $app['orm.ems']['mysql_brain'];

return ConsoleRunner::createHelperSet($entityManager);
