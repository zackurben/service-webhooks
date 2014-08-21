<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Quickscores/SimpleWebhook.
 */

namespace AllPlayers\Webhooks\Quickscores;

use AllPlayers\Webhooks\Webhook;
use AllPlayers\Webhooks\WebhookInterface;

/**
 * A simple webhook that doesnt require unique processing for each webhook type.
 */
class SimpleWebhook extends Webhook implements WebhookInterface
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
     * @var integer
     *
     * @see Webhook::AUTHENTICATION_NONE
     * @see Webhook::AUTHENTICATION_BASIC
     * @see Webhook::AUTHENTICATION_OAUTH
     */
    protected $authentication = self::AUTHENTICATION_BASIC;

    /**
     * The method of data transmission.
     *
     * This establishes the method of transmission between the AllPlayers
     * webhook and the third-party webhook.
     *
     * @var string
     *
     * @see Webhook::TRANSMISSION_URLENCODED
     * @see Webhook::TRANSMISSION_JSON
     */
    protected $method = self::TRANSMISSION_URLENCODED;

    /**
     * Create the simple webhook and process it.
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
     * Process the webhook data and set the domain to the appropriate URL.
     */
    public function process()
    {
        // Do no processing here, because this is a simplex webhook that dumps
        // all raw data to a single URL.
        parent::post();
    }
}
