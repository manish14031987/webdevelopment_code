<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Projectresourceplan;
use App\Createrole;
use App\Project;
use Illuminate\Support\Facades\DB;
use App\Roleauth;

class ProjectresourceplanController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Roleauth::check('project.resourceplanning.index');

        $resourceData = DB::table('createrole')
                ->select(DB::raw('CONCAT(employee_records.employee_first_name, " ", employee_records.employee_middle_name," ",employee_records.employee_last_name) AS resource_name'), 'createrole.role_name', 'tasks_subtask.task_Id As task_id','tasks_subtask.task_name As task_name', 'project.project_Id AS project_id', 'project.project_name AS project_desc')
                ->rightjoin('assignrole', 'createrole.id', '=', 'assignrole.role')
                ->leftjoin('employee_records', 'assignrole.resource_name', '=', 'employee_records.employee_id')
                ->rightjoin('taskassign', 'createrole.id', '=', 'taskassign.role')
                ->join('tasks_subtask', 'taskassign.task', '=', 'tasks_subtask.id')
                ->join('project', 'assignrole.project_id', '=', 'project.id')
                ->get();


   
//                exit();
//        exit;
//        $resourceName = array();
//       
//        foreach($resourceData as $key1 => $valueData)
//        {
//            $resourceName = isset($valueData->full_name) ?  $valueData->full_name : '';
//        }
        return view('admin.projectresourceplan.index', compact('resourceData'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        return view('admin.projectresourceplan.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'resource_plan_Id' => 'required',
            'resource_plan_name' => 'required',
            'resource_plan_type' => 'required',
            'project_id' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'template' => 'required',
            'reference_phase' => 'required',
            'quality_approval' => 'required',
        ]);
        Projectresourceplan::create($request->all());

        session()->flash('flash_message', 'Project resource planning created successfully...');
        return redirect('admin/projectresourceplan');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $projectresourceplan = Projectresourceplan::find($id);
        return view('admin.projectresourceplan.create', compact('projectresourceplan'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $projectresourceplan = Projectresourceplan::find($id);
        $this->validate($request, [
            'resource_plan_Id' => 'required',
            'resource_plan_name' => 'required',
            'resource_plan_type' => 'required',
            'project_id' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'template' => 'required',
            'reference_phase' => 'required',
            'quality_approval' => 'required',
        ]);
        $projectresourceplan->update($request->all());
        session()->flash('flash_message', 'Project resource planning updated successfully...');
        return redirect('admin/projectresourceplan');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $projectresourceplan = Projectresourceplan::find($id);
        $projectresourceplan->delete();
        session()->flash('flash_message', 'Project resource planning deleted successfully...');
        return redirect('admin/projectresourceplan');
    }

}
