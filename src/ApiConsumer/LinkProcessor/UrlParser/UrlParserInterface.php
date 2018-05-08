<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;

interface UrlParserInterface
{
    /**
     * @param $url
     * @return string
     * @throws UrlNotValidException on not-classificable URL
     */
    public function getUrlType($url);

    public function cleanURL($url);

    /**
     * @param $string
     * @return array
     */
    public function extractURLsFromText($string);

    public function getUsername($url);

}