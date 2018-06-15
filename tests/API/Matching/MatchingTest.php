<?php

namespace Tests\API\Matching;

use Model\Matching\MatchingManager;

class MatchingTest extends MatchingAPITest
{
    /**
     * @var MatchingManager
     */
    protected $matchingManager;

    public function setUp()
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $this->matchingManager = $container->get('matching_manager');
    }

    public function testMatching()
    {
        $this->assertMatchingValues();
    }

    public function assertMatchingValues()
    {
        $this->matchingManager->getMatchingBetweenTwoUsersBasedOnAnswers(1, 2);
        $response = $this->getMatching(2);
        $formattedResponse = $this->assertJsonResponse($response, 200, "Get matching");
        $this->assertEquals(['matching' => 0], $formattedResponse, "Matching is not 0");
    }

}