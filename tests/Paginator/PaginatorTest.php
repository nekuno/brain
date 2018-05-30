<?php

namespace Tests\Paginator;

use Model\Exception\ValidationException;
use Paginator\Paginator;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    public function testCanSetMaxLimit()
    {
        $maxLimit = 10;
        $paginator = new Paginator($maxLimit);

        $requestMock = $this->getRequestMock(0, $maxLimit + 50);

        $paginatedMock = $this->getPaginatedMock();

        $result = $paginator->paginate(array(), $paginatedMock, $requestMock);
        $this->assertEquals($maxLimit, $result['pagination']['limit']);
    }

    public function testReturnEmptyIfPaginatedDoesNotValidate()
    {
        $paginator = new Paginator();

        $requestMock = $this->getRequestMock();

        $paginatedMock = $this->getPaginatedMock(false);

        $this->expectException(ValidationException::class);
        $paginator->paginate(array(), $paginatedMock, $requestMock);
    }

    public function testGetLimitAndOffsetFromRequest()
    {
        $paginator = new Paginator();

        $offset = 20;
        $limit = 10;
        $requestMock = $this->getRequestMock($offset, $limit);

        $paginatedMock = $this->getPaginatedMock();

        $result = $paginator->paginate(array(), $paginatedMock, $requestMock);
        $this->assertEquals(10, $result['pagination']['limit']);
        $this->assertEquals(20, $result['pagination']['offset']);
    }

    public function testGetTotalFromPaginatedInterface()
    {
        $paginator = new Paginator();

        $filters = array('id' => 1);
        $total = 100;

        $requestMock = $this->getRequestMock();

        $paginatedMock = $this->getPaginatedMock();
        $paginatedMock
            ->expects($this->any())
            ->method('countTotal')
            ->with($this->equalTo($filters))
            ->will($this->returnValue($total));

        $result = $paginator->paginate($filters, $paginatedMock, $requestMock);
        $this->assertEquals($total, $result['pagination']['total']);
    }

    public function testGetItemsFromPaginatedInterface()
    {
        $paginator = new Paginator();

        $filters = array('id' => 1);
        $offset = 6;
        $limit = 3;
        $slice = array(
            'one',
            'two',
            'three',
        );

        $requestMock = $this->getRequestMock($offset, $limit);

        $paginatedMock = $this->getPaginatedMock();
        $paginatedMock
            ->expects($this->any())
            ->method('slice')
            ->with($this->equalTo($filters), $this->equalTo($offset), $this->equalTo($limit))
            ->will($this->returnValue($slice));

        $result = $paginator->paginate($filters, $paginatedMock, $requestMock);
        $this->assertEquals($slice, $result['items']);
    }

    public function testCalculateNextAndPreviousPageLinks()
    {
        $paginator = new Paginator();

        $offset = 6;
        $limit = 3;
        $total = 20;

        $requestMock = $this->getRequestMock($offset, $limit);
        $requestMock
            ->expects($this->any())
            ->method('getSchemeAndHttpHost')
            ->will($this->returnValue('http://'));
        $requestMock
            ->expects($this->any())
            ->method('getBaseUrl')
            ->will($this->returnValue('www.example.com'));
        $requestMock
            ->expects($this->any())
            ->method('getPathInfo')
            ->will($this->returnValue('/'));
        $requestMock
            ->expects($this->any())
            ->method('getQueryString')
            ->will($this->returnValue(''));

        $paginatedMock = $this->getPaginatedMock();
        $paginatedMock
            ->expects($this->any())
            ->method('countTotal')
            ->will($this->returnValue($total));

        $result = $paginator->paginate(array(), $paginatedMock, $requestMock);
        $this->assertEquals('http://www.example.com/?limit=3&offset=3', $result['pagination']['prevLink']);
        $this->assertEquals('http://www.example.com/?limit=3&offset=9', $result['pagination']['nextLink']);
    }

    public function getRequestMock($offset=0, $limit=20)
    {
        $requestMock = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $requestMock
            ->expects($this->any())
            ->method('get')
            ->will($this->returnCallback(function ($name) use ($offset, $limit) {
                if ($name === 'limit') {
                    return $limit;
                }
                if ($name === 'offset') {
                    return $offset;
                }

                return false;
            }));

        return $requestMock;
    }

    public function getPaginatedMock($validate=true)
    {
        $paginatedMock = $this->getMockBuilder('\Paginator\PaginatedInterface')->getMock();
        $paginatedMock
            ->expects($this->any())
            ->method('validateFilters')
            ->will($this->returnValue($validate));

        return $paginatedMock;
    }
} 