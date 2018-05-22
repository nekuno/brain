<?php

namespace ApiConsumer\Fetcher;

use ApiConsumer\Exception\PaginatedFetchingException;
use ApiConsumer\LinkProcessor\PreprocessedLink;
use Model\Token\Token;

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

    protected $username;

    /**
     * Get pagination field
     *
     * @return string
     */
    protected function getPaginationField()
    {
        return $this->paginationField;
    }

    protected function getQuery($paginationId = null)
    {
        $query = null == $paginationId ? array() : array($this->getPaginationField() => $paginationId);

        return $query;
    }

    /**
     * @return array
     * @throws PaginatedFetchingException
     */
    protected function getLinksByPage()
    {
        $nextPaginationId = null;

        do {
            $url = $this->getUrl();
            $query = $this->getQuery($nextPaginationId);

            $response = $this->request($url, $query);

            $this->rawFeed = array_merge($this->rawFeed, $this->getItemsFromResponse($response));

            $nextPaginationId = $this->getPaginationIdFromResponse($response);

        } while (null !== $nextPaginationId);

        return $this->rawFeed;
    }

    protected function request($url, $query)
    {
        try {
            $response = $this->resourceOwner->request($url, $query, $this->token);
        } catch (\Exception $e) {
            throw new PaginatedFetchingException($this->rawFeed, $e);
        }

        return $response;
    }

    /**
     * { @inheritdoc }
     */
    public function fetchLinksFromUserFeed(Token $token)
    {
        $this->setUpToken($token);

        try {
            $rawFeed = $this->getLinksByPage();
        } catch (PaginatedFetchingException $e) {
            $newLinks = $this->parseLinks($e->getLinks());
            $e->setLinks($newLinks);
            throw $e;
        }

        $links = $this->parseLinks($rawFeed);

        $this->addSourceAndToken($links, $token);

        return $links;
    }

    protected function setUpToken(Token $token)
    {
        $this->setToken($token);
        $this->rawFeed = array();
    }

    public function fetchAsClient($username)
    {
        $this->setUpUsername($username);

        try {
            $rawFeed = $this->getLinksByPage();
        } catch (PaginatedFetchingException $e) {
            $newLinks = $this->parseLinks($e->getLinks());
            $e->setLinks($newLinks);
            throw $e;
        }

        $links = $this->parseLinks($rawFeed);

        $this->addSourceAndToken($links);

        return $links;
    }

    protected function setUpUsername($username)
    {
        $this->username = $username;
        $this->rawFeed = array();
    }

    /**
     * @param $links PreprocessedLink[]
     * @param null $token
     */
    protected function addSourceAndToken($links, $token = null)
    {
        foreach ($links as $link) {
            $source = $link->getSource() ?: $this->resourceOwner->getName();
            $link->setSource($source);
            $link->setToken($token);
        }
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
