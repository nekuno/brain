<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 7/18/14
 * Time: 3:16 PM
 */

namespace ApiConsumer\History;

use Doctrine\ORM\EntityManager;
use Model\Entity\FetchRegistry;

class Registry
{

    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {

        $this->entityManager = $entityManager;
    }

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
