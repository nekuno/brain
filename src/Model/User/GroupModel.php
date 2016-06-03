<?php

namespace Model\User;

use Event\GroupEvent;
use Everyman\Neo4j\Label;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Exception\ValidationException;
use Model\Neo4j\GraphManager;
use Model\User\Filters\FilterUsersManager;
use Manager\UserManager;
use Service\Validator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GroupModel
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var UserManager
     */
    protected $um;

    /**
     * @var FilterUsersManager
     */
    protected $filterUsersManager;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @param GraphManager $gm
     * @param EventDispatcher $dispatcher
     * @param UserManager $um
     * @param FilterUsersManager $filterUsersManager
     */
    public function __construct(GraphManager $gm, EventDispatcher $dispatcher, UserManager $um, FilterUsersManager $filterUsersManager)
    {
        $this->gm = $gm;
        $this->um = $um;
        $this->dispatcher = $dispatcher;
        $this->filterUsersManager = $filterUsersManager;
    }

    public function getAll()
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->optionalMatch('(g)-[:LOCATION]->(l:Location)')
            ->optionalMatch('(g)-[:HAS_FILTER]->(f:FilterUsers)')
            ->returns('g', 'l', 'f');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $return = array();

        foreach ($result as $row) {
            $return[] = $this->build($row);
        }

        return $return;
    }

    public function getAllByUserId($userId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)<-[:BELONGS_TO]-(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->setParameter('userId', $userId)
            ->optionalMatch('(g)-[:LOCATION]->(l:Location)')
            ->optionalMatch('(g)-[:HAS_FILTER]->(f:FilterUsers)')
            ->returns('g', 'l', 'f');

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
            ->with('g', 'l')
            ->optionalMatch('(g)-[:HAS_FILTER]->(f:FilterUsers)')
            ->optionalMatch('(u:User)-[:BELONGS_TO]->(g)')
            ->returns('g', 'l', 'f', 'COUNT(u) AS usersCount');

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
            ->optionalMatch('(g)-[:HAS_FILTER]->(f:FilterUsers)')
            ->setParameter('admin_id', (integer)$enterpriseUserId)
            ->returns('g', 'l', 'f', 'i');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        $return = array();

        foreach ($result as $row) {
            $return[] = $this->buildWithInvitationData($row);
        }

        return $return;
    }

    public function getByIdAndEnterpriseUserId($id, $enterpriseUserId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(i:Invitation)-[:HAS_GROUP]->(g:Group)<-[:CREATED_GROUP]-(eu:EnterpriseUser)')
            ->where('id(g) = { id }', 'eu.admin_id = { admin_id }')
            ->optionalMatch('(g)-[:LOCATION]->(l:Location)')
            ->optionalMatch('(g)-[:HAS_FILTER]->(f:FilterUsers)')
            ->setParameters(
                array(
                    'id' => (integer)$id,
                    'admin_id' => (integer)$enterpriseUserId,
                )
            )
            ->returns('g', 'l', 'f', 'i');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) < 1) {
            throw new NotFoundHttpException('Group not found');
        }

        /* @var $row Row */
        $row = $result->current();

        return $this->buildWithInvitationData($row);
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
        } elseif (isset($data['date']) && !(is_int($data['date']) || is_double($data['date']))) {
            $errors['date'] = array('"date" must be a valid timestamp');
        }

        if (isset($data['followers']) && $data['followers']) {
            if (!is_bool($data['followers'])) {
                $errors['followers'] = array('"followers" must be boolean');
            }
            if (!isset($data['influencer_id'])) {
                $errors['influencer_id'] = array('"influencer_id" is required for followers groups');
            } elseif (!is_int($data['influencer_id'])) {
                $errors['influencer_id'] = array('"influencer_id" must be integer');
            }
            if (!isset($data['min_matching'])) {
                $errors['min_matching'] = array('"min_matching" is required for followers groups');
            } elseif (!is_int($data['min_matching'])) {
                $errors['min_matching'] = array('"min_matching" must be integer');
            }
            if (!isset($data['type_matching'])) {
                $errors['type_matching'] = array('"type_matching" is required for followers groups');
            } elseif ($data['type_matching'] !== 'similarity' && $data['type_matching'] !== 'compatibility') {
                $errors['type_matching'] = array('"type_matching" must be "similarity" or "compatibility"');
            }
        }

        if (!isset($data['location'])) {
            $errors['location'] = array('"location" is required');
        } elseif (!is_array($data['location'])) {
            $errors['location'] = sprintf('The value "%s" is not valid, it should be an array', $data['location']);
        } elseif (isset($data['location'])) {
            if (!array_key_exists('address', $data['location'])) {
                $errors['address'] = 'Address required';
            } elseif (isset($data['location']['address']) && !is_string($data['location']['address'])) {
                $errors['address'] = 'Address must be a string';
            }
            if (!array_key_exists('latitude', $data['location'])) {
                $errors['latitude'] = 'Latitude required';
            } elseif (isset($data['location']['latitude']) && !preg_match(Validator::LATITUDE_REGEX, $data['location']['latitude'])) {
                $errors['latitude'] = 'Latitude not valid';
            } elseif (isset($data['location']['latitude']) && !is_float($data['location']['latitude'])) {
                $errors['latitude'] = 'Latitude must be float';
            }
            if (!array_key_exists('longitude', $data['location'])) {
                $errors['longitude'] = 'Longitude required';
            } elseif (isset($data['location']['longitude']) && !preg_match(Validator::LONGITUDE_REGEX, $data['location']['longitude'])) {
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
            throw new ValidationException($errors);
        }
    }

    public function create(array $data)
    {
        $this->validate($data);

        $qb = $this->gm->createQueryBuilder();

        $qb->create('(g:Group {name:{ name }, html: { html }, date: { date }})');

        if (isset($data['followers']) && $data['followers']) {
            $qb->set('g:GroupFollowers')
                ->with('g')
                ->match('(influencer:User{qnoow_id: { influencer_id }})')
                ->createUnique('(influencer)-[:CREATED_GROUP]->(g)')
                ->createUnique('(influencer)-[:BELONGS_TO]->(g)');
        }

        $qb->with('g')
            ->merge('(l:Location)<-[:LOCATION]-(g)')
            ->set('l.address = { address }', 'l.latitude = { latitude }', 'l.longitude = { longitude }', 'l.locality = { locality }', 'l.country = { country }')
            ->setParameters(
                array(
                    'name' => $data['name'],
                    'html' => $data['html'],
                    'date' => $data['date'] ? (int)$data['date'] : null,
                    'address' => $data['location']['address'],
                    'latitude' => $data['location']['latitude'],
                    'longitude' => $data['location']['longitude'],
                    'locality' => $data['location']['locality'],
                    'country' => $data['location']['country'],
                )
            );

        if (isset($data['followers']) && $data['followers']) {
            $qb->setParameter('influencer_id', $data['influencer_id']);
        }

        $qb->returns('g', 'l');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        $group = $this->build($row);

        if (isset($data['followers'])) {
            $filterUsers = $this->filterUsersManager->updateFilterUsersByGroupId(
                $group['id'],
                array(
                    'userFilters' => array(
                        $data['type_matching'] => $data['min_matching'],
                        'groups' => array($group['id']),
                    )
                )
            );
            $group['filterUsers'] = $filterUsers;
        }

        return $group;
    }

    public function update($id, array $data)
    {
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
            ->with('g', 'l')
            ->optionalMatch('(g)<-[:HAS_GROUP]-(i:Invitation)')
            ->setParameters(
                array(
                    'id' => (integer)$id,
                    'name' => $data['name'],
                    'html' => $data['html'],
                    'date' => $data['date'] ? (int)$data['date'] : null,
                    'address' => $data['location']['address'],
                    'latitude' => $data['location']['latitude'],
                    'longitude' => $data['location']['longitude'],
                    'locality' => $data['location']['locality'],
                    'country' => $data['location']['country'],
                )
            );

        $qb->returns('g', 'l', 'i');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        $group = $this->buildWithInvitationData($row);

        if (isset($data['followers'])) {
            $filterUsers = $this->filterUsersManager->updateFilterUsersByGroupId(
                $group['id'],
                array(
                    'userFilters' => array(
                        $data['type_matching'] => $data['min_matching']
                    )
                )
            );
            $group['filterUsers'] = $filterUsers;
        }

        return $group;
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
            ->with('g')
            ->optionalMatch('(g)-[:LOCATION]->(l:Location)')
            ->optionalMatch('(g)-[:HAS_FILTER]->(f:FilterUsers)')
            ->setParameters(
                array(
                    'id' => (integer)$id,
                    'enterpriseUserId' => (integer)$enterpriseUserId,
                )
            )
            ->returns('g', 'l', 'f');

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
            ->optionalMatch('(g)-[:HAS_FILTER]->(f:FilterUsers)')
            ->returns('g', 'l', 'f');

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
	    $result = $query->getResultSet();

	    if ($result->count() > 0) {
		    $group = $this->getById($id);
		    $user = $this->um->getById($userId);

		    $this->dispatcher->dispatch(\AppEvents::GROUP_ADDED, new GroupEvent($group, $user));
		    return true;
	    }

	    return false;
    }

	public function addGhostUser($id, $userId)
	{
		$this->getById($id);
		$this->um->getById($userId, true);

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
		$result = $query->getResultSet();

		if ($result->count() > 0) {
			return true;
		}

		return false;
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

    /**
     * @param $userId
     * @return \ArrayAccess
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getGroupFollowersFromInfluencerId($userId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->setParameter('userId', (integer)$userId);
        $qb->match('(user:User)')
            ->where('user.qnoow_id = {userId}')
            ->with('user')
            ->match('(user)-[:CREATED_GROUP]->(g:GroupFollowers)')
            ->returns('collect(id(g)) as groups');
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0) {
            return array();
        }

        return $result->current()->offsetGet('groups');
    }

    /**
     * @param $userId1
     * @param $userId2
     * @return array [ 'direct' => 1 is follower of 2 , 'inverse' => 2 is follower of 1 ]
     * @throws \Model\Neo4j\Neo4jException
     */
    public function getIsGroupFollowersOf($userId1, $userId2)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->setParameters(
            array(
                'id1' => (integer)$userId1,
                'id2' => (integer)$userId2,
            )
        );
        $qb->match('(user1:User), (user2:User)')
            ->where('user1.qnoow_id = {id1}', 'user2.qnoow_id = {id2}')
            ->with('user1', 'user2')
            ->optionalMatch('(user1)-[:BELONGS_TO]->(g:GroupFollowers)<-[:CREATED_GROUP]-(user2)')
            ->optionalMatch('(user2)-[:BELONGS_TO]->(g2:GroupFollowers)<-[:CREATED_GROUP]-(user1)')
            ->returns('collect(id(g)) AS direct, collect(id(g2)) AS inverse');

        $result = $qb->getQuery()->getResultSet();

        $return = array('direct' => array(), 'inverse' => array());
        if ($result->count() == 0) {
            return $result;
        }

        /* @var $row Row */
        $row = $result->current();
        foreach ($row->offsetGet('direct') as $item) {
            $return['direct'][] = $item;
        }

        foreach ($row->offsetGet('inverse') as $inverse) {
            $return['inverse'][] = $inverse;
        }

        return $return;
    }

    /**
     * @param Row $row
     * @return array
     */
    protected function build(Row $row)
    {
        /* @var $group Node */
        $group = $row->offsetGet('g');
        /* @var $location Node */
        $location = $row->offsetGet('l');
        /* @var $filter Node */
        $filter = $row->offsetExists('f') ? $row->offsetGet('f') : null;

        $usersCount = $row->offsetGet('usersCount');

        $group = $this->buildGroup($group, $location, $usersCount);

        if ($filter) {
            $group['filterUsers'] = $this->filterUsersManager->getFilterUsersById($filter->getId());
        }

        return $group;
    }

    protected function buildGroup(Node $group, Node $location, $usersCount)
    {
        $additionalLabels = array();
        $labels = $group->getLabels();
        /* @var $label Label */
        foreach ($labels as $label) {
            if ($label->getName() !== 'Group') {
                $additionalLabels[] = $label->getName();
            }
        }

        $group = array(
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
            'usersCount' => $usersCount,
        );

        if (in_array('GroupFollowers', $additionalLabels)) {
            $user = $this->um->getByCreatedGroup($group['id']);
            $group['influencer'] = array(
                'username' => $user->getUsername(),
                'id' => $user->getId(),
            );
        }

        return $group;
    }

    protected function buildWithInvitationData(Row $row)
    {
        $return = $this->build($row);
        if ($row->offsetExists('i')) {
            $invitation = $row->offsetGet('i');

            if ($invitation instanceof Node) {
                $return += array(
                    'invitation_id' => $invitation->getId(),
                    'invitation_token' => $invitation->getProperty('token'),
                    'invitation_image_path' => $invitation->getProperty('image_path'),
                );
            }

        }

        return $return;

    }
}
