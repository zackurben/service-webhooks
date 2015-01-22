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

        if (!empty($config['redis_password'])
            && !empty($config['redis_host'])
        ) {
            $REDIS_BACKEND = 'redis://redis:' . $config['redis_password'] . '@'
                . $config['redis_host'];
            Resque::setBackend($REDIS_BACKEND);
        }
        elseif (!empty($config['redis_host'])
            && empty($config['redis_password'])
        ) {
            $REDIS_BACKEND = $config['redis_host'];
            Resque::setBackend($REDIS_BACKEND);
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
        $classname = 'AllPlayers\\Webhooks\\' . $hook['name'] . '\\'
            . $hook['name'];
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

        // Get the requests final data, to determine if we need to make a new
        // webhook of a new tyoe.
        $data = $webhook->getWebhook()->getAllplayersData();
        if (isset($data['change_webhook']) && $data['change_webhook'] == 1) {
            // Make a temporary job with modified contents to queue.
            \AllPlayers\ResquePlugins\AllplayersPlugin::queueJob(
                $this->job,
                $data
            );
        } elseif (isset($data['requeue']) && $data['requeue'] == 1) {
            // Requeue this webhook because it was requested.
            \AllPlayers\ResquePlugins\AllplayersPlugin::requeueJob($this->job);
        }
    }
}
