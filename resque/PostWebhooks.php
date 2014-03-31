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
     * Perform the webhook post operation.
     */
    public function perform()
    {
        $hook = $this->args['hook'];
        $subscriber = $this->args['subscriber'];
        $event_data = $this->args['event_data'];
		
        $url = (array_key_exists('url', $subscriber['variables'])) ? $subscriber['variables']['url'] : '';
        $classname = 'AllPlayers\\Webhooks\\' . $hook['name'];

        $webhook = new $classname($subscriber['variables'], $event_data);
	
        if (!empty($this->test_url)) {
			// TODO add original url to webhook data: 'original_url' => $url
			// $webhook_data['original_url'] = $url;
			$url = $this->test_url;
        }
        $webhook->post($url);
        $result = $webhook->send($webhook->getData());
    }
}   
