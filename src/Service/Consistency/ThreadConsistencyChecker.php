<?php

namespace Service\Consistency;

class ThreadConsistencyChecker extends ConsistencyChecker
{
    protected function getDateTimeFromTimestamp($timestamp)
    {
        $timestamp = $timestamp/1000;
        $timestamp = round($timestamp);
        return parent::getDateTimeFromTimestamp($timestamp);
    }

}