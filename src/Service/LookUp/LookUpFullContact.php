<?php
/**
 * @author Manolo Salsas <manolez@gmail.com>
 */
namespace Service\LookUp;

use Service\LookUp\LookUpInterface\LookUpInterface;
use Symfony\Component\Security\Core\Exception\RuntimeException;

class LookUpFullContact extends LookUp implements LookUpInterface
{
    const EMAIL_TYPE = 'email';
    const TWITTER_TYPE = 'twitter';
    const FACEBOOK_TYPE = 'facebookUsername';

    public function getTypes()
    {
        return array(
            self::EMAIL_TYPE,
            self::TWITTER_TYPE,
            self::FACEBOOK_TYPE,
        );
    }

    protected function processData($response)
    {
        $data = array();
        if(isset($response['status']) && $response['status'] === 200) {
            if(isset($response['contactInfo'])) {
                if(isset($response['contactInfo']) && is_array($response['contactInfo']) && ! empty($response['contactInfo'])) {
                    if(isset($response['contactInfo']['givenName'])) {
                        $data['name'] = str_replace(' ', '', $response['contactInfo']['givenName']);
                    }
                }
                if(isset($response['demographics']) && is_array($response['demographics']) && ! empty($response['demographics'])) {
                    if(isset($response['demographics']['gender'])) {
                        $data['gender'] = $response['demographics']['gender'];
                    }
                    if(isset($response['demographics']['locationDeduced']) && isset($response['demographics']['locationDeduced']['deducedLocation'])) {
                        $data['location'] = $response['demographics']['locationDeduced']['deducedLocation'];
                    }
                }
            }
            $data['socialProfiles'] = $this->processSocialData($response);
        }

        return $data;
    }

    protected function processSocialData($response)
    {
        $socialData = array();
        if(is_array($response) && isset($response['socialProfiles']) && ! empty($response['socialProfiles'])) {
            foreach($response['socialProfiles'] as $socialProfile) {
                $socialData[$socialProfile['typeName']] = $socialProfile['url'];
            }
        }

        return $socialData;
    }

    protected function validateValue($lookUpType, $value)
    {
        if($lookUpType === self::EMAIL_TYPE) {
            if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException($value . ' is not a valid email');
            }
        } elseif($lookUpType === self::TWITTER_TYPE || $lookUpType === self::FACEBOOK_TYPE) {
            if(! ctype_alnum($value)) {
                throw new RuntimeException($value . ' is not a valid username');
            }
        } else {
            throw new RuntimeException($lookUpType . ' is not a valid type');
        }

        return true;
    }
}