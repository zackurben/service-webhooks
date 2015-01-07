<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserAddsRole.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use AllPlayers\Utilities\PartnerMap;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_removed_from_group webhook.
 */
class UserRemovedFromGroup extends SimpleWebhook implements ProcessInterface
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

        // Delete the given user from TeamSnap.
        $this->removeUser();
    }

    /**
     * Process the payload response and manage the partner-mapping API calls.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   Response from the webhook being processed/called.
     */
    public function processResponse(Response $response)
    {
        $response = $this->helper->processJsonResponse($response);

        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getOriginalData();

        // Delete the User from the partner-mapping API.
        $this->partner_mapping->deletePartnerMap(
            PartnerMap::PARTNER_MAP_USER,
            $original_data['group']['uuid'],
            $original_data['member']['uuid']
        );

        // Delete the Users Email from the partner-mapping API.
        $this->partner_mapping->deletePartnerMap(
            PartnerMap::PARTNER_MAP_RESOURCE,
            $original_data['group']['uuid'],
            $original_data['member']['uuid'],
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_EMAIL
        );
    }

    /**
     * Deletes the users resources on TeamSnap.
     */
    private function removeUser()
    {
        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Get the TeamID from the partner-mapping API.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $data['group']['uuid'],
            $data['group']['uuid']
        );
        $team = $team['external_resource_id'];

        // Get the RosterID from the partner-mapping API.
        $roster = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_USER,
            $data['member']['uuid'],
            $data['group']['uuid']
        );
        $roster = $roster['external_resource_id'];

        // Delete the user from the team.
        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id']
            . '/rosters/' . $roster;

        parent::delete();
    }
}
