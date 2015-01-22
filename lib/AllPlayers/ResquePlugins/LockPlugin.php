<?php
/**
 * @file
 * Contains /AllPlayers/ResquePlugins/LockPlugin.
 *
 * Provides a resource lock for the Resque Job Perform function.
 */

namespace AllPlayers\ResquePlugins;

use \Resque as Php_Resque;
use \Resque_Job;

/**
 * Provides semaphore-like behavior for Resque Jobs, by using the event hooks.
 */
class LockPlugin
{
    /**
     * Attempts to acquire the jobs unique lock, or requeues the job.
     *
     * @param Resque_Job $job
     *   The resque job.
     *
     * @return bool
     *   If the job was acquired.
     *
     * @throws \Exception
     */
    public static function beforePerform(Resque_Job $job)
    {
        if (Php_Resque::redis()->setnx(
            $job->payload['args'][0]['drupal_unique_key'],
            '1'
        )) {
            return true;
        } else {
            // Attempt to requeue this job.
            QueuePlugin::requeueJob($job);
        }
    }


    /**
     * Clear the unique queue after the job has performed.
     *
     * @param Resque_Job $job
     *   The job that failed.
     */
    public static function afterPerform(Resque_Job $job)
    {
        // Clear the job lock.
        self::unlockJob($job);
    }

    /**
     * Clear the unique queue after the job has failed.
     *
     * @param object $exception
     *   Exception that occurred.
     * @param Resque_Job $job
     *   The job that failed.
     *
     * @throws \Exception
     */
    public static function onFailure($exception, Resque_Job $job)
    {
        // Clear the job lock.
        self::unlockJob($job);
    }

    /**
     * Unlock the given job, using the drupal_unique_key defined in the payload.
     *
     * @param Resque_Job $job
     *   The base job to unlock.
     *
     * @return bool
     *   If the job was unlocked.
     */
    public static function unlockJob(Resque_Job $job)
    {
        if (Php_Resque::redis()->exists(
            $job->payload['args'][0]['drupal_unique_key']
        )) {
            Php_Resque::redis()->del(
                $job->payload['args'][0]['drupal_unique_key']
            );

            return true;
        } else {
            return false;
        }
    }
}
