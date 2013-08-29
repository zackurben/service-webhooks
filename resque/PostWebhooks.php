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
     * Before processing, connect to correct instance of redis.
     */
    public function __construct()
    {
        include dirname(__DIR__) . '/resque/config/config.php';        
        if (isset($config['redis_password']) && !$config['redis_password'] == '') {
            Resque::setBackend('redis://redis:' . $config['redis_password'] . '@' . $config['redis_host']);
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
        $webhook = new $classname($subscriber['variables']);
        $webhook->post($url);
        $result = $webhook->send(array('event_name' => $hook['name'], 'event_data' => $event_data));
    }
}   
