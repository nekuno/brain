<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class UrlParser
{
    public function isUrlValid($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
} 