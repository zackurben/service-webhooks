<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserDeletesGroup.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use AllPlayers\Utilities\PartnerMap;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_deletes_group webhook.
 */
class UserDeletesGroup extends SimpleWebhook implements ProcessInterface
{
    /**
     * Create a TeamSnap Webhook object.
     *
     * @param array $subscriber
     *   The Subscriber variable provided by the Resque Job.
     * @param array $data
     *   The Event Data variable provided by the Resque Job.
     * @param array $preprocess
     *   Additional data used for pre-processing, defined in PostWebhooks.
     */
    public function __construct(
        array $subscriber = array(),
        array $data = array(),
        array $preprocess = array()
    ) {
        parent::__construct($subscriber, $data, $preprocess);
    }

    /**
     * Process the webhook data and manage the partner-mapping API calls.
     */
    public function process()
    {
        // Set the original webhook data.
        $data = $this->getData();
        $this->setOriginalData($data);

        // Get TeamID from partner-mapping API.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $data['group']['uuid'],
            $data['group']['uuid']
        );
        $team = $team['external_resource_id'];
        $this->domain .= '/teams/' . $team;

        // Update the request and let PostWebhooks complete.
        $this->headers['X-Teamsnap-Features'] = '{"partner.delete_team": 1}';
        parent::delete();
    }

    /**
     * Process the payload response and manage the partner-mapping API calls.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   Response from the webhook being processed/called.
     */
    public function processResponse(Response $response)
    {
        include 'config/config.php';
        if (isset($config['test_url'])) {
            // Account for the extra JSON wrapper from requestbin (if testing).
            $response = $this->helper->processJsonResponse($response);
            $response = json_decode($response['body'], true);
        } else {
            $response = $this->helper->processJsonResponse($response);
        }

        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getData();
        $original_data = $this->getOriginalData();

        // Delete partner-mapping with the group UUID.
        $this->partner_mapping->deletePartnerMap(
            null,
            $original_data['group']['uuid']
        );
    }
}
