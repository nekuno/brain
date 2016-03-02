<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Tests\API;

use Everyman\Neo4j\Cypher\Query;
use Model\User;
use Silex\Application;
use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Service\AuthService;

abstract class APITest extends WebTestCase
{
    protected $app;

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

    public function createApplication()
    {
        $app = require __DIR__.'/../../app.php';
        require __DIR__.'/../../controllers.php';
        require __DIR__.'/../../routing.php';
        $app['debug'] = true;
        unset($app['exception_handler']);
        $app['session.test'] = true;

        return $app;
    }

    public function setUp()
    {
        parent::setUp();
        $app = $this->app;
        // Clean the database
        $query = new Query($app['neo4j.client'], 'MATCH n-[r]-m DELETE n, r, m');
        $query->getResultSet();
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

    protected function validateUserA()
    {
        $userData = $this->getUserAFixtures();
        return $this->getResponseByRoute('/users/validate', 'POST', $userData);
    }

    protected function createUserA()
    {
        $userData = $this->getUserAFixtures();
        return $this->getResponseByRoute('/users', 'POST', $userData);
    }

    protected function createUserB()
    {
        $userData = $this->getUserBFixtures();
        return $this->getResponseByRoute('/users', 'POST', $userData);
    }

    protected function editOwnUser()
    {
        $userData = $this->getEditedUserAFixtures();
        return $this->getResponseByRoute('/users', 'PUT', $userData, 1);
    }

    protected function resetOwnUser()
    {
        $userData = $this->getUserAFixtures();
        return $this->getResponseByRoute('/users', 'PUT', $userData, 1);
    }

    protected function loginUserA()
    {
        $userData = $this->getUserAFixtures();
        return $this->getResponseByRoute('/login', 'OPTIONS', $userData);
    }

    protected function getOwnUser()
    {
        return $this->getResponseByRoute('/users', 'GET', array(), 1);
    }

    protected function getUserB()
    {
        return $this->getResponseByRoute('/users/2', 'GET', array(), 1);
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

    protected function getEditedUserAFixtures()
    {
        return array(
            'username' => 'JohnDoe',
            'email' => 'nekuno-johndoe_updated@gmail.com',
            'plainPassword' => 'test_updated'
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