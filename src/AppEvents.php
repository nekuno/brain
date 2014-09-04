<?php

final class AppEvents
{
    /**
     * The token.refreshed event is thrown each time an oauth token is successfully
     * refreshed in the system.
     *
     * The event listener receives an
     * ApiConsumer\Event\FilterTokenRefreshedEvent instance.
     *
     * @var string
     */
    const TOKEN_REFRESHED = 'token.refreshed';

    /**
     * Fired when detect that current token is expired
     */
    const TOKEN_EXPIRED = 'token.expired';

    /**
     * Fired before link process starts
     */
    const PROCESS_LINKS = 'process.links';

    /**
     * Fired with each link process
     */
    const PROCESS_LINK = 'process.link';

    /**
     * Fired when link process is finished
     */
    const PROCESS_FINISH = 'process.finish';

}
