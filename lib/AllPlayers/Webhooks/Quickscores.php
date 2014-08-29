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
     */
    public function __construct(
        array $subscriber = array(),
        array $data = array()
    ) {
        // Check if this webhook is enabled.
        include 'config/config.php';
        if (isset($config['quickscores'])) {
            // Determine if the organization is defined.
            $organization = array_key_exists(
                $data['group']['organization_id'][0],
                $config['quickscores']
            );

            // Determine send setting for an organization.
            if ($organization) {
                $organization = $data['group']['organization_id'][0];
                $webhook_send = $config['quickscores'][$organization]['send'];
            } else {
                $webhook_send = $config['quickscores']['default']['send'];
            }

            if ($webhook_send) {
                // Create and process the SimpleWebhook defined for all Quickscores
                //  Webhooks.
                $this->webhook = new SimpleWebhook($subscriber, $data);
                $this->webhook->process();
            }
        }
    }
}
