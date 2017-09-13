<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Redirect;
use App\Project;
use App\Projectphase;
use App\Projecttask;
use App\projectchecklist;
use App\Projectmilestone;
use App\Roleauth;

class ProjectStructureController extends Controller
{

    public function index($projectId = null, $portId = null)
    {
        Roleauth::check('project.structure.index');

        $project = Project::all();
        $portId = null;
        if ($projectId != null) {
            $prj = Project::find($projectId);
            if ($prj)
                $portId = $prj->portfolio_id;
        }
        return view('admin.projectStructure.index', compact('project', 'projectId', 'portId'));
    }

    /**
     * Get Portfolio structure data to show as tree
     */
    public function projectGraphics($projectId)
    {
        try {
            $project = Project::find($projectId);
            $phase = Projectphase::where('project_id', $projectId)->get()->toArray();

            $chartData = array(
                'text' => array('name' => strtoupper($project->project_name)),
                'HTMLclass' => 'sky-blue',
                'children' => array()
            );

            $phaseArray = array();
            foreach ($phase as $phaseKey => $ps) {
                $ps['children_rec'] = Projecttask::with('children_rec')->where('phase_id', $ps['phase_Id'])->where('parent_id', 0)->get()->toArray();
                $taskData = $this->recursiveTaskSubTask(array($ps));
                $phaseArray[] = $taskData[0];
            }
            $chartData['children'] = $phaseArray;
            return response()->json($chartData);
        } catch (\Exception $ex) {
            print_r($ex->getLine());
            print_r($ex->getMessage());
            exit('CATCH');
        }
    }

    /**
     * Iterate each Task sub task till lowermost 
     * @param type $taskSubTasks
     * @return type
     */
    public function recursiveTaskSubTask($taskSubTasks)
    {
        $checklistArray = $phaseChecklist = $chartData = $checklist = $mileStone = $milestoneArray = array();
        foreach ($taskSubTasks as $tKey => $task) {
            if (array_key_exists('phase_name', $task) && array_key_exists('phase_Id', $task)) {
                $checklist = projectchecklist::Where('phase_id', $task['phase_Id'])->get()->toArray();
                if ($checklist && count($checklist) > 0 && isset($checklist) && !empty($checklist)) {
                    $checklistArray = array();
                    $phaseChecklist = array();
                    foreach ($checklist as $list) {
                        $list['name'] = $list['checklist_name'];
                        $list['children_rec'] = array();
                        array_push($task['children_rec'], $list);
                    }
                }
                $name = $task['phase_name'] . ' (' . $task['phase_Id'] . ')';
                $color = 'light-yellow';
            } else if (array_key_exists('phase_id', $task) && array_key_exists('checklist_name', $task)) {
                $task['phase_Id'] = $task['phase_id'];
                $name = $task['checklist_name'] . ' (' . $task['checklist_id'] . ')';
                $color = 'dark-red';
            } else if (array_key_exists('milestone_Id', $task) && array_key_exists('milestone_name', $task)) {
                $name = $task['milestone_name'] . ' (' . $task['milestone_Id'] . ')';
                $color = 'dark-purple';
            } else if (array_key_exists('task_name', $task) && array_key_exists('task_Id', $task) && !array_key_exists('checklist_id', $task)) {
                $mileStone = Projectmilestone::Where('task_id', $task['id'])->get()->toArray();
                if ($mileStone && count($mileStone) > 0 && isset($mileStone) && !empty($mileStone)) {
                    $milestoneArray = array();
                    $taskMilestonelist = array();
                    foreach ($mileStone as $ms) {
                        $ms['name'] = $ms['milestone_name'];
                        $ms['children_rec'] = array();
                        array_push($task['children_rec'], $ms);
                    }
                }
                $name = $task['task_name'] . ' (' . $task['task_Id'] . ')';
                $color = 'light-green';
            }
            $bucketProject = array();
            
            $chartPhase = array(
                'text' => array('name' => strtoupper($name)),
                'HTMLclass' => $color,
                'children' => array()
            );

            $children = $this->recursiveTaskSubTask($task['children_rec']);
            if ($children) {
                $chartPhase['children'] = $children;
            }
            $chartData[] = $chartPhase;
        }
        return $chartData;
    }

}
