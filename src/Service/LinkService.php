<?php

namespace Service;

use Model\Affinity\AffinityManager;
use Model\Link\Link;
use Model\Link\LinkManager;
use Model\Popularity\PopularityManager;
use Model\Rate\RateManager;

class LinkService
{
    protected $linkManager;
    protected $popularityManager;
    protected $affinityManager;
    protected $rateManager;

    /**
     * LinkService constructor.
     * @param LinkManager $linkModel
     * @param PopularityManager $popularityManager
     * @param AffinityManager $affinityManager
     * @param RateManager $rateManager
     */
    public function __construct(LinkManager $linkModel, PopularityManager $popularityManager, AffinityManager $affinityManager, RateManager $rateManager)
    {
        $this->linkManager = $linkModel;
        $this->popularityManager = $popularityManager;
        $this->affinityManager = $affinityManager;
        $this->rateManager = $rateManager;
    }

    public function deleteNotLiked(array $linkUrls)
    {
        $notLiked = array_filter($linkUrls, function($linkUrl){
            return $linkUrl['likes'] == 0;
        });

        foreach ($notLiked as $linkUrl)
        {
            $url = $linkUrl['url'];
            $this->popularityManager->deleteOneByUrl($url);
            $this->linkManager->removeLink($url);
        }
    }

    /**
     * @param string $userId
     * @return Link[]
     * @throws \Exception on failure
     */
    public function findAffineLinks($userId)
    {
        $linkNodes = $this->affinityManager->getAffineLinks($userId);

        $links = array();
        foreach ($linkNodes as $node)
        {
            $linkArray = $this->linkManager->buildLink($node);
            $link = $this->linkManager->buildLinkObject($linkArray);

            $links[] = $link;
        }

        return $links;
    }

    public function like($userId, $linkId)
    {
        $rate = $this->rateManager->userRateLink($userId, $linkId);
        $this->popularityManager->updatePopularity($linkId);

        return $rate;
    }

    public function merge(Link $link)
    {
        $link = $this->linkManager->mergeLink($link);
        $this->popularityManager->updatePopularity($link->getId());

        return $link;
    }

}