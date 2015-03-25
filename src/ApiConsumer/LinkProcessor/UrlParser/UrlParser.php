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

    public function cleanURL($url){
        return $this->removeEndingChars($url, array('?','&','/'));
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