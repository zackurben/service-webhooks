<?php

/**
 * @file Teamsnap.php
 *
 * Provides the TeamSnap Webhooks plugin definition. The TeanSnap Webhook sends
 * data to various API Endpoints, using a custom token based authentication.
 */

namespace AllPlayers\Webhooks;

use Guzzle\Http\Message\Response;

/**
 * Base TeamSnap Webhook definition.
 */
class Teamsnap extends Webhook implements ProcessInterface
{
    /**
     * The list of supported Sports from TeamSnap and their ID numbers.
     *
     * @var array $sports
     */
    public static $sports = array(
        'Archery' => 59,
        'Australian Football' => 26,
        'Badminton' => 27,
        'Bandy' => 28,
        'Baseball' => 5,
        'Basketball' => 1,
        'Bocce' => 29,
        'Bowling' => 13,
        'Broomball' => 30,
        'Cheerleading' => 31,
        'Chess' => 32,
        'Cow Tipping' => 54,
        'Cricket' => 8,
        'Croquet' => 33,
        'Curling' => 34,
        'Cycling' => 35,
        'Dodgeball' => 14,
        'Dragon Boat' => 25,
        'Fencing' => 36,
        'Field Hockey' => 15,
        'Floor Hockey' => 60,
        'Floorball' => 44,
        'Foosball' => 37,
        'Football' => 7,
        'Golf' => 46,
        'Gymnastics-Men' => 56,
        'Gymnastics-Women' => 57,
        'Hurling' => 38,
        'Ice Hockey' => 16,
        'Indoor Soccer' => 39,
        'Inline Hockey' => 17,
        'Ki-O-Rahi' => 50,
        'Kickball' => 18,
        'Lacrosse' => 10,
        'Netball' => 40,
        'Non-Sport Group' => 52,
        'Other Sport' => 24,
        'Outrigger' => 53,
        'Paintball' => 19,
        'Petanque' => 45,
        'Polo' => 20,
        'Racquetball' => 55,
        'Ringette' => 51,
        'Roller Derby' => 48,
        'Rowing' => 21,
        'Rugby' => 9,
        'Running' => 41,
        'Sailing' => 47,
        'Slo-pitch' => 61,
        'Soccer' => 2,
        'Softball' => 4,
        'Street Hockey' => 62,
        'Swimming' => 42,
        'Tennis' => 43,
        'Track And Field' => 58,
        'Ultimate' => 22,
        'Volleyball' => 6,
        'Water Polo' => 23,
        'Wiffleball' => 11,
        'Wrestling' => 49,
    );

    /**
     * The list of TeamSnap supported locations and timezones.
     *
     * @var array $regions
     */
    public static $regions = array(
        '13:00' => array(
            'timezone' => 'Samoa',
            'location' => 'Samoa',
        ),
        '12:00' => array(
            'timezone' => 'Auckland',
            'location' => 'New Zealand',
        ),
        '11:00' => array(
            'timezone' => 'Vladivostok',
            'location' => 'Russia',
        ),
        '10:00' => array(
            'timezone' => 'Sydney',
            'location' => 'Australia',
        ),
        '9:30' => array(
            'timezone' => 'Adelaide',
            'location' => 'Australia',
        ),
        '9:00' => array(
            'timezone' => 'Osaka',
            'location' => 'Japan',
        ),
        '8:00' => array(
            'timezone' => 'Chongqing',
            'location' => 'China',
        ),
        '7:00' => array(
            'timezone' => 'Jakarta',
            'location' => 'Indonesia',
        ),
        '6:30' => array(
            'timezone' => 'Rangoon',
            'location' => 'Myanmar',
        ),
        '6:00' => array(
            'timezone' => 'Dhaka',
            'location' => 'Bangladesh',
        ),
        '5:45' => array(
            'timezone' => 'Kathmandu',
            'location' => 'Nepal',
        ),
        '5:30' => array(
            'timezone' => 'Mumbai',
            'location' => 'India',
        ),
        '5:00' => array(
            'timezone' => 'Karachi',
            'location' => 'Pakistan',
        ),
        '4:30' => array(
            'timezone' => 'Kabul',
            'location' => 'Afghanistan',
        ),
        '4:00' => array(
            'timezone' => 'Moscow',
            'location' => 'Russia',
        ),
        '3:30' => array(
            'timezone' => 'Tehran',
            'location' => 'Iran',
        ),
        '3:00' => array(
            'timezone' => 'Riyadh',
            'location' => 'Saudi Arabia',
        ),
        '2:00' => array(
            'timezone' => 'Athens',
            'location' => 'Greece',
        ),
        '1:00' => array(
            'timezone' => 'Berlin',
            'location' => 'Germany',
        ),
        '-1:00' => array(
            'timezone' => 'Cape Verde Is.',
            'location' => 'Republic of Cabo Verde',
        ),
        '-2:00' => array(
            'timezone' => 'Mid-Atlantic',
            'location' => 'United Kingdom',
        ),
        '-3:00' => array(
            'timezone' => 'Brasilia',
            'location' => 'Brazil',
        ),
        '-3:30' => array(
            'timezone' => 'Newfoundland',
            'location' => 'Canada',
        ),
        '-4:00' => array(
            'timezone' => 'Atlantic Time (Canada)',
            'location' => 'Canada',
        ),
        '-5:00' => array(
            'timezone' => 'Eastern Time (US & Canada)',
            'location' => 'United States',
        ),
        '-6:00' => array(
            'timezone' => 'Central Time (US & Canada)',
            'location' => 'United States',
        ),
        '-7:00' => array(
            'timezone' => 'Mountain Time (US & Canada)',
            'location' => 'United States',
        ),
        '-8:00' => array(
            'timezone' => 'Pacific Time (US & Canada)',
            'location' => 'United States',
        ),
        '-9:00' => array(
            'timezone' => 'Alaska',
            'location' => 'United States',
        ),
        '-10:00' => array(
            'timezone' => 'Hawaii',
            'location' => 'United States',
        ),
        '-11:00' => array(
            'timezone' => 'American Samoa',
            'location' => 'United States',
        ),
    );

    /**
     * The URL to post the webhook.
     *
     * @var string
     */
    protected $domain = 'https://api.teamsnap.com/v2';

    /**
     * The method used for Client authentication.
     *
     * @see AUTHENTICATION_NONE
     * @see AUTHENTICATION_BASIC
     * @see AUTHENTICATION_OAUTH
     *
     * @var integer
     */
    protected $authentication = self::AUTHENTICATION_NONE;

    /**
     * The method of data transmission.
     *
     * This establishes the method of transmission between the AllPlayers
     * webhook and the third-party webhook.
     *
     * @see TRANSMISSION_URLENCODED
     * @see TRANSMISSION_JSON
     *
     * @var string
     */
    protected $method = self::TRANSMISSION_JSON;

    /**
     * Create Teamsnap webhook using no_authentication.
     */
    public function __construct(array $subscriber = array(), array $data = array(), array $preprocess = array())
    {
        include 'config/config.php';
        if (isset($config['teamsnap'])) {
            parent::__construct(
                array(
                    'token' => $config['teamsnap']['token'],
                    'commissioner_id' => $config['teamsnap']['commissioner_id'],
                    'division_id' => $config['teamsnap']['division_id'],
                ),
                $data,
                $preprocess
            );

            $this->headers['X-Teamsnap-Token'] = $this->webhook->subscriber['token'];
            $this->process();
        }
    }

    /**
     * Process the webhook data and set the domain to the appropriate URL.
     *
     * @todo Fix case blockers.
     */
    protected function process()
    {
        $data = $this->getData();

        // set original data hook data
        $this->setOriginalData($data);

        switch ($data['webhook_type']) {
            case self::WEBHOOK_CREATE_GROUP:
                /*
                 * Cancel the webhook if this is not a team being registered
                 */
                if($data['group']['group_type'] != 'Team') {
                    $this->setSend(self::WEBHOOK_CANCEL);
                    break;
                }

                /*
                 * Note: this is a different approach for user_creates_group,
                 * because we need to send multiple calls to the TeamSnap
                 * API, since they do not allow for a roster to be added to a
                 * Team at creation time (we care about this to preserve the
                 * unique property of the group creator).
                 *
                 * This will first make a Team in the TeamSnap system, add our
                 * custom data field for uuids, use the response data to add a
                 * creator to the Team, and send them an invite to finish their
                 * account on the TeamSnap system.
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
                $response = $this->send();

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
                        'roster_email_addresses_attributes' => array(
                            array(
                                'label' => 'Profile',
                                'email' => $data['member']['email'],
                            ),
                        ),
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
                 * Update webhook type, so processResponse() will capture the
                 * owners uid on the next call (in PostWebhooks#perform()).
                 */
                $temp = $this->getOriginalData();
                $temp['webhook_type'] = self::WEBHOOK_ADD_ROLE;
                $this->setOriginalData($temp);
                break;
            case self::WEBHOOK_UPDATE_GROUP:
                $team = parent::readPartnerMap(self::PARTNER_MAP_GROUP, $data['group']['uuid'], $data['group']['uuid']);
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
                    ),
                );

                $this->setData($send);
                parent::put();
                break;
            case self::WEBHOOK_DELETE_GROUP:
                /*
                 * EXTERNAL BLOCKER:
                 *   NYI by TeamSnap.
                 */
                break;
            case self::WEBHOOK_ADD_ROLE:
                $method = $roster = '';
                $group = parent::readPartnerMap(
                    self::PARTNER_MAP_USER,
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );

                if (isset($group['message'])) {
                    // resource was not found
                    $method = self::HTTP_POST;
                } elseif (isset($group['external_resource_id'])) {
                    $method = self::HTTP_PUT;
                    $roster = $group['external_resource_id'];
                }

                $team = parent::readPartnerMap(self::PARTNER_MAP_GROUP, $data['group']['uuid'], $data['group']['uuid']);
                if (isset($team['message'])) {
                    // resource was not found
                    $team = 'TEAM_WAS_NOT_FOUND';
                } elseif (isset($team['external_resource_id'])) {
                    $team = $team['external_resource_id'];
                }

                // role data to send
                $send = array(
                    'roster' => array(
                        'non_player' => $data['member']['role_name'] == 'Player' ? 0 : 1,
                        'is_manager' => $data['member']['is_admin'] ? 1 : 0,
                    ),
                );
                $this->setData($send);

                if ($method == self::HTTP_POST) {
                    /*
                     * The user does not exist in the TeamSnap system; Create
                     * the user from the profile information.
                     */

                    // add first/last name required for user creation
                    $send['roster']['first'] = $data['member']['first_name'];
                    $send['roster']['last'] = $data['member']['last_name'];

                    // add email address for TeamSnap invite
                    $send['roster']['roster_email_addresses_attributes'][] = array(
                        'label' => 'Profile',
                        'email' => $data['member']['email'],
                    );

                    $this->setData($send);
                    $this->domain .= '/teams/' . $team . '/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters';

                    parent::post();
                } else {
                    /*
                     * The user does exist in the TeamSnap system; Update the
                     * existing user with the given information.
                     */
                    $this->setData($send);
                    $this->domain .= '/teams/' . $team . '/as_roster/' .
                        $this->webhook->subscriber['commissioner_id'] . '/rosters/' . $roster;

                    parent::put();
                }

                break;
            case self::WEBHOOK_REMOVE_ROLE:
                $roster = '';
                $group = parent::readPartnerMap(
                    self::PARTNER_MAP_USER,
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );

                if (isset($group['message'])) {
                    // resource was not found
                    $roster = 'ROSTER_WAS_NOT_FOUND';
                } elseif (isset($group['external_resource_id'])) {
                    $roster = $group['external_resource_id'];
                }

                $team = parent::readPartnerMap(self::PARTNER_MAP_GROUP, $data['group']['uuid'], $data['group']['uuid']);
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
            case self::WEBHOOK_ADD_SUBMISSION:
                $method = $roster = '';
                $group = parent::readPartnerMap(
                    self::PARTNER_MAP_USER,
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );

                if (isset($group['message'])) {
                    // resource was not found
                    $method = self::HTTP_POST;
                } elseif (isset($group['external_resource_id'])) {
                    $method = self::HTTP_PUT;
                    $roster = $group['external_resource_id'];
                }

                $team = parent::readPartnerMap(self::PARTNER_MAP_GROUP, $data['group']['uuid'], $data['group']['uuid']);
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
                $webform = $data['webform']['data'];
                if (isset($webform['profile__field_firstname__profile'])) {
                    $send['first'] = $webform['profile__field_firstname__profile'];
                } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_firstname__profile'])) {
                    /*
                     * Element required for roster creation, but not present in
                     * user submission; use information from the users account.
                     */

                    $send['first'] = $data['member']['first_name'];
                }
                if (isset($webform['profile__field_lastname__profile'])) {
                    $send['last'] = $webform['profile__field_lastname__profile'];
                } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_lastname__profile'])) {
                    /*
                     * Element required for roster creation, but not present in
                     * user submission; use information from the users account.
                     */

                    $send['last'] = $data['member']['last_name'];
                }
                if (isset($webform['profile__field_email__profile'])) {
                    $send['roster_email_addresses_attributes'][] = array(
                        'label' => 'Webform',
                        'email' => $webform['profile__field_email__profile'],
                    );
                } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_email__profile'])) {
                    /*
                     * Element required for roster invitation, but not present in
                     * user submission; use information from the users account.
                     */

                    $send['roster_email_addresses_attributes'][] = array(
                        'label' => 'Profile',
                        'email' => $data['member']['email'],
                    );
                }
                if (isset($webform['profile__field_birth_date__profile'])) {
                    $send['birthdate'] = $webform['profile__field_birth_date__profile'];
                }
                if (isset($webform['profile__field_user_gender__profile'])) {
                    $send['gender'] = $webform['profile__field_user_gender__profile'] == 1 ? 'Male' : 'Female';
                }

                // add roster phone numbers if any were set
                $roster_telephones_attributes = array();
                if (isset($webform['profile__field_phone__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Home',
                        'phone_number' => $webform['profile__field_phone__profile'],
                    );
                }
                if (isset($webform['profile__field_phone_cell__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Cell',
                        'phone_number' => $webform['profile__field_phone_cell__profile'],
                    );
                }
                if (isset($webform['profile__field_work_number__profile'])) {
                    $roster_telephones_attributes[] = array(
                        'label' => 'Work',
                        'phone_number' => $webform['profile__field_work_number__profile'],
                    );
                }
                if (count($roster_telephones_attributes) > 0) {
                    $send['roster_telephones_attributes'] = $roster_telephones_attributes;
                }

                // add address fields if they were set
                if (isset($webform['profile__field_home_address_street__profile'])) {
                    $send['address'] = $webform['profile__field_home_address_street__profile'];
                }
                if (isset($webform['profile__field_home_address_additional__profile'])) {
                    $send['address2'] = $webform['profile__field_home_address_additional__profile'];
                }
                if (isset($webform['profile__field_home_address_city__profile'])) {
                    $send['city'] = $webform['profile__field_home_address_city__profile'];
                }
                if (isset($webform['profile__field_home_address_province__profile'])) {
                    $send['state'] = $webform['profile__field_home_address_province__profile'];
                }
                if (isset($webform['profile__field_home_address_postal_code__profile'])) {
                    $send['zip'] = $webform['profile__field_home_address_postal_code__profile'];
                }
                if (isset($webform['profile__field_home_address_country__profile'])) {
                    $send['country'] = $webform['profile__field_home_address_country__profile'];
                }

                $this->setData(array('roster' => $send));

                if ($method == self::HTTP_POST) {
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
     * Process the webhook data returned from sending the webhook.
     *
     * This function should relate a piece of AllPlayers data to a piece of
     * third-party data; This information relationship will be made via the
     * AllPlayers Public PHP API.
     *
     * @param \Guzzle\Http\Message\Response $response
     *   The Guzzle Response from the webhook being processed/called.
     */
    public function processResponse(Response $response)
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
            case self::WEBHOOK_CREATE_GROUP:
                // associate AllPlayers team uid with TeamSnap team id
                parent::createPartnerMap(
                    $response['team']['id'],
                    self::PARTNER_MAP_GROUP,
                    $original_data['group']['uuid'],
                    $original_data['group']['uuid']
                );
                break;
            case self::WEBHOOK_DELETE_GROUP:
                // need to add management for connections to all child groups
                parent::deletePartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $original_data['group']['uuid'],
                    $original_data['group']['uuid']
                );
                break;
            case self::WEBHOOK_ADD_ROLE:
                // associate AllPlayers user uid with TeamSnap roster id
                $query = parent::readPartnerMap(
                    self::PARTNER_MAP_USER,
                    $original_data['member']['uuid'],
                    $original_data['group']['uuid']
                );

                if (isset($query['message'])) {
                    // failed to find a row; create new partner mapping
                    parent::createPartnerMap(
                        $response['roster']['id'],
                        self::PARTNER_MAP_USER,
                        $original_data['member']['uuid'],
                        $original_data['group']['uuid']
                    );

                    // invite the user to complete their TeamSnap account
                    $query = parent::readPartnerMap(
                        self::PARTNER_MAP_GROUP,
                        $original_data['group']['uuid'],
                        $original_data['group']['uuid']
                    );
                    $this->domain = 'https://api.teamsnap.com/v2/teams/' . $query['external_resource_id'] .
                        '/as_roster/' . $this->webhook->subscriber['commissioner_id']. '/invitations';
                    $send = array(
                        'rosters' => array(
                            $response['roster']['id'],
                        )
                    );

                    $this->setData($send);
                    parent::post();
                    $response = $this->send();
                }
                break;
            case self::WEBHOOK_ADD_SUBMISSION:
                // associate AllPlayers user uid with TeamSnap roster id
                $query = parent::readPartnerMap(
                    self::PARTNER_MAP_USER,
                    $original_data['member']['uuid'],
                    $original_data['group']['uuid']
                );

                if (isset($query['message'])) {
                    // failed to find a row; create new partner mapping
                    parent::createPartnerMap(
                        $response['roster']['id'],
                        self::PARTNER_MAP_USER,
                        $original_data['member']['uuid'],
                        $original_data['group']['uuid']
                    );
                }
                break;
        }
    }

    /**
     * Get the TeamSnap Sport from the list of supported Sports.
     *
     * @param string $data
     *   Name of the sport selected on AllPlayers.
     *
     * @return integer
     *   The sport id corresponding to the available sports in the TeamSnap API.
     */
    public function getSport($data)
    {
        // if sport is set in supported list, return its value
        if (isset(self::$sports[$data])) {
            return self::$sports[$data];
        } else {
            // return Non-Sport Group
            return 52;
        }
    }

    /**
     * Get the TeamSnap supported location and timezone.
     *
     * @param string $offset
     *   The timezone offset from UTC/GMT.
     *
     * @return array
     *   An associative keyed array with the location and timezone information.
     */
    public function getRegion($offset)
    {
        // if region is in supported list, return its value.
        if (isset(self::$regions[$offset])) {
            return self::$regions[$offset];
        } else {
            // return default UTC region
            return array(
                'timezone' => 'UTC',
                'location' => 'United Kingdom',
            );
        }
    }
}
