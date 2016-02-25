<?php

namespace Http\OAuth\ResourceOwner;

use ApiConsumer\Event\ChannelEvent;
use ApiConsumer\LinkProcessor\UrlParser\TwitterUrlParser;
use GuzzleHttp\Exception\RequestException;
use Model\User\TokensModel;
use Service\LookUp\LookUp;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class TwitterResourceOwner
 *
 * @package ApiConsumer\ResourceOwner
 * @method TwitterUrlParser GetParser 
 */
class TwitterResourceOwner extends Oauth1GenericResourceOwner
{

    public $name = TokensModel::TWITTER;

    public function getAPIRequest($url, array $query = array(), array $token = array())
    {
        $request = parent::getAPIRequest($url, $query, $token);

        $clientToken = $this->getClientToken();

        if (!empty($clientToken)) {
            $request->addHeader('Authorization', 'Bearer ' . $clientToken);
        }

        $username = $this->getUsername($token);
        if ($username) {
            $request->getQuery()->add('screen_name', $username);
        }

        return $request;
    }

    public function lookupUsersBy($parameter, array $userIds)
    {
        if ($parameter !== 'user_id' && $parameter !== 'screen_name') {
            return false;
        }

        $chunks = array_chunk($userIds, 100);
        $baseUrl = $this->getOption('base_url');
        $url = $baseUrl . 'users/lookup.json';

        $users = array();
        foreach ($chunks as $chunk) {
            $query = array($parameter => implode(',', $chunk));
            $request = $this->getAPIRequest($url, $query);
            try {
                $response = $this->httpClient->send($request)->json();
            } catch (\Exception $e) {
                $response = array();
            }

            $users = array_merge($users, $response);
        }

        return $users;
    }

    public function buildProfileFromLookup($user)
    {
        if (!$user) {
            return $user;
        }

        $profile = array(
            'title' => isset($user['name']) ? $user['name'] : $user['url'],
            'description' => isset($user['description']) ? $user['description'] : $user['name'],
            'url' => isset($user['screen_name']) ? 'https://twitter.com/' . $user['screen_name'] : null,
            'thumbnail' => isset($user['profile_image_url']) ? $user['profile_image_url'] : null,
            'additionalLabels' => array('Creator'),
            'resource' => TokensModel::TWITTER,
            'timestamp' => 1000 * time(),
        );

        return $profile;
    }

    public function getProfileUrl(array $token)
    {
        if (isset($token['screenName'])) {
            $screenName = $token['screenName'];
        } else {
            try {
                $account = $this->authorizedHttpRequest('account/settings.json', array(), $token);
            } catch (RequestException $e) {
                return null;
            }

            $screenName = $account['screen_name'];
        }

        return LookUp::TWITTER_BASE_URL . $screenName;
    }

    public function dispatchChannel(array $data)
    {
        $url = isset($data['url']) ? $data['url'] : null;
        $username = isset($data['username']) ? $data['username'] : null;
        if (!$username && $url) {
            throw new \Exception ('Cannot add twitter channel with username and url not set');
        }

        $this->dispatcher->dispatch(\AppEvents::CHANNEL_ADDED, new ChannelEvent($this->getName(), $url, $username));
    }

    /**
     * {@inheritDoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            array(
                'base_url' => 'https://api.twitter.com/1.1/',
            )
        );
    }

}
