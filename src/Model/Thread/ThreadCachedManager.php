<?php

namespace Model\Thread;

use Model\Link\LinkManager;
use Model\Neo4j\GraphManager;
use Model\Recommendation\ContentRecommendation;
use Model\Recommendation\ContentRecommendator;
use Model\Recommendation\UserRecommendation;
use Model\Recommendation\UserRecommendationBuilder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThreadCachedManager
{
    protected $graphManager;
    protected $userRecommendationBuilder;
    protected $contentRecommendationPaginatedModel;
    protected $linkModel;

    /**
     * ThreadCachedManager constructor.
     * @param GraphManager $graphManager
     * @param UserRecommendationBuilder $userRecommendationBuilder
     * @param ContentRecommendator $contentRecommendationPaginatedModel
     * @param LinkManager $linkModel
     */
    public function __construct(GraphManager $graphManager, UserRecommendationBuilder $userRecommendationBuilder,
    ContentRecommendator $contentRecommendationPaginatedModel, LinkManager $linkModel)
    {
        $this->graphManager = $graphManager;
        $this->userRecommendationBuilder = $userRecommendationBuilder;
        $this->contentRecommendationPaginatedModel = $contentRecommendationPaginatedModel;
        $this->linkModel = $linkModel;
    }

    /**
     * @param Thread $thread Which thread returned the results
     * @param array $items
     * @param $total
     * @return array
     * @throws \Exception
     */
    public function cacheResults(Thread $thread, array $items, $total)
    {
        $this->deleteCachedResults($thread);

        $parameters = array(
            'id' => $thread->getId(),
            'total' => (integer)$total,
        );
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->set('thread.totalResults = {total}')
            ->with('thread');

        foreach ($items as $item) {
            switch (get_class($thread)) {
                case 'Model\Thread\ContentThread':
                    /** @var $item ContentRecommendation */
                    $id = $item->getContent()->getId();
                    $qb->match('(l:Link)')
                        ->where("id(l) = {$id}")
                        ->merge('(thread)-[:RECOMMENDS]->(l)')
                        ->with('l AS cached');
                    $parameters += array($id => $id);
                    break;
                case 'Model\Thread\UsersThread':
                    /** @var $item UserRecommendation */
                    $id = $item->getId();
                    $qb->match('(u:User)')
                        ->where("u.qnoow_id = {$id}")
                        ->merge('(thread)-[:RECOMMENDS]->(u)')
                        ->with('u AS cached');
                    $parameters += array($id => $id);
                    break;
                default:
                    throw new \Exception('Thread ' . $thread->getId() . ' has a not valid category.');
                    break;
            }
        }

        $qb->returns('cached');
        $qb->setParameters($parameters);
        $result = $qb->getQuery()->getResultSet();

        if ($result->count() < 1) {
            throw new NotFoundHttpException('Thread with id ' . $thread->getId() . ' not found');
        }

        $cached = $result->current()->offsetGet('cached');
        return $this->build($cached);
    }

    public function deleteCachedResults(Thread $thread)
    {
        $parameters = array(
            'id' => $thread->getId()
        );
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->optionalMatch('(thread)-[r:RECOMMENDS]->()')
            ->delete('r');
        $qb->setParameters($parameters);
        $qb->getQuery()->getResultSet();

    }

    protected function build($cached)
    {
        return array();
    }

    public function getCachedUsersRecommendations(Thread $thread)
    {
        $parameters = array(
            'id' => $thread->getId(),
        );

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->with('thread')
            ->match('(thread)<-[:HAS_THREAD]-(owner:User)')
            ->with('thread', 'owner')
            ->match('(thread)-[:RECOMMENDS]->(u:User)')
            ->with('owner', 'u')
            ->optionalMatch('(owner)-[like:LIKES]->(u)')
            ->with('owner', 'u', '(CASE WHEN like IS NOT NULL THEN 1 ELSE 0 END) AS like')
            ->optionalMatch('(owner)-[s:SIMILARITY]-(u)')
            ->with('owner', 's.similarity as similarity', 'u', 'like')
            ->optionalMatch('(owner)-[m:MATCHES]-(u)')
            ->with('similarity, u, m.matching_questions AS matching_questions', 'like')
            ->match('(u)<-[:PROFILE_OF]-(p:Profile)')
            ->with('similarity', 'matching_questions', 'p', 'like', 'u')
            ->optionalMatch('(p)-[:LOCATION]-(l:Location)')
            ->returns(
                'similarity',
                'matching_questions',
                'u.qnoow_id AS id',
                'u.username AS username',
                'u.slug AS slug',
                'u.photo AS photo',
                'like',
                'p.birthday AS birthday',
                'l AS location',
                'p AS profile',
                '[] AS options',
                '[] AS tags'
            );
        $qb->setParameters($parameters);
        $result = $qb->getQuery()->getResultSet();

        return $this->userRecommendationBuilder->buildUserRecommendations($result);
    }

    public function getCachedContentRecommendations(Thread $thread)
    {
        $parameters = array(
            'id' => $thread->getId(),
        );

        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(thread:Thread)')
            ->where('id(thread) = {id}')
            ->optionalMatch('(thread)<-[:HAS_THREAD]-(u:User)')
            ->with('thread', 'u.qnoow_id as u')
            ->optionalMatch('(thread)-[:RECOMMENDS]->(r:Link)')
            ->with('u', 'r')
            ->optionalMatch("(r)-[:SYNONYMOUS]->(synonymousLink:Link)")
            ->with('u', 'r', 'collect(distinct(synonymousLink)) AS synonymous')
            ->optionalMatch('(r)-[:TAGGED]->(tag:Tag)')
            ->with('u', 'r', 'synonymous', 'collect(distinct(tag.name)) AS tags')

            ->returns('u, r, synonymous, tags');
        $qb->setParameters($parameters);
        $result = $qb->getQuery()->getResultSet();

        $cached = array();
        foreach ($result as $row){
            $linkNode = $row->offsetGet('r');
            if (null == $linkNode) {
                continue;
            }
            $linkArray =  $this->linkModel->buildLink($linkNode);
            $link = $this->linkModel->buildLinkObject($linkArray);
            $content = new ContentRecommendation();
            $content->setContent($link);

            $content = $this->contentRecommendationPaginatedModel->completeContent($content, $row, $linkNode, $row->offsetGet('u'));

            $cached[] = $content;
        }

        return $cached;
    }
}