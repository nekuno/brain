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
            ->optionalMatch('(g)-[:LOCATION]->(l:Location)')
            ->returns('g', 'l');

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
            ->with('g')
            ->optionalMatch('(g)-[:LOCATION]->(l:Location)')
            ->setParameter('id', (integer)$id)
            ->returns('g', 'l');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Group not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);
    }

    public function getAllByEnterpriseUserId($enterpriseUserId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(i:Invitation)-[:HAS_GROUP]->(g:Group)<-[:CREATED_GROUP]-(eu:EnterpriseUser)')
            ->where('eu.admin_id = { admin_id }')
            ->optionalMatch('(g)-[:LOCATION]->(l:Location)')
            ->setParameter('admin_id', (integer)$enterpriseUserId)
            ->returns('g', 'l', 'i');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $return = array();

        foreach ($result as $row) {
            $return[] = $this->buildWithInvitationToken($row);
        }

        return $return;
    }

    public function getByIdAndEnterpriseUserId($id, $enterpriseUserId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(i:Invitation)-[:HAS_GROUP]->(g:Group)<-[:CREATED_GROUP]-(eu:EnterpriseUser)')
            ->where('id(g) = { id }', 'eu.admin_id = { admin_id }')
            ->optionalMatch('(g)-[:LOCATION]->(l:Location)')
            ->setParameters(array(
                'id' => (integer)$id,
                'admin_id' => (integer)$enterpriseUserId,
            ))
            ->returns('g', 'l', 'i');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Group not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->buildWithInvitationToken($row);
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

        if (!array_key_exists('date', $data)) {
            $errors['date'] = array('"date" is required');
        } elseif (isset($data['date']) && (string)(int)$data['date'] !== (string)$data['date']) {
            $errors['date'] = array('"date" must be a valid timestamp');
        }

        if (!isset($data['location']) || !is_array($data['location'])) {
            $errors['location'] = sprintf('The value "%s" is not valid, it should be an array', $data['location']);
        } elseif(isset($data['location'])) {
            if (!array_key_exists('address', $data['location'])) {
                $errors['address'] = 'Address required';
            } elseif (isset($data['location']['address']) && !is_string($data['location']['address'])) {
                $errors['address'] = 'Address must be a string';
            }
            if (!array_key_exists('latitude', $data['location'])) {
                $errors['latitude'] = 'Latitude required';
            } elseif (isset($data['location']['latitude']) && !preg_match("/^-?([1-8]?[1-9]|[1-9]0)\.{1}\d+$/", $data['location']['latitude'])) {
                $errors['latitude'] = 'Latitude not valid';
            } elseif (isset($data['location']['latitude']) && !is_float($data['location']['latitude'])) {
                $errors['latitude'] = 'Latitude must be float';
            }
            if (!array_key_exists('longitude', $data['location'])) {
                $errors['longitude'] = 'Longitude required';
            } elseif (isset($data['location']['longitude']) && !preg_match("/^-?([1-8]?[1-9]|[1-9]0)\.{1}\d+$/", $data['location']['longitude'])) {
                $errors['longitude'] = 'Longitude not valid';
            } elseif (isset($data['location']['longitude']) && !is_float($data['location']['longitude'])) {
                $errors['longitude'] = 'Longitude must be float';
            }
            if (!array_key_exists('locality', $data['location'])) {
                $errors['locality'] = 'Locality required';
            } elseif (isset($data['location']['locality']) && !is_string($data['location']['locality'])) {
                $errors['locality'] = 'Locality must be a string';
            }
            if (!array_key_exists('country', $data['location'])) {
                $errors['country'] = 'Country required';
            } elseif (isset($data['location']['country']) && !is_string($data['location']['country'])) {
                $errors['country'] = 'Country must be a string';
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
        $qb->create('(g:Group {name:{ name }, html: { html }, date: { date }})')
            ->with('g')
            ->merge('(l:Location {address: { address }, latitude: { latitude }, longitude: { longitude }, locality: { locality }, country: { country }})<-[:LOCATION]-(g)')
            ->setParameters(array(
                'name' => $data['name'],
                'html' => $data['html'],
                'date' => $data['date'] ? (int)$data['date'] : null,
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
            ->set('g.date = { date }')
            ->with('g')
            ->merge('(l:Location)<-[:LOCATION]-(g)')
            ->set('l.address = { address }', 'l.latitude = { latitude }', 'l.longitude = { longitude }', 'l.locality = { locality }', 'l.country = { country }')
            ->setParameters(array(
                'id' => (integer)$id,
                'name' => $data['name'],
                'html' => $data['html'],
                'date' => $data['date'] ? (int)$data['date'] : null,
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
            ->optionalMatch('()-[relationships]-(i:Invitation)-[:HAS_GROUP]->(g)')
            ->delete('g', 'r', 'i', 'relationships');

        $query = $qb->getQuery();

        $query->getResultSet();

        return $group;

    }

    public function setCreatedByEnterpriseUser($id, $enterpriseUserId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)', '(eu:EnterpriseUser)')
            ->where('id(g) = { id } AND eu.admin_id = { enterpriseUserId }')
            ->merge('(g)<-[:CREATED_GROUP]-(eu)')
            ->setParameters(array(
                'id' => (integer)$id,
                'enterpriseUserId' => (integer)$enterpriseUserId,
            ))
            ->returns('g');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        return $this->build($row);

    }

    public function getByUser($userId)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { userId }})')
            ->setParameter('userId', (integer)$userId)
            ->match('(u)-[r:BELONGS_TO]->(g:Group)')
            ->with('g')
            ->optionalMatch('(g)-[:LOCATION]->(l:Location)')
            ->returns('g', 'l');

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
                'address' => $location ? $location->getProperty('address') : null,
                'latitude' => $location ? $location->getProperty('latitude') : null,
                'longitude' => $location ? $location->getProperty('longitude') : null,
                'locality' => $location ? $location->getProperty('locality') : null,
                'country' => $location ? $location->getProperty('country') : null,
            ),
            'date' => $group->getProperty('date'),
        );
    }

    protected function buildWithInvitationToken(Row $row)
    {
        $return = $this->build($row);
        /* @var $invitation Node */
        $invitation = $row->offsetGet('i');

        return $return + array(
            'invitation_token' => $invitation->getProperty('token'),
            'invitation_image_url' => $invitation->getProperty('image_url')
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
