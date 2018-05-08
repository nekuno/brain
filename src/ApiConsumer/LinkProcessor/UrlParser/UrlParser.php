<?php

namespace ApiConsumer\LinkProcessor\UrlParser;

use ApiConsumer\Exception\UrlNotValidException;

class UrlParser implements UrlParserInterface
{
    const SCRAPPER = 'scrapper';
    const IMAGESCRAPPER = 'scrapperImage';

    protected $imageExtensions = array('jpg', 'jpeg', 'tif', 'tiff', 'gif', 'png', 'bmp', 'pbm', 'pgm', 'ppm', 'webp', 'hdr', 'heif', 'heic', 'bpg', 'ico', 'cgm', 'svg', 'gbm');

    public function checkUrlValid($url, $urlDecoded = null)
    {
        $toCheck = $this->removeSpecialCharacters($url);
        //TODO: Check https://mathiasbynens.be/demo/url-regex for improvements
        if (!filter_var($toCheck, FILTER_VALIDATE_URL)) {
            throw new UrlNotValidException($urlDecoded ?: $url);
        }
    }

//    protec

    protected function removeSpecialCharacters($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $encoded_path = array_map('urlencode', explode('/', $path));
        $url = str_replace($path, implode('/', $encoded_path), $url);

        return $url;
    }

    public function cleanURL($url)
    {
        $url = $this->removeSpecialEndingChars($url);
        $urlDecoded = $this->fixCodification($url);
        $this->checkUrlValid($url, $urlDecoded);

        return $this->removeEndingChars($urlDecoded);
    }

    // Regex from https://gist.github.com/gruber/8891611

    /**
     * @param $string
     * @return array
     */
    public function extractURLsFromText($string)
    {
        $regex = '~(?i)\b((?:https?:(?:/{1,3}|[a-z0-9%])|[a-z0-9.\-]+[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)/)(?:[^\s()<>{}\[\]]+|\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\))+(?:\([^\s()]*?\([^\s()]+\)[^\s()]*?\)|\([^\s]+?\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’])|(?:(?<!@)[a-z0-9]+(?:[.\-][a-z0-9]+)*[.](?:com|net|org|edu|gov|mil|aero|asia|biz|cat|coop|info|int|jobs|mobi|museum|name|post|pro|tel|travel|xxx|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cs|cu|cv|cx|cy|cz|dd|de|dj|dk|dm|do|dz|ec|ee|eg|eh|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|Ja|sk|sl|sm|sn|so|sr|ss|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw)\b/?(?!@)))~';
        preg_match_all($regex, $string, $matches);
        $urls = $matches[0];

        return $urls;
    }

    private function removeEndingChars($url)
    {
        $rules = array('?', '&', '/');

        foreach ($rules as $chars) {
            $length = strlen($chars);
            if (substr($url, -$length) === $chars) {
                $url = substr($url, 0, -$length);
            }
        }

        return $url;
    }

    private function removeSpecialEndingChars($url)
    {
        $excludingOrds = array(128);

        if (in_array(ord(substr($url, -1)), $excludingOrds)) {
            $url = substr($url, 0, -2);
        }

        return $url;
    }

    private function fixCodification($url)
    {
        return urldecode($url);
    }

    public function getUrlType($url)
    {
        if ($this->isImageUrl($url)) {
            return UrlParser::IMAGESCRAPPER;
        }

        return UrlParser::SCRAPPER;
    }

    protected function isImageUrl($url)
    {
        $extensionsString = implode('|', $this->imageExtensions);
        $regexp = '/\/[^\/]+\.(' . $extensionsString . ')[^\/]*$/i';
        $match = preg_match($regexp, $url);

        return !!$match;
    }

    /**
     * @param $url
     * @return string | null
     */
    public function getUsername($url)
    {
        if (null == $url) {
            //TODO: throw UrlNotValidException
            return null;
        }
        $parts = explode('/', $url);

        return end($parts);
    }
}