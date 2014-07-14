<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;

// replace with mechanism to retrieve EntityManager in your app
$entityManager = $app['orm.ems']['brain'];

return ConsoleRunner::createHelperSet($entityManager);
