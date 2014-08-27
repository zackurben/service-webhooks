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

        // Get the data from the AllPlayers webhook.
        $data = $this->getData();

        // Cancel the webhook if this is not a team being registered.
        if ($data['group']['group_type'] == 'Team') {
            $this->domain .= '/teams';

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

            // Create a team on TeamSnap.
            $this->setData(array('team' => $send));
            parent::post();
            $response = $this->send();

            // Manually invoke the partner-mapping.
            $this->processResponse($response);

            // Stop the call PostWebhooks#send() because the processing and
            // response processing for both the user_creates_group and
            // user_adds_role was handled above.
            $this->setSend(self::WEBHOOK_CANCEL);

            // Create a UserAddsRole webhook with modified contents to force
            // the creation of the group creator.
            $temp_data = $data;
            $temp_data['webhook_type'] = self::WEBHOOK_ADD_ROLE;
            $temp_data['member']['role_name'] = 'Owner';

            $temp = new \AllPlayers\Webhooks\Teamsnap\UserAddsRole(
                array(),
                $temp_data
            );
            $temp->process();

            if ($temp->getSend() == self::WEBHOOK_SEND) {
                $temp_response = $temp->send();
                $temp->processResponse($temp_response);
                $temp->setSend(self::WEBHOOK_CANCEL);
            }
        } else {
            $this->setSend(self::WEBHOOK_CANCEL);
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

        // Associate an AllPlayers group UUID with a TeamSnap TeamID.
        $this->partner_mapping->createPartnerMap(
            $response['team']['id'],
            PartnerMap::PARTNER_MAP_GROUP,
            $original_data['group']['uuid'],
            $original_data['group']['uuid']
        );
    }
}
