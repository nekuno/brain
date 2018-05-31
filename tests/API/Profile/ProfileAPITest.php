<?php

namespace Tests\API\Profile;

use Tests\API\APITest;

abstract class ProfileAPITest extends APITest
{
    protected function getOwnProfile($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/profile', 'GET', array(), $loggedInUserId);
    }

    protected function getOtherProfile($userSlug = self::OTHER_USER_SLUG, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/profile/' . $userSlug, 'GET', array(), $loggedInUserId);
    }

    protected function validateProfile($userData, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/profile/validate', 'POST', $userData, $loggedInUserId);
    }

    protected function editProfile($userData, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/profile', 'PUT', $userData, $loggedInUserId);
    }

    protected function getProfileMetadata($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/profile/metadata', 'GET', array(), $loggedInUserId);
    }

    protected function getCategories()
    {
        return $this->getResponseByRouteWithCredentials('/profile/categories', 'GET');
    }

    protected function getProfileFilters($loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/profile/filters', 'GET', array(), $loggedInUserId);
    }

    protected function getProfileTags($type, $loggedInUserId = self::OWN_USER_ID)
    {
        return $this->getResponseByRouteWithCredentials('/profile/tags/' . $type, 'GET', array(), $loggedInUserId);
    }

    protected function assertHasLocaleLabel($field, $message = '')
    {
        $this->assertArrayHasKey('label', $field, 'Has not locale label on ' . $message);
        $this->isType('string')->evaluate($field['label'], 'Label is not string on ' . $message);
    }
}