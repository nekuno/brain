<?php

namespace Model\User;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\UserModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GroupModel
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var UserModel
     */
    protected $um;

    /**
     * @param GraphManager $gm
     * @param UserModel $um
     */
    public function __construct(GraphManager $gm, UserModel $um)
    {

        $this->gm = $gm;
        $this->um = $um;
    }

    public function getAll()
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->returns('g');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $return = array();

        foreach ($result as $row) {
            $return[] = $this->build($row);
        }

        return $return;
    }

    public function getById($id)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->where('id(g)= { id }')
            ->setParameter('id', (integer)$id)
            ->returns('g');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Group not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function validate(array $data)
    {
        $errors = array();

        if (!isset($data['name']) || !$data['name']) {
            $errors['name'] = array('"name" is required');
        } elseif (!is_string($data['name'])) {
            $errors['name'] = array('"name" must be string');
        }

        if (!isset($data['html']) || !$data['html']) {
            $errors['html'] = array('"html" is required');
        } elseif (!is_string($data['html'])) {
            $errors['html'] = array('"html" must be string');
        }

        if (!isset($data['location']) || !is_array($data['location'])) {
            $errors['location'] = sprintf('The value "%s" is not valid, it should be an array with "latitude" and "longitude" keys', $data['location']);
        } elseif(isset($data['location'])) {
            if (!isset($data['location']['address']) || !$data['location']['address'] || !is_string($data['location']['address'])) {
                $errors['location'] = 'Address required';
            } else {
                if (!isset($data['location']['latitude']) || !preg_match("/^-?([1-8]?[1-9]|[1-9]0)\.{1}\d+$/", $data['location']['latitude'])) {
                    $errors['location'] = 'Latitude not valid';
                } elseif (!is_float($data['location']['latitude'])) {
                    $errors['location'] = 'Latitude must be float';
                }
                if (!isset($data['location']['longitude']) || !preg_match("/^-?([1]?[1-7][1-9]|[1]?[1-8][0]|[1-9]?[0-9])\.{1}\d+$/", $data['location']['longitude'])) {
                    $errors['location'] = 'Longitude not valid';
                } elseif (!is_float($data['location']['longitude'])) {
                    $errors['location'] = 'Longitude must be float';
                }
                if (!isset($data['location']['locality']) || !$data['location']['locality'] || !is_string($data['location']['locality'])) {
                    $errors['location'] = 'Locality required';
                }
                if (!isset($data['location']['country']) || !$data['location']['country'] || !is_string($data['location']['country'])) {
                    $errors['location'] = 'Country required';
                }
            }
        }

        if (count($errors) > 0) {
            $e = new ValidationException('Validation error');
            $e->setErrors($errors);
            throw $e;
        }
    }

    public function create(array $data)
    {

        $this->validate($data);

        $qb = $this->gm->createQueryBuilder();
        $qb->create('(g:Group {name:{ name }, html: { html }})')
            ->with('g')
            ->merge('(l:Location {address: { address }, latitude: { latitude }, longitude: { longitude }, locality: { locality }, country: { country }})<-[:LOCATION]-(g)')
            ->setParameters(array(
                'name' => $data['name'],
                'html' => $data['html'],
                'address' => $data['location']['address'],
                'latitude' => $data['location']['latitude'],
                'longitude' => $data['location']['longitude'],
                'locality' => $data['location']['locality'],
                'country' => $data['location']['country'],
            ))
            ->returns('g', 'l');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function update($id, array $data)
    {

        $this->getById($id);

        $this->validate($data);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->where('id(g) = { id }')
            ->set('g.name = { name }')
            ->set('g.html = { html }')
            ->with('g')
            ->match('(l:Location)<-[:LOCATION]-(g)')
            ->set('l.address = { address }', 'l.latitude = { latitude }', 'l.longitude = { longitude }', 'l.locality = { locality }', 'l.country = { country }')
            ->setParameters(array(
                'id' => (integer)$id,
                'name' => $data['name'],
                'html' => $data['html'],
                'address' => $data['location']['address'],
                'latitude' => $data['location']['latitude'],
                'longitude' => $data['location']['longitude'],
                'locality' => $data['location']['locality'],
                'country' => $data['location']['country'],
            ))
            ->returns('g', 'l');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function remove($id)
    {

        $group = $this->getById($id);
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->where('id(g) = { id }')
            ->setParameter('id', (integer)$id)
            ->optionalMatch('(g)-[r]-()')
            ->delete('g', 'r');

        $query = $qb->getQuery();

        $query->getResultSet();

        return $group;

    }

    public function getByUser($userId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { userId }})')
            ->setParameter('userId', (integer)$userId)
            ->match('(u)-[r:BELONGS_TO]->(g:Group)')
            ->returns('g');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $return = array();

        foreach ($result as $row) {
            $return[] = $this->build($row);
        }

        return $return;
    }

    public function addUser($id, $userId)
    {

        $this->getById($id);
        $this->um->getById($userId);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->where('id(g) = { id }')
            ->setParameter('id', (integer)$id)
            ->match('(u:User { qnoow_id: { userId } })')
            ->setParameter('userId', (integer)$userId)
            ->merge('(u)-[r:BELONGS_TO]->(g)')
            ->set('r.created = timestamp()')
            ->returns('r');

        $query = $qb->getQuery();
        $query->getResultSet();
    }

    public function removeUser($id, $userId)
    {

        $this->getById($id);
        $this->um->getById($userId);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->where('id(g) = { id }')
            ->setParameter('id', (integer)$id)
            ->match('(u:User { qnoow_id: { userId } })')
            ->setParameter('userId', (integer)$userId)
            ->match('(u)-[r:BELONGS_TO]->(g)')
            ->delete('r');

        $query = $qb->getQuery();
        $query->getResultSet();
    }

    public function isUserFromGroup($id, $userId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->where('id(g) = { id }')
            ->setParameter('id', (integer)$id)
            ->match('(u:User { qnoow_id: { userId } })')
            ->setParameter('userId', (integer)$userId)
            ->match('(u)-[r:BELONGS_TO]->(g)')
            ->returns('r');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            return true;
        }

        return false;
    }

    protected function build(Row $row)
    {
        /* @var $group Node */
        $group = $row->offsetGet('g');
        /* @var $location Node */
        $location = $row->offsetGet('l');

        return array(
            'id' => $group->getId(),
            'name' => $group->getProperty('name'),
            'html' => $group->getProperty('html'),
            'location' => array(
                'address' => $location->getProperty('address'),
                'latitude' => $location->getProperty('latitude'),
                'longitude' => $location->getProperty('longitude'),
                'locality' => $location->getProperty('locality'),
                'country' => $location->getProperty('country'),
            ),
        );
    }

    /**
     * @param $groupId
     * @return bool
     * @throws \Exception
     */
    public function existsGroup($groupId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->where('id(g) = { groupId }')
            ->setParameter('groupId', (integer)$groupId)
            ->returns('g AS group');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        return $result->count() > 0;
    }
}
