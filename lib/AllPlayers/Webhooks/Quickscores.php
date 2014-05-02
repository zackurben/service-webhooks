<?php

/**
 * @file Quickscores.php
 *
 * Provides the Quickscores Webhooks plugin definition. The Quickscores Webhook
 * sends AllPlayers hook data to a single endpoint for processing the data;
 * this webhook uses basic authentication.
 */

namespace AllPlayers\Webhooks;

/**
 * Base Quickscores Webhook definition.
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
     * The method used for Client authentication.
     *
     * Options:
     *   'no_authentication'
     *   'basic_auth'
     *   'oauth'
     * Default:
     *   'no_authentication'
     *
     * If using 'basic_auth', the $subscriber must contain: user and pass.
     * If using 'oauth', the $subscriber must contain: consumer_key, consumer_secret, token, and secret.
     */
    public $authentication = 'basic_auth';

    /**
     * The method of data transmission.
     *
     * Options:
     *   'form-urlencoded'
     *   'json'
     * Default:
     *   'json'
     *
     * @var string
     */
    public $method = 'form-urlencoded';

    /**
     * Determines if the webhook will return data that requires processing.
     *
     * Options:
     *   true
     *   false
     * Default:
     *   false
     *
     * @var boolean
     */
    public $processing = false;

    /**
     * Authenticate using basic auth.
     */
    public function __construct(array $subscriber = array(), array $data = array(), array $preprocess = array())
    {
        parent::__construct(array('user' => $subscriber['user'], 'pass' => $subscriber['token']), $data, $preprocess);
        $this->process();
    }

    /**
     * Process the webhook and set the domain to the appropriate URL
     */
    public function process()
    {
        /**
         * Do nothing here because, QuickScores has a single API endpoint for
         * processing our data.
         */
        parent::post();
    }

}
