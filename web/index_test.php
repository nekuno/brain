<?php

use Symfony\Component\Debug\Debug;

putenv("APP_ENV=test");

require_once __DIR__.'/../vendor/autoload.php';

Debug::enable();
