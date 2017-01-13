<?php

namespace Model\User\SocialNetwork;

use Psr\Log\LoggerInterface;


class LinkedinSocialNetworkModel extends SocialNetworkModel
{
    public function set($id, $profileUrl, LoggerInterface $logger = null)
    {
        $data = $this->get($profileUrl, $logger);

        $qb = $this->gm->createQueryBuilder();
        $qb->match('(u:User)-[:HAS_SOCIAL_NETWORK {url: { profileUrl }}]->(:LinkedinSocialNetwork)')
            ->setParameter('profileUrl', $profileUrl)
            ->where('u.qnoow_id = { id }')
            ->setParameter('id', (int)$id)
            ->with('u');
        foreach($data['skills'] as $index => $skill) {
            if (null == $skill){
                continue;
            }
            $qb->merge('(skill :Skill {name: { skill' . $index . ' }})');
            $qb->merge('(u)-[:HAS_SKILL]->(skill)')
                ->setParameter('skill' . $index, $skill)
                ->with('u');
        }
        foreach($data['languages'] as $index => $language) {
            if (null == $language){
                continue;
            }
            $qb->merge('(language :Language {name: { language' . $index . ' }})');
            $qb->merge('(u)-[:SPEAKS_LANGUAGE]->(language)')
                ->setParameter('language' . $index, $language)
                ->with('u');
        }
        $qb->returns('u');

        $result = $qb->getQuery()->getResultSet();

        return count($result) == 1 ? true : false;
    }

    protected function get($profileUrl, LoggerInterface $logger = null)
    {
        return $this->parser->parse($profileUrl, $logger);
    }
}
