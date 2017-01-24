<?php

namespace Tests\API;

use Console\Command\Neo4jProfileOptionsCommand;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\SchemaTool;
use Everyman\Neo4j\Cypher\Query;
use Model\User;
use Silex\Application;
use Silex\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\Response;
use Service\AuthService;
use Symfony\Component\Console\Application as ConsoleApplication;

abstract class APITest extends WebTestCase
{
    protected $app;

    public function createApplication()
    {
        $app = require __DIR__ . '/../../app.php';
        require __DIR__ . '/../../controllers.php';
        require __DIR__ . '/../../routing.php';
        $app['debug'] = true;
        unset($app['exception_handler']);
        $app['session.test'] = true;

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        /* @var $app Application */
        $app = $this->app;
        // Clean the database
        $query = new Query($app['neo4j.client'], 'MATCH (n) OPTIONAL MATCH n-[r]-m DELETE r, n, m;');
        $query->getResultSet();

        $em = $app['orm.ems']['mysql_brain'];
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        $metadatas = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadatas);

        /* @var $bm Connection */
        $bm = $app['dbs']['mysql_brain'];
        $bm->executeQuery('DROP TABLE IF EXISTS chat_message');
        $bm->executeQuery('CREATE TABLE chat_message (id INTEGER PRIMARY KEY NOT NULL, text VARCHAR(255) NOT NULL, createdAt DATETIME NOT NULL, readed TINYINT(1) NOT NULL, user_from INT DEFAULT NULL, user_to INT DEFAULT NULL)');
    }

    protected function getResponseByRoute($route, $method = 'GET', $data = array(), $userId = null)
    {
        $headers = array('CONTENT_TYPE' => 'application/json');
        if ($userId) {
            $headers += $this->tryToGetJwtByUserId($userId);
        }

        $client = static::createClient();
        $client->request($method, $route, array(), array(), $headers, json_encode($data));

        return $client->getResponse();
    }

    protected function assertJsonResponse(Response $response, $statusCode = 200, $context = "Undefined")
    {
        $this->assertStatusCode($response, $statusCode, $context);

        $this->assertJson($response->getContent(), $context . " response - Not a valid JSON string");

        $formattedResponse = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $formattedResponse, $context . " response - JSON can't be converted into an array");

        return $formattedResponse;
    }

    protected function assertStatusCode(Response $response, $statusCode = 200, $context = "Undefined")
    {
        $this->assertEquals($statusCode, $response->getStatusCode(), $context . " response - Status Code is " . $response->getStatusCode() . ", expected " . $statusCode);
    }

    protected function assertValidationErrorFormat($exception)
    {
        $this->assertArrayHasKey('error', $exception, "Validation exception has not error key");
        $this->assertArrayHasKey('validationErrors', $exception, "Validation exception has not validationErrors key");
        $this->assertEquals('Validation error', $exception['error'], "error key is not Validation error");
    }

    protected function runCommand($commandString)
    {
        $application = new ConsoleApplication();
        $application->add(new Neo4jProfileOptionsCommand($this->app));

        $command = $application->find($commandString);
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        return $commandTester->getDisplay();
    }

    protected function runProfileOptionsCommand()
    {
        return $this->runCommand('neo4j:profile-options');
    }

    protected function loginUser($userData)
    {
        return $this->getResponseByRoute('/login', 'OPTIONS', $userData);
    }

    protected function createUser($userData)
    {
        return $this->getResponseByRoute('/users', 'POST', $userData);
    }

    protected function createAndLoginUserA()
    {
        $userData = $this->getUserAFixtures();
        $this->createUser($userData);
        $this->loginUser($userData);
    }

    protected function createAndLoginUserB()
    {
        $userData = $this->getUserBFixtures();
        $this->createUser($userData);
        $this->loginUser($userData);
    }

    protected function getUserAFixtures()
    {
        return array(
            'username' => 'JohnDoe',
            'email' => 'nekuno-johndoe@gmail.com',
            'plainPassword' => 'test'
        );
    }

    protected function getUserBFixtures()
    {
        return array(
            'username' => 'JaneDoe',
            'email' => 'nekuno-janedoe@gmail.com',
            'plainPassword' => 'test'
        );
    }

    private function tryToGetJwtByUserId($userId)
    {
        try {
            /** @var AuthService $authService */
            $authService = $this->app['auth.service'];
            $jwt = $authService->getToken($userId);
            return array('HTTP_PHP_AUTH_DIGEST' => 'Bearer ' . $jwt);
        } catch (\Exception $e) {
            return array();
        }
    }
}