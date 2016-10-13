<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Exception\PaginatedFetchingException;
use ApiConsumer\LinkProcessor\PreprocessedLink;

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
     * @throws PaginatedFetchingException
     */
    protected function getLinksByPage($public = false)
    {

        $nextPaginationId = null;

        do {
            $query = $this->getQuery();

            if ($nextPaginationId) {
                $query = array_merge($query, array($this->getPaginationField() => $nextPaginationId));
            }

            try{
                if (!$public) {
                    $response = $this->resourceOwner->authorizedHttpRequest($this->getUrl(), $query, $this->user);
                } else {
                    $response = $this->resourceOwner->authorizedAPIRequest($this->getUrl(), $query, $this->user);
                }
            } catch (\Exception $e) {
                throw new PaginatedFetchingException($this->rawFeed, $e);
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
        $this->setUser($user);
        $this->rawFeed = array();

        try{
            $rawFeed = $this->getLinksByPage($public);
        } catch (PaginatedFetchingException $e) {
            $newLinks = $this->parseLinks($e->getLinks());
            $e->setLinks($newLinks);
            throw $e;
        }

        $links = $this->parseLinks($rawFeed);

        return $links;
    }

    abstract protected function getItemsFromResponse($response);

    /**
     * @param array $response
     * @return string|null
     */
    abstract protected function getPaginationIdFromResponse($response);

    /**
     * @param array $rawFeed
     * @return PreprocessedLink[]
     */
    abstract protected function parseLinks(array $rawFeed);

}
