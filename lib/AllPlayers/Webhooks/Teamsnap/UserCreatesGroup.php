<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserCreatesGroup.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use AllPlayers\Utilities\PartnerMap;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_creates_group webhook.
 */
class UserCreatesGroup extends SimpleWebhook implements ProcessInterface
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

        // Stop processing if this webhook is a sync - change to group update.
        $send = $this->checkNotWebhookSync();
        if (!$send) {
            return;
        }

        // Create the Group on TeamSnap.
        $this->createTeamsnapGroup();

        // Create the Admin for the new TeamSnap Group.
        $this->createTeamsnapGroupAdmin();
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

        // Associate an AllPlayers group UUID with a TeamSnap TeamID.
        $this->partner_mapping->createPartnerMap(
            $response['team']['id'],
            PartnerMap::PARTNER_MAP_GROUP,
            $original_data['group']['uuid'],
            $original_data['group']['uuid']
        );
    }

    /**
     * Confirm that this webhook was not triggered by a webhook sync.
     *
     * If the group already exists, this webhook was triggered by a webhook
     * sync; this will change the webhook to be a user_updates_group webhook,
     * and handle the processing separately.
     *
     * @return bool
     *   If this is not a sync webhook.
     */
    private function checkNotWebhookSync() {
        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Check if the group already exists and this is from a webhook sync.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $data['group']['uuid'],
            $data['group']['uuid']
        );
        if (isset($team['external_resource_id'])) {
            // This team already exists - change webhook to UserUpdatesGroup.
            $this->setSend(self::WEBHOOK_CANCEL);

            // Create a UserUpdatesGroup webhook since the group already exists.
            $temp_data = $data;
            $temp_data['webhook_type'] = self::WEBHOOK_UPDATE_GROUP;

            $temp = new \AllPlayers\Webhooks\Teamsnap\UserUpdatesGroup(
                array(),
                $temp_data
            );
            $temp->process();

            // Send the webhook if it hasn't been canceled and process the
            // response.
            if ($temp->getSend() == self::WEBHOOK_SEND) {
                $temp_response = $temp->send();
                $temp->processResponse($temp_response);
                $temp->setSend(self::WEBHOOK_CANCEL);
            }

            // Return false since this was a webhook sync.
            return false;
        } else {
            return true;
        }
    }

    /**
     * Create a group on the TeamSnap website.
     */
    private function createTeamsnapGroup() {
        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Build the webhook payload.
        $geographical = $this->getRegion($data['group']['timezone']);
        $send = array(
            'team_name' => $data['group']['name'],
            'team_league' => 'All Players',
            'division_id' => intval($this->webhook->subscriber['division_id']),
            'sport_id' => $this->getSport($data['group']['group_category']),
            'timezone' => $geographical['timezone'],
            'country' => $geographical['location'],
            'zipcode' => $data['group']['postalcode'],
        );

        // Add additional information to the payload.
        if (isset($data['group']['logo'])) {
            $send['logo_url'] = $data['group']['logo'];
        }

        // Update the payload and send.
        $this->setData(array('team' => $send));
        $this->domain .= '/teams';
        parent::post();

        // Send the webhook if it hasn't been canceled and process the
        // response.
        if ($this->getSend() == self::WEBHOOK_SEND) {
            $response = $this->send();

            // Manually invoke the partner-mapping.
            $this->processResponse($response);
        }

        // Don't send this webhook because it was handled above. This is a hack
        // because we chain the group admin creation after the group is created.
        $this->setSend(self::WEBHOOK_CANCEL);
    }

    /**
     * Create the group admin for the newly created group, using the response.
     */
    private function createTeamsnapGroupAdmin() {
        // Get the data from the AllPlayers webhook.
        $data = $this->getOriginalData();

        // Create a UserAddsRole webhook with modified contents to force the
        // creation of the group creator.
        $temp_data = $data;
        $temp_data['webhook_type'] = self::WEBHOOK_ADD_ROLE;
        $temp_data['member']['role_name'] = 'Owner';

        $temp = new \AllPlayers\Webhooks\Teamsnap\UserAddsRole(
            array(),
            $temp_data
        );
        $temp->process();

        // Send the webhook if it hasn't been canceled and process the
        // response.
        if ($temp->getSend() == self::WEBHOOK_SEND) {
            $temp_response = $temp->send();
            $temp->processResponse($temp_response);
            $temp->setSend(self::WEBHOOK_CANCEL);
        }
    }
}
