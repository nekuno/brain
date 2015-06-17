<?php

namespace Model;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;

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

    public function __construct(GraphManager $gm)
    {

        $this->gm = $gm;
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
                'l.processed = 1',
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

        $qb->match('(l:Link)')
            ->where('l.url = { tempId }')
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

    public function updatePopularity(array $filters)
    {

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)-[r:LIKES]-(:User)')
            ->with('l', 'count(DISTINCT r) AS total')
            ->where('total > 1')
            ->with('total AS max')
            ->orderBy('max DESC')
            ->limit(1);

        if (isset($filters['userId'])) {

            $qb->match('(:User {qnoow_id: { id } })-[LIKES]-(l:Link)');
            $qb->setParameter('id', (integer)$filters['userId']);

        } else {

            $qb->match('(l:Link)');
        }

        $qb->match('(l)-[r:LIKES]-(:User)')
            ->with('l', 'count(DISTINCT r) AS total', 'max')
            ->where('total > 1')
            ->with('l', 'toFloat(total) AS total', 'toFloat(max) AS max');

        if (isset($filters['limit'])) {

            $qb->orderBy('HAS(l.popularity_timestamp)', 'l.popularity_timestamp')
                ->limit('{ limit }');
            $qb->setParameter('limit', (integer)$filters['limit']);
        }

        $qb->set(
            'l.popularity = (total/max)^3',
            'l.unpopularity = (1-(total/max))^3',
            'l.popularity_timestamp = timestamp()'
        );

        $query = $qb->getQuery();

        $query->getResultSet();

        return true;
    }

    /**
     * @param integer $userId
     * @param int $limitContent
     * @param int $limitUsers
     * @param bool $includeAffinity For recalculating affinities
     * @return array
     * @throws \Exception
     */
    public function getPredictedContentForAUser($userId, $limitContent = 40, $limitUsers = 20, $includeAffinity = false)
    {
        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { userId } })')
            ->match('(u)-[r:SIMILARITY]-(users:User)')
            ->with('users,u,r.similarity AS m')
            ->orderby('m DESC')
            ->limit('{limitUsers}');

        $qb->match('(users)-[d:LIKES]->(l:Link)');
        $conditions = array('(NOT (u)-[:LIKES|:DISLIKES]-(l))');
        if (!$includeAffinity) {
            $conditions[] = '(NOT (u)-[:AFFINITY]-(l))';
        };
        $qb->where($conditions)
            ->with('l, avg(m) AS average, count(d) AS amount')
            ->where('amount>=2')
            ->returns('l as link')
            ->orderby('average DESC')
            ->limit('{limitContent}');

        $qb->setParameters(
            array(
                'userId' => (integer)$userId,
                'limitContent' => (integer)$limitContent,
                'limitUsers' => (integer)$limitUsers,
            )
        );

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        $links = array();
        foreach ($result as $row) {
            /* @var $row Row */
            $links[] = $this->buildLink($row->offsetGet('link'));
        }

        return $links;
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
     * @return \Everyman\Neo4j\Query\ResultSet
     * @throws \Exception
     */
    public function findDuplicates()
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)')
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
     * @return array
     * @throws \Exception
     */
    public function findPseudoduplicates()
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l1:Link), (l2:Link)')
            ->where('l2.url=l1.url+"/" OR l2.url=l1.url+"?" OR l2.url=l1.url+"&"')
            ->returns('id(l1) AS id1, l1.url AS url1, id(l2) AS id2, l2.url AS url2');
        $rs = $qb->getQuery()->getResultSet();
        $result = array();
        /** @var $row Row */
        foreach ($rs as $row) {
            $duplicate = array();
            $duplicate['main'] = array('id' => $row->offsetGet('id1'),
                'url' => $row->offsetGet('url1'));
            $duplicate['duplicate'] = array('id' => $row->offsetGet('id2'),
                'url' => $row->offsetGet('url2'));
            $result[] = $duplicate;
        }
        return $result;
    }

    /**
     * @param $url
     * @return boolean
     */
    private function isAlreadySaved($url)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('l.url = { url }')
            ->returns('l')
            ->limit(1);

        $qb->setParameter('url', $url);

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if ($result->count() > 0) {
            return true;
        }

        return false;
    }

    /**
     * @param $node Node
     * @return array
     */
    protected function buildLink(Node $node)
    {
        $link = $node->getProperties();
        $link['id'] = $node->getId();

        return $link;
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
