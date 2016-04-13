<?php

namespace Model;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Symfony\Component\Translation\Translator;

/**
 * Class LinkModel
 *
 * @package Model
 */
class LinkModel
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var Translator
     */
    protected $translator;

    public function __construct(GraphManager $gm, Translator $translator)
    {
        $this->gm = $gm;
        $this->translator = $translator;
    }

    /**
     * @param string $url
     * @return array|boolean the link or false
     * @throws \Exception on failure
     */
    public function findLinkByUrl($url)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('l.url = { url } ')
            ->returns('l AS link')
            ->limit('1');

        $qb->setParameter('url', $url);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) <= 0) {
            return false;
        }

        /* @var $row Row */
        $row = $result->current();
        /* @var $node Node */
        $link = $this->buildLink($row->offsetGet('link'));

        return $link;
    }

    /**
     * @param integer $linkId
     * @return array|boolean the link or false
     * @throws \Exception on failure
     */
    public function findLinkById($linkId)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('id(l) = { linkId } ')
            ->returns('l AS link')
            ->limit('1');

        $qb->setParameter('linkId', (integer)$linkId);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) <= 0) {
            return false;
        }

        /* @var $row Row */
        $row = $result->current();
        /* @var $node Node */
        $link = $this->buildLink($row->offsetGet('link'));

        return $link;
    }

    /**
     * @param string $userId
     * @param string $type The type of relationship between the user and the links
     * @return array of links
     * @throws \Exception on failure
     */
    public function findLinksByUser($userId, $type = null)
    {
        $qb = $this->gm->createQueryBuilder();

        $type = (null !== $type) ? $type : '';
        if ($type != '') {
            $type = ':' . $type;
        }

        $qb->match('(u:User)-[' . $type . ']-(l:Link)')
            ->where('u.qnoow_id = { userId }')
            ->returns('l AS link');

        $qb->setParameter('userId', (integer)$userId);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $links = array();
        foreach ($result as $row) {
            /* @var $row Row */
            /* @var $node Node */
            $node = $row->offsetGet('link');

            $link = $node->getProperties();
            $link['id'] = $node->getId();

            $links[] = $link;
        }

        return $links;
    }

    /**
     * @param array $filters
     * @return array
     * @throws Neo4j\Neo4jException
     * @throws \Exception
     */
    public function findAllLinks($filters = array())
    {
        $linkLabels = $this->buildOptionalTypesLabel($filters);
        //todo: add tag filters, probably with an inter-model buildParamsFromFilters

        $qb = $this->gm->createQueryBuilder();

        $qb->match("(l:$linkLabels)")
            ->returns('l AS link');
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $links = array();
        /** @var Row $row */
        foreach ($result as $row) {
            $links[] = $this->buildLink($row->offsetGet('link'));
        }

        return $links;
    }

    /**
     * @param array $filters
     * @return int
     * @throws Neo4j\Neo4jException
     */
    public function countAllLinks($filters = array())
    {
        $linkLabels = $this->buildOptionalTypesLabel($filters);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: { userId }})');
        $qb->setParameter('userId', (integer)$filters['id']);
        if (isset($filters['tag'])) {
            $qb->match("(:Tag{name: { tag } })-[:TAGGED]-(l:$linkLabels)");
            $qb->setParameter('tag', $filters['tag']);
        } else {
            $qb->match("(l:$linkLabels)");
        }

        //TODO: Cache this at periodic calculations
//        $qb->with('user', 'l')
//            ->optionalMatch('(user)-[ua:AFFINITY]-(l)')
//            ->optionalMatch('(user)-[ul:LIKES]-(l)')
//            ->optionalMatch('(user)-[ud:DISLIKES]-(l)');
        $qb->returns('count(l) AS c');
        $query = $qb->getQuery();
        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();
        return $row->offsetGet('c');

    }

    /**
     * @param array $data
     * @return array
     */
    public function addOrUpdateLink(array $data)
    {
        if (!isset($data['url'])) {
            return array();
        }

        $data['title'] = isset($data['title']) ? $data['title'] : '';
        $data['description'] = isset($data['description']) ? $data['description'] : '';

        $link = $this->findLinkByUrl($data['url']);

        if (!$link) {
            return $this->addLink($data);
        }

        if (isset($link['processed']) || !$link['processed'] == 1) {
            $data['tempId'] = $data['url'];
            $newProcessed = isset($data['processed'])? $data['processed'] : true;
            return $this->updateLink($data, $newProcessed);
        }

        return array();
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function addLink(array $data)
    {
        if (!isset($data['url'])) {
            return array();
        }

        if ($saved = $this->findLinkByUrl($data['url'])) {
            $saved = isset($data['synonymous']) ? array_merge($saved, $this->addSynonymousLink($saved['id'], $data['synonymous'])) : $saved;

            return $saved;
        }

        $processed = isset($data['processed']) ? $data['processed'] : 1;

        $additionalLabels = '';
        if (isset($data['additionalLabels'])) {
            $additionalLabels = ':' . implode(':', $data['additionalLabels']);
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->create('(l:Link' . $additionalLabels . ')')
            ->set(
                'l.url = { url }',
                'l.title = { title }',
                'l.description = { description }',
                'l.language = { language }',
                'l.processed = { processed }',
                'l.created =  timestamp()'
            );

        if (isset($data['thumbnail']) && $data['thumbnail']) {
            $qb->set('l.thumbnail = { thumbnail }');
        }
        if (isset($data['additionalFields'])) {
            foreach ($data['additionalFields'] as $field => $value) {
                $qb->set(sprintf('l.%s = { %s }', $field, $field));
            }
        }

        $qb->returns('l');

        $qb->setParameters(
            array(
                'title' => $data['title'],
                'description' => $data['description'],
                'url' => $data['url'],
                'language' => isset($data['language']) ? $data['language'] : null,
                'thumbnail' => isset($data['thumbnail']) ? $data['thumbnail'] : null,
                'processed' => (integer)$processed,
            )
        );

        if (isset($data['additionalFields'])) {
            foreach ($data['additionalFields'] as $field => $value) {
                $qb->setParameter($field, $value);
            }
        }

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (isset($data['tags'])) {
            foreach ($data['tags'] as $tag) {
                $this->createTag($tag);
                $this->addTag($data, $tag);
            }
        }

        /* @var $row Row */
        $linkArray = array();
        foreach ($result as $row) {

            /** @var $link Node */
            $link = $row->offsetGet('l');
            foreach ($link->getProperties() as $key => $value) {
                $linkArray[$key] = $value;
            }
            $linkArray['id'] = $link->getId();
        }

        $linkArray = isset($data['synonymous']) ? array_merge($linkArray, $this->addSynonymousLink($linkArray['id'], $data['synonymous'])) : $linkArray;

        return $linkArray;
    }

    public function updateLink(array $data, $processed = false)
    {

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)');

        $conditions = array('l.url = { tempId }');
        if (!$processed) {
            $conditions[] = 'l.processed = 0';
        }
        $qb->where($conditions)
            ->set(
                'l.url = { url }',
                'l.title = { title }',
                'l.description = { description }',
                'l.language = { language }',
                'l.processed = { processed }',
                'l.updated = timestamp()'
            );

        if (isset($data['thumbnail']) && $data['thumbnail']) {
            $qb->set('l.thumbnail = { thumbnail }');
        }

        if (isset($data['additionalFields'])) {
            foreach ($data['additionalFields'] as $field => $value) {
                $qb->set(sprintf('l.%s = { %s }', $field, $field));
            }
        }

        if (isset($data['additionalLabels'])) {
            foreach ($data['additionalLabels'] as $label) {
                $qb->set('l:' . $label);
            }
        }

        $qb->returns('l');

        $qb->setParameters(
            array(
                'tempId' => $data['tempId'],
                'url' => $data['url'],
                'title' => $data['title'],
                'description' => $data['description'],
                'language' => isset($data['language']) ? $data['language'] : null,
                'processed' => (integer)$processed,
                'thumbnail' => isset($data['thumbnail']) ? $data['thumbnail'] : null,
            )
        );

        if (isset($data['additionalFields'])) {
            foreach ($data['additionalFields'] as $field => $value) {
                $qb->setParameter($field, $value);
            }
        }

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $linkArray = array();
        foreach ($result as $row) {

            /** @var $link Node */
            $link = $row->offsetGet('l');
            foreach ($link->getProperties() as $key => $value) {
                $linkArray[$key] = $value;
            }
            $linkArray['id'] = $link->getId();
        }

        $linkArray = isset($data['synonymous']) ? array_merge($linkArray, $this->addSynonymousLink($linkArray['id'], $data['synonymous'])) : $linkArray;

        return $linkArray;

    }

    public function removeLink($linkId)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(l:Link)')
            ->where('id(l) = { linkId }')
            ->optionalMatch('(l)-[r]-()')
            ->delete('l,r');

        $qb->setParameter('linkId', $linkId);

        $query = $qb->getQuery();

        return $query->getResultSet();
    }

    public function createTag(array $tag)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->merge('(tag:Tag {name: { name }})')
            ->setParameter('name', $tag['name'])
            ->returns('tag');

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        /* @var $row Row */
        $row = $result->current();
        /* @var $node Node */
        $node = $row->offsetGet('tag');

        if (isset($tag['additionalLabels']) && is_array($tag['additionalLabels'])) {
            $node->addLabels($this->gm->makeLabels($tag['additionalLabels']));
        }

        if (isset($tag['additionalFields']) && is_array($tag['additionalFields'])) {
            foreach ($tag['additionalFields'] as $field => $value) {
                $node->setProperty($field, $value);
            }
            $node->save();
        }

        return $node;

    }

    public function addTag($link, $tag)
    {

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(link:Link)', '(tag:Tag)')
            ->where('link.url = { url }', 'tag.name = { tag }')
            ->createUnique('(link)-[:TAGGED]->(tag)');

        $qb->setParameters(
            array(
                'url' => $link['url'],
                'tag' => $tag['name'],
            )
        );

        $query = $qb->getQuery();

        return $query->getResultSet();

    }

    public function getUnprocessedLinks($limit = 100)
    {

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(link:Link)')
            ->where('link.processed = 0')
            ->returns('link')
            ->limit('{ limit }');

        $qb->setParameters(
            array(
                'limit' => (integer)$limit
            )
        );

        $query = $qb->getQuery();

        $resultSet = $query->getResultSet();

        $unprocessedLinks = array();

        foreach ($resultSet as $row) {
            $unprocessedLinks[] = array(
                'url' => $row['link']->getProperty('url'),
                'description' => $row['link']->getProperty('description'),
                'title' => $row['link']->getProperty('title'),
                'tempId' => $row['link']->getProperty('url'),
            );
        }

        return $unprocessedLinks;

    }

    /**
     * @param array $filters
     * @return bool
     * @throws Neo4j\Neo4jException
     */
    public function updatePopularity(array $filters)
    {

        $qb = $this->gm->createQueryBuilder();

        //get max likes from link with popularity = 1
        $qb->optionalMatch('(l_max:Link)')
            ->where('l_max.popularity = 1')
            ->with('l_max')
            ->limit(1)
            ->optionalMatch('(l_max)-[likes:LIKES]-(:User)')
            //if that link's popularity was calculated more than a day ago, max = 0 as a flag to recalculate
            ->with('CASE
                        WHEN l_max.popularity_timestamp > timestamp()-1000*3600*24 THEN
                            count(likes)
                        ELSE
                            0
                    END as max LIMIT 1
                        ');
        //get links from user (or all!) to set popularity
        if (isset($filters['userId'])) {

            $qb->optionalMatch('(:User {qnoow_id: { id } })-[LIKES]-(l:Link)');
            $qb->setParameter('id', (integer)$filters['userId']);

        } else {

            $qb->optionalMatch('(l:Link)');
        }

        $qb->where('l.popularity_timestamp < timestamp() - 1000*3600*24');
        $qb->with('l', 'max');
        $qb->optionalMatch('(l)-[r:LIKES]-(:User)')
            ->with('l', 'count(DISTINCT r) AS total', 'max')
            ->where('total > 1')
            ->with('l', 'toFloat(total) AS total', 'toFloat(max) AS max');

        if (isset($filters['limit'])) {

            $qb->orderBy('HAS(l.popularity_timestamp)', 'l.popularity_timestamp')
                ->limit('{ limit }');
            $qb->setParameter('limit', (integer)$filters['limit']);
        }

        //ensures max = 0 gives no problems
        $qb->set(
            'l.popularity = CASE max
                                WHEN 0 THEN 0
                                ELSE (total/max)^3
                            END',
            'l.unpopularity = CASE max
                                WHEN 0 THEN 1
                                ELSE (1-(total/max))^3
                            END',
            'l.popularity_timestamp =   CASE max
                                            WHEN 0 THEN 0
                                            ELSE timestamp()
                                        END'
        );

        $qb->returns('max')
            ->limit(1);

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        //If user had no links to set popularity, all done
        if ($result->count() == 0) {
            return true;
        }

        $max = $result->current()->offsetGet('max');
        if ($max == 0) {
            $this->updateMaxPopularity();
            return $this->updatePopularity($filters);
        }
        return true;
    }

    /**
     * @param integer $userId
     * @param int $limitContent
     * @param int $limitUsers
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function getPredictedContentForAUser($userId, $limitContent = 40, $limitUsers = 20, array $filters = array())
    {
        $linkTypes = array('Link');
        if (isset($filters['type'])) {
            $linkTypes = $filters['type'];
        }

        $linkLabels = implode('|:', $linkTypes);

        $params = array(
            'userId' => (integer)$userId,
            'limitContent' => (integer)$limitContent,
            'limitUsers' => (integer)$limitUsers,

            'internalOffset' => 0,
            'internalLimit' => 100,
        );

        $links = array();

        //TODO: Cache this at periodic calculations
        //$maxOffset = $this->countPredictableContent($userId, $limitUsers);
        $maxOffset = 5000;

        while (count($links) < $limitContent && $params['internalOffset'] < $maxOffset) {

            $qb = $this->gm->createQueryBuilder();
            $qb->match('(u:User {qnoow_id: { userId } })')
                ->match('(u)-[r:SIMILARITY]-(users:User)')
                ->with('users,u,r.similarity AS m')
                ->orderby('m DESC')
                ->limit('{limitUsers}');

            $qb->match('(users)-[:LIKES]->(l:' . $linkLabels . ')');

            $qb->with('u', 'avg(m) as average', 'count(m) as amount', 'l')
                ->where('amount >= 2');
            $qb->with('u', 'average', 'l')
                ->orderBy('l.created DESC')
                ->skip('{internalOffset}')
                ->limit('{internalLimit}');

            $conditions = array('l.processed = 1', 'NOT (u)-[:LIKES]-(l)', 'NOT (u)-[:DISLIKES]-(l)');
            if (!(isset($filters['affinity']) && $filters['affinity'] == true)) {
                $conditions[] = '(NOT (u)-[:AFFINITY]-(l))';
            };
            $qb->where($conditions);
            if (isset($filters['tag'])) {
                $qb->match('(l)-[:TAGGED]->(filterTag:Tag)')
                    ->where('filterTag.name IN { filterTags } ');

                $params['filterTags'] = $filters['tag'];
            }

            $qb->with('l', 'average');
            $qb->optionalMatch('(l)-[:TAGGED]->(tag:Tag)')
                ->optionalMatch("(l)-[:SYNONYMOUS]->(synonymousLink:Link)")
                ->returns('id(l) as id',
                    'l as link',
                    'average',
                    'collect(distinct tag.name) as tags',
                    'labels(l) as types',
                    'COLLECT (DISTINCT synonymousLink) AS synonymous')
                ->orderby('average DESC')
                ->limit('{limitContent}');

            $qb->setParameters($params);

            $query = $qb->getQuery();
            $result = $query->getResultSet();

            $links = array();
            foreach ($result as $row) {
                /* @var $row Row */
                $links[] = $this->buildLink($row->offsetGet('link'));
            }

            $params['internalOffset'] += $params['internalLimit'];
        }

        return $links;
    }

    /**
     * @param $userId
     * @param int $limitContent
     * @param int $maxUsers
     * @param array $filters
     * @return array
     */
    public function getLivePredictedContent($userId, $limitContent = 20, $maxUsers = 10, array $filters = array())
    {
        $users = 2;
        $content = array();
        while (($users <= $maxUsers) && count($content) < $limitContent) {
            $content = array();
            $predictedContents = $this->getPredictedContentForAUser($userId, $limitContent, $users, $filters);
            foreach ($predictedContents as $predictedContent) {
                $content[] = array('content' => $predictedContent);
            }
            $users++;
        }
        return $content;
    }

    protected function countPredictableContent($userId, $users = 10)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { userId } })')
            ->match('(u)-[r:SIMILARITY]-(users:User)')
            ->with('users,u,r.similarity AS m')
            ->limit('{limitUsers}');
        $qb->match('(users)-[:LIKES]->(l:Link)')
            ->where('NOT (u)-[:LIKES]-(l)', 'NOT (u)-[:DISLIKES]-(l)', 'NOT (u)-[:AFFINITY]-(l)');
        $qb->returns('count(l) AS c');
        $qb->setParameters(array(
            'userId' => (integer)$userId,
            'limitUsers' => $users,
        ));

        $resultSet = $qb->getQuery()->getResultSet();

        return $resultSet->current()->offsetGet('c');
    }

    /**
     * @param $userId
     * @param $linkId
     * @return bool If the link was already notified to the user
     * @throws \Exception
     */
    public function setLinkNotified($userId, $linkId)
    {
        if ($linkId == null || $userId == null) {
            return false;
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link), (u:User{qnoow_id:{userId}})')
            ->where('id(l)={linkId}')
            ->optionalMatch('(u)-[n2:NOTIFIED]->(l)')
            ->merge('(u)-[n:NOTIFIED]->(l)
                        ON CREATE SET n.timestamp = timestamp()')
            ->returns('(NOT n2 IS NULL) as existed');
        $qb->setParameters(array('userId' => (integer)$userId,
            'linkId' => (integer)$linkId));

        $query = $qb->getQuery();
        $resultSet = $query->getResultSet();
        /* @var $row Row */
        $row = $resultSet->current();
        return $row->offsetGet('existed');
    }

    /**
     * @param $userId
     * @param $linkId
     * @return bool If the link was notified
     * @throws \Exception
     */
    public function unsetLinkNotified($userId, $linkId)
    {
        if ($linkId == null || $userId == null) {
            return false;
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link), (u:User{qnoow_id:{userId}})')
            ->where('id(l)={linkId}')
            ->optionalMatch('(u)-[n:NOTIFIED]->(l)')
            ->delete('n')
            ->returns('(NOT (n IS NULL)) as existed');
        $qb->setParameters(array('userId' => (integer)$userId,
            'linkId' => (integer)$linkId));

        $query = $qb->getQuery();
        $resultSet = $query->getResultSet();
        /* @var $row Row */
        $row = $resultSet->current();
        return $row->offsetGet('existed');
    }

    /**
     * @param $userId
     * @param $linkId
     * @return integer|null
     * @throws \Exception
     */
    public function getWhenNotified($userId, $linkId)
    {
        if ($linkId == null || $userId == null) {
            return false;
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link), (u:User{qnoow_id:{userId}})')
            ->where('id(l)={linkId}')
            ->optionalMatch('(u)-[n:NOTIFIED]->(l)')
            ->returns('n.timestamp as when');
        $qb->setParameters(array('userId' => (integer)$userId,
            'linkId' => (integer)$linkId));

        $query = $qb->getQuery();
        $resultSet = $query->getResultSet();
        /* @var $row Row */
        if ($resultSet->count() == 0) {
            return null;
        }
        $row = $resultSet->current();
        return $row->offsetGet('when');
    }

    /**
     * @param $id
     * @return array
     * @throws \Exception
     */
    public function cleanInconsistencies($id)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(l:Link)')
            ->where('id(l)={id}')
            ->match('(l)<-[r1:LIKES]-(u)')
            ->optionalMatch('(l)<-[r2:DISLIKES]-(u)')
            ->optionalMatch('(l)<-[r3:AFFINITY]-(u)')
            ->delete('r2,r3')
            ->returns('count(r2) AS dislikes, count(r3) AS affinities');
        $qb->setParameter('id', (integer)$id);
        $rs = $qb->getQuery()->getResultSet();
        /* @var $row Row */
        $row = $rs->current();
        $result = array();
        $result['affinities'] = $row->offsetGet('affinities');
        $result['dislikes'] = $row->offsetGet('dislikes');
        return $result;
    }

    /**
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws Neo4j\Neo4jException
     */
    public function findDuplicates($offset = 0, $limit = 99999999)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->setParameters(array(
            'offset' => $offset,
            'limit' => (integer)$limit,
        ));

        $qb->match('(l:Link)')
            ->with('l')
            ->orderBy('l.created DESC')
            ->skip('{offset}')
            ->limit('{limit}')
            ->with('l.url AS url, COLLECT(ID(l)) AS ids, COUNT(*) AS count')
            ->where('count > 1')
            ->returns('url, ids');

        $rs = $qb->getQuery()->getResultSet();
        $result = array();
        /** @var $row Row */
        foreach ($rs as $row) {
            for ($i = 1; $i < count($row->offsetGet('ids')); $i++) {
                $duplicate = array();
                $duplicate['main'] = array('id' => $row->offsetGet('ids')[0],
                    'url' => $row->offsetGet('url'));
                $duplicate['duplicate'] = array('id' => $row->offsetGet('ids')[$i],
                    'url' => $row->offsetGet('url'));
                $result[] = $duplicate;
            }
        }
        return $result;
    }

    /**
     * @param $node Node
     * @return array
     */
    public function buildLink(Node $node)
    {
        $link = $node->getProperties();
        $link['id'] = $node->getId();

        $mandatoryKeys = array('title', 'description', 'url');

        foreach ($mandatoryKeys as $mandatoryKey) {
            if (!array_key_exists($mandatoryKey, $link)) {
                $link[$mandatoryKey] = null;
            }
        }

        return $link;
    }

    public function getValidTypes($locale = 'en')
    {
        $this->translator->setLocale($locale);

        $types = array();
        $keyTypes = array('Audio', 'Video', 'Image', 'Link', 'Creator');

        foreach ( $keyTypes as $type){
            $types[$type] = $this->translator->trans('types.'.lcfirst($type));
        };
        return $types;
    }

    public function buildOptionalTypesLabel($filters){
        $linkTypes = array('Link');
        if (isset($filters['type'])) {
            $linkTypes = $filters['type'];
        }

        return implode('|:', $linkTypes);
    }

    private function addSynonymousLink($id, $synonymousLinks)
    {

        $linkArray = array();

        if (!empty($synonymousLinks)) {
            foreach ($synonymousLinks as $synonymous) {
                $synonymous = $this->addLink($synonymous);
                $qb = $this->gm->createQueryBuilder();
                $qb->match('(l:Link)')
                    ->where('id(l) = { id }')
                    ->match('(synonymousLink:Link)')
                    ->where('id(synonymousLink) = { synonymousId }')
                    ->merge('(l)-[:SYNONYMOUS]-(synonymousLink)')
                    ->returns('synonymousLink')
                    ->setParameters(
                        array(
                            'id' => $id,
                            'synonymousId' => $synonymous['id'],
                        )
                    );

                $query = $qb->getQuery();

                $result = $query->getResultSet();

                $linkArray['synonymous'] = array();
                /* @var $row Row */
                foreach ($result as $index => $row) {

                    /** @var $link Node */
                    $link = $row->offsetGet('synonymousLink');
                    foreach ($link->getProperties() as $key => $value) {
                        $linkArray['synonymous'][$index][$key] = $value;
                    }
                    $linkArray['synonymous'][$index]['id'] = $link->getId();
                }
            }
        }

        return $linkArray;
    }

    private function updateMaxPopularity()
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)-[likes:LIKES]-(:User)')
            ->with('l', 'count(likes) AS amount')
            ->with('collect(l) as links', 'amount')
            ->orderBy('amount DESC')
            ->limit(1)
            ->add('unwind', 'links as l')
            ->set('l.popularity = 1', 'l.unpopularity = 0', 'l.popularity_timestamp = timestamp()');
        $query = $qb->getQuery();
        $query->getResultSet();
    }

}
