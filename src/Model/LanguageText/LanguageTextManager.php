<?php

namespace Model\LanguageText;

use Everyman\Neo4j\Query\ResultSet;
use Model\Neo4j\GraphManager;
use Transliterator;

class LanguageTextManager
{
    protected $graphManager;


    public function __construct(GraphManager $graphManager)
    {
        $this->graphManager = $graphManager;
    }

    public function merge($nodeId, $locale, $text)
    {
        $canonicalText = $this->buildCanonical($text);
        $qb = $this->graphManager->createQueryBuilder();

        $qb->match('(node)')
            ->where('id(node) = {nodeId}')
            ->setParameter('nodeId', (integer)$nodeId)
            ->with('node')
            ->limit(1);

        $qb->merge("(t: TextLanguage{canonical: {canonical}, locale: {locale}})-[:TEXT_OF]->(node)")
            ->setParameter('canonical', $canonicalText)
            ->setParameter('locale', $locale);

        $qb->returns('t.locale AS locale, t.canonical AS canonical');

        $result = $qb->getQuery()->getResultSet();

        return $this->buildOne($result);
    }

    public function findNodeWithText($label, $locale, $text)
    {
        $canonicalText = $this->buildCanonical($text);

        $qb = $this->graphManager->createQueryBuilder();

        $qb->match("(node:$label)<-[:TEXT_OF]-(:TextLanguage{canonical:{canonical}, locale:{locale}})")
            ->setParameter('canonical', $canonicalText)
            ->setParameter('locale', $locale);

        $qb->returns("id(node) AS id");

        $result = $qb->getQuery()->getResultSet();

        if ($result->count() == 0)
        {
            return null;
        }

        return $result->current()->offsetGet('id');
    }

    protected function buildOne(ResultSet $resultSet)
    {
        $row = $resultSet->current();

        $locale = $row->offsetGet('locale');
        $canonical = $row->offsetGet('canonical');

        $languageText = new LanguageText();
        $languageText->setLanguage($locale);
        $languageText->setCanonical($canonical);

        return $languageText;
    }

    public function buildCanonical($text)
    {
        $text = strtolower($text);

        //http://userguide.icu-project.org/transforms/general/rules
        $transliterator = Transliterator::createFromRules(':: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;', Transliterator::FORWARD);
        $text = $transliterator->transliterate($text);

        return $text;
    }
}