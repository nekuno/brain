<?php

namespace Model\Link;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Query\Row;
use Model\Neo4j\GraphManager;
use Model\Recommendation\ContentRecommendation;
use Symfony\Component\Translation\Translator;

class LinkManager
{

    /**
     * @var GraphManager
     */
    protected $gm;

    /**
     * @var Translator
     */
    protected $translator;

    public function __construct(GraphManager $gm)
    {
        $this->gm = $gm;
    }

    /**
     * @param string $url
     * @return false|Link
     */
    public function findLinkByUrl($url)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)')
            ->where('l.url IN [{url}, {lowerUrl}]')
            ->returns('l AS link')
            ->limit('1');

        $qb->setParameter('url', $url);
        $qb->setParameter('lowerUrl', strtolower($url));

        $query = $qb->getQuery();

        $result = $query->getResultSet();

        if (count($result) <= 0) {
            return false;
        }

        /* @var $row Row */
        $row = $result->current();
        /* @var $node Node */
        $link = $this->buildLink($row->offsetGet('link'));
        $link = $this->buildLinkObject($link);

        return $link;
    }

    /**
     * @param array $urls
     * @return Link[]
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

    /**
     * @param array $conditions
     * @param int $offset
     * @param int $limit
     * @return Link[]
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
            $linkArray = $this->buildLink($row->offsetGet('link'));
            $link = $this->buildLinkObject($linkArray);
            $unprocessedLinks[] = $link;
        }

        return $unprocessedLinks;
    }

    /**
     * @param Link $link
     * @return Link
     * @throws \Exception
     */
    public function mergeLink(Link $link)
    {
        $this->limitTextLengths($link);

        $savedLink = $this->findLinkByUrl($link->getUrl());
        if (!$savedLink) {
            return $this->addLink($link->toArray());
        }

        if ($this->canOverwrite($savedLink, $link)) {
            return $this->updateLink($link->toArray());
        }

        return $savedLink;
    }

    public function canOverwrite(Link $oldLink, Link $newLink)
    {
        return $oldLink->getProcessed() == 0 || $newLink->getProcessed() == 1;
    }

    /**
     * @param array $data
     * @return false|Link
     * @throws \Exception
     */
    public function addLink(array $data)
    {
        $this->validateLinkData($data);

        if ($saved = $this->findLinkByUrl($data['url'])) {

            $synonymous = $this->addSynonymousLink($saved->getId(), $data['synonymous']);
            $saved->setSynonymous($synonymous);

            return $saved;
        }

        $processed = isset($data['processed']) ? $data['processed'] : 1;

        if ($this->hasToAddWebLabel($data)) {
            $data['additionalLabels'][] = Link::WEB_LABEL;
        }
        $additionalLabels = ':' . implode(':', $data['additionalLabels']);

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

        $qb->setParameters(
            array(
                'title' => $data['title'],
                'description' => $data['description'],
                'url' => $data['url'],
                'language' => isset($data['language']) ? $data['language'] : null,
                'thumbnail' => isset($data['thumbnail']) ? $data['thumbnail'] : null,
                'thumbnailSmall' => isset($data['thumbnailSmall']) ? $data['thumbnailSmall'] : null,
                'thumbnailMedium' => isset($data['thumbnailMedium']) ? $data['thumbnailMedium'] : null,
                'processed' => (integer)$processed,
            )
        );

        if (isset($data['thumbnail']) && $data['thumbnail']) {
            $qb->set('l.thumbnail = { thumbnail }', 'l.thumbnailSmall = { thumbnailSmall }', 'l.thumbnailMedium = { thumbnailMedium }');
        }
        if (isset($data['additionalFields'])) {
            foreach ($data['additionalFields'] as $field => $value) {
                $qb->set(sprintf('l.%s = { %s }', $field, $field));
                $qb->setParameter($field, $value);
            }
        }

        $qb->returns('l');

        $query = $qb->getQuery();
        $result = $query->getResultSet();

        if ($result->count() === 0) {
            return null;
        }

        if (isset($data['tags'])) {
            foreach ($data['tags'] as $tag) {
                $this->mergeTag($tag);
                $this->addTag($data, $tag);
            }
        }

        /** @var $linkNode Node */
        $linkNode = $result->current()->offsetGet('l');
        $linkArray = $this->buildLink($linkNode);
        $link = $this->buildLinkObject($linkArray);

        $linkSynonymous = $this->addSynonymousLink($link->getId(), $data['synonymous']);
        $link->setSynonymous($linkSynonymous);

        return $link;
    }

    /**
     * @param array $data
     * @return false|Link
     */
    public function updateLink(array $data)
    {
        $this->validateLinkData($data);

        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link)');

        $conditions = array('l.url = { url }');

        $qb->where($conditions)
            ->set(
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

        $qb->remove('l:Audio:Video:Image:Creator:Game');
        if (isset($data['additionalLabels'])) {
            foreach ($data['additionalLabels'] as $label) {
                $qb->set('l:' . $label);
            }
        }

        $qb->returns('l');

        $qb->setParameters(
            array(
                'url' => $data['url'],
                'title' => $data['title'],
                'description' => $data['description'],
                'language' => isset($data['language']) ? $data['language'] : null,
                'processed' => (integer)$data['processed'],
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

        if ($result->count() == 0) {
            $link = $this->findLinkByUrl($data['url']);
        } else {
            /** @var $linkNode Node */
            $linkNode = $result->current()->offsetGet('l');
            $linkArray = $this->buildLink($linkNode);
            $link = $this->buildLinkObject($linkArray);
        }

        if (isset($data['tags'])) {
            foreach ($data['tags'] as $tag) {
                $this->mergeTag($tag);
                $this->addTag($data, $tag);
            }
        }

        $linkSynonymous = $this->addSynonymousLink($link->getId(), $data['synonymous']);
        $link->setSynonymous($linkSynonymous);

        return $link;
    }

    private function hasToAddWebLabel($data)
    {
        if (isset($data['additionalLabels'])) {
            if (in_array(Link::WEB_LABEL, $data['additionalLabels'])) {
                return false;
            }
            if (count(array_intersect(array(Audio::AUDIO_LABEL, Video::VIDEO_LABEL, Creator::CREATOR_LABEL, Image::IMAGE_LABEL, Game::GAME_LABEL), $data['additionalLabels']))) {
                return false;
            }
        }

        return true;
    }

    //TODO: Improve and use Validator service
    private function validateLinkData($data)
    {
        if (!isset($data['url'])) {
            throw new \Exception(sprintf('Url not present while saving link %s', json_encode($data)));
        }

        if (isset($data['additionalLabels'])) {
            foreach ($data['additionalLabels'] as $label) {
                if (!in_array($label, self::getValidTypes())) {
                    throw new \Exception(sprintf('Trying to set invalid link label %s', $label));
                }
            }
        }
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

    private function setLinkPropertyTimestamp($url, $key)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link{url: {url}})')
            ->with('l')
            ->limit(1)
            ->setParameter('url', $url);

        $qb->set("l.$key = timestamp()");

        $qb->returns('l');

        $result = $qb->getQuery()->getResultSet();

        return $result->count() > 0 && $result->current()->offsetExists('l');
    }

    private function sumLinkProperty($url, $key, $value)
    {
        $qb = $this->gm->createQueryBuilder();

        $qb->match('(l:Link{url: {url}})')
            ->with('l')
            ->limit(1)
            ->setParameter('url', $url);

        $qb->set("l.$key = COALESCE(l.$key, 0) + { value }")
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

    public function setLastChecked($url)
    {
        return $this->setLinkPropertyTimestamp($url, 'lastChecked');
    }

    public function setLastReprocessed($url)
    {
        return $this->setLinkPropertyTimestamp($url, 'lastReprocessed');
    }

    public function initializeReprocessed($url)
    {
        return $this->setLinkProperty($url, 'reprocessedCount', 0);
    }

    public function increaseReprocessed($url)
    {
        return $this->sumLinkProperty($url, 'reprocessedCount', 1);
    }

    public function changeUrl($oldUrl, $newUrl)
    {
        return $this->setLinkProperty($oldUrl, 'url', $newUrl);
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

    /**
     * @param $oldUrl
     * @param $newUrl
     * @return false|Link
     */
    public function fuseLinks($oldUrl, $newUrl)
    {
        $oldLink = $this->findLinkByUrl($oldUrl);
        $newLink = $this->findLinkByUrl($newUrl);

        $this->gm->fuseNodes($oldLink->getId(), $newLink->getId());

        $this->changeUrl($oldUrl, $newUrl);

        return $newLink;
    }

    public function mergeTag(array $tag)
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
     * @return Link[]
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
                $linkArray = $this->buildLink($row->offsetGet('link'));
                $links[] = $this->buildLinkObject($linkArray);
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
     * @param Link[] $links
     * @return array
     * //TODO: Move to DuplicateManager
     */
    public function findDuplicates(array $links)
    {
        $urls = $this->getUrlsForDuplicates($links);

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
     * @param Link[] $links
     * @return array
     */
    private function getUrlsForDuplicates(array $links)
    {
        $urls = array();
        foreach ($links as $link) {
            $urls[] = $link->getUrl();
            $urls[] = strtolower($link->getUrl());
        }

        return $urls;
    }

    /**
     * @param $node Node
     * @return array
     */
    public function buildLink(Node $node)
    {
        $link = $node->getProperties();
        $link['id'] = $node->getId();

        $link = $this->fixMandatoryKeys($link);
        $link = $this->fixThumbnails($link);

        return $link;
    }

    /**
     * @param $linkArray
     * @return Link
     */
    public function buildLinkObject($linkArray)
    {
        if (!isset($linkArray['additionalLabels'])){
            return Link::buildFromArray($linkArray);
        }

        switch ($linkArray['additionalLabels']) {
            case array('Video'):
                return Video::buildFromArray($linkArray);
            case array('Audio'):
                return Audio::buildFromArray($linkArray);
            case array('Image'):
                return Image::buildFromArray($linkArray);
            case array('Creator'):
                return Creator::buildFromArray($linkArray);
            case array('Game'):
                return Game::buildFromArray($linkArray);
            default:
                return Link::buildFromArray($linkArray);
        }
    }

    private function fixMandatoryKeys($link)
    {
        $mandatoryKeys = array('title', 'description', 'url');

        foreach ($mandatoryKeys as $mandatoryKey) {
            if (!array_key_exists($mandatoryKey, $link)) {
                $link[$mandatoryKey] = null;
            }
        }

        return $link;
    }

    private function fixThumbnails($link)
    {
        $thumbnail = isset($link['thumbnail']) ? $link['thumbnail'] : null;
        $link['thumbnailMedium'] = isset($link['thumbnailMedium']) ? $link['thumbnailMedium'] : $thumbnail;
        $link['thumbnailSmall'] = isset($link['thumbnailSmall']) ? $link['thumbnailSmall'] : $link['thumbnailMedium'];

        return $link;
    }

    private function limitTextLengths(Link $link)
    {
        $title = $link->getTitle();
        $title = strlen($title) >= 25 ? mb_substr($title, 0, 22, 'UTF-8') . '...' : $title;
        $link->setTitle($title);

        $description = $link->getDescription();
        $description = strlen($description) >= 25 ? mb_substr($description, 0, 22, 'UTF-8') . '...' : $description;
        $link->setDescription($description);
    }

    //TODO: Refactor this to use locale keys or move them to fields.yml
    public static function getValidTypes()
    {
        return array('Audio', 'Video', 'Image', 'Link', 'Creator', 'Game', 'Web', 'LinkFacebook', 'LinkTwitter', 'LinkYoutube', 'LinkSpotify', 'LinkInstagram', 'LinkTumblr', 'LinkSteam');
    }

    /**
     * @param $id
     * @param Link[] $synonymousLinks
     * @return Link[]
     * @throws \Exception
     */
    private function addSynonymousLink($id, array $synonymousLinks)
    {
        $synonymousResult = array();

        foreach ($synonymousLinks as $index => $synonymousLink) {
            $synonymous = $this->mergeLink($synonymousLink);
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

            if ($result->count() === 0) {
                continue;
            }

            $linkNode = $result->current()->offsetGet('synonymousLink');
            $linkArray = $this->buildLink($linkNode);
            $link = $this->buildLinkObject($linkArray);
            $synonymousResult[$index] = $link;
        }

        return $synonymousResult;
    }

}
