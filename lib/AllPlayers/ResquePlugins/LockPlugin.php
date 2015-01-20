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
 * Provides semaphore-like behavior for Reque Jobs, by using the event hooks.
 */
class LockPlugin
{
    /**
     * Check if the job can acquire the processing lock.
     *
     * This will requeue the given job upon failure, if the requeue parameter is
     * set to true.
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
            if ($job->payload['args'][0]['requeue']) {
                Php_Resque::enqueue(
                    $job->queue,
                    $job->payload['class'],
                    $job->payload['args'][0],
                    true
                );
                return false;
            } else {
                throw new \Exception(
                    'The Job failed to acquire the unique key, and requeue ='
                    . ' false.'
                );
            }
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
        if (Php_Resque::redis()->exists(
            $job->payload['args'][0]['drupal_unique_key']
        )) {
            Php_Resque::redis()->del(
                $job->payload['args'][0]['drupal_unique_key']
            );
        }
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
        if (Php_Resque::redis()->exists(
            $job->payload['args'][0]['drupal_unique_key']
        )) {
            Php_Resque::redis()->del(
                $job->payload['args'][0]['drupal_unique_key']
            );
        }

        // Check if the job should be requeued.
        if ($job->payload['args'][0]['requeue']) {
            if ($job->payload['args'][0]['limit'] > 0) {
                // Update requeue limit.
                $job->payload['args'][0]['limit']--;

                // Requeue job.
                Php_Resque::enqueue(
                    $job->queue,
                    $job->payload['class'],
                    $job->payload['args'][0],
                    true
                );
            } else {
                throw new \Exception(
                    'The Job failed and the Requeue limit has been exceeded.'
                );
            }
        } else {
            throw new \Exception('The Job failed, and requeue = false.');
        }
    }
}
