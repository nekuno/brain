<?php

namespace Service\LookUp;

use Model\Entity\LookUpData;
use Model\Exception\ValidationException;

class LookUpPeopleGraph extends LookUp
{
    const EMAIL_TYPE = 'email';
    const URL_TYPE = 'url';

    protected function getTypes()
    {
        return array(
            self::EMAIL_TYPE,
            self::URL_TYPE,
        );
    }

    protected function getType($lookUpType)
    {
        switch ($lookUpType) {
            case LookUpData::LOOKED_UP_BY_EMAIL:
                $fullContactType = LookUpPeopleGraph::EMAIL_TYPE;
                break;
            case LookUpData::LOOKED_UP_BY_TWITTER_USERNAME:
                $fullContactType = LookUpPeopleGraph::URL_TYPE;
                break;
            case LookUpData::LOOKED_UP_BY_FACEBOOK_USERNAME:
                $fullContactType = LookUpPeopleGraph::URL_TYPE;
                break;
            default:
                $fullContactType = LookUpPeopleGraph::EMAIL_TYPE;
        }

        return $fullContactType;
    }

    protected function getValue($lookUpType, $value)
    {
        switch ($lookUpType) {
            case LookUpData::LOOKED_UP_BY_EMAIL:
                break;
            case LookUpData::LOOKED_UP_BY_TWITTER_USERNAME:
                $value = LookUp::TWITTER_BASE_URL . $value;
                break;
            case LookUpData::LOOKED_UP_BY_FACEBOOK_USERNAME:
                $value = LookUp::FACEBOOK_BASE_URL . $value;
                break;
        }

        return $value;
    }

    protected function processData($response)
    {
        $data = array();
        if (is_array($response) && isset($response['result'])) {
            $result = $response['result'];
            if (isset($result['name'])) {
                $data['name'] = str_replace(' ', '', $result['name']);
            }
            if (isset($result['email'])) {
                $data['email'] = $result['email'];
            }
            if (isset($result['locations']) && is_array($result['locations']) && !empty($result['locations'])) {
                $data['location'] = $result['locations'][0];
            }
            $data['response'] = $response;
        }
        $data['socialProfiles'] = $this->processSocialData($response);

        return $data;
    }

    protected function processSocialData($response)
    {
        $socialData = array();
        if (is_array($response) && isset($response['result']['profiles']) && !empty($response['result']['profiles'])) {
            foreach ($response['result']['profiles'] as $socialProfile) {
                $socialData[strtolower($socialProfile['type'])] = $socialProfile['url'];
            }
        }

        return $socialData;
    }

    protected function validateValue($lookUpType, $value)
    {
        $error = '';
        if ($lookUpType === self::EMAIL_TYPE) {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $error = $value . ' is not a valid email';
            }
        } elseif ($lookUpType === self::URL_TYPE) {
            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                $error = $value . ' is not a valid url';
            }
        } else {
            $error = $lookUpType . ' is not a valid type';
        }

        if ($error !== '') {
            throw new ValidationException(array($error));
        }

        return true;
    }
}