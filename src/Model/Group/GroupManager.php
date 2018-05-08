<?php

namespace Model\Group;

use Event\GroupEvent;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Photo\PhotoManager;
use Model\Neo4j\GraphManager;
use Service\Validator\GroupValidator;
use Service\Validator\ValidatorInterface;
use Service\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GroupManager
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var PhotoManager
     */
    protected $pm;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var string
     */
    protected $invitationImagesRoot;

    /**
     * @param GraphManager $gm
     * @param EventDispatcher $dispatcher
     * @param PhotoManager $pm
     * @param $invitationImagesRoot
     */
    public function __construct(GraphManager $gm, EventDispatcher $dispatcher, PhotoManager $pm, $invitationImagesRoot)
    {
        $this->gm = $gm;
        $this->pm = $pm;
        $this->dispatcher = $dispatcher;
        $this->invitationImagesRoot = $invitationImagesRoot;
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

    /**
     * @param $userId
     * @return Group[]
     */
    public function getAllByUserId($userId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)<-[:BELONGS_TO]-(u:User)')
            ->where('u.qnoow_id = { userId }')
            ->setParameter('userId', $userId)
            ->optionalMatch('(g)-[:LOCATION]->(l:Location)')
            ->optionalMatch('(g)-[:HAS_FILTER]->(f:FilterUsers)')
            ->with('g', 'l', 'f')
            ->optionalMatch('(g)-[b:BELONGS_TO]-(:User)')
            ->with('g', 'l', 'f', 'count(b) AS usersCount')
            ->optionalMatch('(g)<-[:HAS_GROUP]-(i:Invitation)')
            ->returns('g', 'l', 'f', 'usersCount', 'collect(i) AS invitations');

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
            ->optionalMatch('(g)<-[:HAS_GROUP]-(i:Invitation)')
            ->returns('g', 'l', 'f', 'i', 'COUNT(u) AS usersCount');

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
            $return[] = $this->build($row);
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

        return $this->build($row);
    }

    public function create(array $data)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->create('(g:Group)')
            ->set('g.name = { name }')
            ->with('g')
            ->setParameter('name', $data['name']);

        if (isset($data['followers']) && $data['followers']) {
            $qb->set('g:GroupFollowers')
                ->with('g')
                ->match('(influencer:User{qnoow_id: { influencer_id }})')
                ->createUnique('(influencer)-[:CREATED_GROUP]->(g)')
                ->createUnique('(influencer)-[:BELONGS_TO]->(g)')
                ->with('g');
            $qb->setParameter('influencer_id', $data['influencer_id']);

        }

        if (isset($data['createdBy'])){
            $qb->match('(u:User) WHERE u.qnoow_id={createdBy}')
                ->merge('(u)-[:CREATED_GROUP]->(g)')
                ->with('g');
            $qb->setParameter('createdBy', $data['createdBy']);
        }

        if (isset($data['html'])){
            $qb->set('g.html = { html }')
                ->with('g')
                ->setParameter('html', $data['html']);
        }

        if (isset($data['date'])){
            $qb->set('g.date = { date }')
                ->with('g')
                ->setParameter('date', (int)$data['date']);
        }

        if (isset($data['image_path'])){
            $qb->set('g.image_path = { image_path }')
                ->with('g')
                ->setParameter('image_path', $data['image_path']);
        }

        if (isset($data['location']))
        {
            $qb->merge('(l:Location)<-[:LOCATION]-(g)');

            $qb->set('l.address = { address }', 'l.latitude = { latitude }', 'l.longitude = { longitude }', 'l.locality = { locality }', 'l.country = { country }')
                ->setParameter('address', $data['location']['address'])
                ->setParameter('latitude', $data['location']['latitude'])
                ->setParameter('longitude', $data['location']['longitude'])
                ->setParameter('locality', $data['location']['locality'])
                ->setParameter('country', $data['location']['country']);

            $qb->returns('g', 'l');
        } else {
            $qb->returns('g');
        }

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        $group = $this->build($row);

        return $group;
    }

    public function update($id, array $data)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->where('id(g) = { id }')
            ->set('g.name = { name }')
            ->set('g.html = { html }')
            ->set('g.image_path = { image_path }')
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
                    'image_path' => isset($data['image_path']) ? $data['image_path'] : null,
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

        $group = $this->build($row);

        return $group;
    }

    public function remove($id)
    {
        $group = $this->getById($id);
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->where('id(g) = { id }')
            ->setParameter('id', (integer)$id)
            ->optionalMatch('(g)<-[:HAS_GROUP]-(i:Invitation)')
            ->detachDelete('g', 'i');

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

    public function addUser($id, $userId)
    {
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

            $this->dispatcher->dispatch(\AppEvents::GROUP_ADDED, new GroupEvent($group, $userId));

            return $group;
        }

        return null;
    }

    public function addGhostUser($id, $userId)
    {
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

        return $result->count() > 0;
    }

    public function removeUser($id, $userId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(g:Group)')
            ->where('id(g) = { id }')
            ->setParameter('id', (integer)$id)
            ->match('(u:User { qnoow_id: { userId } })')
            ->setParameter('userId', (integer)$userId)
            ->match('(u)-[r:BELONGS_TO]->(g)')
            ->delete('r');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() > 0)
        {
            $group = $this->getById($id);
            $this->dispatcher->dispatch(\AppEvents::GROUP_REMOVED, new GroupEvent($group, $userId));

            return true;
        }

        return false;
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
     * @return \ArrayAccess|array
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
     * @return \ArrayAccess|array [ 'direct' => 1 is follower of 2 , 'inverse' => 2 is follower of 1 ]
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
     * @return Group
     */
    protected function build(Row $row)
    {
        /* @var $groupNode Node */
        $groupNode = $row->offsetGet('g');
        /* @var $locationNode Node */
        $locationNode = $row->offsetExists('l') ? $row->offsetGet('l') : null;

        $usersCount = $row->offsetExists('usersCount') ? $row->offsetGet('usersCount') : null;

        $group = $this->buildGroup($groupNode, $locationNode, $usersCount);

        $filterUsers = $this->getFilterUsers($row);
        if ($filterUsers)
        {
            $group->setFilterUsers($filterUsers);
        }

        $invitation = $this->getInvitation($row);
        $group->setInvitation($invitation);

        return $group;
    }

    private function getFilterUsers(Row $row)
    {
        $filterUsers = null;
//        if ($row->offsetExists('f')) {
//            $filterUsers = $this->filterUsersManager->getFilterUsersById($row->offsetGet('f')->getId());
//        }

        return $filterUsers;
    }

    private function getInvitation(Row $row)
    {
        $invitation = array();
        if ($row->offsetExists('i')) {
            $invitationNode = $row->offsetGet('i');
            $invitation = $this->buildInvitationFromNode($invitationNode);
        } else if ($row->offsetExists('invitations')){
            $invitationNodes = $row->offsetGet('invitations');
            $invitation = $this->buildInvitationFromNodes($invitationNodes);
        }

        return $invitation;
    }

    private function buildInvitationFromNode($invitationNode) {
        $invitation = array();
        if ($invitationNode instanceof Node) {
            $invitation = array(
                'invitation_id' => $invitationNode->getId(),
                'invitation_token' => $invitationNode->getProperty('token'),
                'invitation_image_path' => $invitationNode->getProperty('image_path'),
                'invitation_image_url' => $this->invitationImagesRoot . $invitationNode->getProperty('image_path'),
            );
        }

        return $invitation;
    }

    /**
     * @param Node[] $invitationNodes
     * @return array
     */
    private function buildInvitationFromNodes($invitationNodes)
    {
        $invitation = array();
        foreach ($invitationNodes as $invitationNode){
            $currentInvitation = $this->buildInvitationFromNode($invitationNode);
            if ($this->isBetterInvitation($invitation, $currentInvitation)){
                $invitation = $currentInvitation;
            }
        }

        return $invitation;
    }

    private function isBetterInvitation($oldInvitation, $newInvitation)
    {
        $wasEmpty = empty($oldInvitation);
        $newHasImage = !isset($oldInvitation['invitation_image_url']) && isset($newInvitation['invitation_image_url']);

        return $wasEmpty || $newHasImage;
    }

    private function buildGroup(Node $groupNode, Node $locationNode = null, $usersCount = 0)
    {
        $group = Group::createFromNode($groupNode);
        $group->setLocation($this->buildLocation($locationNode));
        $group->setDate($groupNode->getProperty('date'));
        $group->setHtml($groupNode->getProperty('html'));
        $photo = $this->pm->createGroupPhoto();
        if ($groupNode->getProperty('image_path')) {
            $photo->setPath($groupNode->getProperty('image_path'));
            $group->setPhoto($photo);
            $group->setImagePath($groupNode->getProperty('image_path'));
        }
        $group->setUsersCount($usersCount);

        return $group;
    }

    /**
     * @param $groups Group[]
     * @return array
     */
    public function buildGroupNames(array $groups)
    {
        $choices = array();
        foreach ($groups as $group){
            $choices[] = array(
                'id' => $group->getId(),
                'text' => $group->getName()
            );
        }

        return $choices;
    }

    private function buildLocation(Node $locationNode = null)
    {
        return array(
            'address' => $locationNode ? $locationNode->getProperty('address') : null,
            'latitude' => $locationNode ? $locationNode->getProperty('latitude') : null,
            'longitude' => $locationNode ? $locationNode->getProperty('longitude') : null,
            'locality' => $locationNode ? $locationNode->getProperty('locality') : null,
            'country' => $locationNode ? $locationNode->getProperty('country') : null,
        );
    }
}
