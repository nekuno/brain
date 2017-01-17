<?php

namespace Model;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\User\Recommendation\ContentRecommendation;
use Symfony\Component\Translation\Translator;


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
     * @param array $urls
     * @return array
     */
    public function findLinksByUrls(array $urls)
    {
        $links = array();
        foreach ($urls as $url) {
            $link = $this->findLinkByUrl($url);
            if ($link) {
                $links[] = $link;
            }
        }

        return $links;
    }

    //TODO: Check if findLinkById not used and delete
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
     * @param array $conditions
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function getLinks($conditions = array(), $offset = 0, $limit = 100)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(link:Link)')
            ->where($conditions)
            ->returns('link')
            ->skip('{ offset }')
            ->limit('{ limit }');

        $qb->setParameters(
            array(
                'limit' => (integer)$limit,
                'offset' => (integer)$offset,
            )
        );

        $query = $qb->getQuery();
        $resultSet = $query->getResultSet();

        $unprocessedLinks = array();
        foreach ($resultSet as $row) {
            $unprocessedLinks[] = $this->buildLink($row->offsetGet('link'));
        }

        return $unprocessedLinks;
    }

    /**
     * @param array $filters
     * @return int
     * @throws Neo4j\Neo4jException
     */
    public function countAllLinks($filters = array())
    {
        $types = isset($filters['type']) ? $filters['type'] : array();
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(user:User {qnoow_id: { userId }})');
        $qb->setParameter('userId', (integer)$filters['id']);
        if (isset($filters['tag'])) {
            $qb->match('(l:Link{processed: 1})-[:TAGGED]->(filterTag:Tag)')
                ->where('filterTag.name IN { filterTags } ');
            $qb->setParameter('filterTags', $filters['tag']);
        } else {
            $qb->match('(content:Link{processed: 1})');
        }

        $qb->filterContentByType($types, 'l');

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
     * @throws \Exception
     */
    public function addOrUpdateLink(array $data)
    {
        $this->validateLinkData($data);

        $data = $this->limitTextLengths($data);

        $savedLink = $this->findLinkByUrl($data['url']);

        if (!$savedLink) {
            return $this->addLink($data);
        }

        if (isset($savedLink['processed']) && !$savedLink['processed'] == 1) {
            $data['tempId'] = isset($data['tempId']) ? $data['tempId'] : $data['url'];
            $newProcessed = isset($data['processed']) ? $data['processed'] : true;

            return $this->updateLink($data, $newProcessed);
        } else if (isset($data['processed']) && $data['processed'] == 1) {
            $changedCount = $this->partialUpdate($savedLink, $data);

            return $changedCount > 0 ? $this->findLinkByUrl($data['url']) : $savedLink;
        } else {
            return $savedLink;
        }
    }

    /**
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function addLink(array $data)
    {
        $this->validateLinkData($data);

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
                'l.imageProcessed = timestamp()',
                'l.created =  timestamp()' //TODO: If there is created, use this instead (coalesce)
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
        $this->validateLinkData($data);

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
                'l.imageProcessed = timestamp()',
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

        $qb->remove('l:Audio:Video:Image:Creator');
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

        $linkArray = array();
        if ($result->count() == 0) {
            $link = $this->findLinkByUrl($data['url']);
            $linkArray['id'] = $link['id'];
        }

        /* @var $row Row */
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

    private function validateLinkData($data)
    {
        if (!isset($data['url'])) {
            throw new \Exception(sprintf('Url not present while saving link %s', json_encode($data)));
        }

        if (isset( $data['additionalLabels'])){
            foreach ($data['additionalLabels'] as $label){
                if (!in_array($label, $this->getValidTypes())){
                    throw new \Exception(sprintf('Trying to set invalid link label %s', $label));
                }
            }
        }
    }

    private function partialUpdate(array $oldLink, array $newLink)
    {
        $changedCount = 0;
        foreach (array('title', 'description', 'thumbnail') as $field) {
            if (!isset($oldLink[$field]) && isset($newLink[$field]) && null != $newLink[$field]) {
                $hasChanged = $this->setLinkProperty($newLink['url'], $field, $newLink[$field]);
                $changedCount += (integer)$hasChanged;
            }
        }

        return $changedCount;
    }

    private function setLinkProperty($url, $key, $value)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link{url: {url}})')
            ->with('l')
            ->limit(1)
            ->setParameter('url', $url);

        $qb->set("l.$key = { value }")
            ->setParameter('value', $value);

        $qb->returns('l');

        $result = $qb->getQuery()->getResultSet();

        return $result->count() > 0 && $result->current()->offsetExists('l');
    }

    public function setProcessed($url, $processed = true)
    {
        $processedParameter = $processed ? 1 : 0;

        return $this->setLinkProperty($url, 'processed', $processedParameter);
    }

    public function changeUrl($oldUrl, $newUrl)
    {
        return $this->setLinkProperty($oldUrl, 'url', $newUrl);
    }

    public function getTypes($url)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link {url: { url }})')
            ->with('l')
            ->limit(1)
            ->setParameter('url', $url);

        $qb->returns('labels(l) as types');

        $result = $qb->getQuery()->getResultSet();
        $row = $result->current();

        $types = array();
        if (isset($row['types'])) {
            foreach ($row['types'] as $type) {
                $types[] = $type;
            }
        }

        return $types;
    }

    public function removeLink($linkUrl)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(l:Link)')
            ->where('l.url = { linkUrl }')
            ->optionalMatch('(l)-[r]-()')
            ->delete('l,r');

        $qb->setParameter('linkUrl', $linkUrl);

        $result = $qb->getQuery()->getResultSet();

        return $result->count() > 0;
    }

    public function fuseLinks($oldUrl, $newUrl)
    {
        $oldLink = $this->findLinkByUrl($oldUrl);
        $newLink = $this->findLinkByUrl($newUrl);

        $this->gm->fuseNodes($oldLink['id'], $newLink['id']);
        $this->changeUrl($oldUrl, $newUrl);

        return $newLink['id'];
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
        $types = isset($filters['type']) ? $filters['type'] : array();

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
                ->orderBy('m DESC')
                ->limit('{limitUsers}');

            $qb->match('(users)-[:LIKES]->(l:Link)');
            $qb->filterContentByType($types, 'l', array('m', 'u'));

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
                ->returns(
                    'id(l) as id',
                    'l as link',
                    'average',
                    'collect(distinct tag.name) as tags',
                    'labels(l) as types',
                    'COLLECT (DISTINCT synonymousLink) AS synonymous'
                )
                ->orderBy('average DESC')
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
     * @return ContentRecommendation[]
     */
    public function getLivePredictedContent($userId, $limitContent = 20, $maxUsers = 10, array $filters = array())
    {
        $users = 2;
        $content = array();
        while (($users <= $maxUsers) && count($content) < $limitContent) {
            $content = array();
            $predictedContents = $this->getPredictedContentForAUser($userId, $limitContent, $users, $filters);
            foreach ($predictedContents as $predictedContent) {
                $eachContent = new ContentRecommendation();
                $eachContent->setContent($predictedContent);
                $content[] = $eachContent;
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
        $qb->setParameters(
            array(
                'userId' => (integer)$userId,
                'limitUsers' => $users,
            )
        );

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
            ->merge(
                '(u)-[n:NOTIFIED]->(l)
                        ON CREATE SET n.timestamp = timestamp()'
            )
            ->returns('(NOT n2 IS NULL) as existed');
        $qb->setParameters(
            array(
                'userId' => (integer)$userId,
                'linkId' => (integer)$linkId
            )
        );

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
        $qb->setParameters(
            array(
                'userId' => (integer)$userId,
                'linkId' => (integer)$linkId
            )
        );

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
        $qb->setParameters(
            array(
                'userId' => (integer)$userId,
                'linkId' => (integer)$linkId
            )
        );

        $query = $qb->getQuery();
        $resultSet = $query->getResultSet();
        /* @var $row Row */
        if ($resultSet->count() == 0) {
            return null;
        }
        $row = $resultSet->current();

        return $row->offsetGet('when');
    }

    //TODO: Move to ConsistencyCheckerService
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
     * @param array $links
     * @return array
     */
    public function findDuplicates(array $links)
    {
        $urls = array();
        foreach ($links as $link) {
            $urls[] = $link['url'];
        }

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('l.url IN { urls }')
            ->setParameter('urls', $urls)
            ->with('l.url AS url', 'count(l) AS amount')
            ->where('amount > 1');

        $qb->match('(l:Link)')
            ->where('l.url = url')
            ->with('l.url AS url', 'collect(id(l)) AS ids')
            ->returns('url, ids');

        $rs = $qb->getQuery()->getResultSet();
        $result = array();
        /** @var $row Row */
        foreach ($rs as $row) {
            for ($i = 1; $i < count($row->offsetGet('ids')); $i++) {
                $duplicate = array();
                $duplicate['main'] = array(
                    'id' => $row->offsetGet('ids')[0],
                    'url' => $row->offsetGet('url')
                );
                $duplicate['duplicate'] = array(
                    'id' => $row->offsetGet('ids')[$i],
                    'url' => $row->offsetGet('url')
                );
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

    private function limitTextLengths(array $data)
    {
        foreach (array('title', 'description') as $key) {
            $value = isset($data[$key]) ? $data[$key] : '';
            $data[$key] = strlen($value) >= 25 ? mb_substr($value, 0, 22, 'UTF-8') . '...' : $value;;
        }

        return $data;
    }

    //TODO: Refactor this to use locale keys or move them to fields.yml
    public function getValidTypes()
    {
        return array('Audio', 'Video', 'Image', 'Link', 'Creator');
    }

    //TODO: Only called from ContentFilterModel. Probably move logic and translator dependency there.
    public function getValidTypesLabels($locale = 'en')
    {
        $this->translator->setLocale($locale);

        $types = array();
        $keyTypes = $this->getValidTypes();

        foreach ($keyTypes as $type) {
            $types[$type] = $this->translator->trans('types.' . lcfirst($type));
        };

        return $types;
    }

    public function buildOptionalTypesLabel($filters)
    {
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

}
