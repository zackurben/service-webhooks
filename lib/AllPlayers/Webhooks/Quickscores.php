<?php
/**
 * @file
 * Provides the Quickscores webhooks plugin definition.
 */

namespace AllPlayers\Webhooks;

/**
 * Defines quickscores app that will push events to external test app.
 */
class Quickscores extends Webhook
{
    /**
     * The URL of the webhook.
     *
     * @var string
     */
    public $domain = 'http://www.quickscores.com/API/SynchEvents.php';

    /**
     * The authentication method used in the post request.
     *
     * @var string
     */
    public $authentication = 'basic_auth';

    /**
     * Authenticate using basic auth.
     */
    public function __construct($args = array())
    {
        parent::__construct(array('user' => $args['user'], 'pass' => $args['token']));
    }
}

