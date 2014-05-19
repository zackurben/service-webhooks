<?php

/**
 * @file PostWebhooks.php
 *
 * Provides the definition of resque worker used to make POST requests.
 */

/**
 * Resque object that uses basic perform function to process queued jobs.
 */
class PostWebhooks
{

    /**
     * Redirect all requests to this URL for development.
     *
     * @var string
     */
    public $test_url;

    /**
     * Initiate redis connection before processing.
     */
    public function __construct()
    {
        include __DIR__ . '/config/config.php';

        if (isset($config['redis_password']) && !$config['redis_password'] == '') {
            Resque::setBackend('redis://redis:' . $config['redis_password'] . '@' . $config['redis_host']);
        }
        if (isset($config['test_url'])) {
            $this->test_url = $config['test_url'];
        }
    }

    /**
     * Perform the webhook processing operation.
     */
    public function perform()
    {
        $hook = $this->args['hook'];
        $subscriber = $this->args['subscriber'];
        $event_data = $this->args['event_data'];

        $classname = 'AllPlayers\\Webhooks\\' . $hook['name'];

        $webhook = new $classname($subscriber['variables'], $event_data, array('test_url' => $this->test_url));
        $response = $webhook->request->send();

        if ($webhook instanceof ProcessInterface) {
            // process the response, according to each specific webhook
            // call api here to map allplayers to partner uuids
            $webhook->processResponse($response);
        }
    }

}
