<?php

namespace ApiConsumer\Storage;

interface StorageInterface
{

    /**
     * @param array $linksGroupByUser
     * @return mixed
     */
    function storeLinks(array $linksGroupByUser);
}
