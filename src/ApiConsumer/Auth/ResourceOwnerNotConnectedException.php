<?php
/**
 * Created by PhpStorm.
 * User: adridev
 * Date: 7/22/14
 * Time: 1:04 PM
 */

namespace ApiConsumer\Auth;

class ResourceOwnerNotConnectedException extends \Exception
{

    protected $message = 'Given resource owner is not connected';

}
