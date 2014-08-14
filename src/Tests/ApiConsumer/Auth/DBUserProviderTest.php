<?php

namespace Tests\ApiConsumer\Auth;

use ApiConsumer\Auth\DBUserProvider;

class DBUserProviderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param $resourceOwner
     * @param $userId
     * @param $userData
     * @dataProvider getUserByResourceWithUserIdDataProvider
     */
    public function testGetUserByResourceWithUserId($resourceOwner, $userId, $userData)
    {
        $driverMockBuilder = $this->getMockBuilder('\Doctrine\DBAL\Connection');
        $driverMockBuilder->disableOriginalConstructor();
        $driverMock = $driverMockBuilder->getMock();
        $driverMock
            ->expects($this->once())
            ->method('fetchAssoc')
            ->will($this->returnValue(
                    $userData
                ));

        $userProvider = new DBUserProvider($driverMock);
        $actual = $userProvider->getUsersByResource($resourceOwner, $userId);
        $resourceOwnerIdProperty = $resourceOwner . 'ID';

        $this->assertNotEmpty($actual, 'Array is not empty');
        $this->assertArrayHasKey('id', $actual);
        $this->assertArrayHasKey($resourceOwnerIdProperty, $actual);
        $this->assertArrayHasKey('resourceOwner', $actual);
        $this->assertEquals($userId, $actual['id']);
        $this->assertEquals($resourceOwner, $actual['resourceOwner']);
        $this->assertNotEquals('', $actual[$resourceOwnerIdProperty]);
        $this->assertNotNull($actual[$resourceOwnerIdProperty]);
    }

    /**
     * Test cases data provider
     *
     * @return array
     */
    public function getUserByResourceWithUserIdDataProvider(){
        return array(
            array(
                'facebook',
                1,
                array(
                    'username' => 'adridev',
                    'id' => 1,
                    'facebookID' => "dsafjj03jiasje0ti9j3ijs89e0jfiop3wjdjfdasgaefdasfe3adae3fdasfe",
                    'resourceOwner' => 'facebook',
                )
            ),
            array(
                'google',
                1,
                array(
                    'username' => 'adridev',
                    'id' => 1,
                    'googleID' => "51468165187861565481",
                    'resourceOwner' => 'google',
                )
            ),

        );
    }

}
