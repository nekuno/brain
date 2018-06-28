<?php

namespace Tests\API;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Tests\API\MockUp\AuthServiceMockUp;
use Doctrine\ORM\Tools\SchemaTool;

abstract class APITest extends WebTestCase
{
    const OWN_USER_ID = 1;
    const OTHER_USER_SLUG = 'janedoe';
    const UNDEFINED_USER_SLUG = 'undefined';

    /**
     * @var AuthServiceMockUp
     */
    protected $authServiceMockup;

    public function setUp()
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $em = $container->get('doctrine.orm.entity_manager');
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropDatabase();
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        $this->authServiceMockup = $container->get('auth_service_mockup');
        /** @var TestingFixtures $fixtures */
        $fixtures = $container->get('testing_fixtures');
        $fixtures->load();
    }

    protected function getResponseByRouteWithCredentials($route, $method = 'GET', $data = array(), $userId = self::OWN_USER_ID)
    {
        $headers = array();
        if ($userId) {
            $headers = $this->tryToGetJwtByUserId($userId);
        }

        return $this->getResponseByRoute($route, $method, $data, $headers);
    }

    protected function getResponseByRouteWithoutCredentials($route, $method = 'GET', $data = array())
    {
        return $this->getResponseByRoute($route, $method, $data);
    }

    private function getResponseByRoute($route, $method = 'GET', $data = array(), $headers = array())
    {
        $headers += array('CONTENT_TYPE' => 'application/json');
        $client = $this->createClient();
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

    protected function assertArrayOfType($type, $array, $message)
    {
        $this->isType('array')->evaluate($array, 'Is not an array when '. $message);
        foreach ($array as $item)
        {
            $this->isType($type)->evaluate($item, 'Is not an item of type ' . $type . ' when ' .$message);
        }
    }

    private function tryToGetJwtByUserId($userId)
    {
        try {
            $jwt = $this->authServiceMockup->getToken($userId);

            return array('HTTP_PHP_AUTH_DIGEST' => 'Bearer ' . $jwt);
        } catch (\Exception $e) {
            return array();
        }
    }
}