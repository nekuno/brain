<?php

namespace ApiConsumer\History;

use Doctrine\ORM\EntityManager;
use Model\Entity\FetchRegistry;

/**
 * Class Registry
 *
 * @package ApiConsumer\History
 */
class Registry
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {

        $this->entityManager = $entityManager;
    }

    /**
     * @param FetchRegistry $registry
     * @return $this
     * @throws \Exception
     */
    public function recordFetchAttempt(FetchRegistry $registry)
    {

        $pointerFieldName = $this->getPointerFieldName($registry->getResource());
        $registry->setPointerFieldName($pointerFieldName);

        try {
            $this->entityManager->persist($registry);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw $e;
        }

        return $this;
    }

    /**
     * @param $resource
     * @return string
     * @throws \Exception
     */
    private function getPointerFieldName($resource)
    {

        $pointerFieldNames = array(
            'twitter'  => 'since_id',
            'google'   => 'nextPageToken',
            'facebook' => 'since',
        );

        if (array_key_exists($resource, $pointerFieldNames)) {
            return $pointerFieldNames[$resource];
        }

        throw new \Exception('Invalid resource name given');
    }
} 
