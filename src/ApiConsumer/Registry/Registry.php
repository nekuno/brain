<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 7/21/14
 * Time: 2:07 PM
 */

namespace ApiConsumer\Registry;

use Doctrine\ORM\EntityManager;
use Model\Entity\FetchRegistry;

class Registry
{

    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {

        $this->entityManager = $entityManager;
    }

    public function registerFetchAttempt($userId, $resource, $links, $error)
    {

        $lastItemId = $links[count($links) - 1]['resourceItemId'];

        $registryEntry = new FetchRegistry();
        $registryEntry->setUserId($userId);
        $registryEntry->setResource($resource);
        $registryEntry->setLastItemId($lastItemId);

        if ($error) {
            $registryEntry->setStatus($registryEntry::STATUS_ERROR);
        } else {
            $registryEntry->setStatus($registryEntry::STATUS_SUCCESS);
        }

        try {
            $this->entityManager->persist($registryEntry);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
