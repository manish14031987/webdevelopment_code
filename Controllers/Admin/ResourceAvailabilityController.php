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

class ResourceAvailabilityController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id = null)
    {
        $resource_ids = Employee_records::where('company_id', Auth::user()->company_id)->select(DB::raw("CONCAT(employee_first_name,' ',employee_middle_name,' ',employee_last_name) AS employee_name"), 'employee_id')->pluck('employee_name', 'employee_id');

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
        $days = '';
        for ($i = 1; $i <= 90; $i++) {
            $days .= ',sum(personassignment.day' . $i . ') as day' . $i;
        }
        //print_r($days);
        $datum = DB::table('employee_records')
                ->where('employee_records.company_id', Auth::user()->company_id)
                ->whereDate('personassignment.start_date', '>=', $from_date)
                ->whereDate('personassignment.end_date', '<=', $to_date)
                //->orwhereDate('personassignment.start_date', '<', $from_date)
                //->orwhereDate('personassignment.end_date', '>', $to_date)
                ->select(DB::raw('personassignment.start_date,personassignment.end_date,CONCAT(employee_records.employee_first_name," ",employee_records.employee_middle_name," ",employee_records.employee_last_name) as employee,'
                                . 'personassignment.resource_name'
                                . $days))
                ->join('personassignment', 'personassignment.resource_name', '=', 'employee_records.employee_id')
                ->groupBy('employee', 'personassignment.resource_name', 'personassignment.start_date', 'personassignment.end_date')
                ->get()
                ->toArray();
        $datum = json_decode(json_encode($datum), true);


        $result = [];
        foreach ($datum as $index => $data) {

            if (in_array($data['resource_name'], array_column($result, 'resource_name'))) {
                $key = array_search($data['resource_name'], array_column($result, 'resource_name'));
                //print('found at :' . $key);
                //echo "\r\n";

                $startdate = strtotime($data['start_date']);
                $enddate = strtotime($data['end_date']);
                $i = 1;
                while ($startdate <= $enddate) {


                    if (isset($result[$key][date("Y-m-d", $startdate)]))
                        $result[$key][date("Y-m-d", $startdate)] += $data['day' . $i];
                    else
                        $result[$key][date("Y-m-d", $startdate)] = (int) $data['day' . $i];

                    $startdate = strtotime("+1 day", $startdate);
                    $i++;
                }
            } else {
                $result[] = ['resource_name' => $data['resource_name'], 'employee' => $data['employee']];

                $startdate = strtotime($data['start_date']);
                $enddate = strtotime($data['end_date']);
                $i = 1;
                while ($startdate <= $enddate) {

                    if (isset($result[$index][date("Y-m-d", $startdate)]))
                        $result[$index][date("Y-m-d", $startdate)] += $data['day' . $i];
                    else
                        $result[$index][date("Y-m-d", $startdate)] = (int) $data['day' . $i];

                    $startdate = strtotime("+1 day", $startdate);
                    $i++;
                }
            }
        }

        //test data from local data
        $result = isset($result[0]) ? $result[0] : [];
        $resource_name = isset($result['resource_name']) ? $result['resource_name'] : '';
        return view('admin.resource_availiblity.index', compact('resource_ids', 'from_date', 'to_date', 'resource_name', 'result'));
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
        $id = isset($request->only('resource_id')['resource_id']) ? $request->only('resource_id')['resource_id'] : '';


        $days = '';
        for ($i = 1; $i <= 90; $i++) {
            $days .= ',sum(personassignment.day' . $i . ') as day' . $i;
        }
        //print_r($days);
        $datum = DB::table('employee_records')
                ->where('employee_records.company_id', Auth::user()->company_id)
                ->where('personassignment.resource_name', $id)
                ->whereDate('personassignment.start_date', '>=', $from_date)
                ->whereDate('personassignment.end_date', '<=', $to_date)
                //->orwhereDate('personassignment.start_date', '<', $from_date)
                // ->orwhereDate('personassignment.end_date', '>', $to_date)
                ->select(DB::raw('personassignment.start_date,personassignment.end_date,CONCAT(employee_records.employee_first_name," ",employee_records.employee_middle_name," ",employee_records.employee_last_name) as employee,'
                                . 'personassignment.resource_name'
                                . $days))
                ->join('personassignment', 'personassignment.resource_name', '=', 'employee_records.employee_id')
                ->groupBy('employee', 'personassignment.resource_name', 'personassignment.start_date', 'personassignment.end_date')
                ->get()
                ->toArray();

        //print_r($datum);
        $datum = json_decode(json_encode($datum), true);


        $result = [];
        foreach ($datum as $index => $data) {

            if (in_array($data['resource_name'], array_column($result, 'resource_name'))) {
                $key = array_search($data['resource_name'], array_column($result, 'resource_name'));
                //print('found at :' . $key);
                //echo "\r\n";

                $startdate = strtotime($data['start_date']);
                $enddate = strtotime($data['end_date']);
                $i = 1;
                while ($startdate <= $enddate) {


                    if (isset($result[$key][date("Y-m-d", $startdate)]))
                        $result[$key][date("Y-m-d", $startdate)] += $data['day' . $i];
                    else
                        $result[$key][date("Y-m-d", $startdate)] = (int) $data['day' . $i];

                    $startdate = strtotime("+1 day", $startdate);
                    $i++;
                }
            } else {
                $result[] = ['resource_name' => $data['resource_name'], 'employee' => $data['employee']];

                $startdate = strtotime($data['start_date']);
                $enddate = strtotime($data['end_date']);
                $i = 1;
                while ($startdate <= $enddate) {

                    if (isset($result[$index][date("Y-m-d", $startdate)]))
                        $result[$index][date("Y-m-d", $startdate)] += $data['day' . $i];
                    else
                        $result[$index][date("Y-m-d", $startdate)] = (int) $data['day' . $i];

                    $startdate = strtotime("+1 day", $startdate);
                    $i++;
                }
            }
        }

        //test data from local data
        $result = isset($result[0]) ? $result[0] : [];
        //$resource_name = isset($result['resource_name']) ? $result['resource_name'] : '';
        //$result = $result[2];
        //print_r(isset($result[0])?$result[0]:[]);die();
        return response()->json(['status' => 'ok', 'data' => $result]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return response()->json(['status' => 'ok', 'data' => $data]);
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
