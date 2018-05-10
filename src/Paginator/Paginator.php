<?php

namespace Paginator;

use Model\Exception\ErrorList;
use Model\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;

class Paginator
{

    /**
     * @var int maximum items per page
     */
    private $maxLimit;
    /**
     * @var int default items per page
     */
    private $defaultLimit;

    function __construct($maxLimit = 50, $defaultLimit = 20)
    {

        $this->maxLimit = (integer)$maxLimit;
        $this->defaultLimit = (integer)$defaultLimit;
    }

    /**
     * @return int
     */
    public function getMaxLimit()
    {
        return $this->maxLimit;
    }

    /**
     * @param int $maxLimit
     */
    public function setMaxLimit($maxLimit)
    {
        $this->maxLimit = $maxLimit;
    }

    /**
     * @param array $filters
     * @param PaginatedInterface $paginated
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     * @throws ValidationException
     */
    public function paginate(array $filters, PaginatedInterface $paginated, Request $request)
    {

        $limit = $request->get('limit', $this->defaultLimit);
        $limit = min($limit, $this->maxLimit);

        $offset = $request->get('offset', 0);

        $this->checkFilters($filters, $paginated);

        $items = $paginated->slice($filters, $offset, $limit);
        $total = $paginated->countTotal($filters);

        $prevLink = $this->createPrevLink($request, $offset, $limit);
        $nextLink = $this->createNextLink($request, $offset, $limit, $total);

        $pagination = array();
        $pagination['total'] = $total;
        $pagination['offset'] = $offset;
        $pagination['limit'] = $limit;
        $pagination['prevLink'] = $prevLink;
        $pagination['nextLink'] = $nextLink;

        $result = array();
        $result['pagination'] = $pagination;
        $result['items'] = $items;

        return $result;
    }

    /**
     * @return int
     */
    public function getDefaultLimit()
    {
        return $this->defaultLimit;
    }

    /**
     * Creates a previous page link if available.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $offset
     * @param int $limit
     * @return string mixed
     */
    protected function createPrevLink(Request $request, $offset, $limit)
    {

        $prevLink = null;
        $baseUri = $request->getSchemeAndHttpHost() .
            $request->getBaseUrl() .
            $request->getPathInfo();
        if ($offset - $limit >= 0) {
            parse_str($request->getQueryString(), $qsArray);
            $qsArray['limit'] = $limit;
            $qsArray['offset'] = $offset - $limit;
            $qs = Request::normalizeQueryString(http_build_query($qsArray));
            $prevLink = $baseUri . '?' . $qs;
        }

        return $prevLink;
    }

    /**
     * Creates a next page link if available.
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param int $offset
     * @param int $limit
     * @param int $total
     * @return string mixed
     */
    protected function createNextLink(Request $request, $offset, $limit, $total)
    {

        $nextLink = null;
        $baseUri = $request->getSchemeAndHttpHost() .
            $request->getBaseUrl() .
            $request->getPathInfo();
        if ($offset + $limit < $total) {
            parse_str($request->getQueryString(), $qsArray);
            $qsArray['limit'] = $limit;
            $qsArray['offset'] = $limit + $offset;
            $qs = Request::normalizeQueryString(http_build_query($qsArray));
            $nextLink = $baseUri . '?' . $qs;
        }

        return $nextLink;
    }

    protected function checkFilters(array $filters, PaginatedInterface $paginated)
    {
        if (!$paginated->validateFilters($filters)) {
            $errorList = new ErrorList();
            $errorMessage = sprintf('Invalid filters in "%s"', get_class($paginated));

            throw new ValidationException($errorList, $errorMessage);
        }
    }
}