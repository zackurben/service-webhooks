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
        // Check if this webhook is enabled.
        include 'config/config.php';
        if (isset($config['custom'])) {
            // Determine if the organization is defined.
            $organization = array_key_exists(
                $data['group']['organization_id'][0],
                $config['custom']
            );

            // Determine send setting for an organization.
            if ($organization) {
                $organization = $data['group']['organization_id'][0];
                $webhook_send = $config['custom'][$organization]['send'];
            } else {
                $webhook_send = $config['custom']['default']['send'];
            }

            if ($webhook_send) {
                // Create and process the SimpleWebhook defined for all Custom
                // Webhooks.
                $this->webhook = new SimpleWebhook($subscriber, $data);
                $this->webhook->process();
            }
        }
    }
}
