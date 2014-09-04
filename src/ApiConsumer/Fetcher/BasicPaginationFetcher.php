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
     * @param null $paginationId
     * @return array
     */
    protected function getLinksByPage($paginationId = null)
    {

        do {
            $query = $this->getQuery();

            if ($paginationId) {
                $query = array_merge($query, array($this->getPaginationField() => $paginationId));
            }

            $response = $this->resourceOwner->authorizedHttpRequest($this->getUrl(), $query, $this->user);

            $this->rawFeed = array_merge($this->rawFeed, $this->getItemsFromResponse($response));

            $nextPaginationId = $this->getPaginationIdFromResponse($response);

        } while (null !== $nextPaginationId);

        return $this->rawFeed;
    }

    /**
     * { @inheritdoc }
     */
    public function fetchLinksFromUserFeed($user)
    {
        $this->user = $user;
        $this->rawFeed = array();

        $rawFeed = $this->getLinksByPage();
        $links = $this->parseLinks($rawFeed);

        return $links;
    }

    abstract protected function getItemsFromResponse($response);

    abstract protected function getPaginationIdFromResponse($response);

    abstract protected function parseLinks(array $rawFeed);
}
