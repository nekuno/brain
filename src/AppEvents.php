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
     * Fired when an account is connected
     */
    const ACCOUNT_CONNECTED = 'account.connected';

    /**
     * Fired when matching is outdated
     */
    const MATCHING_EXPIRED = 'matching.expired';

    /**
     * Fired when matching is updated
     * @see \Event\MatchingEvent
     */
    const MATCHING_UPDATED = 'matching.updated';

    /**
     * Fired when similarity is updated
     * @see \Event\SimilarityEvent
     */
    const SIMILARITY_UPDATED = 'similarity.updated';

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

    /**
     * Fired when a channel url is detected
     */
    const CHANNEL_ADDED = 'channel.added';

    /**
     * Fired when an unexpected exception is thrown
     */
    const EXCEPTION_ERROR = 'exception.error';

    /**
     * Fired when an expected exception is thrown
     */
    const EXCEPTION_WARNING = 'exception.warning';

    /**
     * Fired when privacy is updated
     */
    const PRIVACY_UPDATED = 'privacy.updated';

    /**
     * Fired when a user is added to a group
     */
    const GROUP_ADDED = 'group.added';

    /**
     * Fired when a user is removed from a group
     */
    const GROUP_REMOVED = 'group.removed';

    /**
     * Fired when a profile is created
     */
    const PROFILE_CREATED = 'profile.created';

	/**
	 * Fired when a similarity process starts
	 */
	const SIMILARITY_PROCESS_START = 'similarity.start';

	/**
	 * Fired for each similarity process step
	 */
	const SIMILARITY_PROCESS_STEP = 'similarity.step';

	/**
	 * Fired when a similarity process finishes
	 */
	const SIMILARITY_PROCESS_FINISH = 'similarity.finish';

	/**
	 * Fired when a matching process starts
	 */
	const MATCHING_PROCESS_START = 'matching.start';

	/**
	 * Fired for each matching process step
	 */
	const MATCHING_PROCESS_STEP = 'matching.step';

	/**
	 * Fired when a matching process finishes
	 */
	const MATCHING_PROCESS_FINISH = 'matching.finish';

	/**
	 * Fired when a affinity process starts
	 */
	const AFFINITY_PROCESS_START = 'affinity.start';

	/**
	 * Fired for each affinity process step
	 */
	const AFFINITY_PROCESS_STEP = 'affinity.step';

	/**
	 * Fired when a affinity process finishes
	 */
	const AFFINITY_PROCESS_FINISH = 'affinity.finish';

    /**
     * Fired when a neo4j consistency error is detected
     */
    const CONSISTENCY_ERROR = 'consistency.error';

    /**
     * Fired when a neo4j consistency check starts or finish
     */
    const CONSISTENCY_START = 'consistency.start';
    const CONSISTENCY_END = 'consistency.end';

    /**
     * Fired when a Link needs consistency checked and fixed if necessary
     */
    const CONSISTENCY_LINK = 'consistency.link';
}
