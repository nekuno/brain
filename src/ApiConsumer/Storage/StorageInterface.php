<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 28/06/14
 * Time: 18:27
 */

namespace ApiConsumer\Storage;

interface StorageInterface
{

    /**
     * @param array $linksGroupByUser
     * @return mixed
     */
    function storeLinks(array $linksGroupByUser);
}
