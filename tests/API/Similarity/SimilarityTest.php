<?php

namespace Tests\API\Similarity;

use Model\Popularity\PopularityManager;
use Model\Similarity\SimilarityManager;

class SimilarityTest extends SimilarityAPITest
{
    /**
     * @var SimilarityManager
     */
    protected $similarityManager;

    /**
     * @var PopularityManager
     */
    protected $popularityManager;

    public function setUp()
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $this->similarityManager = $container->get('similarity_manager');
        $this->popularityManager = $container->get('popularity_manager');
    }

    public function testSimilarity()
    {
        $this->assertSimilarityValues();
    }

    public function assertSimilarityValues()
    {
        $this->similarityManager->getSimilarity(1, 2);
        $this->getSimilarity(2);
        $this->popularityManager->updatePopularityByUser(1);
    }
}