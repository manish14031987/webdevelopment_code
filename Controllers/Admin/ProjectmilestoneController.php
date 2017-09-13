<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Projectmilestone;
use Illuminate\Support\Facades\Input;
use App\Project;
use App\Projectphase;
use App\Projecttask;
use App\milestone_type;
use DB;
use App\User;
use Illuminate\Support\Facades\Validator;
use App\Roleauth;

class ProjectmilestoneController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        Roleauth::check('project.milestone.index');

        $projectmilestone = Projectmilestone::all();
        $createdby = array();
        $key = 0;

        foreach ($projectmilestone as $key1 => $value) {
            $project = Project::Where('id', $value->project_id)->first();
            $task = Projecttask::Where('id', $value->task_id)->first();
            $phase = Projectphase::Where('id', $value->phase_id)->first();
            $projectmilestone[$key1]->project_desc = isset($project->project_desc) ? $project->project_desc : '';
            $projectmilestone[$key1]->project_id = isset($project->project_Id) ? $project->project_Id : '';
            $projectmilestone[$key1]->task_id = isset($task->task_Id) ? $task->task_Id : '';
            $projectmilestone[$key1]->phase_id = isset($phase->phase_Id) ? $phase->project_desc : '';
            $createdby[$key] = ($value->created_by != '') ? User::where('id', $value->created_by)->first()['original']['name'] : '';
            $key++;
        }
        return view('admin.projectmilestone.index', compact('projectmilestone', 'createdby'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        Roleauth::check('project.milestone.create');
        
        $milestone = milestone_type::all();
        $mile = array();
        foreach ($milestone as $group) {
            $mile[$group->milestonetype] = $group->milestonetype;
        }


        $rand_number = substr(md5(microtime()), rand(0, 26), 3);


        $project_id = Project::select('id', 'project_Id', 'project_name')->get();
        $phase_id = Projectphase::select('id', 'phase_Id', 'phase_name')->get();
        $task_id = Projecttask::select('id', 'task_Id', 'task_name')->get();

        $project_data = DB::table("project")
                ->select('project_Id')
                ->get();

        $phase_data = DB::table("project_phase")
                ->select('phase_Id')
                ->get();


        $task_data = DB::table("tasks_subtask")
                ->select('task_Id')
                ->get();


        $pid = '';
        foreach ($project_data as $key => $projectdata) {
            $pid[$projectdata->project_Id] = $projectdata->project_Id;
        }

        $phid = '';
        foreach ($phase_data as $key => $phasedata) {
            $phid[$phasedata->phase_Id] = $phasedata->phase_Id;
        }

        $tid = '';
        foreach ($task_data as $key => $taskdata) {
            $tid[$taskdata->task_Id] = $taskdata->task_Id;
        }

        $user = Auth::user();
        $username = 'you are not logged in';
        if (Auth::check()) {
            $username = $user->name;
        }

        $requestor = User::where('name', $username)->first();
        $requestor = $requestor['id'];
        $created_by = $requestor;

        return view('admin.projectmilestone.create', compact('created_by', 'pid', 'phid', 'tid', 'milestone', 'projectmilestone', 'project_id', 'mile', 'rand_number', 'phase_id', 'task_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        Roleauth::check('project.milestone.create');

        $projectmile = Input::all();

        $start_date = strtotime($projectmile['start_date']);
        $end_date = strtotime($projectmile['finish_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            return redirect('admin/projectmilestone/create/')->withErrors($msgs)->withInput(Input::all());
        }

        $validationmessages = [

            'milestone_name.required' => "Please enter milestone name",
            'project_id.required' => "Please enter project id",
            'phase_id.required' => "Please enter phase id",
        ];

        $validator = Validator::make($projectmile, [
                    'milestone_name' => "required",
                    'project_id' => "required",
                    'phase_id' => "required",
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/projectmilestone/create')->withErrors($validator)->withInput(Input::all());
        }

        Projectmilestone::create($projectmile);
        session()->flash('flash_message', 'projectmilestone created successfully...');


        return redirect('admin/projectmilestone');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        Roleauth::check('project.milestone.edit');
        $projectmilestone = Projectmilestone::find($id);
        if(!isset($projectmilestone)) {
            return redirect('admin/projectmilestone');
        }

        $phase_Id = DB::table('project_phase')
                ->select('project_phase.phase_Id')
                ->where('id', $projectmilestone->phase_id)
                ->first();

        $taskId = DB::table('tasks_subtask')
                ->select('tasks_subtask.task_Id')
                ->where('id', $projectmilestone->task_id)
                ->first();

        $changed_on = date('Y-m-d');
        //get login details
        $user = Auth::user();
        $username = 'you are not logged in';
        if (Auth::check()) {
            $username = $user->name;
        }
        $requestor = User::where('name', $username)->first();
        $requestor = $requestor['id'];
        $created_by = $requestor;

        $milestone = milestone_type::all();

        foreach ($milestone as $group) {
            $mile[$group->milestonetype] = $group->milestonetype;
        }

        $project_id = Project::select('id', 'project_Id', 'project_name')->get();

        $phase = DB::table('project_phase')
                ->select('project_phase.*', 'project.project_Id')
                ->leftJoin('project', 'project_phase.project_id', '=', 'project.id')
                ->where('project_phase.project_id', $projectmilestone->project_id)
                ->get();

        $phase_id = array();
        foreach ($phase as $phaseid) {

            $phase_id[$phaseid->id] = $phaseid->phase_Id . ' ( ' . $phaseid->phase_name . ' )';
        }

        $task = DB::table('tasks_subtask')
                ->select('tasks_subtask.*')
                ->where('phase_id', isset($phase_Id->phase_Id) ? $phase_Id->phase_Id : '')
                ->get();
        $task_id = array();
        foreach ($task as $taskid) {

            $task_id[$taskid->id] = $taskid->task_Id . ' ( ' . $taskid->task_name . ' )';
        }

        return view('admin.projectmilestone.edit', compact('taskId', 'created_by', 'mile', 'changed_on', 'projectmilestone', 'project_id', 'phase_id', 'task_id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        Roleauth::check('project.milestone.edit');
        $projectmilestone = Projectmilestone::find($id);
        if(!isset($projectmilestone)) {
            return redirect('admin/projectmilestone');
        }

        $data_get = $request->all();
        $start_date = strtotime($data_get['start_date']);
        $end_date = strtotime($data_get['finish_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            return redirect('admin/projectmilestone/' . $id . '/edit')->withErrors($msgs)->withInput(Input::all());
        }

        $validationmessages = [

            'milestone_name.required' => "Please enter milestone name",
            'project_id.required' => "Please enter project id",
            'phase_id.required' => "Please enter phase id",
        ];

        $validator = Validator::make($data_get, [
                    'milestone_name' => "required",
                    'project_id' => "required",
                    'phase_id' => "required",
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/projectmilestone/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }
        $projectmilestone->update($data_get);
        session()->flash('flash_message', 'Project milestone updated successfully...');
        return redirect('admin/projectmilestone');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        Roleauth::check('project.milestone.delete');
        $projectmilestone = Projectmilestone::find($id);
        if(!isset($projectmilestone)) {
            return redirect('admin/projectmilestone');
        }
        
        $projectmilestone->delete();
        session()->flash('flash_message', 'Project milestone deleted successfully...');
        return redirect('admin/projectmilestone');
    }

}
