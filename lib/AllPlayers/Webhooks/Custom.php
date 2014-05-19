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
     * The URL to post the webhook.
     *
     * @var string
     */
    public $domain;

    /**
     * The method used for Client authentication.
     *
     * @see AUTHENTICATION_NONE
     * @see AUTHENTICATION_BASIC
     * @see AUTHENTICATION_OAUTH
     *
     * @var integer
     */
    public $authentication = self::AUTHENTICATION_NONE;

    /**
     * The method of data transmission.
     *
     * This establishes the method of transmission between the AllPlayers
     * webhook and the third-party webhook.
     *
     * @see TRANSMISSION_URLENCODED
     * @see TRANSMISSION_JSON
     *
     * @var string
     */
    public $method = self::TRANSMISSION_JSON;

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
