<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/Teamsnap.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\Webhook;
use AllPlayers\Webhooks\WebhookProcessor;

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
        // Check if this webhook is enabled.
        include __DIR__ . '/../../../../resque/config/config.php';
        if (isset($config['teamsnap'])) {
            // Determine if the organization is defined.
            $organization = isset($data['group']['organization_id'][0]) && array_key_exists(
                $data['group']['organization_id'][0],
                $config['teamsnap']
            );

            // Determine send setting for an organization.
            if ($organization) {
                $organization = $data['group']['organization_id'][0];
                $webhook_send = $config['teamsnap'][$organization]['send'];
            } else {
                $webhook_send = $config['teamsnap']['default']['send'];
            }

            if ($webhook_send || isset($data['unit_test'])) {
                // Create the TeamSnap specific webhook, if it is defined.
                $teamsnap_class = 'AllPlayers\\Webhooks\\Teamsnap\\'
                    . Webhook::$classes[$data['webhook_type']];

                if (array_key_exists('webhook_type', $data) && class_exists($teamsnap_class)) {
                    $class = '\\AllPlayers\\Webhooks\\Teamsnap\\'
                        . Webhook::$classes[$data['webhook_type']];
                    $this->webhook = new $class($subscriber, $data);

                    if (!isset($data['unit_test'])) {
                        $this->webhook->process();
                    }
                }
            }
        }
    }
}
