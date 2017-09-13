<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Project;
use App\Admin_employees;
use App\Roleauth;

class ResourceLoadingController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id = null)
    {
        Roleauth::check('project.resourceplanning.resourceloading.index');

        $project_ids = Project::where('company_id', Auth::user()->company_id)->select(DB::raw("CONCAT(project_Id ,'  (',project_name,')') AS project_name"), 'id')->pluck('project_name', 'id');

        $from_date = date('Y-m-d', strtotime('today'));
        $to_date = date('Y-m-d', strtotime('15 days'));
        /**
         * Fetch graph data , DB query 
         * (total days - task assigned days) -> not assigned to person (days) 
         * person assigne to task -> task assigned (days)
         * assigne role to person -> role asssigned (days)
         * Total days -> 15|| diff between datepickers
         * @return array
         */
        $data1 = DB::table('employee_records')
                ->where('employee_records.company_id', Auth::user()->company_id)
                //->whereDate('assignrole.start_date', '>=', $from_date)
                ->whereDate('personassignment.start_date', '>=', $from_date)
                ->whereDate('personassignment.end_date', '<=', $to_date)
                ->orwhereDate('personassignment.start_date', '<', $from_date)
                ->orwhereDate('personassignment.end_date', '>', $to_date)
                ->select(DB::raw('sum(IF(DATEDIFF(personassignment.end_date,personassignment.start_date)!=0,(DATEDIFF(personassignment.end_date,personassignment.start_date)+1 ), 1 )) as assigned,'
                                . '0 as role_assigned,'
                                . 'CONCAT(employee_records.employee_first_name," ",employee_records.employee_middle_name," ",employee_records.employee_last_name) as employee,'
                                . '15 - sum(IF(DATEDIFF(personassignment.end_date,personassignment.start_date)!=0,(DATEDIFF(personassignment.end_date,personassignment.start_date)+1 ), 1 ))  as unassigned,'
                                . '15 as total_days,personassignment.resource_name'))
                ->join('personassignment', 'personassignment.resource_name', '=', 'employee_records.employee_id')
                ->groupBy('employee', 'personassignment.resource_name')
                ->get()
                ->toArray();

        $data2 = DB::table('employee_records')
                ->where('employee_records.company_id', Auth::user()->company_id)
                ->whereDate('assignrole.start_date', '>=', $from_date)
                ->whereDate('assignrole.end_date', '<=', $to_date)
                ->orwhereDate('assignrole.start_date', '<', $from_date)
                ->orwhereDate('assignrole.end_date', '>', $to_date)
                ->select(DB::raw('0 as assigned,'
                                . 'sum(IF(DATEDIFF(assignrole.end_date,assignrole.start_date)!=0,(DATEDIFF(assignrole.end_date,assignrole.start_date)+1 ), 1 )) as role_assigned,'
                                . 'CONCAT(employee_records.employee_first_name," ",employee_records.employee_middle_name," ",employee_records.employee_last_name) as employee,'
                                . '15 as unassigned,'
                                . '15 as total_days,assignrole.resource_name'))
                ->join('assignrole', 'assignrole.resource_name', '=', 'employee_records.employee_id')
                ->groupBy('employee', 'assignrole.resource_name')
                ->get()
                ->toArray();

        $data1 = json_decode(json_encode($data1), true);
        $data2 = json_decode(json_encode($data2), true);


        $bucket = [];
        $data_temp = $data2;
        foreach ($data1 as $index => $emp) {
            $bucket[] = array_keys(array_column($data2, 'resource_name'), $emp['resource_name'], true);
            foreach ($bucket[$index] as $key) {
                $data1[$index]['role_assigned'] = intval($data1[$index]['role_assigned']);
                $data2[$key]['role_assigned'] = intval($data2[$key]['role_assigned']);
                $data1[$index]['role_assigned'] = $data1[$index]['role_assigned'] + $data2[$key]['role_assigned'];
            }
        }

        foreach ($bucket as $index) {
            foreach ($index as $key)
                unset($data2[$key]);
        }

        $data = array_merge($data1, $data2);

        return view('admin.resource_load.index', compact('project_ids', 'from_date', 'to_date', 'data'));
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
    public function show(Request $request, $id)
    {
        $to_date = date('Y-m-d', strtotime(isset($request->only('to_date')['to_date']) ? $request->only('to_date')['to_date'] : 'today'));
        $from_date = date('Y-m-d', strtotime(isset($request->only('from_date')['from_date']) ? ($request->only('from_date')['from_date']) : '15 days'));
        $diff = date_diff(date_create($from_date), date_create($to_date));

        if (intVal($diff->format("%R%a")) < 0) {

            return response()->json(['status' => 'msg', 'msg' => 'From Date cannot be greater than To Date!']);
        }

        $diff = $diff->days + 1;

        $project_to = ($request->only('ProjectTo')['ProjectTo'] != '') ? $request->only('ProjectTo')['ProjectTo'] : 1000;
        $project_from = ($request->only('ProjectFrom')['ProjectFrom'] != '') ? $request->only('ProjectFrom')['ProjectFrom'] : 1;



        $data1 = DB::table('employee_records')
                ->where('employee_records.company_id', Auth::user()->company_id)
                ->where('personassignment.project_id', '>=', $project_from)
                ->where('personassignment.project_id', '<=', $project_to)
                ->whereDate('personassignment.start_date', '>=', $from_date)
                ->whereDate('personassignment.end_date', '<=', $to_date)
                ->orwhereDate('personassignment.start_date', '<', $from_date)
                ->orwhereDate('personassignment.end_date', '>', $to_date)
                ->select(DB::raw('sum(IF(DATEDIFF(personassignment.end_date,personassignment.start_date)!=0,(DATEDIFF(personassignment.end_date,personassignment.start_date)+1 ), 0 )) as assigned,'
                                . '0 as role_assigned,'
                                . 'CONCAT(employee_records.employee_first_name," ",employee_records.employee_middle_name," ",employee_records.employee_last_name) as employee,'
                                . $diff . ' - sum(IF(DATEDIFF(personassignment.end_date,personassignment.start_date)!=0,(DATEDIFF(personassignment.end_date,personassignment.start_date)+1 ), 1 ))  as unassigned,'
                                . $diff . ' as total_days,personassignment.resource_name'))
                ->join('personassignment', 'personassignment.resource_name', '=', 'employee_records.employee_id')
                ->groupBy('employee', 'personassignment.resource_name')
                ->get()
                ->toArray();

        $data2 = DB::table('employee_records')
                ->where('employee_records.company_id', Auth::user()->company_id)
                ->where('assignrole.project_id', '>=', $project_from)
                ->where('assignrole.project_id', '<=', $project_to)
                ->whereDate('assignrole.start_date', '>=', $from_date)
                ->whereDate('assignrole.end_date', '<=', $to_date)
                ->orwhereDate('assignrole.start_date', '<', $from_date)
                ->orwhereDate('assignrole.end_date', '>', $to_date)
                ->select(DB::raw('0 as assigned,'
                                . 'sum(IF(DATEDIFF(assignrole.end_date,assignrole.start_date)!=0,(DATEDIFF(assignrole.end_date,assignrole.start_date)+1 ), 1 )) as role_assigned,'
                                . 'CONCAT(employee_records.employee_first_name," ",employee_records.employee_middle_name," ",employee_records.employee_last_name) as employee,'
                                . $diff . ' as unassigned,'
                                . $diff . ' as total_days,assignrole.resource_name'))
                ->join('assignrole', 'assignrole.resource_name', '=', 'employee_records.employee_id')
                ->groupBy('employee', 'assignrole.resource_name')
                ->get()
                ->toArray();

        $data1 = json_decode(json_encode($data1), true);
        $data2 = json_decode(json_encode($data2), true);
      
        $bucket = [];
        $data_temp = $data2;
        foreach ($data1 as $index => $emp) {
           
            $bucket[] = array_keys(array_column($data2, 'resource_name'), $emp['resource_name'], true);
            foreach ($bucket[$index] as $key) {

                $data1[$index]['role_assigned'] = intval($data1[$index]['role_assigned']);
                $data2[$key]['role_assigned'] = intval($data2[$key]['role_assigned']);
                $data1[$index]['role_assigned'] = $data1[$index]['role_assigned'] + $data2[$key]['role_assigned'];
                
            }
        }

        foreach ($bucket as $index) {
            foreach ($index as $key)
                unset($data2[$key]);
        }


        $data = array_merge($data1, $data2);

        return response()->json(['status' => 'ok', 'data' => $data]);
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
