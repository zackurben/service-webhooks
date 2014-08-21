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
     * Redirect all requests to this URL for testing/development.
     *
     * @var string
     */
    public $test_url;

    /**
     * Initiate a redis connection before processing.
     */
    public function __construct()
    {
        include __DIR__ . '/config/config.php';

        if (isset($config['redis_password']) && !$config['redis_password'] == '') {
            Resque::setBackend(
                'redis://redis:' . $config['redis_password'] . '@' . $config['redis_host']
            );
        }
        if (isset($config['test_url'])) {
            $this->test_url = $config['test_url'];
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
        $classname = 'AllPlayers\\Webhooks\\' . $hook['name'];
        $webhook = new $classname(
            $subscriber['variables'],
            $event_data,
            array('test_url' => $this->test_url)
        );

        // Send the webhook if it was not canceled during its processing.
        if ($webhook->getWebhook()->getSend() == \AllPlayers\Webhooks\Webhook::WEBHOOK_SEND) {
            $response = $webhook->send();

            if ($webhook->getWebhook() instanceof \AllPlayers\Webhooks\ProcessInterface) {
                // Process the response, according to each specific webhook.
                $webhook->getWebhook()->processResponse($response);
            }
        }
    }
}
