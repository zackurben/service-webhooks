<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/WebhookInterface.
 *
 * Provides the required method signatures for a webhook.
 */

namespace AllPlayers\Webhooks;

/**
 * The required functions for any webhook that implements the WebhookInterface.
 */
interface WebhookInterface
{
    /**
     * Process the webhook data and manage any partner-mapping API calls.
     */
    public function process();
}
