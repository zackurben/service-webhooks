<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/WebhookProcessor.
 */

namespace AllPlayers\Webhooks;

/**
 * The WebhookProcessor skeleton object.
 *
 * The WebhookProcessor provides a common interface for all Custom Webhook
 * Processor definitions.
 */
class WebhookProcessor
{
    /**
     * The Webhook that the Processor is working on.
     *
     * @var \AllPlayers\Webhooks\Webhook
     */
    protected $webhook;

    /**
     * Get the send flag for the webhook.
     *
     * @return integer
     *
     * @see Webhook::WEBHOOK_SEND
     * @see Webhook::WEBHOOK_CANCEL
     */
    public function getSend()
    {
        return $this->webhook->getSend();
    }

    /**
     * Return the Webhook object.
     *
     * @return \AllPlayers\Webhooks\Webhook
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * Invoke the send function for the webhook.
     *
     * @return \Guzzle\Http\Message\Response
     */
    public function send()
    {
        return $this->webhook->send();
    }
}
