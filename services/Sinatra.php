<?php

namespace Webhooks;
/**
 * Defines an example sinatra app that receives a post when a user joins a group.
 */
class Sinatra extends Webhook {
    /**
     * Authenticate using basic auth.
     */
    public function __construct($args = array(), $authentication = 'basic_auth', $domain = 'http://webhooks-test.herokuapp.com') {
        parent::__construct(array('user' => $args['user'], 'pass' => $args['pass']), $authentication, $domain);
}
}

