<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Model\User\SocialNetwork;

use Model\Neo4j\GraphManager;
use Model\Parser\BaseParser;

/**
 * Class LinkedinSocialNetworkModel
 *
 * @package Model
 */
class LinkedinSocialNetworkModel extends SocialNetworkModel
{
    public function set($id, $profileUrl)
    {
        $data = $this->get($profileUrl);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)-[:HAS_SOCIAL_NETWORK {url: { profileUrl }}]->(:LinkedinSocialNetwork)')
            ->setParameter('profileUrl', $profileUrl)
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (int)$id)
            ->with('u');
        foreach($data['skills'] as $index => $skill) {
            $qb->merge('(u)-[:HAS_SKILL]->(:Skill {name: { skill' . $index . ' }})')
                ->setParameter('skill' . $index, $skill)
                ->with('u');
        }
        foreach($data['languages'] as $index => $language) {
            $qb->merge('(u)-[:SPEAKS_LANGUAGE]->(:Language {name: { language' . $index . ' }})')
                ->setParameter('language' . $index, $language)
                ->with('u');
        }
        $qb->returns('u');

        $result = $qb->getQuery()->getResultSet();

        return count($result) == 1 ? true : false;
    }

    protected function get($profileUrl)
    {
        return $this->parser->parse($profileUrl);
    }
}
