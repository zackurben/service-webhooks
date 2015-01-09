<?php
/**
 * @file
 * Contains /AllPlayers/Utilities/TeamsnapPartnerMap.
 *
 * Provides the TeamSnap specific Partner-Mapping API functionality.
 */

namespace AllPlayers\Utilities;

class TeamsnapPartnerMap extends PartnerMap
{
    /**
     * Create the PartnerMap instance and create the AllPlayers auth Cookie.
     *
     * @param string $username
     *   The AllPlayers username for APIv1 authentication.
     * @param string $password
     *   The AllPlayers password for APIv1 authentication.
     */
    public function __construct($username, $password)
    {
        parent::__construct('teamsnap', $username, $password);
    }

    /**
     * Fetch the TeamSnap LocationID for an event without a location.
     *
     * @param string $group
     *   The AllPlayers UUID for the Group.
     *
     * @return array
     *   The Partner Mapping response from the AllPlayers Partner Mapping API.
     */
    public function getDefaultLocationId($group)
    {
        $id = $this->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $group,
            $group,
            'default_location'
        );

        return $id;
    }

    /**
     * Fetch the TeamSnap Event ID for the given event and group.
     *
     * @param string $event
     *   The AllPlayers UUID for the Event.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     *
     * @return array
     *   The Partner Mapping response from the AllPlayers Partner Mapping API.
     */
    public function getEventId($event, $group)
    {
        $id = $this->readPartnerMap(
            PartnerMap::PARTNER_MAP_EVENT,
            $event,
            $group
        );

        return $id;
    }

    /**
     * Fetch the TeamSnap LocationID for the given location resource and group.
     *
     * @param string $location
     *   The AllPlayers UUID for the event location.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     *
     * @return array
     *   The Partner Mapping response from the AllPlayers Partner Mapping API.
     */
    public function getLocationId($location, $group)
    {
        $id = $this->readPartnerMap(
            PartnerMap::PARTNER_MAP_RESOURCE,
            $location,
            $group
        );

        return $id;
    }

    /**
     * Fetch the TeamSnap OpponentID for the given competitor and group.
     *
     * @param string $competitor
     *   The AllPlayers UUID for the Competitor group.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     *
     * @return array
     *   The Partner Mapping response from the AllPlayers Partner Mapping API.
     */
    public function getOpponentId($competitor, $group)
    {
        $id = $this->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $competitor,
            $group
        );

        return $id;
    }

    /**
     * Fetch the TeamSnap Roster ID for the given user and group.
     *
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     *
     * @return array
     *   The Partner Mapping response from the AllPlayers Partner Mapping API.
     */
    public function getRosterId($user, $group)
    {
        $id = $this->readPartnerMap(
            PartnerMap::PARTNER_MAP_USER,
            $user,
            $group
        );

        return $id;
    }

    /**
     * Fetch the TeamSnap Team ID for the and group.
     *
     * @param string $group
     *   The AllPlayers UUID for the Group.
     *
     * @return array
     *   The Partner Mapping response from the AllPlayers Partner Mapping API.
     */
    public function getTeamId($group)
    {
        $id = $this->readPartnerMap(
            PartnerMap::PARTNER_MAP_GROUP,
            $group,
            $group
        );

        return $id;
    }

    /**
     * Add or Update the Default TeamSnap Event Location ID.
     *
     * Note: This is a fix for game events without a location on AllPlayers.
     *
     * @param integer $id
     *   The TeamSnap Location ID to link with the given group.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setDefaultLocationId($id, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_GROUP,
            $group,
            $group,
            'default_location'
        );
    }

    /**
     * Add or Update the TeamSnap Event.
     *
     * @param integer $id
     *   The TeamSnap Event ID to link with the given event and group.
     * @param string $event
     *   The AllPlayers UUID for the Event.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setEvent($id, $event, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_EVENT,
            $event,
            $group
        );
    }

    /**
     * Add or Update the TeamSnap Event Location.
     *
     * @param integer $id
     *   The TeamSnap Location ID to link with the given group.
     * @param string $event
     *   The AllPlayers UUID for the Event.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setLocationId($id, $event, $group)
    {
        $this->partner_mapping->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_RESOURCE,
            $event,
            $group
        );
    }

    /**
     * Add or Update the TeamSnap Opponent.
     *
     * @param integer $id
     *   The TeamSnap Opponent ID to link with the given competitor and group.
     * @param string $competitor
     *   The AllPlayers UUID for the Competitor group.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setOpponentId($id, $competitor, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_GROUP,
            $competitor,
            $group
        );
    }

    /**
     * Add or Update the TeamSnap Roster.
     *
     * @param integer $id
     *   The TeamSnap RosterID to link the given user.
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setRoster($id, $user, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_USER,
            $user,
            $group
        );
    }

    /**
     * Add or Update the TeamSnap Rosters Cell Phone ID.
     *
     * @param integer $id
     *   The TeamSnap Phone ID to link with the given user and group.
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setRosterCellPhone($id, $user, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_USER,
            $user,
            $group,
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE_CELL
        );
    }

    /**
     * Add or Update the TeamSnap Rosters Email ID.
     *
     * @param integer $id
     *   The TeamSnap Email Address ID to link with the given user and group.
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setRosterEmail($id, $user, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_USER,
            $user,
            $group,
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_EMAIL
        );
    }

    /**
     * Add or Update the TeamSnap Rosters Home Phone ID.
     *
     * @param integer $id
     *   The TeamSnap Phone ID to link with the given user and group.
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setRosterHomePhone($id, $user, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_USER,
            $user,
            $group,
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE
        );
    }

    /**
     * Add or Update the TeamSnap Rosters Work Phone ID.
     *
     * @param integer $id
     *   The TeamSnap Phone ID to link with the given user and group.
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function setRosterWorkPhone($id, $user, $group)
    {
        $this->createPartnerMap(
            $id,
            PartnerMap::PARTNER_MAP_USER,
            $user,
            $group,
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_PHONE_WORK
        );
    }

    /**
     * Delete all the Partner Mapping resources for the given AllPlayers group.
     *
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function deleteGroup($group)
    {
        // Delete all partner mappings associated with the group.
        $this->partner_mapping->deletePartnerMap(
            null,
            $group
        );
    }

    /**
     * Delete the Partner Mapping resource for the user in the given group.
     *
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function deleteUser($user, $group)
    {
        // Delete the partner-mapping for a user.
        $this->partner_mapping->deletePartnerMap(
            PartnerMap::PARTNER_MAP_USER,
            $group,
            $user
        );
    }

    /**
     * Delete the Partner Mapping resource for the users email address.
     *
     * @param string $user
     *   The AllPlayers UUID for the User.
     * @param string $group
     *   The AllPlayers UUID for the Group.
     */
    public function deleteUserEmail($user, $group)
    {
        // Delete the partner-mapping for a user email id.
        $this->partner_mapping->deletePartnerMap(
            PartnerMap::PARTNER_MAP_RESOURCE,
            $group,
            $user,
            PartnerMap::PARTNER_MAP_SUBTYPE_USER_EMAIL
        );
    }
}
