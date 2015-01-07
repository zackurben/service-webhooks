<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserRemovesRole.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use AllPlayers\Utilities\PartnerMap;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_removes_role webhook.
 */
class UserRemovesRole extends SimpleWebhook implements ProcessInterface
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

        // Get RosterID from the partner-mapping API.
        $roster = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_USER,
            $data['member']['uuid'],
            $data['group']['uuid']
        );
        $roster = $roster['external_resource_id'];

        // Get TeamID from the partner-mapping API.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $data['group']['uuid'],
            $data['group']['uuid']
        );
        $team = $team['external_resource_id'];

        // Update the request domain, using the TeamSnap Resource ID's.
        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id']
            . '/rosters/' . $roster;

        // Build the webhook payload to update the status of a user on TeamSnap.
        $send = $this->updateTeamsnapRole();

        // Only continue if update data is present.
        if (empty($send)) {
            $this->setSend(self::WEBHOOK_CANCEL);
        } else {
            $this->setData(array('roster' => $send));
            parent::put();
        }
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
     * Build the webhook payload to update the users roles.
     *
     * @return array
     *   The user data to send to update the user account.
     */
    private function updateTeamsnapRole()
    {
        // Build the webhook payload to update the status of a user on TeamSnap.
        $send = array();
        $this->addRoleChanges($send);

        return $send;
    }

    /**
     * Edit the Users roles, using the given webhook data.
     *
     * @param array $send
     *   The array to append the new user data.
     */
    private function addRoleChanges(&$send)
    {
        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Update the roles by negating the data sent in the webhook.
        switch ($data['member']['role_name']) {
            case 'Player':
                $send['non_player'] = 1;
                break;
            case 'Admin':
            case 'Manager':
            case 'Coach':
                $send['is_manager'] = 0;
                break;
            case 'Guardian':
                // Ignore AllPlayers guardian changes.
                $this->setSend(self::WEBHOOK_CANCEL);
                break;
        }
    }
}
