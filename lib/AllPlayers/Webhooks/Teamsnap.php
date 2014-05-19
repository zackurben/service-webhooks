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
class Teamsnap extends Webhook implements ProcessInterface
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
        $data = $this->getData();

        // set original data hook data
        $this->setOriginalData($data);

        switch ($data['webhook_type']) {
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

                // update request body and make the team
                $this->setData($send);
                parent::post();
                $response = $this->request->send();

                // process response from team creation (using original data)
                $this->processResponse($response, $data);

                /*
                 * Use data returned from creating the team, to attach the owner.
                 * (if test mode, account for extra json wrapper from requestbin)
                 */
                include 'config/config.php';
                if (isset($config['test_url'])) {
                    $this->domain .= '/INSERT_TEAM_ID/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters';
                } else {
                    $response = $this->processJsonResponse($response);

                    $this->domain .= '/' . $response['team']['id'] . '/as_roster/' .
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

                // update request body and allow PostWebhooks to complete the call
                $this->setData($send);
                parent::post();

                /*
                 * change webhook type, so processResponse() will capture the
                 * owners uid on the next call (in PostWebhooks#perform()).
                 */
                $temp = $this->getOriginalData();
                $temp['webhook_type'] = 'user_adds_role';
                $this->setOriginalData($temp);
                break;
            case 'user_updates_group':
                $team = parent::readPartnerMap('group', $data['group']['uuid'], $data['group']['uuid']);
                $team = $team['external_resource_id'];
                $this->domain .= '/teams/' . $team;

                // team data to send
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

                $this->setData($send);
                parent::put();
                break;
            case 'user_deletes_group':
                /*
                 * EXTERNAL BLOCKER:
                 *   NYI by TeamSnap.
                 */
                break;
            case 'user_adds_role':
                $method = $roster = '';
                $group = parent::readPartnerMap('user', $data['member']['uuid'], $data['group']['uuid']);
                if (isset($group['message'])) {
                    // resource was not found
                    $method = 'POST';
                } elseif (isset($group['external_resource_id'])) {
                    $method = 'PUT';
                    $roster = $group['external_resource_id'];
                }

                $team = parent::readPartnerMap('group', $data['group']['uuid'], $data['group']['uuid']);
                if (isset($team['message'])) {
                    // resource was not found
                    $team = 'TEAM_WAS_NOT_FOUND';
                } elseif (isset($team['external_resource_id'])) {
                    $team = $team['external_resource_id'];
                }

                // role data to send
                $send = array(
                    'roster' => array(
                        'first' => $data['member']['first_name'],
                        'last' => $data['member']['last_name'],
                        'non_player' => $data['member']['role_name'] == 'Player' ? 0 : 1,
                        'is_manager' => $data['member']['is_admin'] ? 1 : 0,
                    ),
                );
                $this->setData($send);

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
                $roster = '';
                $group = parent::readPartnerMap('user', $data['member']['uuid'], $data['group']['uuid']);
                if (isset($group['message'])) {
                    // resource was not found
                    $roster = 'ROSTER_WAS_NOT_FOUND';
                } elseif (isset($group['external_resource_id'])) {
                    $roster = $group['external_resource_id'];
                }

                $team = parent::readPartnerMap('group', $data['group']['uuid'], $data['group']['uuid']);
                if (isset($team['message'])) {
                    // resource was not found
                    $team = 'TEAM_WAS_NOT_FOUND';
                } elseif (isset($team['external_resource_id'])) {
                    $team = $team['external_resource_id'];
                }

                $this->domain .= '/teams/' . $team . '/as_roster/' .
                    $this->webhook->subscriber['commissioner_id'] . '/rosters/' . $roster;

                // roster data to send
                $send = array(
                    'roster' => array(
                        'non_player' => $data['member']['role_name'] == 'Player' ? 1 : 0,
                        'is_manager' => $data['member']['is_admin'] ? 1 : 0,
                    ),
                );

                $this->setData($send);
                parent::put();
                break;
            case 'user_adds_submission':
                $method = $roster = '';
                $group = parent::readPartnerMap('user', $data['member']['uuid'], $data['group']['uuid']);
                if (isset($group['message'])) {
                    // resource was not found
                    $method = 'POST';
                } elseif (isset($group['external_resource_id'])) {
                    $method = 'PUT';
                    $roster = $group['external_resource_id'];
                }

                $team = parent::readPartnerMap('group', $data['group']['uuid'], $data['group']['uuid']);
                if (isset($team['message'])) {
                    // resource was not found
                    $team = 'TEAM_WAS_NOT_FOUND';
                } elseif (isset($team['external_resource_id'])) {
                    $team = $team['external_resource_id'];
                }

                /**
                 * Gathers all available data to send to TeamSnap. If required
                 * elements are not present in the submission, and the user does
                 * not previously exist, this will default to using All Players
                 * account information.
                 */
                $send = array();
                $webform = $data['webform_submission']['data'];
                if (isset($webform['profile__field_firstname__profile'])) {
                    $send['first'] = $webform['profile__field_firstname__profile']['value'];
                } elseif ($method == 'POST' && !isset($webform['profile__field_firstname__profile'])) {
                    /*
                     * Element required for roster creation, but not present in
                     * user submission; use information from the users account.
                     */

                    $send['first'] = $data['member']['first_name'];
                }
                if (isset($webform['profile__field_lastname__profile'])) {
                    $send['last'] = $webform['profile__field_lastname__profile']['value'];
                } elseif ($method == 'POST' && !isset($webform['profile__field_lastname__profile'])) {
                    /*
                     * Element required for roster creation, but not present in
                     * user submission; use information from the users account.
                     */

                    $send['last'] = $data['member']['last_name'];
                }
                if (isset($webform['profile__field_email__profile'])) {
                    $send['roster_email_addresses_attributes'][] = array(
                        'label' => 'Profile',
                        'email' => $webform['profile__field_email__profile']['value'],
                    );
                }
                if (isset($webform['profile__field_birth_date__profile'])) {
                    $send['birthdate'] = $webform['profile__field_birth_date__profile']['value'];
                }
                if (isset($webform['profile__field_user_gender__profile'])) {
                    $send['gender'] = $webform['profile__field_user_gender__profile']['value'] == 1 ? 'Male' : 'Female';
                }

                // add roster phone numbers if any were set
                $roster_telephones_attributes = array();
                if (isset($webform['profile__field_phone__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Home',
                        'phone_number' => $webform['profile__field_phone__profile']['value'],
                    );
                }
                if (isset($webform['profile__field_phone_cell__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Cell',
                        'phone_number' => $webform['profile__field_phone_cell__profile']['value'],
                    );
                }
                if (isset($webform['profile__field_work_number__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Work',
                        'phone_number' => $webform['profile__field_work_number__profile']['value'],
                    );
                }
                if (count($roster_telephones_attributes) > 0) {
                    $send['roster_telephones_attributes'] = $roster_telephones_attributes;
                }

                // add address fields if they were set
                if (isset($webform['profile__field_home_address_street__profile'])) {
                    $send['address'] = $webform['profile__field_home_address_street__profile']['value'];
                }
                if (isset($webform['profile__field_home_address_additional__profile'])) {
                    $send['address2'] = $webform['profile__field_home_address_additional__profile']['value'];
                }
                if (isset($webform['profile__field_home_address_city__profile'])) {
                    $send['city'] = $webform['profile__field_home_address_city__profile']['value'];
                }
                if (isset($webform['profile__field_home_address_province__profile'])) {
                    $send['state'] = $webform['profile__field_home_address_province__profile']['value'];
                }
                if (isset($webform['profile__field_home_address_postal_code__profile'])) {
                    $send['zip'] = $webform['profile__field_home_address_postal_code__profile']['value'];
                }
                if (isset($webform['profile__field_home_address_country__profile'])) {
                    $send['country'] = $webform['profile__field_home_address_country__profile']['value'];
                }

                $this->setData(array('roster' => $send));

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
     * @param \Guzzle\Http\Message\Response $response
     *   Response from the webhook being processed/called.
     */
    public function processResponse(\Guzzle\Http\Message\Response $response)
    {
        // if test mode, account for extra json wrapper from requestbin
        include 'config/config.php';
        if (isset($config['test_url'])) {
            $response = json_decode($this->processJsonResponse($response)->body, true);
        } else {
            $response = $this->processJsonResponse($response);
        }

        // get original data sent from AllPlayers webhook
        $data = $this->getData();
        $original_data = $this->getOriginalData();
        switch ($original_data['webhook_type']) {
            case 'user_creates_group':
                // associate AllPlayers team uid with TeamSnap team id
                parent::createPartnerMap($response['team']['id'], 'group', $original_data['group']['uuid'], $original_data['group']['uuid']);
                break;
            case 'user_deletes_group':
                // need to add management for connections to all child groups
                parent::deletePartnerMap('group', $original_data['group']['uuid'], $original_data['group']['uuid']);
                break;
            case 'user_adds_role':
                // associate AllPlayers user uid with TeamSnap roster id
                $query = parent::readPartnerMap('user', $original_data['member']['uuid'], $original_data['group']['uuid']);

                if (isset($query['message'])) {
                    // failed to find a row; create new partner mapping
                    parent::createPartnerMap($response['roster']['id'], 'user', $original_data['member']['uuid'], $original_data['group']['uuid']);
                }
                break;
            case 'user_adds_submission':
                // associate AllPlayers user uid with TeamSnap roster id
                $query = parent::readPartnerMap('user', $original_data['member']['uuid'], $original_data['group']['uuid']);

                if (isset($query['message'])) {
                    // failed to find a row; create new partner mapping
                    parent::createPartnerMap($response['roster']['id'], 'user', $original_data['member']['uuid'], $original_data['group']['uuid']);
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
