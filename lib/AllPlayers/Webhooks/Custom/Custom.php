<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Custom/Custom.
 */

namespace AllPlayers\Webhooks\Custom;

use AllPlayers\Webhooks\WebhookProcessor;

/**
 * Custom WebhookProcessor which will send any webhook to the specified URL.
 */
class Custom extends WebhookProcessor
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
        // Create and process the SimpleWebhook defined for all Custom Webhooks.
        $this->webhook = new SimpleWebhook($subscriber, $data);
        $this->webhook->process();
    }
}
