<?php

namespace Http\OAuth\ResourceOwner;

use ApiConsumer\LinkProcessor\UrlParser\UrlParser;

interface ResourceOwnerInterface
{

    /**
     * Get an option
     *
     * @param $name
     * @return mixed
     */
    public function getOption($name);

    /**
     * Get Resource Owner Name
     *
     * @return string
     */
    public function getName();

    /**
     * Performs an authorized HTTP request
     *
     * @param string $url The url to fetch
     * @param array $query The query of the request
     * @param array $token The token values as an array
     *
     * @return array
     */
    public function authorizedHttpRequest($url, array $query = array(), array $token = array());

    public function authorizedApiRequest($url, array $query = array(), array $token = array());

    public function getUsername($user);

    public function getProfileUrl(array $token);

    /**
     * @return UrlParser
     */
    public function getParser();
}
