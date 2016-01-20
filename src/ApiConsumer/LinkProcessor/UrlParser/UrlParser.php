<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

/**
 * @author Juan Luis Martínez <juanlu@comakai.com>
 */
class UrlParser
{
    public function isUrlValid($url)
    {
        //TODO: Check https://mathiasbynens.be/demo/url-regex for improvements
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    public function cleanURL($url){
        return $this->removeEndingChars($url, array('?','&','/'));
    }

    // Basic regex from http://www.phpro.org/examples/Get-All-URLs-From-Page.html
    /**
     * @param $string
     * @return array
     */
    public function extractURLsFromText($string)
    {
        $regex = '/https?\:\/\/[^\" ]+/i';
        preg_match_all($regex, $string, $matches);
        $urls = $matches[0];

        foreach($urls as $key => &$url){
            $url = $this->cleanURL($url);
            if (!$this->isUrlValid($url)){
                unset($urls[$key]);
            }
        }

        return $urls;

    }
    private function removeEndingChars($url, array $rules){
        if (!is_array($rules)){
            $rules=array($rules);
        }

        foreach ($rules as $chars){
            $length=strlen($chars);
            if (substr($url, -$length)===$chars){
                $url= substr($url, 0, -$length);
            }
        }

        return $url;
    }
} 