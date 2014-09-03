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
     * Process the webhook data and manage the partner-mapping API calls.
     */
    public function process()
    {
        parent::process();

        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

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
