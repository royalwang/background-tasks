<?php
/**
 * User: Chester-He
 */

namespace Chester\BackgroundMission;


use Chester\BackgroundMission\DataBases\BackgroundTasks;

class Queue
{
    protected $manager;
    protected $executor;

    protected $frequency = 5;

    public function __construct(MissionInterface $manager, Logic $executor)
    {
        $this->manager = $manager;
        $this->executor = $executor;
    }

    public function records($type = 'system')
    {
        return $this->manager->getLastTasksByType($type);
    }

    public function push($params)
    {
        return $this->manager->addTask($params);
    }

    public function scheduleRun()
    {
        if (!$this->manager->hasInitTask()) {
            return;
        }

        $taskList = $this->manager->getLastInitTasks($this->frequency);
        $task_ids = collect($taskList)->pluck('unique_id');
        $this->manager->changeTaskStateByIds($task_ids, BackgroundTasks::STATE_EXECUTING);

        foreach ($taskList as $task) {
            $method = $task['method'];
            $params = json_decode($task['params'], true);
            $result = $this->executor->$method($params);
            $state = $result['state'] ? BackgroundTasks::STATE_SUCCESS : BackgroundTasks::STATE_FAIL;
            $this->manager->changeTaskStateByIds($task['unique_id'], $state, $result['content']);
        }
    }

}