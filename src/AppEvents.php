<?php

/**
 * Class AppEvents
 */
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

    /**
     * Fired when fetch process starts
     */
    const USER_DATA_FETCHING_START = 'user.data.fetching.start';

    /**
     * Fired when fetch process finish
     */
    const USER_DATA_FETCHING_FINISH = 'user.data.fetching.finish';

    /**
     * Fired when link processing starts
     */
    const USER_DATA_PROCESS_START = 'user.data.process.start';

    /**
     * Fired when link processing finish
     */
    const USER_DATA_PROCESS_FINISH = 'user.data.process.finish';

    /**
     * Fired when matching is outdated
     */
    const USER_MATCHING_EXPIRED = 'user.matching.expired';

    /**
     * Fired when user rated content
     */
    const USER_DATA_CONTENT_RATED = 'user.data.content.added';
}
