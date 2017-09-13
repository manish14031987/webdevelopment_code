<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use App\projectchecklist;
use App\Projectphase;
use App\Phasetype;
use App\Projecttask;
use App\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Capacityunits;
use App\ProjectIssue;
use App\Roleauth;
use App\Employee_records;

class ProjectchecklistController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        Roleauth::check('project.checklist.index');
        $projectchecklist = DB::table('project_checklist')
                ->select('project_checklist.*', 'employee_records.employee_first_name')
                ->leftJoin('employee_records', 'project_checklist.person_responsible', '=', 'employee_records.employee_id')
                ->get();
        return view('admin.projectchecklist.index', compact('projectchecklist'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        Roleauth::check('project.checklist.create');

        $rand_number = substr(md5(microtime()), rand(0, 26), 6);

        $created_on = date('Y-m-d');

        //get login details
        $user = Auth::user();

        if (Auth::check()) {
            $username = $user->name;
        } else {
            $username = 'you are not logged in';
        }

        $capacity_units = Capacityunits::select('id', 'name')->get();

        $project = DB::table('project')->get();
        $phase_data = DB::table("project_phase")->get();
        $task_data = DB::table("tasks_subtask")->get();

        $project_id = array();
        foreach ($project as $projectid) {
            $project_id[$projectid->project_Id] = $projectid->project_Id . ' ( ' . $projectid->project_name . ' )';
        }

        $phase_id = array();
        foreach ($phase_data as $key => $phasedata) {
            $phase_id[$phasedata->phase_Id] = $phasedata->phase_Id . '(' . $phasedata->phase_name . ')';
        }

        $task_id = array();
        foreach ($task_data as $key => $taskdata) {
            $task_id[$taskdata->task_Id] = $taskdata->task_Id . '(' . $taskdata->task_name . ')';
        }





        return view('admin.projectchecklist.create', compact('projectchecklist', 'project_id', 'phase_id', 'task_id', 'pid', 'phid', 'rand_number', 'created_on', 'username'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        Roleauth::check('project.checklist.create');

        $checklist_data = Input::all();

        $start_date = strtotime($checklist_data['start_date']);
        $end_date = strtotime($checklist_data['end_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            return redirect('admin/projectchecklist/create/')->withErrors($msgs)->withInput(Input::all());
        }
        $validationmessages = [
            'checklist_id.required' => 'Please enter checklist id',
            'checklist_name.required' => 'Please enter checklist name',
            'checklist_text.required' => 'Please enter checklist text less than 240 character',
            'checklist_type.required' => 'Please select checklist type',
            'project_id.required' => 'Please select project id',
            'phase_id.required' => 'Please select phase id',
            'task_id.required' => 'Please select task id',
        ];

        $validator = Validator::make($checklist_data, [
                    'checklist_id' => 'required',
                    'checklist_name' => 'required',
                    'checklist_text' => 'required|max:240',
                    'checklist_type' => 'required',
                    'project_id' => 'required',
                    'phase_id' => 'required',
                    'task_id' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/projectchecklist/create')->withErrors($validator)->withInput(Input::all());
        }

        Projectchecklist::create($checklist_data);

        session()->flash('flash_message', 'Project checklist created successfully...');
        return redirect('admin/projectchecklist');
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
        Roleauth::check('project.checklist.edit');
        $projectchecklist = Projectchecklist::find($id);
        if (!isset($projectchecklist)) {
            return redirect('admin/projectchecklist');
        }

        $pid = DB::table('project')
                ->select('project.id')
                ->where('project_Id', $projectchecklist->project_id)
                ->first();


        $changed_on = date('Y-m-d');

        //get login details
        $user = Auth::user();

        if (Auth::check()) {
            $username = $user->name;
        } else {
            $username = 'you are not logged in';
        }

        $project = DB::table('project')->get();
        $project_id = array();
        foreach ($project as $projectid) {
            $project_id[$projectid->project_Id] = $projectid->project_Id . ' ( ' . $projectid->project_name . ' )';
        }

        $phase = DB::table('project_phase')
                ->select('project_phase.*', 'project.project_Id')
                ->leftJoin('project', 'project_phase.project_id', '=', 'project.id')
                ->where('project_phase.project_id', $pid->id)
                ->get();

        $phase_id = array();
        foreach ($phase as $phaseid) {

            $phase_id[$phaseid->phase_Id] = $phaseid->phase_Id . ' ( ' . $phaseid->phase_name . ' )';
        }

        $task = DB::table('tasks_subtask')
                ->select('tasks_subtask.*')
                ->where('phase_id', $projectchecklist->phase_id)
                ->get();

        $task_id = array();
        foreach ($task as $taskid) {

            $task_id[$taskid->task_Id] = $taskid->task_Id . ' ( ' . $taskid->task_name . ' )';
        }
        $capacity = array();
        $temp = Capacityunits::pluck('name', 'id');
        foreach ($temp as $key => $value) {
            $capacity[$key] = $value;
        }
        $SingleIssue = ProjectIssue::where('id', $id)->get();
        //get employee master
        $employeemaster = array();
        $employeeData = Employee_records::where('company_id', Auth::user()->company_id)->get();

        foreach ($employeeData as $value) {
            $employeemaster[$value->employee_id] = $value->employee_id . ' ( ' . (isset($value->employee_first_name) ? $value->employee_first_name : '') . ' )';
        }
        return view('admin.projectchecklist.edit', compact('employeemaster', 'SingleIssue', 'capacity', 'projectchecklist', 'project_id', 'phase_id', 'task_id', 'changed_on', 'username'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        Roleauth::check('project.checklist.edit');
        $projectchecklist = Projectchecklist::find($id);
        if (!isset($projectchecklist)) {
            return redirect('admin/projectchecklist');
        }
        $data_get = $request->all();
        $start_date = strtotime($data_get['start_date']);
        $end_date = strtotime($data_get['end_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            return redirect('admin/projectchecklist/' . $id . '/edit')->withErrors($msgs)->withInput(Input::all());
        }
        $validationmessages = [
            'checklist_id.required' => 'Please enter checklist id',
            'checklist_name.required' => 'Please enter checklist name',
            'checklist_text.required' => 'Please enter checklist text less than 240 character',
            'checklist_type.required' => 'Please enter checklist type',
            'project_id.required' => 'Please enter project id',
            'task_id.required' => 'Please select task id',
            'phase_id.required' => 'Please enter phase id',
        ];

        $validator = Validator::make($data_get, [
                    'checklist_id' => 'required',
                    'checklist_name' => 'required',
                    'checklist_text' => 'required|max:240',
                    'checklist_type' => 'required',
                    'project_id' => 'required',
                    'task_id' => 'required',
                    'phase_id' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/projectchecklist/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }
        $projectchecklist->update($data_get);
        session()->flash('flash_message', 'Project checklist updated successfully...');
        return redirect('admin/projectchecklist');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        Roleauth::check('project.checklist.delete');
        $projectchecklist = Projectchecklist::find($id);
        if (!isset($projectchecklist)) {
            return redirect('admin/projectchecklist');
        }

        $projectchecklist->delete();
        session()->flash('flash_message', 'Project checklist deleted successfully...');
        return redirect('admin/projectchecklist');
    }

    public function getprojectname(Request $request) {

        $name = DB::table('project')->select('project_desc')->where('project_Id', $request->project_Id)->first();
        $projectId = Project::Where('project_Id', $request->project_Id)->first();
        $phase_data = DB::table("project_phase")
                ->select('id', 'phase_Id', 'phase_name')
                ->where('project_id', $projectId->id)
                ->get();

        $phaseArray = array();
        foreach ($phase_data as $phase) {

            $phaseArray[$phase->phase_Id] = $phase->phase_Id . ' ( ' . $phase->phase_name . ' )';
        }
        return response()->json(array('name' => $name, 'phase' => $phaseArray));
    }

    public function getphasename(Request $request) {

        $name = DB::table('project_phase')->select('phase_name')->where('phase_id', $request->phase_id)->first();

        $task = DB::table("tasks_subtask")
                ->select('id', 'task_Id', 'task_name')
                ->where('phase_id', $request->phase_id)
                ->get();
        $task_id = array();
        foreach ($task as $taskid) {

            $task_id[$taskid->task_Id] = $taskid->task_Id . ' ( ' . $taskid->task_name . ' )';
        }
        return response()->json(array('name' => $name, 'task' => $task_id));
    }

    public function gettaskname(Request $request) {

        $name = DB::table('tasks_subtask')->select('task_name')->where('task_id', $request->task_id)->first();


        return response()->json($name);
    }

}
