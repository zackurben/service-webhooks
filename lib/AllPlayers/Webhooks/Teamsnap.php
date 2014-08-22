<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap.
 */

namespace AllPlayers\Webhooks;

/**
 * TeamSnap WebhookProcessor which will uniquely process each webhook.
 */
class Teamsnap extends WebhookProcessor
{
    /**
     * Create a TeamSnap Webhook object to process.
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
        // Create the TeamSnap specific webhook, if it is defined.
        $teamsnap_class = 'AllPlayers\\Webhooks\\Teamsnap\\'
            . Webhook::$classes[$data["webhook_type"]];
        if (array_key_exists("webhook_type", $data) && class_exists($teamsnap_class)) {
            $class = 'AllPlayers\\Webhooks\\Teamsnap\\' . Webhook::$classes[$data["webhook_type"]];
            $this->webhook = new $class($subscriber, $data);
            $this->webhook->process();
        }
    }
}
