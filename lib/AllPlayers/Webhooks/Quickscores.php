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
     * The URL to post the webhook.
     *
     * @var string
     */
    public $domain = 'http://www.quickscores.com/API/SynchEvents.php';

    /**
     * The authentication method used in the post requests.
     *
     * @var string
     */
    public $authentication = 'basic_auth';

    /**
     * Authenticate using basic auth.
     */
    public function __construct(array $subscriber = array(), array $data = array())
    {
        parent::__construct(array('user' => $subscriber['user'], 'pass' => $subscriber['token']));
    }
}
