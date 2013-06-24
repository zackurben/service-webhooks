<?php
/**
 * @file
 * Provides the Importer webhooks plugin definition.
 */

namespace Webhooks;

/**
 * Defines importer app that gets pushed group and member data on events.
 */
class Importer extends Webhook
{
    /**
     * Authenticate using basic auth.
     */
    public function __construct($args = array(), $domain = 'http://54.225.169.159:8080/', $authentication = 'oauth')
    {
        parent::__construct(array('token' => $args['token'], 'pass' => $args['secret']), $domain, $authentication);
    }
}

