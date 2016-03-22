<?php
/**
 * @author yawmoght <yawmoght@gmail.com>
 */

namespace Model\User;


use Model\Neo4j\GraphManager;

abstract class FilterModel
{
    protected $gm;
    protected $metadata;
    protected $defaultLocale;

    public function __construct(GraphManager $gm, array $metadata, $defaultLocale)
    {
        $this->gm = $gm;
        $this->metadata = $metadata;
        $this->defaultLocale = $defaultLocale;
    }

    public function getLocale($locale)
    {

        if (!$locale || !in_array($locale, array('en', 'es'))) {
            $locale = $this->defaultLocale;
        }

        return $locale;
    }
}