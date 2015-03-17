<?php

namespace ApiConsumer\Auth;

class ResourceOwnerNotConnectedException extends \Exception
{

    protected $message = 'Given resource owner is not connected';

}
