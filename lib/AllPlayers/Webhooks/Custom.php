<?php

/**
 * @file Custom.php
 *
 * Provides the Custom Webhooks plugin definition. The Custom Webhook is a
 * simplex Webhook for sending all data to a single, non-authenticated, url.
 */

namespace AllPlayers\Webhooks;

/**
 * Base Custom Webhook definition, to send data to a custom URL.
 */
class Custom extends Webhook
{

    /**
     * The URL to post the webhook
     *
     * @var string
     */
    public $domain;

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
    public $authentication = 'no_authentication';

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
    public $method = 'json';

    /**
     * Use custom url as domain.
     */
    public function __construct(array $subscriber = array(), array $data = array(), array $preprocess = array())
    {
        $this->domain = $subscriber['url'];
        parent::__construct($subscriber, $data, $preprocess);
        $this->process();
    }

    /**
     * Process the webhook data and set the domain to the appropriate URL
     */
    public function process()
    {
        /**
         * Do no processing here, because this is a simplex webhook that dumps
         * all raw data to a single URL.
         */
        parent::post();
    }

}
