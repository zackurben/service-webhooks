<?php
/**
 * @file
 * Provides the Importer webhooks plugin definition.
 */

namespace AllPlayers/Webhooks;

/**
 * Defines importer app that gets pushed group and member data on events.
 */
class Importer extends Webhook
{
    /**
     * The URL of the webhook.
     *
     * @var string
     */
    public $domain = 'http://54.225.169.159:8080/';

    /**
     * The authentication method used in the post request.
     *
     * @var string
     */
    public $authentication = 'oauth';

    /**
     * Authenticate using basic auth.
     */
    public function __construct($args = array())
    {
        parent::__construct(array('token' => $args['token'], 'pass' => $args['secret']));
    }
}

