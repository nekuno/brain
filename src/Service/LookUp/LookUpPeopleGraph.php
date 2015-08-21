<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service\LookUp;

use Service\LookUp\LookUpInterface\LookUpInterface;
use Model\Exception\ValidationException;

class LookUpPeopleGraph extends LookUp implements LookUpInterface
{
    const EMAIL_TYPE = 'email';
    const URL_TYPE = 'url';

    public function getTypes()
    {
        return array(
            self::EMAIL_TYPE,
            self::URL_TYPE,
        );
    }

    protected function processData($response)
    {
        $data = array();
        if(isset($response['status']) && $response['status'] === 200) {
            if(is_array($response) && isset($response['result'])) {
                $result = $response['result'];
                if(isset($result['name'])) {
                    $data['name'] = str_replace(' ', '', $result['name']);
                }
                if(isset($result['email'])) {
                    $data['email'] = $result['email'];
                }
                if(isset($result['locations']) && is_array($result['locations']) && ! empty($result['locations'])) {
                    $data['location'] = $result['locations'][0];
                }
            }
            $data['socialProfiles'] = $this->processSocialData($response);
        }

        return $data;
    }

    protected function processSocialData($response)
    {
        $socialData = array();
        if(is_array($response) && isset($response['result']['profiles']) && ! empty($response['result']['profiles'])) {
            foreach($response['result']['profiles'] as $socialProfile) {
                $socialData[strtolower($socialProfile['type'])] = $socialProfile['url'];
            }
        }

        return $socialData;
    }

    protected function validateValue($lookUpType, $value)
    {
        $error = '';
        if($lookUpType === self::EMAIL_TYPE) {
            if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $error = $value . ' is not a valid email';
            }
        } elseif($lookUpType === self::URL_TYPE) {
            if (! filter_var($value, FILTER_VALIDATE_URL)) {
                $error = $value . ' is not a valid url';
            }
        } else {
            $error = $lookUpType . ' is not a valid type';
        }

        if($error !== '') {
            $exception = new ValidationException('Validation errors');
            $exception->setErrors(array($error));
            throw $exception;
        }

        return true;
    }
}