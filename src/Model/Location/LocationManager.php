<?php

namespace Model\Location;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;

class LocationManager
{
    protected $graphManager;

    /**
     * LocationManager constructor.
     * @param $graphManager
     */
    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    public function createLocation($locationData)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->create('(location:Location {latitude: { latitude }, longitude: { longitude }, address: { address }, locality: { locality }, country: { country }})')
            ->setParameter('latitude', isset($locationData['latitude']) ? $locationData['latitude'] : null)
            ->setParameter('longitude', isset($locationData['longitude']) ? $locationData['longitude'] : null)
            ->setParameter('address', isset($locationData['address']) ? $locationData['address'] : null)
            ->setParameter('locality', isset($locationData['locality']) ? $locationData['locality'] : null)
            ->setParameter('country', isset($locationData['country']) ? $locationData['country'] : null)
            ->returns('location');

        $result = $qb->getQuery()->getResultSet();

        $location = $this->buildLocation($result->current());

        return $location;
    }

    public function buildLocation(Row $row)
    {
        /** @var Node $locationNode */
        $locationNode = $row->offsetGet('location');
        $properties = $locationNode->getProperties();

        $location = new Location();
        $location->setId($locationNode->getId());
        $location->setLatitude(isset($properties['latitude']) ? $properties['latitude'] : null);
        $location->setLongitude(isset($properties['longitude']) ? $properties['longitude'] : null);
        $location->setAddress(isset($properties['address']) ? $properties['address'] : null);
        $location->setLocality(isset($properties['locality']) ? $properties['locality'] : null);
        $location->setCountry(isset($properties['country']) ? $properties['country'] : null);

        return $location;
    }

}