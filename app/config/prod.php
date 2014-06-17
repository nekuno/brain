<?php

// configure your app for the production environment


$app['twig.path'] = array(__DIR__.'/../templates');
$app['twig.options'] = array('cache' => __DIR__.'/../var/cache/twig');

$app['neo4j.host'] = 'qnoow.sb02.stations.graphenedb.com';
$app['neo4j.port'] = 24789;
$app['neo4j.user'] = 'qnoow';
$app['neo4j.pass'] = 'jgysJFcDujHaVrdP2DC2';


