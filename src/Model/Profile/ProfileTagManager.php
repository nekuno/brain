<?php

namespace Model\Profile;

use Everyman\Neo4j\Label;
use Everyman\Neo4j\Query\Row;
use Model\LanguageText\LanguageTextManager;
use Model\Metadata\MetadataUtilities;
use Model\Neo4j\GraphManager;

class ProfileTagManager
{

    protected $graphManager;

    protected $languageTextManager;

    protected $metadataUtilities;

    /**
     * @param GraphManager $graphManager
     * @param LanguageTextManager $languageTextManager
     * @param MetadataUtilities $metadataUtilities
     */
    public function __construct(GraphManager $graphManager, LanguageTextManager $languageTextManager, MetadataUtilities $metadataUtilities)
    {
        $this->graphManager = $graphManager;
        $this->languageTextManager = $languageTextManager;
        $this->metadataUtilities = $metadataUtilities;
    }

    /**
     * @param int $limit
     * @return array[]
     * @throws \Exception
     */
    public function findAllOld($limit = 99999)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(tag:ProfileTag)')
            ->optionalMatch('(tag)--(:Profile)-[r]-(i:InterfaceLanguage)')
            ->with('tag', 'i.id AS locale', 'count(r) AS amount')
            ->returns('id(tag) AS id, tag.name AS name', 'locale', 'amount')
            ->limit((integer)$limit);

        $result = $qb->getQuery()->getResultSet();

        $tags = array();
        foreach ($result as $row) {
            $id = $row->offsetGet('id');
            $name = $row->offsetGet('name');
            $locale = $row->offsetGet('locale') ?: 'es';
            $amount = $row->offsetGet('amount');

            $isAlreadyMigrated = $name === null;
            if ($isAlreadyMigrated) {
                continue;
            }

            $tags[] = array('id' => $id, 'name' => $name, 'locale' => $locale, 'amount' => $amount);
        }

        return $tags;
    }

    public function deleteName($tagId)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(tag:ProfileTag)')
            ->where('id(tag) = {tagId}')
            ->setParameter('tagId', (integer)$tagId)
            ->remove('tag.name');

        $qb->getQuery()->getResultSet();
    }

    /**
     * Get a list of recommended tag
     * @param $type
     * @param $startingWith
     * @param int $limit
     * @throws \Exception
     * @return array
     */
    public function getProfileTagsSuggestion($type, $startingWith = null, $limit = 3)
    {
        $startingWith = $this->languageTextManager->buildCanonical($startingWith);
        $label = $this->metadataUtilities->typeToLabel($type);

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match("(tag:ProfileTag:$label)<-[:TEXT_OF]-(text:TextLanguage)");
        if ($startingWith !== null && $startingWith !== '') {
            $qb->where('text.canonical STARTS WITH {starts}')
                ->setParameter('starts', $startingWith);
        }

        $qb->returns('distinct text.canonical AS text')
            ->orderBy('text')
            ->limit('{limit}')
            ->setParameter('limit', (integer)$limit);

        $result = $qb->getQuery()->getResultSet();

        $response = array();
        foreach ($result as $row) {
            $response['items'][] = array('name' => $row['text']);
        }

        return $response;
    }

    public function deleteAllTagRelationships($userId)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (int)$userId)
            ->with('profile');

        $qb->match('(profile)-[tagRel:TAGGED]-(:ProfileTag)')
            ->delete('tagRel');

        $qb->getQuery()->getResultSet();
    }

    public function deleteTagRelationships($userId, $tagLabel, $tags)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (int)$userId)
            ->with('profile');
//TODO: After this is called, delete text nodes
        foreach ($tags as $index => $tag) {
            $tagName = $tag['name'];
            $qb->optionalMatch("(profile)<-[tagRel:TAGGED]-(tag:ProfileTag: $tagLabel )<-[:TEXT_OF]-(:TextLanguage {canonical:{tag$index}})")
                ->setParameter("tag$index", $tagName)
                ->delete('tagRel')
                ->with('profile');
        }

        $qb->returns('profile');

        $result = $qb->getQuery()->getResultSet();

        return $result->count();
    }

    public function addTags($userId, $locale, $tagLabel, $tags)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (int)$userId)
            ->with('profile');

        foreach ($tags as $index => $tag) {
            $tagName = $tag['name'];
            $existentTagId = $this->languageTextManager->findNodeWithText($tagLabel, $locale, $tagName);
            if (!$existentTagId){
                $tagGoogleGraphId = isset($tag['googleGraphId']) ? $tag['googleGraphId'] : null;

                $newTag = $this->mergeTag($tagLabel, $tagGoogleGraphId);
                $newTagId = $newTag['id'];
                $this->languageTextManager->merge($newTagId, $locale, $tagName);

                $qb->match("(tag:ProfileTag:$tagLabel)")
                    ->where("id(tag) = {tagId$index}")
                    ->setParameter("tagId$index", $newTagId);

            } else {
                $qb->match("(tag:ProfileTag:$tagLabel)")
                    ->where("id(tag) = {tagId$index}")
                    ->setParameter("tagId$index", $existentTagId);
            }

            $qb->merge('(profile)<-[:TAGGED]-(tag)')
                ->with('profile');
        }

        $qb->returns('profile');

        $result = $qb->getQuery()->getResultSet();

        return $result->count();
    }

    public function addTagsToNode($nodeId, $locale, $tagLabel, $tags)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(profile)')
            ->where('id(profile) = { id }')
            ->setParameter('id', (int)$nodeId)
            ->with('profile');

        foreach ($tags as $index => $tag) {
            $tagName = $tag['name'];
            $existentTagId = $this->languageTextManager->findNodeWithText($tagLabel, $locale, $tagName);
            if (!$existentTagId){
                $tagGoogleGraphId = isset($tag['googleGraphId']) ? $tag['googleGraphId'] : null;

                $newTag = $this->mergeTag($tagLabel, $tagGoogleGraphId);
                $newTagId = $newTag['id'];
                $this->languageTextManager->merge($newTagId, $locale, $tagName);

                $qb->match("(tag:ProfileTag:$tagLabel)")
                    ->where("id(tag) = {tagId$index}")
                    ->setParameter("tagId$index", $newTagId);

            } else {
                $qb->match("(tag:ProfileTag:$tagLabel)")
                    ->where("id(tag) = {tagId$index}")
                    ->setParameter("tagId$index", $existentTagId);
            }

            $qb->merge('(profile)<-[:TAGGED]-(tag)')
                ->with('profile');
        }

        $qb->returns('profile');

        $result = $qb->getQuery()->getResultSet();

        return $result->count();
    }

    public function setTagsAndChoice($userId, $locale, $fieldName, $tagLabel, $tags)
    {
        $qb = $this->graphManager->createQueryBuilder();
        $qb->match('(profile:Profile)-[:PROFILE_OF]->(u:User)')
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (int)$userId)
            ->with('profile');

        //TODO: Call this->delete and then this method just create
        $qb->optionalMatch('(profile)<-[tagsAndChoiceOptionRel:TAGGED]-(:' . $tagLabel . ')')
            ->delete('tagsAndChoiceOptionRel')
            ->with('profile');

        $savedTags = array();
        foreach ($tags as $index => $value) {
            $tagValue = $value['tag'];
            if (in_array($tagValue, $savedTags)) {
                continue;
            }

            $tagName = $tagValue['name'];
            $existentTagId = $this->languageTextManager->findNodeWithText($tagLabel, $locale, $tagName);
            $tagIndex = 'tag_' . $index;

            if (!$existentTagId){
                $tagGoogleGraphId = isset($tagValue['googleGraphId']) ? $tagValue['googleGraphId'] : null;

                $newTag = $this->mergeTag($tagLabel, $tagGoogleGraphId);
                $newTagId = $newTag['id'];
                $this->languageTextManager->merge($newTagId, $locale, $tagName);

                $qb->match("($tagIndex:ProfileTag:$tagLabel)")
                    ->where("id($tagIndex) = {tagId$index}")
                    ->setParameter("tagId$index", $newTagId);

            } else {
                $qb->match("($tagIndex:ProfileTag:$tagLabel)")
                    ->where("id($tagIndex) = {tagId$index}")
                    ->setParameter("tagId$index", $existentTagId);
            }

            $choice = !is_null($value['choice']) ? $value['choice'] : '';
            $tagParameter = $fieldName . '_' . $index;
            $choiceParameter = $fieldName . '_choice_' . $index;

            $qb ->merge('(profile)<-[:TAGGED {detail: {' . $choiceParameter . '}}]-(' . $tagIndex . ')')
                ->setParameter($tagParameter, $tagName)
                ->setParameter('localeTag' . $index, $locale)
                ->setParameter($choiceParameter, $choice)
                ->with('profile');
            $savedTags[] = $tagValue;
        }
        $qb->returns('profile');
        $query = $qb->getQuery();
        $query->getResultSet();
    }

    public function setTagsAndChoiceToNode($nodeId, $locale, $fieldName, $tagLabel, $tags)
    {
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(profile)')
            ->where('id(profile) = { id }')
            ->setParameter('id', (int)$nodeId)
            ->with('profile');

        //TODO: Call this->delete and then this method just create
        $qb->optionalMatch('(profile)<-[tagsAndChoiceOptionRel:TAGGED]-(:' . $tagLabel . ')')
            ->delete('tagsAndChoiceOptionRel')
            ->with('profile');

        $savedTags = array();
        foreach ($tags as $index => $value) {
            $tagValue = $value['tag'];
            if (in_array($tagValue, $savedTags)) {
                continue;
            }

            $tagName = $tagValue['name'];
            $existentTagId = $this->languageTextManager->findNodeWithText($tagLabel, $locale, $tagName);
            $tagIndex = 'tag_' . $index;

            if (!$existentTagId){
                $tagGoogleGraphId = isset($tagValue['googleGraphId']) ? $tagValue['googleGraphId'] : null;

                $newTag = $this->mergeTag($tagLabel, $tagGoogleGraphId);
                $newTagId = $newTag['id'];
                $this->languageTextManager->merge($newTagId, $locale, $tagName);

                $qb->match("($tagIndex:ProfileTag:$tagLabel)")
                    ->where("id($tagIndex) = {tagId$index}")
                    ->setParameter("tagId$index", $newTagId);

            } else {
                $qb->match("($tagIndex:ProfileTag:$tagLabel)")
                    ->where("id($tagIndex) = {tagId$index}")
                    ->setParameter("tagId$index", $existentTagId);
            }

            $choice = !is_null($value['choice']) ? $value['choice'] : '';
            $tagParameter = $fieldName . '_' . $index;
            $choiceParameter = $fieldName . '_choice_' . $index;

            $qb ->merge('(profile)<-[:TAGGED {detail: {' . $choiceParameter . '}}]-(' . $tagIndex . ')')
                ->setParameter($tagParameter, $tagName)
                ->setParameter('localeTag' . $index, $locale)
                ->setParameter($choiceParameter, $choice)
                ->with('profile');
            $savedTags[] = $tagValue;
        }
        $qb->returns('profile');
        $query = $qb->getQuery();
        $query->getResultSet();
    }

    public function buildTags(Row $row, $locale)
    {
        $tags = $row->offsetGet('tags');
        $tagsResult = array();
        /** @var Row $tagData */
        foreach ($tags as $tagData) {
            $text = $tagData->offsetGet('text');
            if ($text === null || $text->getProperty('locale') !== $locale){
                continue;
            }
            $tag = $tagData->offsetGet('tag');
            $tagged = $tagData->offsetGet('tagged');
            $labels = $tag ? $tag->getLabels() : array();

            /* @var Label $label */
            foreach ($labels as $label) {
                if ($label->getName() && $label->getName() != 'ProfileTag') {
                    $typeName = $this->metadataUtilities->labelToType($label->getName());
                    $detail = $tagged->getProperty('detail');
                    if (!is_null($detail)) {
                        $tagResult = array();
                        $tagResult['tag'] = array('name' => $text->getProperty('canonical'));
                        $tagResult['choice'] = $detail;
                    } else {
                        $tagResult = array('name' => $text->getProperty('canonical'));
                    }

                    $tagsResult[$typeName][] = $tagResult;
                }
            }
        }

        return $tagsResult;
    }

    public function mergeTag($label, $googleGraphId = null)
    {
        $qb = $this->graphManager->createQueryBuilder();

        if ($googleGraphId) {
            $qb->merge("(tag:ProfileTag:$label{googleGraphId: {id}})")
                ->setParameter('id', $googleGraphId);
        } else {
            $qb->create("(tag:ProfileTag:$label)");
        }

        $qb->returns('id(tag) AS id');

        $result = $qb->getQuery()->getResultSet();

        $tagId = $result->current()->offsetGet('id');

        return array('id' => $tagId, 'googleGraphId' => $googleGraphId);
    }
}