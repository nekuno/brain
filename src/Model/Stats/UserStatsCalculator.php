<?php

namespace Model\Stats;

use ApiConsumer\Images\ImageAnalyzer;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\Group\Group;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserStatsCalculator
{
    /**
     * @var GraphManager
     */
    protected $graphManager;

    /**
     * @var ImageAnalyzer
     */
    protected $imageAnalyzer;

    function __construct(GraphManager $graphManager, ImageAnalyzer $imageAnalyzer)
    {
        $this->graphManager = $graphManager;
        $this->imageAnalyzer = $imageAnalyzer;
    }

    public function calculateStats($id)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u:User {qnoow_id: { id }})')
            ->setParameter('id', (integer)$id)
            ->with('u')
            ->optionalMatch('(u)-[r:ANSWERS]->(:Answer)')
            ->returns('count(r) AS questionsAnswered', 'u.available_invitations AS available_invitations');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('User not found');
        }

        /* @var $row Row */
        $row = $result->current();

        $userStats = new UserStats();
        $userStats->setNumberOfQuestionsAnswered($row->offsetGet('questionsAnswered'));
        $userStats->setAvailableInvitations($row->offsetGet('available_invitations'));

        return $userStats;

    }

    /**
     * @param $id1
     * @param $id2
     * @return UserComparedStats
     * @throws \Exception
     */
    public function calculateComparedStats($id1, $id2)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->setParameters(
            array(
                'id1' => (integer)$id1,
                'id2' => (integer)$id2
            )
        );

        $qb->match('(u:User {qnoow_id: { id1 }}), (u2:User {qnoow_id: { id2 }})')
            ->optionalMatch('(u)-[:BELONGS_TO]->(g:Group)<-[:BELONGS_TO]-(u2)')
            ->with('u', 'u2', 'collect(distinct g) AS groupsBelonged')
            ->optionalMatch('(u)-[:TOKEN_OF]-(token:Token)')
            ->with('u', 'u2', 'groupsBelonged', 'collect(distinct token.resourceOwner) as resourceOwners')
            ->optionalMatch('(u2)-[:TOKEN_OF]-(token2:Token)');
        $qb->with('u, u2', 'groupsBelonged', 'resourceOwners', 'collect(distinct token2.resourceOwner) as resourceOwners2')
            ->optionalMatch('(u)-[:LIKES]->(link:Link)')
            ->where('(u2)-[:LIKES]->(link)', 'link.processed = 1', 'NOT link:LinkDisabled')
            ->with('u', 'u2', 'groupsBelonged', 'resourceOwners', 'resourceOwners2', 'count(distinct(link)) AS commonContent')
            ->optionalMatch('(u)-[:ANSWERS]->(answer:Answer)')
            ->where('(u2)-[:ANSWERS]->(answer)', '(u)<-[:PROFILE_OF]-(:Profile)<-[:OPTION_OF]-(:Mode)<-[:INCLUDED_IN]-(:QuestionCategory)-[:CATEGORY_OF]->(:Question)<-[:IS_ANSWER_OF]-(answer)')
            ->returns('groupsBelonged, resourceOwners, resourceOwners2, commonContent, count(distinct(answer)) as commonAnswers');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();

        $groups = array();
        foreach ($row->offsetGet('groupsBelonged') as $groupNode) {
            $groups[] = Group::createFromNode($groupNode);
        }

        $resourceOwners = array();
        foreach ($row->offsetGet('resourceOwners') as $resourceOwner) {
            $resourceOwners[] = $resourceOwner;
        }
        $resourceOwners2 = array();
        foreach ($row->offsetGet('resourceOwners2') as $resourceOwner2) {
            $resourceOwners2[] = $resourceOwner2;
        }

        $commonContent = $row->offsetGet('commonContent') ?: 0;
        $commonAnswers = $row->offsetGet('commonAnswers') ?: 0;

        $userStats = new UserComparedStats(
            $groups,
            $resourceOwners,
            $resourceOwners2,
            $commonContent,
            $commonAnswers
        );

        return $userStats;
    }

    public function calculateTopLinks($userId1, $userId2)
    {
        $newThumbnails = $this->getTopLinks($userId1, $userId2);

        return $this->getWorkingThumbnails($newThumbnails);
    }

    /**
     * @param $userId1
     * @param $userId2
     * @return array
     */
    protected function getTopLinks($userId1, $userId2)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(u1:User{qnoow_id: {id1}})', '(u2:User{qnoow_id: {id2}})')
            ->setParameter('id1', (integer)$userId1)
            ->setParameter('id2', (integer)$userId2)
            ->with('u1', 'u2');

        $qb->optionalMatch('(u1)-[:LIKES]->(link:Link)<-[:LIKES]-(u2)')
            ->with('u1', 'u2', 'collect(link) AS links');

        $qb->unwind('links AS link')
            ->match('(link)-[:HAS_POPULARITY]->(p:Popularity)')
            ->where('link.processed = 1', 'EXISTS (link.thumbnail)', 'EXISTS (p.popularity)', 'p.popularity > 0')
            ->with('link.thumbnail AS thumbnail', 'link.thumbnailSmall AS thumbnailSmall', 'link.thumbnailMedium AS thumbnailMedium', 'id(link) AS linkId', 'p.popularity AS popularity')
            ->orderBy('popularity ASC');

        $qb->returns('thumbnail', 'linkId');

        $result = $qb->getQuery()->getResultSet();

        $thumbnails = array();
        foreach ($result as $row) {
            $thumbnail = $row->offsetGet('thumbnail');
            $thumbnailSmall = $row->offsetGet('thumbnailSmall');
            $thumbnailMedium = $row->offsetGet('thumbnailMedium');
            $thumbnails[] = $thumbnailSmall ?: ($thumbnailMedium ?: $thumbnail);
        }

        return $thumbnails;
    }

    protected function getWorkingThumbnails(array $thumbnails, $limit = 3)
    {
        $workingThumbnails = array();

        foreach ($thumbnails as $thumbnail) {
            $imageResponse = $this->imageAnalyzer->buildResponse($thumbnail);

            if (!in_array($thumbnail, $workingThumbnails) && $imageResponse->isImage()) {
                $workingThumbnails[] = $thumbnail;
            }

            if (count($workingThumbnails) >= $limit) {
                break;
            }
        }

        return $workingThumbnails;
    }
}