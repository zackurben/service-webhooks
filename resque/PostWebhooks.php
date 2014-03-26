<?php
/**
 * @file
 *
 * Provides definition of resque worker used to make post requests.
 */

/**
 * Class that uses basic perform function to process queued jobs.
 */
class PostWebhooks
{
    /**
     * Rewrite the URL to this test URL for development.
     *
     * @var string
     */
    public $test_url;

    /**
     * Before processing, connect to correct instance of redis.
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
     * Perform the post operations.
     */
    public function perform()
    {
        $hook = $this->args['hook'];
        $subscriber = $this->args['subscriber'];
        $event_data = $this->args['event_data'];
        $url = (array_key_exists('url', $subscriber['variables'])) ? $subscriber['variables']['url'] : '';

        $classname = 'AllPlayers\\Webhooks\\' . $hook['name'];
        $webhook = new $classname($subscriber['variables'], $event_data);
        $webhook_data = array(
          'event_name' => $hook['name'],
          'event_data' => $event_data
        );
        if (!empty($this->test_url)) {
          $webhook_data['original_url'] = $url;
          $url = $this->test_url;
        }
        $webhook->post($url);
        $result = $webhook->send($webhook_data);
    }
}   
