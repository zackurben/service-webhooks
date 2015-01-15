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

        // Stop processing if this request isn't being sent.
        $send = $this->checkSend();
        if (!$send) {
            return;
        }

        // Only allow a user_creates_webhook to continue if the group does not
        // already exist on TeamSnap.
        $send = $this->checkNotWebhookSync();
        if (!$send) {
            return;
        }

        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Build the request payload.
        $send = array();
        $this->addTeam($send);
        $this->addTeamRegion($send);
        $this->addTeamSport($send);
        $this->addTeamLogo($send);
        $send = array('team' => $send);

        // Update the payload domain.
        $this->domain .= '/teams';

        // Set the payload data.
        $this->setRequestData($send);

        // Update the request type.
        parent::post();

        // Manually send the request.
        if ($this->getSend() == self::WEBHOOK_SEND) {
            $response = $this->send();

            // Manually process the response.
            $this->processResponse($response);
        } else {
            // Skip the user_adds_role hack, since the team was not actually
            // made.
            return;
        }

        // Cancel this primary request (manually processed).
        $this->setSend(self::WEBHOOK_CANCEL);

        // Manipulate the original webhook payload to be a
        // user_updates_group webhook.
        $data['webhook_type'] = self::WEBHOOK_ADD_ROLE;
        $data['member']['role_name'] = 'Owner';

        // Create a new webhook to be manually processed.
        $webhook = new UserAddsRole(
            array(),
            $data
        );
        $webhook->process();

        // Send the webhook if it hasn't been canceled and process the
        // response.
        if ($webhook->getSend() == self::WEBHOOK_SEND) {
            $temp_response = $webhook->send();
            $webhook->processResponse($temp_response);
            $webhook->setSend(self::WEBHOOK_CANCEL);
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
        // Convert the raw Guzzle Response object to an array.
        $response = $this->helper->processJsonResponse($response);

        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getAllplayersData();

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
     * Note: If the group already exists, this webhook was triggered by a
     * webhook sync; this will change the webhook to be a user_updates_group
     * webhook, and handle the processing separately.
     *
     * @return bool
     *   If this is not a sync webhook.
     */
    private function checkNotWebhookSync()
    {
        // Get the original data sent from the AllPlayers webhook.
        $data = $this->getAllplayersData();

        // Get the TeamID from partner-mapping API.
        $team = $this->partner_mapping->getTeamId($data['group']['uuid']);

        // If the team exists in the partner-mapping api, this shouldn't be a
        // user_creates_group webhook; change to user_updates_group to ensure
        // the team information on TeamSnap is not stale.
        if (isset($team['external_resource_id'])) {
            // Cancel this request from sending.
            $this->setSend(self::WEBHOOK_CANCEL);

            // Manipulate the original webhook payload to be a
            // user_updates_group webhook.
            $data['webhook_type'] = self::WEBHOOK_UPDATE_GROUP;

            // Create a new webhook to be manually processed.
            $webhook = new UserUpdatesGroup(
                array(),
                $data
            );
            $webhook->process();

            // Send the webhook if it hasn't been canceled and process the
            // response.
            if ($webhook->getSend() == self::WEBHOOK_SEND) {
                $response = $webhook->send();
                $webhook->processResponse($response);
                $webhook->setSend(self::WEBHOOK_CANCEL);
            }

            // Return false since this webhook was triggered by a webhook sync.
            return false;
        } else {
            // Confirm this was not triggered from a sync, and allow processing
            // to continue.
            return true;
        }
    }
}
