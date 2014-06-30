<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 29/06/14
 * Time: 23:43
 */

namespace Social\API\Consumer\Http;


interface HttpClientInterface {

    public function fetch($url, array $config = array(),  $legacy = false);

} 