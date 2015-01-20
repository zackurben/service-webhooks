<?php
/**
 * @file
 * Contains /AllPlayers/ResquePlugins/ChangeWebhookPlugin.
 *
 * Provides functionality for requeueing a Resque Job with a new payload.
 */

namespace AllPlayers\ResquePlugins;

use \Resque as Php_Resque;
use \Resque_Job;

/**
 * Provides the ability to manually change the webhook type and have it queued
 * as a new singular event with its corresponding unique id for job concurrency.
 */
class ChangeWebhookPlugin
{
    /**
     * Make a new job using the base job object and the given payload.
     *
     * @param Resque_Job $job
     *   The base job to queue.
     * @param array $data
     *   The data to insert into the resque job, for the webhook change.
     */
    public static function queueJob(Resque_Job $job, array $data)
    {
        // Remove change_webhook flag, to stop infinite loops.
        unset($data['change_webhook']);
        $job->payload['args'][0]['event_data'] = $data;

        // Delete the old key before updating the key.
        if (Php_Resque::redis()->exists(
            $job->payload['args'][0]['drupal_unique_key']
        )) {
            Php_Resque::redis()->del(
                $job->payload['args'][0]['drupal_unique_key']
            );
        }

        // Update the unique key for the new job.
        ChangeWebhookPlugin::updateUniqueKey($job);

        // Reset the webhook variables to emulate a normal new job.
        ChangeWebhookPlugin::resetWebhookSettings($job);

        // Queue our new job.
        Php_Resque::enqueue(
            $job->queue,
            $job->payload['class'],
            $job->payload['args'][0],
            true
        );
    }

    /**
     * Update the given job, to match its unique key with its payload.
     *
     * @param Resque_Job $job
     *   The job to update.
     */
    public static function updateUniqueKey(Resque_Job &$job)
    {
        // Use the job class to start a key.
        $key = $job->payload['class'];

        // Regulate queues to force concurrency amongst similar events.
        $data = $job->payload['args'][0]['event_data'];
        switch ($data['webhook_type']) {
            case 'user_creates_group':
            case 'user_updates_group':
            case 'user_deletes_group':
                $key .= ':group';
                break;

            case 'user_adds_role':
            case 'user_removes_role':
            case 'user_adds_submission':
            case 'user_removed_from_group':
                $key .= ':user';
                break;

            case 'user_creates_event':
            case 'user_updates_event':
            case 'user_deletes_event':
                $key .= ':event';
                break;

            default:
                $key .= ':unknown';
        }

        // Update the lock to ensure similar items are blocked from processing
        // simultaneously.
        if (isset($data['member']['uuid'])) {
            $key .= ':' . $data['member']['uuid'];
        }
        $key .= $data['group']['uuid'] . ':'
            . $job->payload['args'][0]['subscriber']['gid'] . ':'
            . $job->payload['args'][0]['hook']['name'];

        $job->payload['args'][0]['drupal_unique_key'] = $key;
    }

    /**
     * Update the given job, to ensure its requeue and limit are standard.
     *
     * @param Resque_Job $job
     *   The job to update.
     */
    public static function resetWebhookSettings(Resque_Job &$job)
    {
        $data = $job->payload['args'][0];

        // Update the requeue and limit variables.
        $data['requeue'] = true;
        $data['limit'] = 2;
    }
}
