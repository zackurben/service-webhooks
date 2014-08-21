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
     * @param array $preprocess
     *   Additional data used for pre-processing, defined in PostWebhooks.
     */
    public function __construct(
        array $subscriber = array(),
        array $data = array(),
        array $preprocess = array()
    ) {
        // Create the TeamSnap specific webhook, if it is defined.
        if (array_key_exists("webhook_type", $data) && class_exists('AllPlayers\\Webhooks\\Teamsnap\\' . Webhook::$classes[$data["webhook_type"]])) {
            $class = 'AllPlayers\\Webhooks\\Teamsnap\\' . Webhook::$classes[$data["webhook_type"]];
            $this->webhook = new $class($subscriber, $data, $preprocess);
            $this->webhook->process();
        }
    }
}
