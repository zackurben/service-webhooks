<?php
/**
 * @file
 * Contains /AllPlayers/ResquePlugins/LockPlugin.
 *
 * Provides functionality for queueing Resque Jobs within a Resque Job.
 */

namespace AllPlayers\ResquePlugins;

use \Resque_Job;

/**
 * Provides an implementation of QueuePlugin for AllPlayers service-webhooks.
 */
class AllplayersPlugin extends QueuePlugin
{
    /**
     * Queue the given job with its given event_data.
     *
     * @param Resque_Job $job
     *   The base job to queue.
     * @param array $data
     *   The data to replace the given jobs event_data.
     */
    public static function queueJob(Resque_Job $job, $data)
    {
        // Remove change_webhook flag, to stop infinite loops.
        unset($data['change_webhook']);
        $job->payload['args'][0]['event_data'] = $data;

        // Delete the old key before updating the key.
        LockPlugin::unlockJob($job);

        // Update the unique key for the new job.
        self::updateUniqueKey($job);

        // Reset the webhook variables to emulate a normal new job.
        self::resetWebhookSettings($job);

        parent::queueJob($job);
    }

    /**
     * Requeue the given job, if the requeue limit is still valid.
     *
     * @param Resque_Job $job
     *   The base job to queue.
     *
     * @throws \Exception
     */
    public static function requeueJob(Resque_Job $job)
    {
        // Remove requeue flag, to stop infinite loops.
        unset($job->payload['args'][0]['event_data']['requeue']);

//        // Delete the old key before updating the key.
//        LockPlugin::unlockJob($job);

        // Requeue the modified job.
        parent::requeueJob($job);
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

        // Make the unique lock more restrictive if it's a synchronize webhook.
        if (isset($data['sync']) && !$data['sync']) {
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
        } else {
            $key .= ':synchronize:';
        }

        // Update the lock to ensure similar items are blocked from processing
        // simultaneously.
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
        // Update/add the requeue and limit variables.
        $job->payload['args'][0]['requeue'] = true;
        $job->payload['args'][0]['limit'] = 2;
    }
}
