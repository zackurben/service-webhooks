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
    protected $domain = 'http://www.quickscores.com/API/SynchEvents.php';

    /**
     * The method used for Client authentication.
     *
     * @see AUTHENTICATION_NONE
     * @see AUTHENTICATION_BASIC
     * @see AUTHENTICATION_OAUTH
     *
     * @var integer
     */
    protected $authentication = self::AUTHENTICATION_BASIC;

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
    protected $method = self::TRANSMISSION_URLENCODED;

    /**
     * Authenticate using basic auth.
     */
    public function __construct(
        array $subscriber = array(),
        array $data = array(),
        array $preprocess = array()
    ) {
        parent::__construct(
            array(
                'user' => $subscriber['user'],
                'pass' => $subscriber['token']
            ),
            $data,
            $preprocess
        );
        $this->process();
    }

    /**
     * Process the webhook and set the domain to the appropriate URL
     */
    protected function process()
    {
        /**
         * Do nothing here because, QuickScores has a single API endpoint for
         * processing our data.
         */
        parent::post();
    }
}
