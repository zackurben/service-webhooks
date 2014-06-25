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
    public function __construct(
        array $subscriber = array(),
        array $data = array(),
        array $preprocess = array()
    ) {
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
                if ($data['group']['group_type'] != 'Team') {
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
                    'team_name' => $data['group']['name'],
                    'team_league' => 'All Players',
                    'division_id' => intval($this->webhook->subscriber['division_id']),
                    'sport_id' => $this->getSport($data['group']['group_category']),
                    'timezone' => $geographical['timezone'],
                    'country' => $geographical['location'],
                    'zipcode' => $data['group']['postalcode'],
                );

                // add team logo if present
                if (isset($data['group']['logo'])) {
                    $send['logo_url'] = $data['group']['logo'];
                }

                // update request body and make the team
                $this->setData(array('team' => $send));
                parent::post();
                $response = $this->send();

                // process response from team creation (using original data)
                $this->processResponse($response);

                /*
                 * Use data returned from creating the team, to attach the owner.
                 * (if test mode, account for extra json wrapper from requestbin)
                 */
                include 'config/config.php';
                if (isset($config['test_url'])) {
                    // cancel sending the webhook for testing
                    $this->setSend(self::WEBHOOK_CANCEL);
                } else {
                    $response = $this->processJsonResponse($response);

                    $this->domain .= '/' . $response['team']['id']
                        . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/rosters';
                }

                // add the owner to the team
                $send = array(
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
                );

                // update request body and allow PostWebhooks to complete the call
                $this->setData(array('roster' => $send));
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
                $team = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];
                $this->domain .= '/teams/' . $team;

                // team data to send
                $geographical = $this->getRegion($data['group']['timezone']);
                $send = array(
                    'team_name' => $data['group']['name'],
                    'sport_id' => $this->getSport($data['group']['group_category']),
                    'timezone' => $geographical['timezone'],
                    'country' => $geographical['location'],
                    'zipcode' => $data['group']['postalcode'],
                );

                // add team logo if present
                if (isset($data['group']['logo'])) {
                    $send['logo_url'] = $data['group']['logo'];
                }

                $this->setData(array('team' =>$send));
                parent::put();
                break;
            case self::WEBHOOK_DELETE_GROUP:
                $team = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                $team = $team['external_resource_id'];
                $this->domain .= '/teams/' . $team;

                $this->headers['X-Teamsnap-Features'] = '{"partner.delete_team": 1}';
                parent::delete();
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

                $team = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                if (isset($team['message'])) {
                    // resource was not found
                    $this->setSend(self::WEBHOOK_CANCEL);
                } elseif (isset($team['external_resource_id'])) {
                    $team = $team['external_resource_id'];
                }

                // role data to send
                $send = array(
                    'non_player' => $data['member']['role_name'] == 'Player' ? 0 : 1,
                    'is_manager' => $data['member']['is_admin'] ? 1 : 0,
                );

                if ($method == self::HTTP_POST) {
                    /*
                     * The user does not exist in the TeamSnap system; Create
                     * the user from the profile information.
                     */

                    // add first/last name required for user creation
                    $send['first'] = $data['member']['first_name'];
                    $send['last'] = $data['member']['last_name'];

                    // add email address for TeamSnap invite
                    if (isset($data['member']['guardian'])) {
                        $send['roster_email_addresses_attributes'] = array(
                            array(
                                'label' => 'Guardian Profile',
                                'email' => $data['member']['guardian']['email'],
                            ),
                        );
                    } else {
                        $send['roster_email_addresses_attributes'] = array(
                            array(
                                'label' => 'Profile',
                                'email' => $data['member']['email'],
                            ),
                        );
                    }

                    $this->setData(array('roster' => $send));
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/rosters';

                    parent::post();
                } else {
                    /*
                     * The user does exist in the TeamSnap system; Update the
                     * existing user with the given information.
                     */
                    $this->setData(array('roster' => $send));
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/rosters/' . $roster;

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
                    $this->setSend(self::WEBHOOK_CANCEL);
                } elseif (isset($group['external_resource_id'])) {
                    $roster = $group['external_resource_id'];
                }

                $team = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                if (isset($team['message'])) {
                    // resource was not found
                    $this->setSend(self::WEBHOOK_CANCEL);
                } elseif (isset($team['external_resource_id'])) {
                    $team = $team['external_resource_id'];
                }

                $this->domain .= '/teams/' . $team . '/as_roster/'
                    . $this->webhook->subscriber['commissioner_id']
                    . '/rosters/' . $roster;

                // roster data to send
                $send = array(
                    'non_player' => $data['member']['role_name'] == 'Player' ? 1 : 0,
                    'is_manager' => $data['member']['is_admin'] ? 1 : 0,
                );

                $this->setData(array('roster' => $send));
                parent::put();
                break;
            case self::WEBHOOK_ADD_SUBMISSION:
                $roster = $method = '';
                $roster = parent::readPartnerMap(
                    self::PARTNER_MAP_USER,
                    $data['member']['uuid'],
                    $data['group']['uuid']
                );

                if (isset($roster['message'])) {
                    // resource was not found
                    $method = self::HTTP_POST;
                } elseif (isset($roster['external_resource_id'])) {
                    $method = self::HTTP_PUT;
                    $roster = $roster['external_resource_id'];
                }

                $team = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $data['group']['uuid'],
                    $data['group']['uuid']
                );
                if (isset($team['message'])) {
                    // resource was not found
                    $this->setSend(self::WEBHOOK_CANCEL);
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

                // override email with guardian email, if present
                if (isset($data['member']['guardian'])) {
                    $send['roster_email_addresses_attributes'] = array(
                        array(
                            'label' => 'Guardian Profile',
                            'email' => $data['member']['guardian']['email'],
                        ),
                    );
                } else {
                    if (isset($webform['profile__field_email__profile'])) {
                        $send['roster_email_addresses_attributes'] = array(
                            array(
                                'label' => 'Webform',
                                'email' => $webform['profile__field_email__profile'],
                            ),
                        );
                    } elseif ($method == self::HTTP_POST && !isset($webform['profile__field_email__profile'])) {
                        /*
                         * Element required for roster invitation, but not present in
                         * user submission; use information from the users account.
                         */

                        $send['roster_email_addresses_attributes'] = array(
                            array(
                                'label' => 'Profile',
                                'email' => $data['member']['email'],
                            ),
                        );
                    }
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

                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/rosters';

                    parent::post();
                } else {
                    /*
                     * The user does exist in the TeamSnap system; Update the
                     * existing user with the given information.
                     */

                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/rosters/' . $roster;

                    parent::put();
                }
                break;
            case self::WEBHOOK_CREATE_EVENT:
                // make/get location resource
                $location = $this->getLocationResource(
                    $data['group']['uuid'],
                    isset($data['event']['location']) ? $data['event']['location'] : null
                );

                // make request payload for an event
                $send = array(
                    'eventname' => $data['event']['title'],
                    'division_id' => $this->webhook->subscriber['division_id'],
                    'league_controlled' => false,
                    'event_date_start' => $data['event']['start'],
                    'event_date_end' => $data['event']['end'],
                    'location_id' => $location,
                );

                // add additional information to event payload
                if (isset($data['event']['description']) && !empty($data['event']['description'])) {
                    $send['notes'] = $data['event']['description'];
                }

                // determine if game or event
                if (isset($data['event']['competitor']) && !empty($data['event']['competitor'])) {
                    $original_domain = $this->domain;
                    $original_send = $send;

                    foreach ($data['event']['competitor'] as $group) {
                        // retrieve group specific data to complete the request
                        $team = parent::readPartnerMap(
                            self::PARTNER_MAP_GROUP,
                            $group['uuid'],
                            $group['uuid']
                        );
                        $team = $team['external_resource_id'];

                        $opponent = $this->getOpponentResource(
                            $group['uuid'],
                            $team,
                            $data['event']['competitor']
                        );

                        // update request payload to make a game event
                        $send = $original_send;
                        $send['opponent_id'] = $opponent;
                        $this->domain = $original_domain . '/teams/' . $team
                            . '/as_roster/'
                            . $this->webhook->subscriber['commissioner_id']
                            . '/games';

                        // set request payload and process the response for each
                        $this->setData(array('game' => $send));
                        parent::post();
                        $response = $this->send();
                        $response = $this->processJsonResponse($response);

                        parent::createPartnerMap(
                            $response['game']['id'],
                            self::PARTNER_MAP_EVENT,
                            $data['event']['uuid'],
                            $group['uuid']
                        );

                        // reset temp variables for next iteration
                        $this->domain = $original_domain;
                    }

                    // cancel webhook from sending since we handled it above
                    // (only cancel for game events)
                    parent::setSend(parent::WEBHOOK_CANCEL);
                } else {
                    $team = parent::readPartnerMap(
                        self::PARTNER_MAP_GROUP,
                        $data['group']['uuid'],
                        $data['group']['uuid']
                    );
                    $team = $team['external_resource_id'];
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/practices';
                }

                $this->setData(array('practice' => $send));
                parent::post();
                break;
            case self::WEBHOOK_UPDATE_EVENT:
                // make request payload for event
                $send = array(
                    'eventname' => $data['event']['title'],
                    'division_id' => $this->webhook->subscriber['division_id'],
                    'event_date_start' => $data['event']['start'],
                    'event_date_end' => $data['event']['end'],
                );

                // add additional information to event payload
                if (isset($data['event']['description']) && !empty($data['event']['description'])) {
                    $send['notes'] = $data['event']['description'];
                }

                // determine if game or event
                if (isset($data['event']['competitor']) && !empty($data['event']['competitor'])) {
                    $original_domain = $this->domain;
                    $original_send = $send;

                    foreach ($data['event']['competitor'] as $group) {
                        // retrieve group specific data to complete the request
                        $team = parent::readPartnerMap(
                            self::PARTNER_MAP_GROUP,
                            $group['uuid'],
                            $group['uuid']
                        );
                        $team = $team['external_resource_id'];

                        $event = parent::readPartnerMap(
                            self::PARTNER_MAP_EVENT,
                            $data['event']['uuid'],
                            $group['uuid']
                        );
                        $event = $event['external_resource_id'];

                        $event_location = isset($data['event']['location']) ? $data['event']['location'] : null;
                        $location = $this->getLocationResource(
                            $group['uuid'],
                            $event_location
                        );

                        $opponent = $this->getOpponentResource(
                            $group['uuid'],
                            $team,
                            $data['event']['competitor']
                        );

                        // set request payload and send for each
                        $send = $original_send;
                        $send['location_id'] = $location;
                        $send['opponent_id'] = $opponent;
                        $this->domain = $original_domain . '/teams/' . $team
                            . '/as_roster/'
                            . $this->webhook->subscriber['commissioner_id']
                            . '/games/' . $event;
                        $this->setData(array('game' => $send));
                        parent::put();
                        $this->send();

                        // reset temp variables for next iteration
                        $this->domain = $original_domain;
                    }

                    // cancel webhook from sending since we handled it above
                    // (only cancel for game events)
                    parent::setSend(parent::WEBHOOK_CANCEL);
                } else {
                    // retrieve group specific data to complete the request
                    $team = parent::readPartnerMap(
                        self::PARTNER_MAP_GROUP,
                        $data['group']['uuid'],
                        $data['group']['uuid']
                    );
                    $team = $team['external_resource_id'];

                    $event = parent::readPartnerMap(
                        self::PARTNER_MAP_EVENT,
                        $data['event']['uuid'],
                        $data['group']['uuid']
                    );
                    $event = $event['external_resource_id'];

                    $event_location = isset($data['event']['location']) ? $data['event']['location'] : null;
                    $location = $this->getLocationResource(
                        $data['group']['uuid'],
                        $event_location
                    );

                    // set request payload and send
                    $send['location_id'] = $location;
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/practices/' . $event;
                    $this->setData(array('practice' => $send));
                    parent::put();
                }
                break;
            case self::WEBHOOK_DELETE_EVENT:
                // determine if game or event
                if (isset($data['event']['competitor']) && !empty($data['event']['competitor'])) {
                    $original_domain = $this->domain;

                    foreach ($data['event']['competitor'] as $group) {
                        // retrieve group specific data to complete the request
                        $team = parent::readPartnerMap(
                            self::PARTNER_MAP_GROUP,
                            $group['uuid'],
                            $group['uuid']
                        );
                        $team = $team['external_resource_id'];

                        $event = parent::readPartnerMap(
                            self::PARTNER_MAP_EVENT,
                            $data['event']['uuid'],
                            $group['uuid']
                        );
                        $event = $event['external_resource_id'];

                        // set request payload and send
                        $this->domain = $original_domain . '/teams/' . $team
                            . '/as_roster/'
                            . $this->webhook->subscriber['commissioner_id']
                            . '/games/' . $event;
                        parent::delete();
                        $this->send();

                        // reset temp variables for next iteration
                        $this->domain = $original_domain;
                    }

                    // cancel webhook from sending since we handled it above
                    // (only cancel for game events)
                    parent::setSend(parent::WEBHOOK_CANCEL);
                } else {
                    // retrieve group specific data to complete the request
                    $team = parent::readPartnerMap(
                        self::PARTNER_MAP_GROUP,
                        $data['group']['uuid'],
                        $data['group']['uuid']
                    );
                    $team = $team['external_resource_id'];

                    $event = parent::readPartnerMap(
                        self::PARTNER_MAP_EVENT,
                        $data['event']['uuid'],
                        $data['group']['uuid']
                    );
                    $event = $event['external_resource_id'];

                    // set request payload and send
                    $this->domain .= '/teams/' . $team . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/practices/' . $event;
                    parent::delete();
                }

                break;
            default:
                $this->setSend(self::WEBHOOK_CANCEL);
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
            $response = $this->processJsonResponse($response);
            $response = json_decode($response['body'], true);
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

                    // add guardian if present and does not exist
                    if (isset($original_data['member']['guardian'])) {
                        $this->domain = 'https://api.teamsnap.com/v2';
                        $this->getContactResource(
                            $original_data['group']['uuid'],
                            $original_data['member']['uuid'],
                            $original_data['member']['guardian']
                        );
                    }

                    // invite the user to complete their TeamSnap account
                    $query = parent::readPartnerMap(
                        self::PARTNER_MAP_GROUP,
                        $original_data['group']['uuid'],
                        $original_data['group']['uuid']
                    );
                    $this->domain = 'https://api.teamsnap.com/v2/teams/'
                        . $query['external_resource_id'] . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/invitations';
                    $send = array(
                        $response['roster']['id'],
                    );

                    $this->setData(array('rosters' => $send));
                    parent::post();
                    $this->send();
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
            case self::WEBHOOK_CREATE_EVENT:
                // associate an AllPlayers event UUID with TeamSnap event ID
                // this will only occur if a practice event is scheduled
                parent::createPartnerMap(
                    $response['practice']['id'],
                    self::PARTNER_MAP_EVENT,
                    $original_data['event']['uuid'],
                    $original_data['group']['uuid']
                );
                break;
            case self::WEBHOOK_DELETE_EVENT:
                parent::deletePartnerMap(
                    self::PARTNER_MAP_EVENT,
                    $original_data['event']['uuid'],
                    $original_data['group']['uuid']
                );
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
     * @param string $timezone
     *   A PHP supported timezone: http://php.net/manual/en/timezones.php
     *
     * @return array
     *   An associative keyed array with the location and timezone information.
     */
    public function getRegion($timezone)
    {
        $user_timezone = timezone_offset_get(
            new \DateTimeZone($timezone),
            (new \DateTime(null, new \DateTimeZone('UTC')))
        )/(3600);
        if (date_format(new \DateTime($timezone), 'I') == 1) {
            $user_timezone += -1; //adjust for dst
        }

        // convert to utc (non-DST) format
        $user_timezone = str_replace(
            '.',
            ':',
            number_format((floor($user_timezone) + ($user_timezone - floor($user_timezone)) * 60), 2)
        );

        // if region is in supported list, return its value.
        if (isset(self::$regions[$user_timezone])) {
            return self::$regions[$user_timezone];
        } else {
            // return default UTC region
            return array(
                'timezone' => 'UTC',
                'location' => 'United Kingdom',
            );
        }
    }

    /**
     * Get the partner-mapped location resource ID for TeamSnap.
     *
     * This will create the resource if it does not already exist.
     *
     * @param string $group_uuid
     *   The Group UUID to make the location mapping with.
     * @param array $event_location
     *   The Event location information from the AllPlayers Webhook.
     *
     * @return string
     *   The resource ID for TeamSnap API calls.
     */
    public function getLocationResource($group_uuid, array $event_location = null)
    {
        $original_domain = $this->domain; // store old domain
        $location = '';

        // check team partner mapping for the given uuid
        $team = parent::readPartnerMap(
            self::PARTNER_MAP_GROUP,
            $group_uuid,
            $group_uuid
        );
        $team = $team['external_resource_id'];

        // check if location partner mapping exists
        if (isset($event_location) && !empty($event_location)) {
            $location = parent::readPartnerMap(
                self::PARTNER_MAP_RESOURCE,
                $event_location['uuid'],
                $group_uuid
            );
        }

        // set default data if something is missing
        if (!isset($event_location['title']) || empty($event_location['title'])) {
            $event_location['title'] = '(TBD)';
        }
        if (!isset($event_location['street']) || empty($event_location['street'])) {
            $event_location['street'] = '(TBD)';
        }

        // make a basic location resource
        $send = array(
            'location_name' => $event_location['title'],
            'address' => $event_location['street'],
        );

        // add additional location information, if present
        if (isset($event_location['additional']) && !empty($event_location['additional'])) {
            $send['address'] .= ', ' . $event_location['additional'];
        }
        if (isset($event_location['city']) && !empty($event_location['city'])) {
            $send['address'] .= '. ' . $event_location['city'];
        }
        if (isset($event_location['province']) && !empty($event_location['province'])) {
            $send['address'] .= ', ' . $event_location['province'];
        }
        if (isset($event_location['postal_code']) && !empty($event_location['postal_code'])) {
            $send['address'] .= '. ' . $event_location['postal_code'];
        }
        if (isset($event_location['country']) && !empty($event_location['country'])) {
            $send['address'] .= '. ' . strtoupper($event_location['country']) . '.';
        }

        // update request body
        $this->setData(array('location' => $send));

        // update existing partner-mapping, or create a new mapping
        if (isset($location['external_resource_id'])) {
            $location = $location['external_resource_id'];
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/locations/'
                . $location;

            // update existing location data
            parent::put();
            $this->send();
        } else {

            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/locations';

            // create location
            parent::post();
            $response = $this->send();
            $response = $this->processJsonResponse($response);

            // make partner mapping with location creation response data
            if (isset($event_location['uuid']) && !empty($event_location['uuid'])) {
                parent::createPartnerMap(
                    $response['location']['id'],
                    parent::PARTNER_MAP_RESOURCE,
                    $event_location['uuid'],
                    $group_uuid
                );
            }

            // update location id
            $location = $response['location']['id'];
        }

        // restore the old domain
        $this->domain = $original_domain;
        return $location;
    }

    /**
     * Get the partner-mapped opponent resource ID for TeamSnap.
     *
     * This will create the resource if it does not already exist.
     *
     * @param string $group_uuid
     *   The group recieving an event update from the webhook.
     * @param string $group_partner_id
     *   The Team ID for the corresponding TeamSnap team.
     * @param array $competitors
     *   The list of competitors from the AllPlayers Webhook.
     *
     * @return string
     *   The resource ID for TeamSnap API calls.
     */
    public function getOpponentResource($group_uuid, $group_partner_id, array $competitors)
    {
        $original_domain = $this->domain; // store old domain
        $opponent = '';

        // return the opponent resource mapping
        foreach ($competitors as $competitor) {
            if ($competitor['uuid'] != $group_uuid) {
                $opponent = parent::readPartnerMap(
                    self::PARTNER_MAP_GROUP,
                    $competitor['uuid'],
                    $group_uuid
                );

                // set default data if something is missing
                if (!isset($competitor['name']) || empty($competitor['name'])) {
                    $competitor['name'] = '(TBD)';
                }

                // make basic opponent resource
                $send = array(
                    'opponent_name' => $competitor['name'],
                    'opponent_contact_name' => '(TBD)',
                );

                // update request body and make the opponent
                $this->setData(array('opponent' => $send));

                // use existing partner mapping data, or create a new mapping
                if (isset($opponent['external_resource_id'])) {
                    $opponent = $opponent['external_resource_id'];
                    $this->domain = $original_domain . '/teams/'
                        . $group_partner_id . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/opponents/' . $opponent;

                    // update existing opponent data
                    parent::put();
                    $this->send();
                } else {
                    $this->domain = $original_domain . '/teams/'
                        . $group_partner_id . '/as_roster/'
                        . $this->webhook->subscriber['commissioner_id']
                        . '/opponents';

                    // create new opponent
                    parent::post();
                    $response = $this->send();
                    $response = $this->processJsonResponse($response);

                    // make partner opponent mapping (opponent to group)
                    if (isset($competitor['uuid']) && !empty($competitor['uuid'])) {
                        parent::createPartnerMap(
                            $response['opponent']['id'],
                            parent::PARTNER_MAP_GROUP,
                            $competitor['uuid'],
                            $group_uuid
                        );
                    }

                    // update opponent id
                    $opponent = $response['opponent']['id'];
                }
            }

            // reset temp variables for next iteration
            $this->domain = $original_domain;
        }

        // restore old domain
        $this->domain = $original_domain;

        return $opponent;
    }

    /**
     * Get the partner-mapped contact resource ID for TeamSnap.
     *
     * This will create the resource if it does not already exist.
     *
     * @param string $group_uuid
     *   The group that the user belongs too.
     * @param string $user_uuid
     *   The user that has a contact.
     * @param array $contact_info
     *   The contacts information.
     *
     * @return string
     *   The resource ID for TeamSnap API calls.
     */
    public function getContactResource($group_uuid, $user_uuid, array $contact_info)
    {
        $original_domain = $this->domain; // store old domain
        $contact = '';

        // check team partner mapping for the given uuid
        $team = parent::readPartnerMap(
            self::PARTNER_MAP_GROUP,
            $group_uuid,
            $group_uuid
        );
        $team = $team['external_resource_id'];

        // check if contact partner mapping exists
        $contact = parent::readPartnerMap(
            self::PARTNER_MAP_USER,
            $contact_info['uuid'],
            $user_uuid
        );

        // get teamsnap user id
        $user = parent::readPartnerMap(
            parent::PARTNER_MAP_USER,
            $user_uuid,
            $group_uuid
        );
        $user = $user['external_resource_id'];

        // set default data if something is missing
        if (!isset($contact_info['first_name']) || empty($contact_info['first_name'])) {
            $contact_info['first_name'] = '(Guardian)';
        }

        // make a basic contact resource
        $send = array(
            'label' => 'Guardian',
            'first' => $contact_info['first_name'],
        );

        // add additional contact information, if present
        if (isset($contact_info['last_name']) && !empty($contact_info['last_name'])) {
            $send['last'] = $contact_info['last_name'];
        }

        // update existing partner-mapping, or create a new mapping
        if (isset($contact['external_resource_id'])) {
            $contact = $contact['external_resource_id'];
            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] . '/rosters/'
                . $user .  '/contacts/' . $contact;

            // update existing contact data
            $this->setData(array('contact' => $send));
            parent::put();
            $this->send();
        } else {
            // add additional contact information, if present
            if (isset($contact_info['email']) && !empty($contact_info['email'])) {
                $send['contact_email_addresses_attributes'] = array(
                    array(
                        'label' => 'Profile',
                        'email' => $contact_info['email'],
                    ),
                );
            }

            $this->domain = $original_domain . '/teams/' . $team . '/as_roster/'
                . $this->webhook->subscriber['commissioner_id'] .  '/rosters/'
                . $user . '/contacts';

            // create contact
            $this->setData(array('contact' => $send));
            parent::post();
            $response = $this->send();
            $response = $this->processJsonResponse($response);

            // make partner mapping with contact creation response data
            if (isset($contact_info['uuid']) && !empty($contact_info['uuid'])) {
                parent::createPartnerMap(
                    $response['contact']['id'],
                    parent::PARTNER_MAP_USER,
                    $contact_info['uuid'],
                    $user_uuid
                );
            }

            // update contact id
            $contact = $response['contact']['id'];
        }

        // restore the old domain
        $this->domain = $original_domain;
        return $contact;
    }
}
