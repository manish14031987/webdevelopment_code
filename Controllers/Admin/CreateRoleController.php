<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Createrole;
use App\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Roleauth;

class CreateRoleController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Roleauth::check('project.resourceplanning.role.index');

        $createrole = Createrole::where('company_id', Auth::user()->company_id)->get();
        
        foreach($createrole  as $key => $value)
        {
            $project = Project::Where('id', $value->project_id)->first();            
            $createrole[$key]->project_id = isset($project->project_Id) ? $project->project_Id .' (' . $project->project_name . ')' : '';
        }
        return view('admin.createrole.index', compact('createrole'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Roleauth::check('project.resourceplanning.role.create');

        $project = DB::table('project')
                ->where('company_id', Auth::user()->company_id)
                ->get();


        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->id] = $projectid->project_Id . ' ( ' . $projectid->project_desc . ' )';
        }


        return view('admin.createrole.create', compact('project_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Roleauth::check('project.resourceplanning.role.create');

        $createrole = Input::all();
        $createrole['created_by'] = Auth::user()->id;
        $createrole['company_id'] = Auth::user()->company_id;

        $start_date = strtotime($createrole['start_date']);
        $end_date = strtotime($createrole['end_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            return redirect('admin/createrole/create/')->withErrors($msgs)->withInput(Input::all());
        }

        //compare the start and end date with the role start and end date should be betweeen the range
        $project = Project::find($createrole['project_id']);
        $cr_start_date = strtotime(explode(' ', $project->start_date)[0]);
        $cr_end_date = strtotime(explode(' ', $project->end_date)[0]);
        $msgs = array();
        if (!($cr_start_date <= $start_date)) {
            $msgs = ['start_date' => 'Start Date can`t be lesser than Project validity | ' . explode(' ', $project->start_date)[0]];
        }

        if ($project->end_date != '' && $project->end_date != 'NULL' && !($cr_end_date >= $end_date)) {
            $msgs+= ['end_date' => 'End Date can`t be greater than Project validity | ' . explode(' ', $project->end_date)[0]];
        }
        if (count($msgs) > 0)
            return redirect('admin/createrole/create/')->withErrors($msgs)->withInput(Input::all());



        $validationmessages = [
            'project_id.required' => 'please select project id',
            'role_name.required' => 'Please enter role name',
            'createrole.unique' => 'Please enter unique role name',
            'role_type.required' => 'Please select role type',
            'description.required' => 'Please enter description less than 191 character',
            'role_fun.required' => 'Please select role function',
        ];

        $validator = Validator::make($createrole, [
                    'project_id' => 'required',
                    'role_name' => 'required|unique:createrole,role_name,null,id,project_id,' . $createrole['project_id'],
                    'description' => 'required|max:191',
                    'role_fun' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/createrole/create')->withErrors($validator)->withInput(Input::all());
        }

        $role = Createrole::where(['project_id' => $createrole['project_id'], 'role_name' => $createrole['role_name']])->get()->toArray();
//         print_r($role);
//         exit();
        if (count($role) > 0) {
            session()->flash('flash_error', ' Role  Name alredy Exist for thi Project');
            return redirect('admin/createrole/create')->withInput(Input::all());
        }

        Createrole::create($createrole);

        session()->flash('flash_message', ' Role created successfully...');
        return redirect('admin/createrole');
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
        Roleauth::check('project.resourceplanning.role.edit');

        $createrole = Createrole::find($id);


        $project = DB::table('project')
                ->where('company_id',  Auth::user()->company_id)
                ->get();


        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->id] = $projectid->project_Id . ' ( ' . $projectid->project_desc . ' )';
        }

        return view('admin.createrole.create', compact('createrole', 'project_id'));
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
        Roleauth::check('project.resourceplanning.role.edit');

        $createrole = Createrole::find($id);
        $data_get = $request->only($createrole->getEditable());
        $data_get['changed_by'] = Auth::user()->id;

        if ($data_get['role_name'] != $createrole->role_name) {
            $validation = '|unique:createrole,role_name,null,id,project_id,' . $data_get['project_id'];
        } else {
            $validation = '';
        }

        $start_date = strtotime($data_get['start_date']);
        $end_date = strtotime($data_get['end_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            return redirect('admin/createrole/' . $id . '/edit/')->withErrors($msgs)->withInput(Input::all());
        }

        //compare the start and end date with the role start and end date should be betweeen the range
        $project = Project::find($data_get['project_id']);
        $cr_start_date = strtotime(explode(' ', $project->start_date)[0]);
        $cr_end_date = strtotime(explode(' ', $project->end_date)[0]);
        $msgs = array();
        if ($cr_start_date != false)
            if (!($cr_start_date <= $start_date)) {
                $msgs = ['start_date' => 'Start Date can`t be lesser than Project validity | ' . explode(' ', $project->start_date)[0]];
            }
        if ($cr_end_date != false)
            if (!($cr_end_date >= $start_date)) {
                $msgs = ['start_date' => 'Start Date can`t be greater than Project End Date | ' . explode(' ', $project->end_date)[0]];
            }


        if ($project->end_date != false && $project->end_date != 'NULL' && !($cr_end_date >= $end_date)) {
            $msgs+= ['end_date' => 'End Date can`t be greater than Project validity | ' . explode(' ', $project->end_date)[0]];
        }
        if ($project->end_date != false && $project->end_date != 'NULL' && !($cr_start_date <= $end_date)) {
            $msgs+= ['end_date' => 'End Date can`t be lesser than Project Start Date | ' . explode(' ', $project->start_date)[0]];
        }
        if (count($msgs) > 0)
            return redirect('admin/createrole/' . $id . '/edit')->withErrors($msgs)->withInput(Input::all());


        $validationmessages = [
            'project_id.required' => 'please select project id',
            'role_name.required' => 'Please enter role name',
            'role_type.required' => 'Please select role type',
            'description.required' => 'Please enter description less than 240 character',
            'role_fun.required' => 'Please select role function',
        ];

        $validator = Validator::make($data_get, [
                    'project_id' => 'required',
                    'role_name' => 'required' . $validation,
                    'role_type' => 'required',
                    'description' => 'required|max:191',
                    'role_fun' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/createrole/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }
        $createrole->update($data_get);
        session()->flash('flash_message', ' role updated successfully...');
        return redirect('admin/createrole');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Roleauth::check('project.resourceplanning.role.delete');

        $createrole = Createrole::find($id);
        $createrole->delete();
        session()->flash('flash_message', 'role deleted successfully...');
        return redirect('admin/createrole');
    }

}
