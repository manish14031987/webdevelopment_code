<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Assignrole;
use App\Employee_records;
use Illuminate\Support\Facades\DB;
use App\Createrole;
use App\Project;
use Illuminate\Support\Facades\Auth;
use App\Roleauth;

class AssignRoleController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Roleauth::check('project.resourceplanning.assignrole.index');

        $assignrole = Assignrole::where('company_id', Auth::user()->company_id)->get();
        $resource_name = array();
        $role_name = array();
        $project_names = array();

        foreach ($assignrole as $key => $value) {
            $assign[$key] = Assignrole::where('id', $value->id)->first();
            $res = Employee_records::where('employee_id', $assign[$key]->resource_name)->select('employee_first_name', 'employee_middle_name', 'employee_last_name')->first();
            $res = (count($res) > 0) ? $res->toArray() : [];
            $resource_name[$key] = (count($res) > 0) ? $res['employee_first_name'] . ' ' . $res['employee_middle_name'] . ' ' . $res['employee_last_name'] : '';

            $result3 = Createrole::where('id', $value->role)->select('role_name')->get();
            if ($result3->count()) {
                $role_name[] = $result3[0]->role_name;
            } else {
                $role_name[] = '';
            }
            $result = Project::where('id', $value->project_id)->select('project_name', 'project_id')->get();
            if ($result->count()) {
                $project_names[] = $result[0]->project_id . ' (' . $result[0]->project_name . ')';
            } else {
                $project_names[] = '';
            }
        }

        return view('admin.assignroletoperson.index', compact('assignrole', 'project_names','resource_name', 'role_name', 'project_names'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Roleauth::check('project.resourceplanning.assignrole.create');

        $project = DB::table('project')
                ->where('company_id', Auth::user()->company_id)
                ->get();


        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->id] = $projectid->project_Id . ' ( ' . $projectid->project_desc . ' )';
        }

        $Role = DB::table('createrole')
                ->where('company_id', Auth::user()->company_id)
                ->get();

        $roles = array();
        foreach ($Role as $value) {
            $roles[$value->role_name] = $value->role_name;
        }


        $empname = DB::table('employee_records')
                ->where('company_id', Auth::user()->company_id)
                ->get();

        $resource_name = array();

        foreach ($empname as $emp) {

            $resource_name[$emp->employee_id] = $emp->employee_first_name . ' ' . $emp->employee_middle_name . ' ' . $emp->employee_last_name;
        }




        return view('admin.assignroletoperson.create', compact('resource_name', 'roles', 'project_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Roleauth::check('project.resourceplanning.assignrole.create');

        $assignrole = Input::all();
        $assignrole['created_by'] = Auth::user()->id;
        $assignrole['changed_by'] = Auth::user()->id;
        $assignrole['company_id'] = Auth::user()->company_id;

        $createrole = Createrole::where('project_id', $assignrole['project_id'])->where('company_id', Auth::user()->company_id)->get();
        $roleid = array();
        foreach ($createrole as $role) {
            $roleid[$role->id] = $role->role_name;
        }

        $start_date = strtotime($assignrole['start_date']);
        $end_date = strtotime($assignrole['end_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            session()->flash('role_id', $roleid);
            return redirect('admin/assignroletoperson/create/')->withErrors($msgs)->withInput(Input::all());
        }

        //compare the start and end date with the role start and end date should be betweeen the range
        $create_role = Createrole::find($assignrole['role']);
        $cr_start_date = strtotime($create_role->start_date);
        $cr_end_date = strtotime($create_role->end_date);
        $msgs = array();
        if ($cr_start_date > $start_date) {
            $msgs = ['start_date' => 'Start Date can`t be lesser than Role Start date | ' . $create_role->start_date];
        }
        if ($cr_end_date < $end_date) {
            $msgs+= ['end_date' => 'End Date can`t be greater than Role End date| ' . $create_role->end_date];
        }
        if ($start_date > $cr_end_date) {
            $msgs = ['start_date' => 'Start Date can`t be gereater than Role Start date | ' . $create_role->start_date];
        }
        if ($end_date < $cr_start_date) {
            $msgs+= ['end_date' => 'End Date can`t be lesser than Role End date | ' . $create_role->end_date];
        }
        if (count($msgs) > 0) {
            session()->flash('role_id', $roleid);
            return redirect('admin/assignroletoperson/create/')->withErrors($msgs)->withInput(Input::all());
        }


        $validationmessages = [
            'project_id.required' => 'please select project id',
            'role.required' => 'Please select role',
            'role_type.required' => 'role type is required',
            'role_fun.required' => 'role function is required',
            'start_date.required' => 'Please select Start Date',
            'end_date.required' => 'Please select End Date',
            'resource_name' => 'Please select Resource Name'
        ];

        $validator = Validator::make($assignrole, [
                    'project_id' => 'required',
                    'role' => 'required',
                    'role_type' => 'required',
                    'role_fun' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'resource_name' => 'required|unique:assignrole,resource_name,null,id,project_id,' . $assignrole['project_id'],
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            session()->flash('role_id', $roleid);
            return redirect('admin/assignroletoperson/create')->withErrors($validator)->withInput(Input::all());
        }

        Assignrole::create($assignrole);

        session()->flash('flash_message', 'Assign Role created successfully...');
        return redirect('admin/assignroletoperson');
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
        Roleauth::check('project.resourceplanning.assignrole.edit');

        $assignrole = Assignrole::find($id);



        $project = DB::table('project')
                ->where('company_id', Auth::user()->company_id)
                ->get();


        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->id] = $projectid->project_Id . ' ( ' . $projectid->project_desc . ' )';
        }

        $Role = Createrole::where('project_id', $assignrole->project_id)
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

        return view('admin.assignroletoperson.create', compact('resource_name', 'roles', 'assignrole', 'project_id'));
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
        Roleauth::check('project.resourceplanning.assignrole.edit');

        $assignrole = new Assignrole();

        $data_get = $request->only($assignrole->getEditable());
        $data_get['changed_by'] = Auth::user()->id;

        $start_date = strtotime($data_get['start_date']);
        $end_date = strtotime($data_get['end_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            return redirect('admin/assignroletoperson/' . $id . '/edit/')->withErrors($msgs)->withInput(Input::all());
        }

        //compare the start and end date with the role start and end date should be betweeen the range
        $create_role = Createrole::find($data_get['role']);
        $cr_start_date = strtotime($create_role->start_date);
        $cr_end_date = strtotime($create_role->end_date);
        $msgs = array();
        if ($cr_start_date > $start_date) {
            $msgs = ['start_date' => 'Start Date can`t be lesser than Role Start date | ' . $create_role->start_date];
        }
        if ($cr_end_date < $end_date) {
            $msgs+= ['end_date' => 'End Date can`t be greater than Role End date| ' . $create_role->end_date];
        }


        if ($start_date > $cr_end_date) {
            $msgs = ['start_date' => 'Start Date can`t be gereater than Role Start date | ' . $create_role->start_date];
        }
        if ($end_date < $cr_start_date) {
            $msgs+= ['end_date' => 'End Date can`t be lesser than Role End date | ' . $create_role->end_date];
        }
        if (count($msgs) > 0)
            return redirect('admin/assignroletoperson/' . $id . '/edit/')->withErrors($msgs)->withInput(Input::all());



        $validationmessages = [
            'project_id.required' => 'please select project id',
            'role.required' => 'Please select role',
            'role_type.required' => 'role type is required',
            'role_fun.required' => 'role function is required',
            'start_date.required' => 'Please select Start Date',
            'end_date.required' => 'Please select End Date',
            'resource_name' => 'Please select Resource Name'
        ];

        $validator = Validator::make($data_get, [
                    'project_id' => 'required',
                    'role' => 'required',
                    'role_type' => 'required',
                    'role_fun' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'resource_name' => 'required',
                        ], $validationmessages);


        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/assignroletoperson/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        Assignrole::find($id)->update($data_get);
        session()->flash('flash_message', 'assign role updated successfully...');
        return redirect('admin/assignroletoperson');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Roleauth::check('project.resourceplanning.assignrole.delete');

        $assignrole = Assignrole::find($id);
        $assignrole->delete();
        session()->flash('flash_message', 'Assign role deleted successfully...');
        return redirect('admin/assignroletoperson');
    }

    public function getrole()
    {
        $id = Input::all()['project_id'];
//        $rolename = DB::table('createrole')->select('role_name')->where('project_id', $request->project_id)->get();
////        print_r($rolename);
////        exit();
//
//
//        return response()->json($rolename);
        if (isset($id) and $id > 0) {
            $roles = Createrole::where('project_id', $id)->get()->toArray();

            $listRoles = array();

            if (isset($roles) && count($roles)) {

                foreach ($roles as $ProjectRoleList) {
                    array_push($listRoles, array($ProjectRoleList['id'], $ProjectRoleList['role_name'] . ' (' . \Illuminate\Support\Str::words($ProjectRoleList['role_fun'], 5, '....') . ')'));
                }

                echo json_encode(array('roleList' => $listRoles));
            }
        }
    }

    public function getroletype()
    {
        $id = Input::all()['role_name'];
//        $roletype = DB::table('createrole')->select('role_type')->where('id', $request->role_name)->get();
//
//
//
//        return response()->json($roletype);

        if (isset($id) && $id > 0) {
            $roletype = Createrole::where('id', $id)->get();

            $list = array();
            if (isset($roletype) && $roletype->count()) {

                foreach ($roletype as $role) {

                    $list[] = $role->role_type;
                    $list2[] = $role->role_fun;
                }
                echo json_encode(array('role_type' => $list, 'role_function' => $list2));
            } else
                echo json_encode(array('role_type' => array(''), 'role_function' => ''));
        }
    }

}
