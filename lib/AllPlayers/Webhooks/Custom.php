<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Custom.
 *
 * Provides the Custom Webhook definitions.
 */

namespace AllPlayers\Webhooks;

/**
 * Base Custom Webhook definition.
 */
class Custom extends Webhook
{
    /**
     * The URL to post the webhook.
     *
     * @var string
     */
    protected $domain;

    /**
     * The method used for Client authentication.
     *
     * @var integer
     *
     * @see AUTHENTICATION_NONE
     * @see AUTHENTICATION_BASIC
     * @see AUTHENTICATION_OAUTH
     */
    protected $authentication = self::AUTHENTICATION_NONE;

    /**
     * The method of data transmission.
     *
     * This establishes the method of transmission between the AllPlayers
     * webhook and the third-party webhook.
     *
     * @var string
     *
     * @see TRANSMISSION_URLENCODED
     * @see TRANSMISSION_JSON
     */
    protected $method = self::TRANSMISSION_JSON;

    /**
     * Create a Custom webhook object.
     *
     * @param array $subscriber
     *   The Subscriber variable provided by the Resque Job.
     * @param array $data
     *   The Event Data variable provided by the Resque Job.
     * @param array $preprocess
     *   Additional data used for pre-processing, defined in PostWebhooks.
     */
    public function __construct(
        array $subscriber = array(),
        array $data = array(),
        array $preprocess = array()
    ) {
        $this->domain = $subscriber['url'];
        parent::__construct($subscriber, $data, $preprocess);
        $this->process();
    }

    /**
     * Process the webhook data and set the domain to the appropriate URL.
     */
    protected function process()
    {
        // Do no processing here, because this is a simplex webhook that dumps
        // all raw data to a single URL.
        parent::post();
    }
}
