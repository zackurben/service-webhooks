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
     * Authenticate using basic auth.
     */
    public function __construct($args = array(), $domain = 'http://webhooks-test.herokuapp.com', $authentication = 'basic_auth')
    {
        parent::__construct(array('user' => $args['user'], 'pass' => $args['pass']), $domain, $authentication);
    }
}

