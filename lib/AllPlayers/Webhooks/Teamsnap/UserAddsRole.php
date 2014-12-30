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
 * The unique TeamSnap implementation of the user_adds_role webhook.
 */
class UserAddsRole extends SimpleWebhook implements ProcessInterface
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

        // Add the role for the user defined in the webhook contents.
        $this->addTeamsnapRole();
    }

    /**
     * Process the payload response and manage the partner-mapping API calls.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   Response from the webhook being processed/called.
     */
    public function processResponse(Response $response)
    {
        // Convert the raw Guzzle Response object to an array.
        $response = $this->helper->processJsonResponse($response);

        // Partner map the user..
        $this->mapRoster($response);

        // Partner map the users Phone.
        $this->mapRosterPhone($response);
    }

    /**
     * Add a role for the given user.
     *
     * Note: This will create the user on TeamSnap, if they don't already exist.
     */
    private function addTeamsnapRole()
    {
        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Get the UserID from partner-mapping API.
        $method = $roster = '';
        $roster = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_USER,
            $data['member']['uuid'],
            $data['group']['uuid']
        );

        // Create a new user on TeamSnap, if the resource was not found.
        if (isset($roster['message'])) {
            $method = self::HTTP_POST;
        } elseif (isset($roster['external_resource_id'])) {
            $method = self::HTTP_PUT;
            $roster = $roster['external_resource_id'];
        }

        // Get the TeamID from partner-mapping API.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $data['group']['uuid'],
            $data['group']['uuid']
        );
        if (isset($team['external_resource_id'])) {
            $team = $team['external_resource_id'];
        } else {
            // @TODO: If the team is null, requeue this webhook.
            $team = null;
        }

        // Build the webhook payload.
        $send = array();
        switch ($data['member']['role_name']) {
            case 'Owner':
                // Owner role does not exist by default, but it is injected when
                // the UserAddsRole webhook is created during the
                // UserCreatesGroup webhook. This is used to avoid further hacks
                // inside the UserCreatesGroup webhook for adding the creator.
                $send['non_player'] = 1;
                $send['is_manager'] = 1;
                $send['is_commissioner'] = 0;
                $send['is_owner'] = 1;
                break;
            case 'Player':
                $send['non_player'] = 0;
                break;
            case 'Admin':
            case 'Manager':
            case 'Coach':
                $send['is_manager'] = 1;
                break;
            case 'Fan':
                // If the user is being created, specify non-player
                // role, otherwise disregard to avoid overwriting
                // a pre-existing player status.
                if ($method == self::HTTP_POST) {
                    $send['non_player'] = 1;
                }
                break;
            case 'Guardian':
                // Ignore AllPlayers guardian changes.
                $this->setSend(self::WEBHOOK_CANCEL);
                break;
        }

        // Add email information to the webhook payload.
        if (isset($data['member']['guardian'])) {
            $send['roster_email_addresses_attributes'] = $this->getEmailResource(
                $data['member']['guardian']['email'],
                $data['member']['uuid'],
                $data['group']['uuid']
            );
        } else {
            $send['roster_email_addresses_attributes'] = $this->getEmailResource(
                $data['member']['email'],
                $data['member']['uuid'],
                $data['group']['uuid']
            );
        }

        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id']
            . '/rosters';

        // Create/update partner-mapping information.
        if ($method == self::HTTP_POST) {
            // Add additional information to the payload.
            $send['first'] = $data['member']['first_name'];
            $send['last'] = $data['member']['last_name'];

            // Update the request and let PostWebhooks complete.
            $this->setData(array('roster' => $send));
            parent::post();
        } else {
            $this->domain .= '/' . $roster;

            // Update the request and let PostWebhooks complete.
            $this->setData(array('roster' => $send));
            parent::put();
        }
    }

    /**
     * Add or Update the users Roster mapping for TeamSnap.
     *
     * Note: If the Email mapping is created, send an email invitation.
     *
     * @param array $response
     *   An array representation of the response data.
     */
    private function mapRoster(array $response)
    {
        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getOriginalData();

        // Get UserID from the partner-mapping API.
        $query = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_USER,
            $original_data['member']['uuid'],
            $original_data['group']['uuid']
        );

        // Add/update the mapping of an email with a Roster.
        $this->partner_mapping->createPartnerMap(
            $response['roster']['roster_email_addresses'][0]['id'],
            PartnerMap::PARTNER_MAP_USER,
            $original_data['member']['uuid'],
            $original_data['group']['uuid'],
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_EMAIL
        );

        // If the partner map was just created, send an email invite.
        if (isset($query['message'])) {
            // Add a mapping of the user UUID with a TeamSnap RosterID.
            $this->partner_mapping->createPartnerMap(
                $response['roster']['id'],
                PartnerMap::PARTNER_MAP_USER,
                $original_data['member']['uuid'],
                $original_data['group']['uuid']
            );

            // Send the TeamSnap account invite.
            $team = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_GROUP,
                $original_data['group']['uuid'],
                $original_data['group']['uuid']
            );
            if (isset($team['external_resource_id'])) {
                $team = $team['external_resource_id'];
            } else {
                $team = null;
            }

            $this->domain = 'https://api.teamsnap.com/v2/teams/'
                . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id']
                . '/invitations';
            $send = array(
                $response['roster']['id'],
            );

            // Update the request and send.
            $this->setData(array('rosters' => $send));
            parent::post();
            $this->send();
        }
    }

    /**
     * Add or Update the users Roster phone mapping for TeamSnap.
     *
     * @param array $response
     *   An array representation of the response data.
     */
    private function mapRosterPhone($response)
    {
        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getOriginalData();

        // Add/update the mapping of phones with a Roster.
        $phones = $response['roster']['roster_telephone_numbers'];
        if (count($phones) > 0) {
            // Determine which phones are present.
            foreach ($phones as $entry) {
                switch ($entry['label']) {
                    case 'Home':
                        $this->partner_mapping->createPartnerMap(
                            $entry['id'],
                            PartnerMap::PARTNER_MAP_USER,
                            $original_data['member']['uuid'],
                            $original_data['group']['uuid'],
                            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE
                        );
                        break;
                    case 'Cell':
                        $this->partner_mapping->createPartnerMap(
                            $entry['id'],
                            PartnerMap::PARTNER_MAP_USER,
                            $original_data['member']['uuid'],
                            $original_data['group']['uuid'],
                            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE_CELL
                        );
                        break;
                    case 'Work':
                        $this->partner_mapping->createPartnerMap(
                            $entry['id'],
                            PartnerMap::PARTNER_MAP_USER,
                            $original_data['member']['uuid'],
                            $original_data['group']['uuid'],
                            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE_WORK
                        );
                        break;
                }
            }
        }
    }
}
