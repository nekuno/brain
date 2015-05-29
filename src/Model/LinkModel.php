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
        $node = $row->offsetGet('link');

        $link = $node->getProperties();
        $link['id'] = $node->getId();

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
     * @param integer $limit Max Number of content to return
     * @return array
     * @throws \Exception
     */
    public function getPredictedContentForAUser($userId, $limit = 10)
    {

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User {qnoow_id: { uid } })')
            ->match('(u)-[r:SIMILARITY]-(users:User)')
            ->with('users,u,r.similarity AS m')
            ->orderby('m DESC');

        $qb->match('(users)-[d:LIKES]->(l:Link)')
            ->where('NOT(u)-[:LIKES|:DISLIKES|:AFFINITY]-(l)')
            ->with('id(l) AS id, avg(m) AS average, count(d) AS amount')
            ->where('amount>=2')
            ->returns('id')
            ->orderby('average DESC')
            ->limit('{ limit }');

        $qb->setParameters(
            array(
                'uid' => (integer)$userId,
                'limit' => (integer)$limit,
            )
        );

        $query = $qb->getQuery();
        $result = $query->getResultSet();

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

    private function addSynonymousLink($id, $synonymousLinks)
    {
        $qb = $this->gm->createQueryBuilder();
        $linkArray = array();

        if (!empty($synonymousLinks)) {
            foreach ($synonymousLinks as $synonymous) {
                $synonymous = $this->addLink($synonymous);
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
