<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Project;
use App\Employee_records;

class ResourceOverviewController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id = null)
    {

        //assigned with project details
        if ($id == 'resource') {

            $project_data = DB::table('personassignment')->where('personassignment.company_id', Auth::user()->company_id)
                    ->select(DB::raw('concat(employee_records.employee_first_name," ",employee_records.employee_middle_name," ",employee_records.employee_last_name) as resource_name,sum(tasks_subtask.total_demand) as total_demand,SUM(IF (DATEDIFF(personassignment.end_date,personassignment.start_date)!=0,(DATEDIFF(personassignment.end_date,personassignment.start_date)* 8 ), 8 ))  as assigned'))
                    ->join('tasks_subtask', 'personassignment.task', '=', 'tasks_subtask.id')
                    ->join('employee_records', 'personassignment.resource_name', '=', 'employee_records.employee_id')
                    ->where('total_demand', '!=', NULL)
                    ->groupBy('resource_name')
                    ->get();

            $arr = array();
            foreach ($project_data as $assign) {
                array_push($arr, array('project_id' => $assign->resource_name, 'assign' => $assign->assigned));
            }


            $project_data = DB::table('personassignment')->where('personassignment.company_id', Auth::user()->company_id)
                    ->select(DB::raw('CONCAT(employee_records.employee_first_name," ",employee_records.employee_middle_name," ",employee_records.employee_last_name) as resource_name,'
                                    . 'CONCAT(createrole.role_name,"(",createrole.id,")") as role_name,'
                                    . 'project.project_Id,sum(tasks_subtask.total_demand) as total_demand,'
                                    . 'tasks_subtask.phase_id,tasks_subtask.task_Id,personassignment.start_date,'
                                    . 'personassignment.end_date,'
                                    . 'sum(IF(DATEDIFF(personassignment.end_date,personassignment.start_date)!=0,(DATEDIFF(personassignment.end_date,personassignment.start_date)* 8 ), 8 )) as assigned,'
                                    . ' IF (DATEDIFF(personassignment.end_date,personassignment.start_date)!=0,(sum(tasks_subtask.total_demand) - (DATEDIFF(personassignment.end_date,personassignment.start_date)* 8)),sum(tasks_subtask.total_demand)-8) as remaining'))
                    ->leftjoin('tasks_subtask', 'personassignment.task', '=', 'tasks_subtask.id')
                    ->leftjoin('project', 'tasks_subtask.project_id', '=', 'project.project_Id')
                    ->leftjoin('createrole', 'personassignment.role', '=', 'createrole.id')
                    ->join('employee_records', 'personassignment.resource_name', '=', 'employee_records.employee_id')
                    ->groupBy('resource_name', 'role_name' ,'createrole.id','createrole.role_name', 'project.project_id', 'tasks_subtask.phase_id', 'tasks_subtask.task_Id', 'personassignment.start_date', 'personassignment.end_date')
                    ->get();

            $resources = Employee_records::all();
            $resource_id = [];
            foreach ($resources as $resource) {
                $resource_id [$resource->employee_first_name . ' ' . $resource->employee_middle_name . ' ' . $resource->employee_last_name] = $resource->employee_first_name . ' ' . $resource->employee_middle_name . ' ' . $resource->employee_last_name;
            }

            return view('admin.resourceoverview.index', compact('id', 'resource_id', 'arr', 'project_data'));
        } else {
            $project_data = DB::table('personassignment')->where('project.company_id', Auth::user()->company_id)
                    ->select(DB::raw('project.project_id,sum(tasks_subtask.total_demand) as total_demand,'
                                    . 'SUM(IF (DATEDIFF(personassignment.end_date,personassignment.start_date)!=0,(DATEDIFF(personassignment.end_date,personassignment.start_date)* 8 ), 8 ))  as assigned'))
                    ->join('tasks_subtask', 'personassignment.task', '=', 'tasks_subtask.id')
                    ->join('project', 'tasks_subtask.project_id', '=', 'project.project_Id')
                    ->where('total_demand', '!=', NULL)
                    ->groupBy('project.project_Id')
                    ->get();


//        $project_data = DB::table('personassignment')->where('project.company_id', Auth::user()->company_id)
//                ->select(DB::raw('project.project_id,sum(tasks_subtask.total_demand) as total_demand'))
//                ->join('tasks_subtask', 'personassignment.task', '=', 'tasks_subtask.id')
//                ->join('project', 'tasks_subtask.project_id', '=', 'project.project_id')
//                ->where('total_demand', '!=', NULL)
//                ->groupBy('project.project_id')
//                ->get();





            $arr = array();
            foreach ($project_data as $assign) {
                array_push($arr, array('project_id' => $assign->project_id, 'assign' => $assign->assigned));
            }


            $project_data = DB::table('personassignment')->where('project.company_id', Auth::user()->company_id)
                    ->select(DB::raw('project.project_Id,sum(tasks_subtask.total_demand) as total_demand,'
                                    . 'CONCAT(createrole.role_name,"(",createrole.id,")") as role_name,'
                                    . 'tasks_subtask.phase_id,tasks_subtask.task_Id,personassignment.start_date,'
                                    . 'personassignment.end_date,'
                                    . 'CONCAT(employee_records.employee_first_name," ",employee_records.employee_middle_name," ",employee_records.employee_last_name) as resource_name,'
                                    . 'sum(IF(DATEDIFF(personassignment.end_date,personassignment.start_date)!=0,(DATEDIFF(personassignment.end_date,personassignment.start_date)* 8 ), 8 )) as assigned,'
                                    . 'IF (DATEDIFF(personassignment.end_date,personassignment.start_date)!=0,(sum(tasks_subtask.total_demand) - (DATEDIFF(personassignment.end_date,personassignment.start_date)* 8)),sum(tasks_subtask.total_demand)-8) as remaining'))
                    ->leftjoin('tasks_subtask', 'personassignment.task', '=', 'tasks_subtask.id')
                    ->leftjoin('project', 'tasks_subtask.project_id', '=', 'project.project_Id')
                    ->leftjoin('createrole', 'personassignment.role', '=', 'createrole.id')
                    ->join('employee_records', 'personassignment.resource_name', '=', 'employee_records.employee_id')
                    ->groupBy('project.project_Id','role_name' ,'createrole.id','createrole.role_name', 'tasks_subtask.phase_id', 'tasks_subtask.task_Id', 'personassignment.start_date', 'personassignment.end_date', 'resource_name')
                    ->get();

            $project_id = [];
            $projects = Project::all();
            foreach ($projects as $project) {
                $project_id [$project->project_Id] = $project->project_Id;
            }
            return view('admin.resourceoverview.index', compact('id', 'project_id', 'arr', 'project_data'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

}
