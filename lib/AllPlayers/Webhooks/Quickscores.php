<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Quickscores.
 */

namespace AllPlayers\Webhooks;

use AllPlayers\Webhooks\Quickscores\SimpleWebhook;

/**
 * Quickscores WebhookProcessor which will send all webhooks to the designated
 * URL.
 */
class Quickscores extends WebhookProcessor
{
    /**
     * Instantiate a SimpleWebhook using basic auth.
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
        // Create and process the SimpleWebhook defined for all Quickscores
        //  Webhooks.
        $this->webhook = new SimpleWebhook($subscriber, $data, $preprocess);
        $this->webhook->process();
    }
}
