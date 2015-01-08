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

        // Stop processing if this webhook isn't being sent.
        $send = $this->checkSend();
        if (!$send) {
            return;
        }

        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Get TeamID from the partner-mapping API.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $data['group']['uuid'],
            $data['group']['uuid']
        );
        $team = $team['external_resource_id'];

        // Build the webhook payload.
        $send = $this->updateTeamsnapGroup();

        // Update the request and let PostWebhooks complete.
        $this->domain .= '/teams/' . $team;
        $this->setData(array('team' => $send));
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

    /**
     * Build the webhook payload to update a TeamSnap group.
     *
     * @return array
     *   The group data to send to make an update.
     */
    private function updateTeamsnapGroup()
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

        // Build the initial payload information.
        $send = array(
            'team_name' => $data['group']['name'],
            'zipcode' => $data['group']['postalcode'],
        );

        // Add additional information if present.
        $this->addSport($send);
        $this->addGeographical($send);
        $this->addLogo($send);

        return $send;
    }

    /**
     * Add the TeamSnap SportID to the update payload.
     *
     * @param array $send
     *   The array to append update data.
     */
    private function addSport(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

        // Convert the AllPlayers sport to the supported TeamSnap SportID.
        $send['sport_id'] = $this->getSport($data['group']['group_category']);
    }

    /**
     * Add the TeamSnap Timezone and Location data to the update payload.
     *
     * @param array $send
     *   The array to append update data.
     */
    private function addGeographical(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

        // Get the geographical information based on the timezone.
        $geographical = $this->getRegion($data['group']['timezone']);

        // Update the timezone and location for the determined region.
        $send['timezone'] = $geographical['timezone'];
        $send['location'] = $geographical['location'];
    }

    /**
     * Add the AllPlayers group logo url to the update payload.
     *
     * @param array $send
     *   The array to append update data.
     */
    private function addLogo(&$send)
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getOriginalData();

        // Add the logo from AllPlayers, if present.
        if (isset($data['group']['logo'])) {
            $send['logo_url'] = $data['group']['logo'];
        }
    }
}
