<?php
/**
 * @file
 * Contains /AllPlayers/Webhooks/Teamsnap/UserDeletesEvent.
 */

namespace AllPlayers\Webhooks\Teamsnap;

use AllPlayers\Webhooks\ProcessInterface;
use AllPlayers\Utilities\PartnerMap;
use Guzzle\Http\Message\Response;

/**
 * The unique TeamSnap implementation of the user_deletes_event webhook.
 */
class UserDeletesEvent extends SimpleWebhook implements ProcessInterface
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

        // Determine if this is a game or an event, and remove its resources.
        if (isset($data['event']['competitor']) && !empty($data['event']['competitor'])) {
            $this->deleteGame();
        } else {
            $this->deletePractice();
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
        $response = $this->helper->processJsonResponse($response);

        // Get the original data sent from the AllPlayers webhook.
        $original_data = $this->getOriginalData();

        // Delete the partner-mapping with an event UUID.
        $this->partner_mapping->deletePartnerMap(
            PartnerMap::PARTNER_MAP_EVENT,
            $original_data['group']['uuid'],
            $original_data['event']['uuid']
        );
    }

    /**
     * Deletes all the resources on TeamSnap/AllPlayers for a game event.
     */
    private function deleteGame()
    {
        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Store the base domain to revert back to for each iteration.
        $original_domain = $this->domain;

        foreach ($data['event']['competitor'] as $group) {
            // Get TeamID from the partner-mapping API.
            $team = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_GROUP,
                $group['uuid'],
                $group['uuid']
            );
            $team = $team['external_resource_id'];

            // Get EventID from the partner-mapping API.
            $event = $this->partner_mapping->readPartnerMap(
                PartnerMap::PARTNER_MAP_EVENT,
                $data['event']['uuid'],
                $group['uuid']
            );
            $event = $event['external_resource_id'];

            // Update the request payload and send.
            $this->domain = $original_domain . '/teams/' . $team
                . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id']
                . '/games/' . $event;
            parent::delete();
            $this->send();

            // Reset the temp variables for the next iteration.
            $this->domain = $original_domain;
        }

        // Cancel the webhook for game events (manually processed).
        parent::setSend(parent::WEBHOOK_CANCEL);
    }

    /**
     * Deletes all the resources on TeamSnap/AllPlayers for a non-game event.
     */
    private function deletePractice()
    {
        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Get TeamID from the partner-mapping API.
        $team = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $data['group']['uuid'],
            $data['group']['uuid']
        );
        $team = $team['external_resource_id'];

        // Get EventID from partner-mapping API.
        $event = $this->partner_mapping->readPartnerMap(
            PartnerMap::PARTNER_MAP_EVENT,
            $data['event']['uuid'],
            $data['group']['uuid']
        );
        $event = $event['external_resource_id'];

        // Update the request and let PostWebhooks complete.
        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id']
            . '/practices/' . $event;
        parent::delete();
    }
}
