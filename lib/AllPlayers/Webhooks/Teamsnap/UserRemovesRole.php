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

        // Cancel the continued processing of this webhook, if this was canceled
        // in the parent:process() call.
        if ($this->getSend() != \AllPlayers\Webhooks\Webhook::WEBHOOK_SEND) {
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

        $this->domain .= '/teams/' . $team . '/as_roster/'
            . $this->webhook->subscriber['commissioner_id']
            . '/rosters/' . $roster;

        // Build the webhook payload.
        $send = array();
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

        // Update the request and let PostWebhooks complete, only if the
        // update data is included.
        if (empty($send)) {
            $this->setSend(self::WEBHOOK_CANCEL);
        } else {
            $this->setData(array('roster' => $send));
        }
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
