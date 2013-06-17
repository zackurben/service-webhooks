<?php

namespace Webhooks;
/**
 * Defines an example sinatra app that receives a post when a user joins a group.
 */
class Importer extends Webhook {
    /**
     * Authenticate using basic auth.
     */
    public function __construct($args = array(), $authentication = 'basic_auth', $domain = 'http://54.225.169.159:8080/') {
        parent::__construct(array('user' => $args['user'], 'pass' => $args['pass']), $authentication, $domain);
    }

}

