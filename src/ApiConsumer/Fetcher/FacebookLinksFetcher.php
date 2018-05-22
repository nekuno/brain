<?php

namespace ApiConsumer\Fetcher;


class FacebookLinksFetcher extends AbstractFacebookFetcher
{
    protected $paginationField = '__paging_token';

    protected $until = null;

    protected function getPaginationIdFromResponse($response)
    {
        $paginationId = null;

        //In case Facebook unifies paging /posts with other edges
        if (isset($response['paging']['cursors']['after'])){
            $this->paginationField = 'after';
            return parent::getPaginationIdFromResponse($response);
        }

        if (isset($response['paging']['next'])) {
            $query = parse_url($response['paging']['next'])['query'];
            parse_str($query, $queryParameters);
            $paginationId = $queryParameters[$this->paginationField];
            $this->until = $queryParameters['until'];
        } else {
            return null;
        }

        if ($this->paginationId === $paginationId) {
            return null;
        }

        $this->paginationId = $paginationId;

        return $paginationId;
    }

    public function getUrl($userId = null)
    {
        return parent::getUrl($userId) . '/posts';
    }

    protected function getQuery($paginationId = null)
    {
        $query = array('fields' => 'link,created_time,attachments{type}');
        if (isset ($this->until)) {
            $query = array_merge(
                $query,
                array('until' => $this->until));
            $this->until=null;
        }
        return array_merge(
            $query,
            parent::getQuery()
        );
    }
}