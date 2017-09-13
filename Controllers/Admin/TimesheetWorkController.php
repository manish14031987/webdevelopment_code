<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Carbon\Carbon;
use Validator;
use Auth;
use App\Employee_records;
use App\Timesheet_profile;
use App\Timesheet_approver;
use App\Project;
use App\Projecttask;
use App\Timesheet_week;
use App\Timesheet_day;

class TimesheetWorkController extends Controller
{

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index()
    {
        $login_employee_data = null;
        $login_approvers_data = null;
        $employees_data = null;
        $employee_profile_weeksnumber = null;
        $per_week_dates = null;
        $all_weeks = null;
        $from_date = null;
        $last_date = null;
        $employee_id = null;

        $user = Auth::user();
        $user_role_id = $user->role_id;

        $selected_date = date('d-m-Y');

        if ($user_role_id != 1) {
            $login_employee_data = Employee_records::where('company_id',Auth::user()->company_id)->where('employee_user_id', $user->id)->first();
            if (!empty($login_employee_data)) {
                $login_approvers_data = Timesheet_approver::wheretime_sheet_user_id($login_employee_data->employee_id)->first();
                $employee_profile_weeksnumber = isset($login_employee_data->timesheet_profile_name->time_sheet_number_days)?$login_employee_data->timesheet_profile_name->time_sheet_number_days:'';
                $employee_id = isset($login_employee_data->employee_id)?$login_employee_data->employee_id:'';
            }
        } else {
            $employees_data = Employee_records::orderBy('employee_first_name', 'asc')->get();
            $first_employee = collect($employees_data)->first();
            $employee_id = $first_employee->employee_id;

            $employee_profile_weeksnumber = isset($first_employee->timesheet_profile_name->time_sheet_number_days)?$first_employee->timesheet_profile_name->time_sheet_number_days:'';
        }

        if (!(empty($employee_id))) {
            if (!empty($employee_profile_weeksnumber)) {
                $current_date = date('Y-m-d');
                for ($wd = 0; $wd < $employee_profile_weeksnumber; $wd++) {
                    $interval = '+' . $wd . ' week';
                    $per_week_dates[] = date('Y-m-d', strtotime($interval, strtotime($current_date)));
                }

                $all_weeks = array();
                $from_to_dates = array();
                foreach ($per_week_dates as $dat) {
                    $dt = Carbon::parse($dat);

                    $year = $dt->year;
                    $week_number = $dt->weekOfYear;
                    $weeknum_padded = sprintf("%02d", $week_number);
                    $dt->setISODate($year, $weeknum_padded);

                    $start_date = date('Y-m-d', strtotime($dt->startOfWeek()));
                    $end_date = date('Y-m-d', strtotime($dt->endOfWeek()));

                    $current = strtotime($start_date);
                    $last = strtotime($end_date);
                    $step = '+1 day';
                    $output_format = 'Y-m-d';
                    $all_dates = array();
                    while ($current <= $last) {
                        $from_to_dates[] = date($output_format, $current);
                        $all_dates[] = date($output_format, $current);
                        $current = strtotime($step, $current);
                    }

                    $all_weeks[$weeknum_padded] = $all_dates;
                }
                $from_date = $from_to_dates[0];
                $last_date = $from_to_dates[count($from_to_dates) - 1];

                $week_numbers = array_keys($all_weeks);

                $weeks_timesheet_data = Timesheet_week::whereIn('week_number', $week_numbers)
                        ->where('employee_id', $employee_id)
                        ->get();

                $days_timesheet_data = Timesheet_day::whereIn('week_number', $week_numbers)
                        ->where('employee_id', $employee_id)
                        ->get();
                        
                return view('admin/timesheet_work/timesheet_view', array('employee_data' => $employees_data, 'week_dates' => $all_weeks, 'login_employee' => $login_employee_data, 'login_approvers_data' => $login_approvers_data, 'from_date' => $from_date, 'to_date' => $last_date, 'employee_id' => $employee_id, 'timesheet_week_data' => $weeks_timesheet_data, 'timesheet_day_data' => $days_timesheet_data, 'selected_date' => $selected_date));
            }
        } else {
            return view('admin/timesheet_work/timesheet_view', array('employee_id' => $employee_id));
        }
    }

    public function showdata()
    {
        $employees_data = Employee_records::all();
        $employee_id = $this->request->input('employee_id');
        $start_date = $this->request->input('period_start');

        $var = $start_date;
        $start_date = str_replace('/', '-', $var);
        $selected_date = $start_date;
        //echo date('Y-m-d', strtotime($start_date));
        //$start_timestamp = strtotime($start_date);
        $new_date = date('Y-m-d', strtotime($start_date));
        //$new_start_timestamp=strtotime($new_date);

        $login_employee_data = Employee_records::find($employee_id)->first();
        $login_approvers_data = Timesheet_approver::wheretime_sheet_user_id($employee_id)->first();
        $employee_profile_weeksnumber = $login_employee_data->timesheet_profile_name->time_sheet_number_days;


        // code to get the weekly dates
        if (!(empty($employee_id))) {
            if (!empty($employee_profile_weeksnumber)) {
                $current_date = $new_date;
                //echo date('Y-m-d', strtotime('+0 week', strtotime($date))); 
                for ($wd = 0; $wd < $employee_profile_weeksnumber; $wd++) {
                    $interval = '+' . $wd . ' week';
                    $per_week_dates[] = date('Y-m-d', strtotime($interval, strtotime($current_date)));
                }
                //echo "<pre/>";
                //print_r($per_week_dates);


                $all_weeks = array();
                $from_to_dates = array();
                foreach ($per_week_dates as $dat) {
                    $dt = Carbon::parse($dat);

                    $year = $dt->year;
                    $week_number = $dt->weekOfYear;
                    $weeknum_padded = sprintf("%02d", $week_number);
                    $dt->setISODate($year, $weeknum_padded);

                    $start_date = date('Y-m-d', strtotime($dt->startOfWeek()));
                    $end_date = date('Y-m-d', strtotime($dt->endOfWeek()));

                    $current = strtotime($start_date);
                    $last = strtotime($end_date);
                    $step = '+1 day';
                    $output_format = 'Y-m-d';
                    $all_dates = array();
                    while ($current <= $last) {
                        $from_to_dates[] = date($output_format, $current);
                        $all_dates[] = date($output_format, $current);
                        $current = strtotime($step, $current);
                    }

                    //$from_dates = array_column($all_dates, 0);
                    //$to_dates = array_column($all_dates, 6);
                    //print_r(array_keys($all_dates));
                    //foreach($all_dates as $ke=>$val)
                    //{
                    //echo "key ". $ke." Val ".$val;
                    //}
                    //echo "<pre/>". $current." Counts all ". count($all_dates, COUNT_RECURSIVE); // output 8;
                    //print_r($all_dates);

                    $all_weeks[$weeknum_padded] = $all_dates;


                    //echo "start date". $dt->startOfWeek(); // 2016-10-17 00:00:00.000000
                    //echo "End date" .$dt->endOfWeek(); // 2016-10-23 23:59:59.000000
                    //echo "week number" .$weeknum_padded; // 2016-10-23 23:59:59.000000
                }
                //echo "<pre/>";
                $from_date = $from_to_dates[0];
                $last_date = $from_to_dates[count($from_to_dates) - 1];
                //echo "From ".$from_date." To date is ".$last_date;
                //print_r($from_to_dates);
                //weekly dates ends here
                // code ends here
                //die();
                // code to show the current date with week days
                /* $mytime = Carbon::now();
                  $dt = Carbon::parse($mytime);
                  $year=$dt->year;
                  $week_number=$dt->weekOfYear;
                  $weeknum_padded = sprintf("%02d", $week_number);
                  $total_str_parameter=$year.'W'.$weeknum_padded;
                  $week_start_date=date('M d',strtotime($total_str_parameter));
                  echo "Week Number is".$dt->weekOfYear .' week start date '.$week_start_date;

                  $mytime->setISODate($year,$weeknum_padded); // 2016-10-17 23:59:59.000000
                  $start_date=date('Y-m-d',strtotime($dt->startOfWeek()));
                  $end_date=date('Y-m-d',strtotime($dt->endOfWeek()));

                  $dates = array();
                  $current = strtotime($start_date);
                  $last = strtotime($end_date);
                  $step = '+1 day';
                  $output_format = 'Y-m-d';

                  while( $current <= $last ) {

                  $dates[] = date($output_format, $current);
                  $current = strtotime($step, $current);
                  } */

                //echo "start date and end date ".$start_date."end date ".$end_date.'week number '.$week_number;
                // code ends here
                //$employees_data = Employee_records::all()->sortByDesc("employee_first_name");
                //return view('admin/timesheet_work/timesheet_view',array('employee_data'=>$employees_data,'week_dates'=>$all_weeks,'week_number'=>$weeknum_padded,'login_employee'=>$login_employee_data));
                // code to fetch the week abd days data for particular employee from database tables
                //echo "<pre/>"	;
                //print_r($all_weeks);
                //print_r(array_keys($all_weeks));

                $week_numbers = array_keys($all_weeks);

                $weeks_timesheet_data = Timesheet_week::whereIn('week_number', $week_numbers)
                        ->where('employee_id', $employee_id)
                        ->get();
                /* if($weeks_timesheet_data->first())
                  //if(count($weeks_timesheet_data))
                  {
                  $timesheet_week_data=$weeks_timesheet_data;
                  }else{
                  $timesheet_week_data=null;
                  } */

                $days_timesheet_data = Timesheet_day::whereIn('week_number', $week_numbers)
                        ->where('employee_id', $employee_id)
                        ->get();
                /* if(!$days_timesheet_data->isEmpty())	
                  //if(count($days_timesheet_data))
                  {
                  $timesheet_day_data=$days_timesheet_data;
                  }else{
                  $timesheet_day_data=null;
                  } */

                //echo "<pre/>";
                //print_r($timesheet_week_data);
                //print_r($timesheet_day_data);
                //die();
                // code ends here
                //die();
                return view('admin/timesheet_work/timesheet_view', array('employee_data' => $employees_data, 'week_dates' => $all_weeks, 'login_employee' => $login_employee_data, 'login_approvers_data' => $login_approvers_data, 'from_date' => $from_date, 'to_date' => $last_date, 'employee_id' => $employee_id, 'timesheet_week_data' => $weeks_timesheet_data, 'timesheet_day_data' => $days_timesheet_data, 'selected_date' => $selected_date));
            }
        } else {
            return view('admin/timesheet_work/timesheet_view', array('employee_id' => $employee_id));
        }
    }

    public function entry_form()
    {
        $employee_id = $this->request->input('employee_id_timesheet');
        $submit_date = $this->request->input('submit');

        $days_data = explode('@#$', $submit_date);

        $entry_date = $days_data[0];
        $week_number = $days_data[1];
        $week_dates = $days_data[2];

        $dates_info = explode('-', $week_dates);
        $year = $dates_info[0];
        $month = $dates_info[1];

        $login_approvers_data = Timesheet_approver::wheretime_sheet_user_id($employee_id)->first();

        // code to fetch the week timesheet data
        $week_timesheet_data = Timesheet_week::where('week_number', $week_number)
                ->where('employee_id', $employee_id)
                ->where('week_year', $year)
                ->pluck("timesheet_week_id");

        $days_timesheet_data = Timesheet_day::where('day_date', $entry_date)
                ->where('employee_id', $employee_id)
                ->get();
                
        $project_data = Project::where('status', 'active')
                ->orderBy('project_Id', 'asc')
                ->get();
        if (count($project_data) == 0) {
            $project_data = null;
        }

        // code ends here
        // code for fetching all the tasks
        $project_tasks = Projecttask::where('status', '!=', 'Completed')
                ->orderBy('task_Id', 'asc')
                ->get();

        if (count($project_tasks) == 0) {
            $project_tasks = null;
        }
        
        $login_employee_data = Employee_records::find($employee_id);

        return view('admin/timesheet_work/timesheet_entry', array('login_employee' => $login_employee_data, 'week_number' => $week_number, 'entry_date' => $entry_date, 'project_data' => $project_data, 'week_dates' => $week_dates, 'login_approvers_data' => $login_approvers_data, 'from_date' => $entry_date, 'to_date' => $entry_date, 'timesheet_day_data' => $days_timesheet_data, 'project_tasks' => $project_tasks, 'timesheet_week_data' => $week_timesheet_data));
    }

    public function ajax_check()
    {
        $project_id = $this->request->input('project_id');
        $project = Project::where('Id', $project_id)->first();
        $project_desc = $project->project_desc;

        //$matchThese = ['field' => 'value', 'another_field' => 'another_value', ...];
        // if you need another group of wheres as an alternative:
        //$orThose = ['yet_another_field' => 'yet_another_value', ...];
        //$results = User::where($matchThese)->get();

        $matchThese = ['project_id' => $project->project_Id];
        $project_tasks = Projecttask::where($matchThese)
                ->where('status', '!=', 'Completed')
                ->pluck("id", "task_Id");
        if (empty($project_tasks)) {
            $project_tasks = null;
        }

        $data = array('project_desc' => $project_desc, 'project_tasks' => $project_tasks);
        //       ->orderBy('name', 'desc')
        //       ->take(10)
        //       ->get();
        //print_r($project_tasks);
        //$msg = "This is a simple message.".$project_tasks;
        //return $msg;
        //return response()->json(array('project_tasks'=> $project_tasks), 200);
        return json_encode($data);

        //$data = $this->request->all(); // This will get all the request data.
        //dd($data); // This will dump and die
    }

    public function task_description()
    {
        $project_task_id = $this->request->input('project_task_id');
        return $project_task_id;
        $project_task_desc = Projecttask::where('Id', $project_id)->pluck('project_desc');
        echo "task id is " . $project_task_id;
    }

    public function timesheetentry_save()
    {
        $all_data = $this->request->all();
        //dd($all_data);

        $validator = Validator::make($this->request->all(), [
                    "project_id" => 'required|array|min:1',
                    "project_id.*" => 'required',
        ]);

        $employee_id = $this->request->input('employee_id');
        $employee_cost_centre = $this->request->input('employee_cost_centre');
        $employee_activity_type = $this->request->input('employee_activity_type');
        $employee_timesheet_profile = $this->request->input('employee_timesheet_profile');

        $approver_id = $this->request->input('approver_id');
        $created_by = $this->request->input('created_by');
        $changed_by = $this->request->input('changed_by');

        $week_number = $this->request->input('week_number');
        $week_dates = $this->request->input('week_dates');
        $day_name = $this->request->input('day_name');
        $day_date = $this->request->input('day_date');

        $dates_info = explode('-', $day_date);
        $week_year = $dates_info[0];
        //$year=2018;
        $week_month = $dates_info[1];

        $project_ids = $this->request->input('project_id');
        $project_descriptions = $this->request->input('project_desc');
        $task_ids = $this->request->input('project_task');
        $task_descriptions = $this->request->input('project_task_desc');

        $billables = $this->request->input('project_billable');

        $project_task_start_times = $this->request->input('project_task_start_time');
        $project_task_finish_times = $this->request->input('project_task_finish_time');

        $project_task_lunch_nonworked_times = $this->request->input('project_lunch_nonworked_time');

        $project_task_worked_times = $this->request->input('project_worked_time');

        $project_worked_string = implode(',', $project_task_worked_times);
        //echo "<pre>";
        //echo "sum(a) = " . implode(',',$project_task_worked_times) . "\n";
        $project_worked_string = str_replace(':', '.', $project_worked_string);
        $new_project_worked = explode(',', $project_worked_string);
        $dynamic_column_day_total_time = $day_name . '_total_time';
        //echo "sum(a) = " . array_sum($new_project_worked) . "\n" .$dynamic_column_day_total_time."Total Projects ".count($project_ids);
        //${$day_name.'_start_time'}=>$project_task_start_times[$i]
        //print_r($new_project_worked);
        //dd($all_data);
        $day_total_worked = array_sum($new_project_worked);
        $dynamic_column_day_total_time = $day_name . '_total_time';

        $timesheet_day_ids = $this->request->input('timesheet_day_id');
        $timesheet_week_id = $this->request->input('timesheet_week_id');

        $check_timesheet_day = array_filter($timesheet_day_ids);
        //dd($check_timesheet_day);
        // code for insertion in the timesheet week and day entry
        if (!empty($project_ids)) {
            if (empty($timesheet_week_id)) {
                $timesheet_week = new Timesheet_week;
            } else {
                $timesheet_week = Timesheet_week::find($timesheet_week_id);
            }

            $timesheet_week->employee_id = $employee_id;
            $timesheet_week->week_number = $week_number;
            $timesheet_week->week_year = $week_year;
            $timesheet_week->week_month = $week_month;
            $timesheet_week->week_dates = $week_dates;
            $timesheet_week->$dynamic_column_day_total_time = $day_total_worked;
            $timesheet_week->employee_cost_centre = $employee_cost_centre;
            $timesheet_week->employee_activity_type = $employee_activity_type;
            $timesheet_week->employee_timesheet_profile = $employee_timesheet_profile;
            $timesheet_week->approver_id = $approver_id;

            if (empty($timesheet_week_id)) {
                $timesheet_week->created_by = $created_by;
            } else {
                $timesheet_week->changed_by = $changed_by;
            }

            $week_save_status = $timesheet_week->save();
            //dd($all_data);

            if ($week_save_status) {


                //echo "Successfull insertion with ID -".$timesheet_week->timesheet_week_id;
                // code for making entry in the timesheet day
                $array_task_start_time = $day_name . "_start_time";
                $array_task_end_time = $day_name . "_end_time";
                $array_task_lunch_time = $day_name . "_lunch_time";
                $array_task_worked_time = $day_name . "_worked_time";
                $array_task_time = $day_name . "_time";

                $day_data = [];
                for ($i = 0; $i < count($project_ids); $i++) {

                    $project_desc = null;
                    $project_tasks_ids = null;
                    $task_desc = null;
                    $billable = null;
                    $project_task_start_time = null;
                    $project_task_finish_time = null;
                    $project_task_lunch_nonworked_time = null;
                    $project_task_worked_time = null;
                    $worked_time = null;
                    $project_id = null;
                    $check = null;
                    $timesheet_day_id = null;

                    if (!empty($project_ids[$i])) {
                        $project_id = $project_ids[$i];


                        if (!empty($project_descriptions[$i])) {
                            $project_desc = $project_descriptions[$i];
                        }

                        if (!empty($task_ids[$i])) {
                            $project_tasks_ids = $task_ids[$i];
                        }

                        if (!empty($task_descriptions[$i])) {
                            $task_desc = $task_descriptions[$i];
                        }

                        if (!empty($billables[$i])) {
                            $billable = $billables[$i];
                        }

                        if (!empty($project_task_start_times[$i])) {
                            $project_task_start_time = $project_task_start_times[$i];
                        }

                        if (!empty($project_task_finish_times[$i])) {
                            $project_task_finish_time = $project_task_finish_times[$i];
                        }

                        if (!empty($project_task_lunch_nonworked_times[$i])) {
                            $project_task_lunch_nonworked_time = $project_task_lunch_nonworked_times[$i];
                        }

                        if (!empty($project_task_worked_times[$i])) {
                            $project_task_worked_time = $project_task_worked_times[$i];
                        }

                        if ((!empty($new_project_worked[$i]))) {
                            $worked_time = $new_project_worked[$i];
                        }

                        if ((!empty($timesheet_day_ids[$i]))) {
                            $timesheet_day_id = $timesheet_day_ids[$i];
                        }

                        $day_time_data = Timesheet_day::find($timesheet_day_id);
                        if (empty($day_time_data)) {
                            $day_time_data_obj = new Timesheet_day;
                            $day_time_data_obj->week_id = $timesheet_week->timesheet_week_id;
                            $day_time_data_obj->employee_id = $employee_id;
                            $day_time_data_obj->week_number = $week_number;
                            $day_time_data_obj->project_id = $project_id;
                            $day_time_data_obj->project_description = $project_desc;
                            $day_time_data_obj->task_id = $project_tasks_ids;
                            $day_time_data_obj->task_description = $task_desc;
                            $day_time_data_obj->day_date = $day_date;
                            $day_time_data_obj->billable = $billable;
                            $day_time_data_obj->$array_task_start_time = $project_task_start_time;
                            $day_time_data_obj->$array_task_end_time = $project_task_finish_time;
                            $day_time_data_obj->$array_task_lunch_time = $project_task_lunch_nonworked_time;
                            $day_time_data_obj->$array_task_worked_time = $project_task_worked_time;
                            $day_time_data_obj->$array_task_time = $worked_time;
                            $day_time_data_obj->employee_cost_centre = $employee_cost_centre;
                            $day_time_data_obj->employee_activity_type = $employee_activity_type;
                            $day_time_data_obj->employee_timesheet_profile = $employee_timesheet_profile;
                            $day_time_data_obj->created_by = $created_by;
                            $check = $day_time_data_obj->save();
                        } else {

                            $day_time_data->project_id = $project_id;
                            $day_time_data->project_description = $project_desc;
                            $day_time_data->task_id = $project_tasks_ids;
                            $day_time_data->task_description = $task_desc;
                            $day_time_data->day_date = $day_date;
                            $day_time_data->billable = $billable;
                            $day_time_data->$array_task_start_time = $project_task_start_time;
                            $day_time_data->$array_task_end_time = $project_task_finish_time;
                            $day_time_data->$array_task_lunch_time = $project_task_lunch_nonworked_time;
                            $day_time_data->$array_task_worked_time = $project_task_worked_time;
                            $day_time_data->$array_task_time = $worked_time;
                            $day_time_data->changed_by = $changed_by;
                            $check = $day_time_data->save();
                        }


                        /* if(empty($timesheet_day_id))	
                          {
                          $day_data[]= array (
                          'week_id'=>$timesheet_week->timesheet_week_id,
                          'employee_id'=>$employee_id,
                          'week_number'=>$week_number,
                          'project_id'=>$project_id,
                          'project_description'=>$project_desc,
                          'task_id'=>$project_tasks_ids,
                          'task_description'=>$task_desc,
                          'day_date'=>$day_date,
                          'billable'=>$billable,
                          $array_task_start_time=>$project_task_start_time,
                          $array_task_end_time=>$project_task_finish_time,
                          $array_task_lunch_time=>$project_task_lunch_nonworked_time,
                          $array_task_worked_time=>$project_task_worked_time,
                          $array_task_time=>$worked_time,
                          'employee_cost_centre'=>$employee_cost_centre,
                          'employee_activity_type'=>$employee_activity_type,
                          'employee_timesheet_profile'=>$employee_timesheet_profile,
                          'created_by'=>$created_by,
                          'created_at'=>date("Y-m-d H:i:s")
                          );
                          $day_time_data = new Timesheet_day;

                          $day_time_data->week_id = $timesheet_week->timesheet_week_id;
                          $day_time_data->employee_id = $employee_id;
                          $day_time_data->week_number = $week_number;
                          $day_time_data->project_id = $project_id;
                          $day_time_data->project_description = $project_desc;
                          $day_time_data->task_id = $project_tasks_ids;
                          $day_time_data->task_description = $task_desc;
                          $day_time_data->day_date = $day_date;
                          $day_time_data->billable = $billable;
                          $day_time_data->$array_task_start_time = $project_task_start_time;
                          $day_time_data->$array_task_end_time = $project_task_finish_time;
                          $day_time_data->$array_task_lunch_time = $project_task_lunch_nonworked_time;
                          $day_time_data->$array_task_worked_time = $project_task_worked_time;
                          $day_time_data->$array_task_time = $worked_time;
                          $day_time_data->employee_cost_centre = $employee_cost_centre;
                          $day_time_data->employee_activity_type = $employee_activity_type;
                          $day_time_data->employee_timesheet_profile = $employee_timesheet_profile;
                          $day_time_data->created_by = $created_by;
                          }

                          // code to update the timesheet day ids
                          if(!empty($timesheet_day_id))
                          {
                          $day_time_data = Timesheet_day::find($timesheet_day_id);

                          $day_time_data->project_id = $project_id;
                          $day_time_data->project_description = $project_desc;
                          $day_time_data->task_id = $project_tasks_ids;
                          $day_time_data->task_description = $task_desc;
                          $day_time_data->day_date = $day_date;
                          $day_time_data->billable = $billable;
                          $day_time_data->$array_task_start_time = $project_task_start_time;
                          $day_time_data->$array_task_end_time = $project_task_finish_time;
                          $day_time_data->$array_task_lunch_time = $project_task_lunch_nonworked_time;
                          $day_time_data->$array_task_worked_time = $project_task_worked_time;
                          $day_time_data->$array_task_time = $worked_time;
                          $day_time_data->changed_by = $changed_by;

                          }
                          // code ends here */
                    }
                }


                //dd($day_time_data);
                /* if(empty($check_timesheet_day)) // code to check if timesheet day id not edit but insert
                  {
                  $check=Timesheet_day::insert($day_data);
                  }
                 */
                if ($check) {
                    $this->request->session()->flash('alert-success', 'Timesheet entry was successfully added!');
                } else {
                    $this->request->session()->flash('alert-danger', 'Timesheet entry was not successfully added!');
                }
                // code ends here
            } else {
                $this->request->session()->flash('alert-danger', 'Timesheet entry was not successfully added!');
            }
            //echo "<pre/>";
            //print_r($day_data);
        } else {
            $this->request->session()->flash('alert-danger', 'Please choose the projects!');
        }

        // code ends here
        return redirect('admin/timesheetview');
    }

    public function date_range($first, $last, $step = '+1 day', $output_format = 'd/m/Y')
    {

        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);

        while ($current <= $last) {

            $dates[] = date($output_format, $current);
            $current = strtotime($step, $current);
        }

        return $dates;
    }

}
