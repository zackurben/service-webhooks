<?php
/**
 * @file
 * Contains PostWebhooks.
 *
 * Provides the definition for a resque worker with Webhook jobs.
 */

/**
 * Resque object that uses basic perform function to process queued jobs.
 */
class PostWebhooks
{
    /**
     * Initiate a redis connection before processing.
     */
    public function __construct()
    {
        include __DIR__ . '/config/config.php';

        $redis = (array_key_exists('redis_password', $config)
            && !$config['redis_password'] == '');
        if ($redis) {
            Resque::setBackend(
                'redis://redis:' . $config['redis_password'] . '@' . $config['redis_host']
            );
        }

        // Listen for Perform events so that we can manage Unique Locks.
        Resque_Event::listen(
            'beforePerform',
            array(new \AllPlayers\ResquePlugins\LockPlugin(), 'beforePerform')
        );
        Resque_Event::listen(
            'onFailure',
            array(new \AllPlayers\ResquePlugins\LockPlugin(), 'onFailure')
        );
        Resque_Event::listen(
            'afterPerform',
            array(new \AllPlayers\ResquePlugins\LockPlugin(), 'afterPerform')
        );
    }

    /**
     * Perform the Resque Job processing operation.
     */
    public function perform()
    {
        $hook = $this->args['hook'];
        $subscriber = $this->args['subscriber'];
        $event_data = $this->args['event_data'];

        // Create a new WebhookProcessor for the given partner.
        $classname = 'AllPlayers\\Webhooks\\' . $hook['name'] . '\\' . $hook['name'];
        $webhook = new $classname(
            $subscriber['variables'],
            $event_data
        );

        // Check if our webhook was canceled at creation.
        $defined = ($webhook->getWebhook() != null);
        $send = false;

        if ($defined) {
            $send = ($webhook->getWebhook()->getSend() == \AllPlayers\Webhooks\Webhook::WEBHOOK_SEND);
        }

        // Send the webhook if it was not canceled during its processing.
        if ($defined && $send) {
            $response = $webhook->send();

            if ($webhook->getWebhook() instanceof \AllPlayers\Webhooks\ProcessInterface) {
                // Process the response, according to each specific webhook.
                $webhook->getWebhook()->processResponse($response);
            }
        }
    }
}
