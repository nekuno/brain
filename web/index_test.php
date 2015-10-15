<?php

use Symfony\Component\Debug\Debug;

putenv("APP_ENV=test");

$neo4jVersion = '2.2.5';

require_once __DIR__.'/../bootstrap-neo4j-testing.php';
require_once __DIR__.'/../vendor/autoload.php';

Debug::enable();


/* TODO: Uncomment to delete Neo4j after all tests
register_shutdown_function('removeNeo4j', $neo4jVersion);

function removeNeo4j($neo4jVersion)
{
    exec("rm -rf neo4j-community-{$neo4jVersion}");
}
*/
