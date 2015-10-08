<?php

namespace ApiConsumer\Fetcher;

abstract class BasicPaginationFetcher extends AbstractFetcher
{
    /**
     * @var string
     */
    protected $paginationField;

    /**
     * @var int Number of items by page
     */
    protected $pageLength = 200;

    /**
     * @var array
     */
    protected $rawFeed = array();

    /**
     * Get query
     *
     * @return array
     */
    protected function getQuery()
    {
        return array();
    }

    /**
     * Get pagination field
     *
     * @return string
     */
    protected function getPaginationField()
    {
        return $this->paginationField;
    }

    /**
     * @param bool $public
     * @return array
     */
    protected function getLinksByPage($public = false)
    {

        $nextPaginationId = null;

        do {
            $query = $this->getQuery();

            if ($nextPaginationId) {
                $query = array_merge($query, array($this->getPaginationField() => $nextPaginationId));
            }

            if (!$public){
                $response = $this->resourceOwner->authorizedHttpRequest($this->getUrl(), $query, $this->user);
            } else {
                $response = $this->resourceOwner->authorizedApiRequest($this->getUrl(), $query, $this->user);
            }

            $this->rawFeed = array_merge($this->rawFeed, $this->getItemsFromResponse($response));

            $nextPaginationId = $this->getPaginationIdFromResponse($response);

        } while (null !== $nextPaginationId);

        return $this->rawFeed;
    }

    /**
     * { @inheritdoc }
     */
    public function fetchLinksFromUserFeed($user, $public)
    {
        $this->user = $user;
        $this->rawFeed = array();

        $rawFeed = $this->getLinksByPage($public);
        $links = $this->parseLinks($rawFeed);

        return $links;
    }

    abstract protected function getItemsFromResponse($response);

    abstract protected function getPaginationIdFromResponse($response);

    abstract protected function parseLinks(array $rawFeed);
}
