<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Custom/SimpleWebhook.
 */

namespace AllPlayers\Webhooks\Custom;

use AllPlayers\Webhooks\Webhook;
use AllPlayers\Webhooks\WebhookInterface;

/**
 * A simple webhook that doesnt require unique processing for each webhook type.
 */
class SimpleWebhook extends Webhook implements WebhookInterface
{
    /**
     * Create the simple webhook and process it.
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
    public function process()
    {
        // Do no processing here, because this is a simplex webhook that dumps
        // all raw data to a single URL.
        parent::post();
    }
}
