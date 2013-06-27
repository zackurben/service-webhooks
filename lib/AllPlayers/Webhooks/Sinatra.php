<?php
/**
 * @file
 * Provides the Sinatra webhooks plugin definition.
 */

namespace AllPlayers\Webhooks;

/**
 * Defines an example sinatra app that will push events to external test app.
 */
class Sinatra extends Webhook
{
    /**
     * The URL of the webhook.
     *
     * @var string
     */
    public $domain = 'http://webhooks-test.herokuapp.com';

    /**
     * The authentication method used in the post request.
     *
     * @var string
     */
    public $authentication = 'basic auth';

    /**
     * Authenticate using basic auth.
     */
    public function __construct($args = array())
    {
        parent::__construct(array('user' => $args['user'], 'pass' => $args['pass']));
    }
}

