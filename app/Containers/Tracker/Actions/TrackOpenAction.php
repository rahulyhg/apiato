<?php

namespace App\Containers\Tracker\Actions;

use App\Containers\Tracker\Models\TimeTracker;
use App\Containers\Tracker\Tasks\FindTimeTrackerTask;
use App\Containers\Tracker\Data\Repositories\TimeTrackerRepository;
use App\Containers\User\Tasks\FindUserByVisitorIdTask;
use App\Port\Action\Abstracts\Action;
use Carbon\Carbon;

/**
 * Class TrackOpenAction.
 *
 * @author Mahmoud Zalt <mahmoud@zalt.me>
 */
class TrackOpenAction extends Action
{

    /**
     * @var  \App\Containers\User\Tasks\FindUserByVisitorIdTask
     */
    private $findUserByVisitorIdTask;

    /**
     * @var  \App\Containers\Tracker\Data\Repositories\TimeTrackerRepository
     */
    private $timeTrackerRepository;

    /**
     * @var  \App\Containers\Tracker\Tasks\FindTimeTrackerTask
     */
    private $findTimeTrackerTask;

    /**
     * TrackOpenAction constructor.
     *
     * @param \App\Containers\User\Tasks\FindUserByVisitorIdTask                       $findUserByVisitorIdTask
     * @param \App\Containers\Tracker\Data\Repositories\TimeTrackerRepository $timeTrackerRepository
     */
    public function __construct(
        FindUserByVisitorIdTask $findUserByVisitorIdTask,
        TimeTrackerRepository $timeTrackerRepository,
        FindTimeTrackerTask $findTimeTrackerTask
    ) {
        $this->findUserByVisitorIdTask = $findUserByVisitorIdTask;
        $this->timeTrackerRepository = $timeTrackerRepository;
        $this->findTimeTrackerTask = $findTimeTrackerTask;
    }

    /**
     * @param $email
     * @param $password
     *
     * @return mixed
     */
    public function run($visitorId)
    {
        $user = $this->findUserByVisitorIdTask->run($visitorId);

        // check if any previous session was not closed
        $timeTracker = $this->findTimeTrackerTask->byUserIdAndStatusPending($user->id);
        if ($timeTracker && $timeTracker->status == TimeTracker::PENDING) {
            $this->timeTrackerRepository->update(['status' => TimeTracker::FAILED], $timeTracker->id);
        }

        // create the new record with pending status
        $timeTracker = new TimeTracker();
        $timeTracker->open_at = Carbon::now();
        $timeTracker->status = TimeTracker::PENDING;
        $timeTracker->user()->associate($user);
        $timeTracker = $this->timeTrackerRepository->create($timeTracker->toArray());

        return $timeTracker;
    }
}