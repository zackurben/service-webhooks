<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserUpdatesGroup.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use AllPlayers\Utilities\PartnerMap;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_updates_group webhook.
 */
class UserUpdatesGroup extends SimpleWebhook implements ProcessInterface
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

        // Get TeamID from the partner-mapping API.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $data['group']['uuid'],
            $data['group']['uuid']
        );
        $team = $team['external_resource_id'];
        $this->domain .= '/teams/' . $team;

        // Build the webhook payload.
        $geographical = $this->getRegion($data['group']['timezone']);
        $send = array(
            'team_name' => $data['group']['name'],
            'sport_id' => $this->getSport($data['group']['group_category']),
            'timezone' => $geographical['timezone'],
            'country' => $geographical['location'],
            'zipcode' => $data['group']['postalcode'],
        );

        // Add additional information to the payload.
        if (isset($data['group']['logo'])) {
            $send['logo_url'] = $data['group']['logo'];
        }

        // Update the request and let PostWebhooks complete.
        $this->setData(array('team' =>$send));
        parent::put();
    }

    /**
     * Process the payload response and manage the partner-mapping API calls.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   Response from the webhook being processed/called.
     */
    public function processResponse(Response $response)
    {

    }
}
