<?php

/**
 * Class AppEvents
 */
final class AppEvents
{

    /**
     * Fired each time an oauth token is successfully refreshed in the system.
     */
    const TOKEN_REFRESHED = 'token.refreshed';

    /**
     * Fired when detects that current token is expired
     */
    const TOKEN_EXPIRED = 'token.expired';

    /**
     * Fired when fetch process starts
     */
    const FETCH_START = 'fetch.start';

    /**
     * Fired when fetch process finish
     */
    const FETCH_FINISH = 'fetch.finish';

    /**
     * Fired when links process starts
     */
    const PROCESS_START = 'process.start';

    /**
     * Fired with each link process
     */
    const PROCESS_LINK = 'process.link';

    /**
     * Fired when links process is finished
     */
    const PROCESS_FINISH = 'process.finish';

    /**
     * Fired when user created
     */
    const USER_CREATED = 'user.created';

    /**
     * Fired when user updated
     */
    const USER_UPDATED = 'user.updated';

    /**
     * Fired when user status changes
     */
    const USER_STATUS_CHANGED = 'user.statusChanged';

    /**
     * Fired when matching is outdated
     */
    const MATCHING_EXPIRED = 'matching.expired';

    /**
     * Fired when user rated content
     */
    const CONTENT_RATED = 'content.rated';

    /**
     * Fired when user answers a question
     */
    const ANSWER_ADDED = 'answer.added';

    /**
     * Fired when SocialNetworks are detected from LookUp
     */
    const SOCIAL_NETWORKS_ADDED = 'socialNetworks.added';
}
