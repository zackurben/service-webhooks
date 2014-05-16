<?php

/**
 * @file Teamsnap.php
 *
 * Provides the TeamSnap Webhooks plugin definition. The TeanSnap Webhook sends
 * data to various API Endpoints, using a custom token based authentication.
 */

namespace AllPlayers\Webhooks;

/**
 * Base TeamSnap Webhook definition.
 */
class Teamsnap extends Webhook
{

    /**
     * The URL to post the webhook.
     *
     * @var string
     */
    public $domain = 'https://api.teamsnap.com/v2';

    /**
     * The method used for Client authentication.
     *
     * Options:
     *   'no_authentication'
     *   'basic_auth'
     *   'oauth'
     * Default:
     *   'no_authentication'
     *
     * If using 'basic_auth', the $subscriber must contain: user and pass.
     *
     * If using 'oauth', the $subscriber must contain: consumer_key,
     * consumer_secret, token, and secret.
     *
     * @var string
     */
    public $authentication = 'no_authentication';

    /**
     * The method of data transmission.
     *
     * Options:
     *   'form-urlencoded'
     *   'json'
     * Default:
     *   'json'
     *
     * @var string
     */
    public $method = 'json';

    /**
     * Determines if the webhook will return data that requires processing.
     *
     * Options:
     *   true
     *   false
     * Default:
     *   false
     *
     * @var boolean
     */
    public $processing = true;

    /**
     * This variable is used for determining the type of the webhook being sent.
     *
     * This is required because, the $data of the webhook is being rewrtten
     * before it is sent, and we lose the type, which is then needed for
     * processResponse().
     *
     * @var string
     */
    public $webhook_type;

    /**
     * Create Teamsnap webhook using no_authentication.
     */
    public function __construct(array $subscriber = array(), array $data = array(), array $preprocess = array())
    {
        include 'config/config.php';
        if (isset($config['teamsnap'])) {
            parent::__construct(array('token' => $config['teamsnap']['token'],
                'commissioner_id' => $config['teamsnap']['commissioner_id'],
                'division_id' => $config['teamsnap']['division_id']), $data, $preprocess);

            $this->headers['X-Teamsnap-Token'] = $this->webhook->subscriber['token'];
            $this->process();
        }
    }

    /**
     * Process the webhook data and set the domain to the appropriate URL.
     *
     * @todo Fix case blockers.
     */
    public function process()
    {
        $this->webhook_type = $this->webhook->data['webhook_type'];

        // Consolidate test team/roster variables
//        $test_team = 515657;
//        $test_roster = 6144645;

        switch ($this->webhook_type) {
            case 'user_creates_group':
                /*
                 * Note: this is a different approach for user_creates_group,
                 * because we need to send two seperate calls to the TeamSnap
                 * API, since they do not allow for a roster to be added to a
                 * Team at creation time (we care about this to preserve the
                 * unique property of the group creator).
                 *
                 * This will first make a Team in the TeamSnap system, and will
                 * use the response data to add a creator to the Team.
                 */
                $this->domain .= '/teams';

                // team data to send
                $data = $this->webhook->data;
                $geographical = $this->getRegion($data['group']['timezone']);
                $send = array(
                    'team' => array(
                        'team_name' => $data['group']['name'],
                        'team_league' => 'All Players',
                        'division_id' => intval($this->webhook->subscriber['division_id']),
                        'sport_id' => $this->getSport($data['group']['group_category']),
                        'timezone' => $geographical['timezone'],
                        'country' => $geographical['location'],
                        'zipcode' => $data['group']['postalcode'],
                    ),
                );

                /*
                 * A copy of the original webhook data for processResponse();
                 * this is needed because we send more than one API request on
                 * this webhook call.
                 */
                $webhook_data = $this->webhook->data;

                // update request body and send (make the team).
                $this->webhook->data = $send;
                parent::post();
                $response = $this->request->send();

                // process response from team creation (using original data)
                $this->processResponse($response, $webhook_data);

                /*
                 * change webhook type, so processResponse() will capture the
                 * owners uid on the next call (in PostWebhooks#perform()).
                 */
                $this->webhook_type = 'user_adds_role';

                /*
                 * Use data returned from creating the team, to attach the owner.
                 * (if test mode, account for extra json wrapper from requestbin)
                 */
                include 'config/config.php';
                if (isset($config['test_url'])) {
                    $this->domain .= '/INSERT_TEAM_ID/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters';
                } else {
                    $response_data = json_decode(substr($response->getMessage(), strpos($response->getMessage(), '{')), true);

                    $this->domain .= '/' . $response_data['team']['id'] . '/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters';
                }

                // add the owner to the team
                $send = array(
                    'roster' => array(
                        'first' => $data['member']['first_name'],
                        'last' => $data['member']['last_name'],
                        'non_player' => 1,
                        'is_manager' => 1,
                        'is_commissioner' => 0,
                        'is_owner' => 1,
                    ),
                );
                $this->webhook->data = $send;
                parent::post();

                /*
                 * Reset webhook data for processResponse call in
                 * PostWebhooks#perform(); this is able to happen because the
                 * request data was set in parent::post(), but the webhook data
                 * must be reset for the final evaluation in the last
                 * processResponse call (in PostWebhooks#perform()).
                 */
                $this->webhook->data = $webhook_data;
                break;
            case 'user_updates_group':
                /*
                 * INTERNAL BLOCKER:
                 *   Need partner mapping API
                 */
                $team = parent::readPartnerMap('group', $this->webhook->data['group']['uuid'], $this->webhook->data['group']['uuid']);
                $team = $team['external_resource_id'];
                $this->domain .= '/teams/' . $team;

                // team data to update
                $data = $this->webhook->data;
                $geographical = $this->getRegion($data['group']['timezone']);
                $send = array(
                    'team' => array(
                        'team_name' => $data['group']['name'],
                        'sport_id' => $this->getSport($data['group']['group_category']),
                        'timezone' => $geographical['timezone'],
                        'country' => $geographical['location'],
                        'zipcode' => $data['group']['postalcode'],
                        'logo_url' => $data['group']['logo'],
                        'cname' => $data['group']['url'],
                    ),
                );

                $this->webhook->data = $send;
                parent::put();
                break;
            case 'user_deletes_group':
                /*
                 * EXTERNAL BLOCKER:
                 *   NYI by TeamSnap.
                 */
                break;
            case 'user_adds_role':
                /*
                 * INTERNAL BLOCKER:
                 *   Need partner mapping API
                 */
                $method = $roster = '';
                $group = parent::readPartnerMap('user', $webhook_data['member']['uuid'], $webhook_data['member']['uuid']);
                if (isset($group['message'])) {
                    // resource was not found
                    $method = 'POST';
                } else {
                    $method = 'PUT';
                    $roster = $group['external_resource_id'];
                }

                $team = parent::readPartnerMap('group', $webhook_data['group']['uuid'], $webhook_data['group']['uuid']);
                if (isset($team['message'])) {
                    // resource was not found
                    $team = 'TEAM_WAS_NOT_FOUND';
                } else {
                    $team = $team['external_resource_id'];
                }

                // build role data to send
                $data = $this->webhook->data;
                $send = array(
                    'roster' => array(
                        'first' => $data['member']['first_name'],
                        'last' => $data['member']['last_name'],
                        'non_player' => $data['member']['role_name'] == 'Player' ? 0 : 1,
                        'is_manager' => $data['member']['is_admin'] ? 1 : 0,
                    ),
                );
                $this->webhook->data = $send;

                if ($method == 'POST') {
                    /*
                     * The user does not exist in the TeamSnap system; Create
                     * the user from the given information.
                     */

                    $this->domain .= '/teams/' . $team . '/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters';

                    parent::post();
                } else {
                    /*
                     * The user does exist in the TeamSnap system; Update the
                     * existing user with the given information.
                     */

                    $this->domain .= '/teams/' . $team . '/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters/' . $roster;

                    parent::put();
                }

                break;
            case 'user_removes_role':
                /*
                 * INTERNAL BLOCKER:
                 *   Need partner mapping API
                 */
                $roster = '';
                $group = parent::readPartnerMap('user', $webhook_data['member']['uuid'], $webhook_data['member']['uuid']);
                if (isset($group['message'])) {
                    // resource was not found
                    $roster = 'ROSTER_WAS_NOT_FOUND';
                } else {
                    $roster = $group['external_resource_id'];
                }

                $team = parent::readPartnerMap('group', $webhook_data['group']['uuid'], $webhook_data['group']['uuid']);
                if (isset($team['message'])) {
                    // resource was not found
                    $team = 'TEAM_WAS_NOT_FOUND';
                } else {
                    $team = $team['external_resource_id'];
                }

                $this->domain .= '/teams/' . $team . '/as_roster/' .
                    $this->webhook->subscriber['commissioner_id'] . '/rosters/' . $roster;

                // put data to send
                $data = $this->webhook->data;
                $send = array(
                    'roster' => array(
                        'non_player' => $data['member']['role_name'] == 'Player' ? 1 : 0,
                        'is_manager' => $data['member']['is_admin'] ? 1 : 0,
                    ),
                );

                $this->webhook->data = $send;
                parent::put();
                break;
            case 'user_adds_submission':
                /*
                 * INTERNAL BLOCKER:
                 *   Need partner mapping API
                 */
                $method = $roster = '';
                $group = parent::readPartnerMap('user', $webhook_data['member']['uuid'], $webhook_data['member']['uuid']);
                if (isset($group['message'])) {
                    // resource was not found
                    $method = 'POST';
                } else {
                    $method = 'PUT';
                    $roster = $group['external_resource_id'];
                }

                $team = parent::readPartnerMap('group', $webhook_data['group']['uuid'], $webhook_data['group']['uuid']);
                if (isset($team['message'])) {
                    // resource was not found
                    $team = 'TEAM_WAS_NOT_FOUND';
                } else {
                    $team = $team['external_resource_id'];
                }

                $data = $this->webhook->data['webform_submission']['data'];
                //$partner_response = parent::readPartnerMap($item_type, $data['member']['uuid']);

                /**
                 * Check partner mapping db to determine if user exists; if not,
                 * send post to create and add submission information, else
                 * update existing user information.
                 *
                 * if(user does not exist)
                 *   method = POST
                 * else
                 *   method = PUT
                 */
                //$test_method = 'PUT';
                $send = array();

                /**
                 * Gathers all available data to send to TeamSnap. If required
                 * elements are not present in the submission, and the user does
                 * not previously exist, this will default to using account
                 * information.
                 */
                if (isset($data['profile__field_firstname__profile'])) {
                    $send['first'] = $data['profile__field_firstname__profile']['value'];
                } elseif ($method == 'POST' && !isset($data['profile__field_firstname__profile'])) {
                    /*
                     * Element required for roster creation, but not present in
                     * user submission; use information from the users account.
                     */
                    $send['first'] = $this->webhook->data['member']['first_name'];
                }
                if (isset($data['profile__field_lastname__profile'])) {
                    $send['last'] = $data['profile__field_lastname__profile']['value'];
                } elseif ($method == 'POST' && !isset($data['profile__field_lastname__profile'])) {
                    /*
                     * Element required for roster creation, but not present in
                     * user submission; use information from the users account.
                     */
                    $send['last'] = $this->webhook->data['member']['last_name'];
                }
                if (isset($data['profile__field_email__profile'])) {
                    $send['roster_email_addresses_attributes'][] = array(
                        'label' => 'Profile',
                        'email' => $data['profile__field_email__profile']['value'],
                    );
                }
                if (isset($data['profile__field_birth_date__profile'])) {
                    $send['birthdate'] = $data['profile__field_birth_date__profile']['value'];
                }
                if (isset($data['profile__field_user_gender__profile'])) {
                    $send['gender'] = $data['profile__field_user_gender__profile']['value'] == 1 ? 'Male' : 'Female';
                }

                // add roster phone numbers if any were set
                $roster_telephones_attributes = array();
                if (isset($data['profile__field_phone__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Home',
                        'phone_number' => $data['profile__field_phone__profile']['value'],
                    );
                }
                if (isset($data['profile__field_phone_cell__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Cell',
                        'phone_number' => $data['profile__field_phone_cell__profile']['value'],
                    );
                }
                if (isset($data['profile__field_work_number__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Work',
                        'phone_number' => $data['profile__field_work_number__profile']['value'],
                    );
                }
                if (count($roster_telephones_attributes) > 0) {
                    $send['roster_telephones_attributes'] = $roster_telephones_attributes;
                }

                // add address fields if they were set
                if (isset($data['profile__field_home_address_street__profile'])) {
                    $send['address'] = $data['profile__field_home_address_street__profile']['value'];
                }
                if (isset($data['profile__field_home_address_additional__profile'])) {
                    $send['address2'] = $data['profile__field_home_address_additional__profile']['value'];
                }
                if (isset($data['profile__field_home_address_city__profile'])) {
                    $send['city'] = $data['profile__field_home_address_city__profile']['value'];
                }
                if (isset($data['profile__field_home_address_province__profile'])) {
                    $send['state'] = $data['profile__field_home_address_province__profile']['value'];
                }
                if (isset($data['profile__field_home_address_postal_code__profile'])) {
                    $send['zip'] = $data['profile__field_home_address_postal_code__profile']['value'];
                }
                if (isset($data['profile__field_home_address_country__profile'])) {
                    $send['country'] = $data['profile__field_home_address_country__profile']['value'];
                }

                $this->webhook->data = array('roster' => $send);

                if ($method == 'POST') {
                    /*
                     * The user does not exist in the TeamSnap system; Create
                     * the user from the given information.
                     */

                    $this->domain .= '/teams/' . $team . '/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters';

                    parent::post();
                } else {
                    /*
                     * The user does exist in the TeamSnap system; Update the
                     * existing user with the given information.
                     */

                    $this->domain .= '/teams/' . $team . '/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters/' . $roster;

                    parent::put();
                }
                break;
        }
    }

    /**
     * Process the webhook data returned from sending the webhook; The function
     * should relate a piece of AllPlayers data to a piece of TeamSnap data;
     * This information relationship will be made via the AllPlayers Public
     * PHP API.
     *
     * @param $response_data
     *   Response from the webhook being processed/called.
     * @param array $webhook_data
     *   The webhook data used for the response associations.
     */
    public function processResponse($response_data, array $webhook_data)
    {
        // if test mode, account for extra json wrapper from requestbin
        include 'config/config.php';
        if (isset($config['test_url'])) {
            $response_data = json_decode(json_decode(substr($response_data->getMessage(), strpos($response_data->getMessage(), '{')))->body, true);
        } else {
            $response_data = json_decode(substr($response_data->getMessage(), strpos($response_data->getMessage(), '{')), true);
        }

        echo "dbg:\n", print_r($response_data, true), "\n\n", print_r($webhook_data, true);

        switch ($this->webhook_type) {
            case 'user_creates_group':
                // associate AllPlayers team uid with TeamSnap team id
                parent::createPartnerMap($response_data['team']['id'], 'group', $webhook_data['group']['uuid'], $webhook_data['group']['uuid']);
                break;
            case 'user_deletes_group':
                // need to add management for connections to all child groups
                parent::deletePartnerMap('group', $webhook_data['group']['uuid'], $webhook_data['group']['uuid']);
                break;
            case 'user_adds_role':
                // associate AllPlayers user uid with TeamSnap roster id
                $response = parent::readPartnerMap('user', $webhook_data['member']['uuid']);
                // associate AllPlayers user uid with TeamSnap roster id

                if (isset($response['message'])) {
                    // failed to find a row; create new partner mapping
                    parent::createPartnerMap($response_data['roster']['id'], 'user', $webhook_data['member']['uuid'], $webhook_data['member']['uuid']);
                }
                break;
            case 'user_adds_submission':
                // associate AllPlayers user uid with TeamSnap roster id
                $response = parent::readPartnerMap('user', $webhook_data['member']['uuid'], $webhook_data['member']['uuid']);

                if (isset($response['message'])) {
                    // failed to find a row; create new partner mapping
                    parent::createPartnerMap($response_data['roster']['id'], 'user', $webhook_data['member']['uuid'], $webhook_data['member']['uuid']);
                }
                break;
        }
    }

    /**
     * Select the TeamSnap Sport id corresponding to an item from the list of
     * currently supported Sports.
     *
     * @param array $data
     *   Array of the Group Category selected in the group creation process on AllPlayers.
     *
     * @return integer
     *   The sport id corresponding to the available sports in the TeamSnap API.
     */
    public function getSport($data)
    {
        $id = null;

        // if the sport group was not selected, default to non-sport group.
        switch ($data) {
            case 'Archery':
                $id = 59;
                break;
            case 'Australian Football':
                $id = 26;
                break;
            case 'Badminton':
                $id = 27;
                break;
            case 'Bandy':
                $id = 28;
                break;
            case 'Baseball':
                $id = 5;
                break;
            case 'Basketball':
                $id = 1;
                break;
            case 'Bocce':
                $id = 29;
                break;
            case 'Bowling':
                $id = 13;
                break;
            case 'Broomball':
                $id = 30;
                break;
            case 'Cheerleading':
                $id = 31;
                break;
            case 'Chess':
                $id = 32;
                break;
            case 'Cow Tipping':
                $id = 54;
                break;
            case 'Cricket':
                $id = 8;
                break;
            case 'Croquet':
                $id = 33;
                break;
            case 'Curling':
                $id = 34;
                break;
            case 'Cycling':
                $id = 35;
                break;
            case 'Dodgeball':
                $id = 14;
                break;
            case 'Dragon Boat':
                $id = 25;
                break;
            case 'Fencing':
                $id = 36;
                break;
            case 'Field Hockey':
                $id = 15;
                break;
            case 'Floor Hockey':
                $id = 60;
                break;
            case 'Floorball':
                $id = 44;
                break;
            case 'Foosball':
                $id = 37;
                break;
            case 'Football':
                $id = 7;
                break;
            case 'Golf':
                $id = 46;
                break;
            case 'Gymnastics-Men':
                $id = 56;
                break;
            case 'Gymnastics-Women':
                $id = 57;
                break;
            case 'Hurling':
                $id = 38;
                break;
            case 'Ice Hockey':
                $id = 16;
                break;
            case 'Indoor Soccer':
                $id = 39;
                break;
            case 'Inline Hockey':
                $id = 17;
                break;
            case 'Ki-O-Rahi':
                $id = 50;
                break;
            case 'Kickball':
                $id = 18;
                break;
            case 'Lacrosse':
                $id = 10;
                break;
            case 'Netball':
                $id = 40;
                break;
            case 'Non-Sport Group':
                $id = 52;
                break;
            case 'Other Sport':
                $id = 24;
                break;
            case 'Outrigger':
                $id = 53;
                break;
            case 'Paintball':
                $id = 19;
                break;
            case 'Petanque':
                $id = 45;
                break;
            case 'Polo':
                $id = 20;
                break;
            case 'Racquetball':
                $id = 55;
                break;
            case 'Ringette':
                $id = 51;
                break;
            case 'Roller Derby':
                $id = 48;
                break;
            case 'Rowing':
                $id = 21;
                break;
            case 'Rugby':
                $id = 9;
                break;
            case 'Running':
                $id = 41;
                break;
            case 'Sailing':
                $id = 47;
                break;
            case 'Slo-pitch':
                $id = 61;
                break;
            case 'Soccer':
                $id = 2;
                break;
            case 'Softball':
                $id = 4;
                break;
            case 'Street Hockey':
                $id = 62;
                break;
            case 'Swimming':
                $id = 42;
                break;
            case 'Tennis':
                $id = 43;
                break;
            case 'Track And Field':
                $id = 58;
                break;
            case 'Ultimate':
                $id = 22;
                break;
            case 'Volleyball':
                $id = 6;
                break;
            case 'Water Polo':
                $id = 23;
                break;
            case 'Wiffleball':
                $id = 11;
                break;
            case 'Wrestling':
                $id = 49;
                break;
            default:
                $id = 52; // Non-Sport Group
                break;
        }

        return $id;
    }

    /**
     * Translate UTC/GMT timezone offset to TeamSnap supported Timezones and
     * their corresponding estimated locations.
     *
     * @param integer $offset
     *   The timezone offset from UTC/GMT.
     *
     * @return array
     *   [timezone] = TeamSnap Timezone
     *   [location] = Estimated Geographical Location
     */
    public function getRegion($offset)
    {
        $timezone = '';
        $location = '';

        switch ($offset) {
            case '13:00':
                $timezone = 'Samoa';
                $location = 'Samoa';
                break;
            case '12:00':
                $timezone = 'Auckland';
                $location = 'New Zealand';
                break;
            case '11:00':
                $timezone = 'Vladivostok';
                $location = 'Russia';
                break;
            case '10:00':
                $timezone = 'Sydney';
                $location = 'Australia';
                break;
            case '9:30':
                $timezone = 'Adelaide';
                $location = 'Australia';
                break;
            case '9:00':
                $timezone = 'Osaka';
                $location = 'Japan';
                break;
            case '8:00':
                $timezone = 'Chongqing';
                $location = 'China';
                break;
            case '7:00':
                $timezone = 'Jakarta';
                $location = 'Indonesia';
                break;
            case '6:30':
                $timezone = 'Rangoon';
                $location = 'Myanmar';
                break;
            case '6:00':
                $timezone = 'Dhaka';
                $location = 'Bangladesh';
                break;
            case '5:45':
                $timezone = 'Kathmandu';
                $location = 'Nepal';
                break;
            case '5:30':
                $timezone = 'Mumbai';
                $location = 'India';
                break;
            case '5:00':
                $timezone = 'Karachi';
                $location = 'Pakistan';
                break;
            case '4:30':
                $timezone = 'Kabul';
                $location = 'Afghanistan';
                break;
            case '4:00':
                $timezone = 'Moscow';
                $location = 'Russia';
                break;
            case '3:30':
                $timezone = 'Tehran';
                $location = 'Iran';
                break;
            case '3:00':
                $timezone = 'Riyadh';
                $location = 'Saudi Arabia';
                break;
            case '2:00':
                $timezone = 'Athens';
                $location = 'Greece';
                break;
            case '1:00':
                $timezone = 'Berlin';
                $location = 'Germany';
                break;
            case '-1:00':
                $timezone = 'Cape Verde Is.';
                $location = 'Republic of Cabo Verde';
                break;
            case '-2:00':
                $timezone = 'Mid-Atlantic';
                $location = 'United Kingdom';
                break;
            case '-3:00':
                $timezone = 'Brasilia';
                $location = 'Brazil';
                break;
            case '-3:30':
                $timezone = 'Newfoundland';
                $location = 'Canada';
                break;
            case '-4:00':
                $timezone = 'Atlantic Time (Canada)';
                $location = 'Canada';
                break;
            case '-4:00':
                $timezone = 'Caracas';
                $location = 'Venezuela';
                break;
            case '-5:00':
                $timezone = 'Eastern Time (US & Canada)';
                $location = 'United States';
                break;
            case '-6:00':
                $timezone = 'Central Time (US & Canada)';
                $location = 'United States';
                break;
            case '-7:00':
                $timezone = 'Mountain Time (US & Canada)';
                $location = 'United States';
                break;
            case '-8:00':
                $timezone = 'Pacific Time (US & Canada)';
                $location = 'United States';
                break;
            case '-9:00':
                $timezone = 'Alaska';
                $location = 'United States';
                break;
            case '-10:00':
                $timezone = 'Hawaii';
                $location = 'United States';
                break;
            case '-11:00':
                $timezone = 'American Samoa';
                $location = 'United States';
                break;
            default:
                $timezone = 'UTC';
                $location = 'United Kingdom';
                break;
        }

        return array('timezone' => $timezone, 'location' => $location);
    }

}
