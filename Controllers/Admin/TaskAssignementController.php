<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Project;
use App\Createrole;
use App\Projecttask;
use App\taskAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Roleauth;

class TaskAssignementController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Roleauth::check('project.resourceplanning.assigntask.index');

        $taskassignement = array();
        $taskassignement = taskAssignment::where('company_id', Auth::user()->company_id)->get();
        $project_names = array();
        $role_names = array();
        $task_names = array();
        foreach ($taskassignement as $project) {
            $result = Project::where('id', $project->project_id)->where('company_id', Auth::user()->company_id)->select('project_name', 'project_id')->first();
            if ($result != null) {
                $project_names[] = $result->project_id . ' (' . $result->project_name . ')';
            } else {
                $project_names[] = '';
            }

            $result2 = Projecttask::where('id', $project->task)->where('company_id', Auth::user()->company_id)->select('task_name', 'task_Id')->first();
            if ($result2 != null) {
                $task_names[] = $result2->task_Id . ' (' . $result2->task_name . ')';
            } else {
                $task_names[] = '';
            }

            $result3 = Createrole::where('id', $project->role)->where('company_id', Auth::user()->company_id)->select('role_name')->first();
            if ($result3 != null) {
                $role_names[] = $result3->role_name;
            } else {
                $role_names[] = '';
            }
        }


        $data =  DB::table('tasks_subtask')->where('tasks_subtask.company_id', Auth::user()->company_id)
                ->whereIn('tasks_subtask.id', DB::table('taskassign')->pluck('taskassign.task'))
                ->select(DB::raw('project.project_id,sum(tasks_subtask.total_demand) as total_demand,project.project_name'))
                ->join('project', 'tasks_subtask.project_id', '=', 'project.project_Id')
                ->groupBy('project.project_Id', 'project.project_name')
                ->get();


        $data2 = DB::table('tasks_subtask')->where('tasks_subtask.company_id', Auth::user()->company_id)
                ->whereNOTIn('tasks_subtask.id', DB::table('taskassign')->pluck('taskassign.task'))
                ->select(DB::raw('project.project_id,sum(tasks_subtask.total_demand) as pending,project.project_name'))
                ->join('project', 'tasks_subtask.project_id', '=', 'project.project_Id')
                ->groupBy('project.project_Id', 'project.project_name')
                ->get();


        $arr = array();
        foreach ($data as $assign) {
            $arr[$assign->project_id]['project_id'] = $assign->project_id;
            $arr[$assign->project_id]['assign'] = $assign->total_demand;
            $arr[$assign->project_id]['pending'] = 0;
        }
        foreach ($data2 as $pending) {
            $arr[$pending->project_id]['project_id'] = $pending->project_id;
            $arr[$pending->project_id]['pending'] = $pending->pending;
            if (!isset($arr[$pending->project_id]['assign'])) {
                $arr[$pending->project_id]['assign'] = 0;
            }
        }

        $result = array();
        array_push($result, array('data' => $data));
        array_push($result, array('data2' => $data2));
        array_push($result, array('arr' => $arr));
        return view('admin.taskassign.index', compact('role_names', 'task_names', 'project_names', 'taskassignement', 'result'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Roleauth::check('project.resourceplanning.assigntask.create');

        $project = Project::where('company_id', Auth::user()->company_id)->get();
//        $roles = Createrole::all();
        $task = Projecttask::where('company_id', Auth::user()->company_id)->where('status','<>','Completed')->get();



        $Role = DB::table('createrole')->where('company_id', Auth::user()->company_id)->get();

        $roles = array();
        foreach ($Role as $value) {
            $roles[$value->role_name] = $value->role_name;
        }

        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->id] = $projectid->project_Id . ' ( ' . $projectid->project_name . ' )';
        }


//        $role_id = array();
//        foreach ($roles as $role) {
//
//            $role_id[$role->id] = $role->id . ' ( ' . $role->role_name . ' )';
//        }

        $task_id = array();
        foreach ($task as $item) {

            $task_id[$item->id] = $item->id . ' ( ' . $item->task_name . ' )';
        }
        return view('admin.taskassign.create', compact('project_id', 'task_id', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Roleauth::check('project.resourceplanning.assigntask.create');

        $data_get = $request->all();
        $data_get['created_by'] = Auth::user()->id;
        $data_get['changed_by'] = Auth::user()->id;
        $data_get['company_id'] = Auth::user()->company_id;
        $project_id = Project::where('id', $data_get['project_id'])->where('company_id', Auth::user()->company_id)->first();

        $roles = Createrole::where('project_id', $data_get['project_id'])->where('company_id', Auth::user()->company_id)->get();
        $roleid = array();
        foreach ($roles as $role) {
            $roleid[$role->id] = $role->role_name;
        }

        $tasks = Projecttask::where('project_id', $project_id->project_Id)
                ->where('company_id', Auth::user()->company_id)
                ->get();

        $task_id = array();
        foreach ($tasks as $task) {
            $task_id[$task->id] = $task->id . ' ( ' . $task->task_name . ' )';
        }

        $start_date = strtotime($data_get['start_date']);
        $end_date = strtotime($data_get['end_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            session()->flash('role_id', $roleid);
            session()->flash('task_id', $task_id);


            return redirect('admin/taskassign/create/')->withErrors($msgs)->withInput(Input::all());
        }

        //compare the start and end date with the role start and end date should be betweeen the range
        $create_role = Createrole::find($data_get['role']);
        $cr_start_date = strtotime($create_role->start_date);
        $cr_end_date = strtotime($create_role->end_date);
        $msgs = array();
        if (!($cr_start_date <= $start_date)) {
            $msgs = ['start_date' => 'Start Date can`t be lesser than Role Start date | ' . $create_role->start_date];
        }
        if (!($cr_end_date >= $start_date )) {
            $msgs = ['start_date' => 'Start Date can`t be greater than Role End date | ' . $create_role->end_date];
        }
        if (!($cr_end_date >= $end_date )) {
            $msgs+= ['end_date' => 'End Date can`t be greater than Role End date | ' . $create_role->end_date];
        }
        if (!($cr_start_date <= $end_date )) {
            $msgs+= ['end_date' => 'End Date can`t be greater than Role Start date | ' . $create_role->start_date];
        }
        if (count($msgs) > 0) {
            session()->flash('role_id', $roleid);
            session()->flash('task_id', $task_id);

            return redirect('admin/taskassign/create/')->withErrors($msgs)->withInput(Input::all());
        }


        $validationmessages = [
            'project_id.required' => 'please select project id',
            'role.required' => 'Please select role or Role already Added to this task',
            'task.required' => 'Please select task ',
            'start_date.required' => 'Please select Start Date',
            'end_date.required' => 'Please select End Date',
        ];

        $validator = Validator::make($data_get, [
                    'project_id' => 'required',
                    'role' => 'required|unique:taskassign,role,null,id,task,' . $data_get['task'],
                    'task' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            session()->flash('role_id', $roleid);
            session()->flash('task_id', $task_id);

            return redirect('admin/taskassign/create/')->withErrors($validator)->withInput(Input::all());
        }

        taskAssignment::create($data_get);
        session()->flash('flash_message', ' Created ... Assigned task to role successfully...');
        return redirect('admin/taskassign/');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

        if (isset($id) and $id > 0) {
            $roles = Createrole::where('project_id', $id)
                    ->where('company_id', Auth::user()->company_id)
                    ->get();
            $listRoles = array();

            if (isset($roles) and $roles->count()) {

                foreach ($roles as $ProjectRoleList) {
                    $listRoles[] = array($ProjectRoleList->id, $ProjectRoleList->role_name . ' (' . \Illuminate\Support\Str::words($ProjectRoleList['role_fun'], 5, '....') . ')');
                }
                echo json_encode(array('roleList' => $listRoles));
            }
        }
    }

    public function getRoleDesc($id)
    {

        if (isset($id) and $id > 0) {
            $roles = Createrole::where('id', $id)
                    ->where('company_id', Auth::user()->company_id)
                    ->get();
            //  return view('admin.projectchecklist.index', compact('projectchecklist'));
            $list = array();
            if (isset($roles) and $roles->count()) {

                foreach ($roles as $role) {
                    // echo '<option value="'.$projectList->id.'">';
                    $list[] = $role->description;
                }
                echo json_encode(array('desc' => $list));
            } else
                echo json_encode(array('desc' => array('')));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        Roleauth::check('project.resourceplanning.assigntask.edit');

        $taskassignment = taskAssignment::where('id', $id)->where('company_id', Auth::user()->company_id)->first();
        $project = Project::where('id', $taskassignment->project_id)->where('company_id', Auth::user()->company_id)->get();
        $task = Projecttask::where('project_id', $project[0]->project_Id)->where('company_id', Auth::user()->company_id)->get();
        $role = Createrole::where('project_id', $taskassignment->project_id)->where('company_id', Auth::user()->company_id)->get();
        $project = Project::where('company_id', Auth::user()->company_id)->get();
        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->id] = $projectid->project_Id . ' ( ' . $projectid->project_name . ' )';
        }


//        $role_id = array();
//        foreach ($roles as $role) {
//
//            $role_id[$role->id] = $role->id . ' ( ' . $role->role_name . ' )';
//        }

        $roles = array();
        foreach ($role as $value) {
            $roles[$value->id] = $value->role_name;
        }

        $task_id = array();
        foreach ($task as $item) {

            $task_id[$item->id] = $item->id . ' ( ' . $item->task_name . ' )';
        }
        return view('admin.taskassign.create', compact('taskassignment', 'project_id', 'task_id', 'roles'));
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
        Roleauth::check('project.resourceplanning.assigntask.edit');

        $taskassign = new taskAssignment();
        $data_get = $request->only($taskassign->getEditable());
        $data_get['changed_by'] = Auth::user()->id;

        $start_date = strtotime($data_get['start_date']);
        $end_date = strtotime($data_get['end_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            return redirect('admin/taskassign/' . $id . '/edit/')->withErrors($msgs)->withInput(Input::all());
        }


        $create_role = Createrole::find($data_get['role']);
        $cr_start_date = strtotime($create_role->start_date);
        $cr_end_date = strtotime($create_role->end_date);
        $msgs = array();
        //compare the start and end date with the role start and end date should be betweeen the range
        if (!($cr_start_date <= $start_date)) {
            $msgs = ['start_date' => 'Start Date can`t be lesser than Role Start date | ' . $create_role->start_date];
        }
        if (!($cr_end_date >= $start_date )) {
            $msgs = ['start_date' => 'Start Date can`t be greater than Role End date | ' . $create_role->end_date];
        }
        if (!($cr_end_date >= $end_date )) {
            $msgs+= ['end_date' => 'End Date can`t be greater than Role End date | ' . $create_role->end_date];
        }
        if (!($cr_start_date <= $end_date )) {
            $msgs+= ['end_date' => 'End Date can`t be greater than Role Start date | ' . $create_role->start_date];
        }
        if (count($msgs) > 0) {
            return redirect('admin/taskassign/' . $id . '/edit/')->withErrors($msgs)->withInput(Input::all());
        }



        $validationmessages = [
            'project_id.required' => 'please select project id',
            'role.required' => 'Please select role ',
            'task.required' => 'Please select task ',
            'start_date.required' => 'Please select Start Date',
            'end_date.required' => 'Please select End Date',
        ];

        $validator = Validator::make($data_get, [
                    'project_id' => 'required',
                    'role' => 'required',
                    'task' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/taskassign/' . $id . '/edit/')->withErrors($validator)->withInput(Input::all());
        }

        taskAssignment::find($id)->update($data_get);
        session()->flash('flash_message', ' Assigned task to role successfully Updated ...');
        return redirect('admin/taskassign/');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Roleauth::check('project.resourceplanning.assigntask.delete');

        taskAssignment::destroy($id);
        session()->flash('flash_message', ' Assigned task to role successfully deleted ...');
        return redirect('admin/taskassign/');
    }

}
