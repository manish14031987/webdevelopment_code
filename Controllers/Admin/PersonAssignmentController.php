<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Personassignment;
use Illuminate\Support\Facades\DB;
use App\Employee_records;
use App\Assignrole;
use App\Projecttask;
use App\Createrole;
use App\Project;
use App\taskAssignment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Roleauth;

class PersonAssignmentController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Roleauth::check('project.resourceplanning.assignperson.index');

        $personassignment = Personassignment::where('company_id', Auth::user()->company_id)->get();
        $resource_name = array();
        $role_name = array();
        $project_names = array();
        $assign = array();
        $result3 = array();
        $result = array();
        $task = array();
        $task_names = array();
        foreach ($personassignment as $key => $value) {

            $assign[$key] = (Personassignment::where('id', $value->id)->where('company_id', Auth::user()->company_id)->get()->first() == null) ? '' : Personassignment::where('id', $value->id)->where('company_id', Auth::user()->company_id)->get()->first();
            if (isEmptyOrNullString($assign[$key])) {
                $res = Employee_records::where('employee_id', $assign[$key]->resource_name)->where('company_id', Auth::user()->company_id)->select('employee_first_name', 'employee_middle_name', 'employee_last_name')->first();
                $res = json_decode(json_encode($res),true);
                $resource_name[$key] = (count($res) > 0) ? $res['employee_first_name'] . ' ' . $res['employee_middle_name'] . ' ' . $res['employee_last_name'] : '';
            } else {
                $resource_name[$key] = '';
            }


            $result3 = Createrole::where('id', $value->role)->where('company_id', Auth::user()->company_id)->select('role_name')->get();
            if ($result3->count()) {
                $role_name[] = $result3[0]->role_name;
            } else {
                $role_name[] = '';
            }
            
            $task = Projecttask::where('id',$value->task)->where('company_id', Auth::user()->company_id)->select('task_Id', 'task_name')->get(); 
           if ($task->count()) {
                $task_names[] = $task[0]->task_Id;
            } else {
                $task_names[] = '';
            }
            
            $result = Project::where('id', $value->project_id)->where('company_id', Auth::user()->company_id)->select('project_name', 'project_id')->get();
            if ($result->count()) {
                $project_names[] = $result[0]->project_id . ' (' . $result[0]->project_name . ')';
            } else {
                $project_names[] = '';
            }
        }
        return view('admin.personassignment.index', compact('task_names','resource_name', 'personassignment', 'project_names', 'role_name'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Roleauth::check('project.resourceplanning.assignperson.create');

        $project = DB::table('project')
                ->where('company_id', Auth::user()->company_id)
                ->get();


        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->id] = $projectid->project_Id . ' ( ' . $projectid->project_desc . ' )';
        }

        $task = DB::table('tasks_subtask')
                ->where('company_id', Auth::user()->company_id)
                ->get();


        $task_id = array();


        $Role = DB::table('createrole')->get();

        $roles = array();
        foreach ($Role as $value) {
            $roles[$value->role_name] = $value->role_name;
        }

        $empname = DB::table('employee_records')
                ->where('employee_records.company_id', Auth::user()->company_id)
                ->select('employee_records.employee_id', 'employee_records.employee_first_name', 'employee_records.employee_middle_name', 'employee_records.employee_last_name')
                ->join('assignrole', 'assignrole.resource_name', '=', 'employee_records.employee_id')
                ->get();

        $resource_name = array();

        foreach ($empname as $emp) {

            $resource_name[$emp->employee_id] = $emp->employee_first_name . ' ' . $emp->employee_middle_name . ' ' . $emp->employee_last_name;
        }



        return view('admin.personassignment.create', compact('resource_name', 'task_id', 'project_id', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Roleauth::check('project.resourceplanning.assignperson.create');

        $personassignment = Input::all();
        $personassignment['created_by'] = Auth::user()->id;
        $personassignment['company_id'] = Auth::user()->company_id;

        $project_id = Project::where('id', $personassignment['project_id'])->where('company_id', Auth::user()->company_id)->first();
        $roles = Createrole::where('project_id', $personassignment['project_id'])
                ->where('company_id', Auth::user()->company_id)
                ->get();

        $roleid = array();
        foreach ($roles as $role) {
            $roleid[$role->id] = $role->role_name;
        }

        $tasks = DB::table('taskassign')
                ->where('taskassign.project_id', $personassignment['project_id'])
                ->where('taskassign.company_id', Auth::user()->company_id)
                ->select('tasks_subtask.id', 'tasks_subtask.task_Id', 'tasks_subtask.task_name', 'tasks_subtask.task_Id')
                ->join('tasks_subtask', 'taskassign.task', '=', 'tasks_subtask.id')
                ->distinct()
                ->get();

        $taskid = array();
        foreach ($tasks as $task) {
            $taskid[$task->id] = $task->task_Id . ' (' . $task->task_name . ')';
        }


        $start_date = strtotime($personassignment['start_date']);
        $end_date = strtotime($personassignment['end_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            session()->flash('task_id', $taskid);
            session()->flash('role_id', $roleid);
            return redirect('admin/personassignmenttotask/create/')->withErrors($msgs)->withInput(Input::all());
        }

//compare the start and end date with the role start and end date should be betweeen the range

        $task_assign = taskAssignment::where(['task' => $personassignment['task'], 'role' => $personassignment['role']])
                ->where('company_id', Auth::user()->company_id)
                ->first();
        if ($task_assign != null) {
            $cr_start_date = strtotime($task_assign->start_date);
            $cr_end_date = strtotime($task_assign->end_date);
            $msgs = array();
            if (!($cr_start_date <= $start_date)) {
                $msgs = ['start_date' => 'Start Date can`t be lesser than Project Start Date | ' . explode(' ', $task_assign->start_date)[0]];
            }
            if (!($cr_end_date >= $start_date)) {
                $msgs = ['start_date' => 'Start Date can`t be greater than Project End Date | ' . explode(' ', $task_assign->end_date)[0]];
            }

            if ($task_assign->end_date != false && $task_assign->end_date != 'NULL' && !($cr_end_date >= $end_date)) {
                $msgs+= ['end_date' => 'End Date can`t be greater than Project End Date | ' . explode(' ', $task_assign->end_date)[0]];
            }

            if ($task_assign->end_date != false && $task_assign->end_date != 'NULL' && !($cr_start_date <= $end_date)) {
                $msgs+= ['end_date' => 'End Date can`t be lesser than Project Start Date | ' . explode(' ', $task_assign->start_date)[0]];
            }

            if (count($msgs) > 0) {
                session()->flash('task_id', $taskid);
                session()->flash('role_id', $roleid);
                return redirect('admin/personassignmenttotask/create/')->withErrors($msgs)->withInput(Input::all());
            }
        }


        $validationmessages = [
            'project_id.required' => 'please select project id',
            'role.required' => 'Please select role',
            'task.required' => 'Please select task id',
            'resource_name.required' => 'Please select resource name',
            'start_date.required' => 'Please select Start Date',
            'end_date.required' => 'Please select End Date',
        ];

        $validator = Validator::make($personassignment, [
                    'project_id' => 'required',
                    'role' => 'required',
                    'task' => 'required',
                    'resource_name' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/personassignmenttotask/create')->withErrors($validator)->withInput(Input::all());
        }

        Personassignment::create($personassignment);

        session()->flash('flash_message', 'Person Assignment to task created successfully...');
        return redirect('admin/personassignmenttotask');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
//
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        Roleauth::check('project.resourceplanning.assignperson.edit');

        $personassignment = Personassignment::find($id);



        $project = DB::table('project')
                ->where('company_id', Auth::user()->company_id)
                ->get();

        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->id] = $projectid->project_Id . ' ( ' . $projectid->project_desc . ' )';
        }


        $projectid = Project::where('id', $personassignment->project_id)->where('company_id', Auth::user()->company_id)->first();
        $task = DB::table('tasks_subtask')->where('project_id', $projectid->project_Id)
                ->where('company_id', Auth::user()->company_id)
                ->get();


        $task_id = array();
        foreach ($task as $taskid) {
            $task_id[$taskid->id] = $taskid->task_Id . ' ( ' . $taskid->task_name . ' )';
        }

        $Role = Createrole::where('project_id', $personassignment->project_id)
                ->where('company_id', Auth::user()->company_id)
                ->get();

        $roles = array();
        foreach ($Role as $value) {
            $roles[$value->id] = $value->role_name;
        }


        $empname = DB::table('employee_records')
                ->where('company_id', Auth::user()->company_id)
                ->get();

        $resource_name = array();
        foreach ($empname as $emp) {

            $resource_name[$emp->employee_id] = $emp->employee_first_name . ' ' . $emp->employee_middle_name . ' ' . $emp->employee_last_name;
        }

        return view('admin.personassignment.create', compact('personassignment', 'task_id', 'resource_name', 'roles', 'assignrole', 'project_id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Roleauth::check('project.resourceplanning.assignperson.edit');

        $personassignment = Personassignment::find($id);

        $data_get = $request->only($personassignment->getEditable());

        $data_get['changed_by'] = Auth::user()->id;

        $roles = Createrole::where('project_id', $personassignment['project_id'])
                ->where('company_id', Auth::user()->company_id)
                ->get();
        $roleid = array();
        foreach ($roles as $role) {
            $roleid[$role->id] = $role->role_name;
        }

        $start_date = strtotime($data_get['start_date']);
        $end_date = strtotime($data_get['end_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            session()->flash('role_id', $roleid);
            return redirect('admin/personassignmenttotask/' . $id . '/edit')->withErrors($msgs)->withInput(Input::all());
        }

//compare the start and end date with the role start and end date should be betweeen the range
        $task_assign = taskAssignment::where(['task' => $data_get['task'], 'role' => $data_get['role']])->where('company_id', Auth::user()->company_id)->first();


        if ($task_assign != null) {
            $cr_start_date = strtotime($task_assign->start_date);
            $cr_end_date = strtotime($task_assign->end_date);
            $msgs = array();
            if (!($cr_start_date <= $start_date)) {
                $msgs = ['start_date' => 'Start Date can`t be lesser than Task Assigned validity | ' . explode(' ', $task_assign->start_date)[0]];
            }

            if ($task_assign->end_date != '' && $task_assign->end_date != 'NULL' && !($cr_end_date >= $end_date)) {
                $msgs+= ['end_date' => 'End Date can`t be greater than Task Assigned validity | ' . explode(' ', $task_assign->end_date)[0]];
            }
            if (count($msgs) > 0) {
                session()->flash('role_id', $roleid);
                return redirect('admin/personassignmenttotask/' . $id . '/edit')->withErrors($msgs)->withInput(Input::all());
            }
        }




        $validationmessages = [
            'project_id.required' => 'please select project id',
            'role.required' => 'Please select role',
            'task.required' => 'Please select task id',
            'resource_name.required' => 'Please select resource name',
            'start_date.required' => 'Please select Start Date',
            'end_date.required' => 'Please select End Date',
        ];

        $validator = Validator::make($data_get, [
                    'project_id' => 'required',
                    'role' => 'required',
                    'task' => 'required',
                    'resource_name' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                        ], $validationmessages);
        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/personassignmenttotask/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }
        $personassignment->update($data_get);
        session()->flash('flash_message', 'Assign role updated successfully...');
        return redirect('admin/personassignmenttotask');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Roleauth::check('project.resourceplanning.assignperson.delete');

        $personassignment = Personassignment::find($id);
        $personassignment->delete();
        session()->flash('flash_message', 'Person assignment to task deleted successfully...');
        return redirect('admin/personassignmenttotask');
    }

    public function getRole()
    {


        if (isset(Input::all()['project_id'])) {
            $id = Input::all()['project_id'];

            if (isset($id) and $id > 0) {
                $roles = Createrole::where('project_id', $id)->where('company_id', Auth::user()->company_id)->get()->toArray();

//            print_r($roles);
                $listRoles = array();

                if (isset($roles) && count($roles)) {

                    foreach ($roles as $ProjectRoleList) {
                        array_push($listRoles, array($ProjectRoleList['id'], $ProjectRoleList['role_name'] . ' (' . \Illuminate\Support\Str::words($ProjectRoleList['role_fun'], 5, '....') . ')'));
                    }

                    echo json_encode(array('roleList' => $listRoles));
                }
            }
        } else {
            $id = Input::all()['task_id'];

            if (isset($id) and $id > 0) {
                $roles = DB::table('taskassign')
                        ->where('taskassign.company_id', Auth::user()->company_id)
                        ->where('task', $id)
                        ->select('createrole.id', 'createrole.role_name', 'createrole.role_fun')
                        ->join('createrole', 'createrole.id', '=', 'taskassign.role')
                        ->distinct()
                        ->get();

                $listRoles = array();

                if (isset($roles) && count($roles)) {

                    foreach ($roles as $ProjectRoleList) {
                        array_push($listRoles, array($ProjectRoleList->id, $ProjectRoleList->role_name . ' (' . \Illuminate\Support\Str::words($ProjectRoleList->role_fun, 5, '....') . ')'));
                    }

                    echo json_encode(array('roleList' => $listRoles));
                }
            }
        }
    }

    public function getRoledesc()
    {
        $id = Input::all();
        
        if (isset($id) && $id > 0) {
            $roledesc = Createrole::where('id', $id)
                    ->where('company_id', Auth::user()->company_id)
                    ->get();

            $roles = DB::table('taskassign')
                    ->where('createrole.company_id', Auth::user()->company_id)
                    ->where('createrole.id', $id)
                    ->select('assignrole.resource_name', 'taskassign.task')
                    ->join('createrole', 'createrole.id', '=', 'taskassign.role')
                    ->join('assignrole', 'assignrole.role', '=', 'taskassign.role')
                    ->distinct()
                    ->groupby('taskassign.task', 'assignrole.resource_name')
                    ->get();

            $taskandresource = array();
            if (isset($roles) && $roles->count()) {

                foreach ($roles as $data) {
                    $emp = Employee_records::where('employee_id', $data->resource_name)
                                    ->where('company_id', Auth::user()->company_id)->first();

                    $task = Projecttask::where('id', $data->task)
                                    ->where('company_id', Auth::user()->company_id)->first();


                    if ($task != null)
                        $task_name = $task->task_Id . '( ' . $task->task_name . ' )';

                    if ($emp != null)
                        $emp_name = $emp->employee_first_name . ' ' . $emp->employee_middle_name . ' ' . $emp->employee_last_name;

                    $taskandresource[] = array('resource_name' => array($emp->employee_id, $emp_name), 'task_name' => array($task->id, $task_name));
                }
            }


            $list = array();
            if (isset($roledesc) && $roledesc->count()) {

                foreach ($roledesc as $role) {

                    $list[] = $role->description;
                }
                echo json_encode(array('role_desc' => $list, 'taskandresource' => $taskandresource));
            } else
                echo json_encode(array('role_desc' => array('')));
        }
    }

    public function getTask()
    {
        $id = Input::all()['project_id'];

        if (isset($id) and $id > 0) {

            $tasks = DB::table('taskassign')->where('taskassign.project_id', $id)
                    ->where('taskassign.company_id', Auth::user()->company_id)
                    ->select('tasks_subtask.id', 'tasks_subtask.task_Id', 'tasks_subtask.task_name', 'tasks_subtask.task_Id')
                    ->join('tasks_subtask', 'taskassign.task', '=', 'tasks_subtask.id')
                    ->distinct()
                    ->get();

            $resource = DB::table('assignrole')->where('assignrole.project_id', $id)
                    ->where('assignrole.company_id', Auth::user()->company_id)
                    ->select('employee_records.employee_id', 'employee_records.employee_first_name', 'employee_records.employee_middle_name', 'employee_records.employee_last_name')
                    ->join('employee_records', 'assignrole.resource_name', '=', 'employee_records.employee_id')
                    ->distinct()
                    ->get();

            $listResource = array();
            if (isset($resource) && count($resource)) {

                foreach ($resource as $resourceList) {

                    array_push($listResource, array($resourceList->employee_id, $resourceList->employee_first_name . ' ' . $resourceList->employee_middle_name . ' ' . $resourceList->employee_last_name));
                }
            }


            $listTasks = array();

            if (isset($tasks) && count($tasks)) {

                foreach ($tasks as $ProjectTaskList) {

                    array_push($listTasks, array($ProjectTaskList->id, $ProjectTaskList->task_Id . ' (' . \Illuminate\Support\Str::words($ProjectTaskList->task_name, 5, '....') . ')'));
                }

                echo json_encode(array('taskList' => $listTasks, 'resourceList' => $listResource));
            }
        }
    }

    public function gettaskname(Request $request)
    {
        $id = Input::all()['task_id'];
        $name = Projecttask::select('task_name')
                        ->where('company_id', Auth::user()->company_id)
                        ->where('id', $id)->first();
        return response()->json($name);
    }

}
