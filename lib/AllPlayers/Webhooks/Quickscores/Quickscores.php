<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Quickscores/Quickscores.
 */

namespace AllPlayers\Webhooks\Quickscores;

use AllPlayers\Webhooks\WebhookProcessor;

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
     */
    public function __construct(
        array $subscriber = array(),
        array $data = array()
    ) {
        // Create and process the SimpleWebhook defined for all Quickscores
        // Webhooks.
        $this->webhook = new SimpleWebhook($subscriber, $data);
        $this->webhook->process();
    }
}
