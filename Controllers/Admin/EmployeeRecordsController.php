<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\country;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use App\Cost_centres;
use App\Activity_types;
use App\Timesheet_profile;
use App\Employee_records;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Roleauth;

class EmployeeRecordsController extends Controller {

    public function index() {
        Roleauth::check('time.epr.index');

        $employees_data = Employee_records::where('company_id',Auth::user()->company_id)->get();
        
        return view('admin/employee/employee_records', compact('employees_data'));
    }

    public function employee_form() {
        Roleauth::check('time.epr.create');

        $users_data = User::where('company_id',Auth::user()->company_id)
            ->select(DB::raw("CONCAT(name,' ',lname) AS full_name"), 'id')
            ->pluck('full_name','id')
            ->prepend('Please select user', '');
        $cost_centre = Cost_centres::where('company_id',Auth::user()->company_id)->get();
        $activity_type = Activity_types::where('company_id',Auth::user()->company_id)->get();
        $timesheet_profile = Timesheet_profile::where('company_id',Auth::user()->company_id)->get();
        $country_data = country::all()->pluck('country_name', 'country_name');

        return view('admin/employee/employee_form', compact('users_data', 'cost_centre', 'activity_type', 'timesheet_profile' , 'country_data'));
    }

    public function employee_save(Request $request) {
        Roleauth::check('time.epr.create');

        $validationmessages = [
            'employee_first_name.required' => "Please enter first name",
            'employee_middle_name.required' => "Please enter middle name",
            'employee_last_name.required' => "Please enter last name",
            'email_id.required' => 'Please enter email',
            'email_id.unique' => 'This email already used, please enter another',
            'employee_dob.required' => 'Please enter date of birth',
            'employee_type.required' => "Please select employee type",
            'employee_cost_centre.required' => "Please select cost center",
            'employee_activity_type.required' => "Please select activity type",
            //'employee_timesheet_profile.required' => "Please select timesheet profile",
            'employee_birth_country.required' => "Please select birth country"
        ];

        $validator = Validator::make($request->all(), [
            'employee_first_name' => "required",
            'employee_middle_name' => "required",
            'employee_last_name' => "required",
            'email_id' => 'required|email|unique:employee_records',
            'employee_dob' => "required",
            'employee_type' => "required",
            'employee_cost_centre' => "required",
            'employee_activity_type' => "required",
            //  'employee_timesheet_profile' => "required",
            'employee_birth_country' => "required"
        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addemployee')->withErrors($validator)->withInput(Input::all());
        }

        Employee_records::create([
            'employee_first_name' => $request->input('employee_first_name'),
            'employee_middle_name' => $request->input('employee_middle_name'),
            'employee_last_name' => $request->input('employee_last_name'),
            'email_id' => $request->input('email_id'),
            'employee_user_id' => $request->input('employee_user_id'),
            'employee_dob' => $request->input('employee_dob'),
            'employee_birth_country' => $request->input('employee_birth_country'),
            'employee_type' => $request->input('employee_type'),
            'employee_role' => $request->input('employee_role'),
            'employee_cost_centre' => $request->input('employee_cost_centre'),
            'employee_activity_type' => $request->input('employee_activity_type'),
            'employee_timesheet_profile' => $request->input('employee_timesheet_profile'),
            'employee_start' => $request->input('employee_start'),
            'employee_end' => $request->input('employee_end'),
            'status' => $request->input('status'),
            'company_id' => Auth::user()->company_id,
            'created_by' => Auth::id()
        ]);

        $request->session()->flash('alert-success', 'Employee was successful added!');
        return redirect('admin/employees');
    }

    public function employee_edit_form($employee_data) {
        Roleauth::check('time.epr.edit');
        $employee_data = Employee_records::where('company_id', Auth::user()->company_id)->find($employee_data);
        if (!isset($employee_data)) {
            return redirect('admin/employees');
        }

        $users_data = User::where('company_id',Auth::user()->company_id)
            ->select(DB::raw("CONCAT(name,' ',lname) AS full_name"), 'id')
            ->pluck('full_name','id')
            ->prepend('Please select user', '');
        $cost_centre = Cost_centres::where('company_id',Auth::user()->company_id)->get();
        $activity_type = Activity_types::where('company_id',Auth::user()->company_id)->get();
        $timesheet_profile = Timesheet_profile::where('company_id',Auth::user()->company_id)->get();
        $country_data = country::all()->pluck('country_name', 'country_name');

        return view('admin/employee/employee_edit_form', compact('users_data', 'employee_data', 'cost_centre', 'activity_type', 'timesheet_profile', 'country_data'));
    }

    public function employee_edit_save(Request $request, $employee_data) {
        Roleauth::check('time.epr.edit');
        $employee_data = Employee_records::where('company_id', Auth::user()->company_id)->find($employee_data);
        if (!isset($employee_data)) {
            return redirect('admin/employees');
        }

        $validationmessages = [
            'employee_first_name.required' => "Please enter first name",
            'employee_middle_name.required' => "Please enter middle name",
            'employee_last_name.required' => "Please enter last name",
            'email_id.required' => 'Please enter email',
            'employee_type.required' => "Please select employee type",
            'employee_cost_centre.required' => "Please select cost center",
            'employee_activity_type.required' => "Please select activity type",
          //  'employee_timesheet_profile.required' => "Please select timesheet profile",
            'employee_birth_country.required' => "Please select birth country"
        ];

        $validator = Validator::make($request->all(), [
            'employee_first_name' => "required",
            'employee_middle_name' => "required",
            'employee_last_name' => "required",
            'email_id' => 'required|email',
            'employee_type' => "required",
            'employee_cost_centre' => "required",
            'employee_activity_type' => "required",
        //    'employee_timesheet_profile' => "required",
            'employee_birth_country' => "required"
        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/editemployee/' . $employee_data->employee_id)->withErrors($validator)->withInput(Input::all());
        }
        $emp = new Employee_records();
        $get_data = $request->only($emp->getEditable());
        $get_data['updated_by'] = Auth::id();
        $employee_data->fill($get_data);
        $employee_updated = $employee_data->save();

        if ($employee_updated) {

            $request->session()->flash('alert-success', 'Employee was successful updated!');
        } else {
            $request->session()->flash('alert-danger', 'Employee was not successfully updated!');
        }

        return redirect('admin/employees');
    }

    public function employee_delete(Request $request, $employee_data) {
        Roleauth::check('time.epr.delete');
        $employee_data = Employee_records::where('company_id', Auth::user()->company_id)->find($employee_data);
        if (!isset($employee_data)) {
            return redirect('admin/employees');
        }

        $employee_delete = $employee_data->delete();
        if ($employee_delete) {
            $request->session()->flash('alert-success', 'Employee was successful deleted!');
        } else {
            $request->session()->flash('alert-danger', 'Employee was not successfully deleted!');
        }
        return redirect('admin/employees');
    }

    public function employee_export_cs() {
        Roleauth::check('time.epr.export');
        
        // echo "<pre>";
        // print_r($portfolio);exit;

        $header = "Personnel ID" . "\t";
        $header .= "Name" . "\t";
        $header .= "DOB" . "\t";
        $header .= "Birth Country" . "\t";
        $header .= "Employee Type" . "\t";
        $header .= "Cost Centre" . "\t";
        $header .= "Activity Type" . "\t";
        $header .= "Timesheet Profile" . "\t";
        $header .= "Start Date" . "\t";
        $header .= "End Date" . "\t";
        $header .= "Created By" . "\t";
        $header .= "Created At" . "\t";
        $header .= "Edited By" . "\t";
        $header .= "Updated At" . "\t";

        print "$header\n";

        $employees = Employee_records::where('company_id', Auth::user()->company_id)->get();

        foreach ($employees as $employ_data) {
            // echo "<pre>";

            $row1 = array();
            $row1[] = $employ_data->employee_id;
            $row1[] = $employ_data->employee_first_name . ' ' . $employ_data->employee_middle_name . ' ' . $employ_data->employee_last_name;
            $row1[] = $employ_data->employee_dob;
            $row1[] = $employ_data->employee_birth_country;
            $row1[] = $employ_data->employee_type;
            $row1[] = $employ_data->cost_centre->cost_centre;
            $row1[] = $employ_data->activity_type->activity_type;
            $row1[] = $employ_data->timesheet_profile_name->time_sheet_profile_number;
            $row1[] = $employ_data->employee_start;
            $row1[] = $employ_data->employee_end;
            $row1[] = $employ_data->creator->name;
            $row1[] = $employ_data->created_at;
            $row1[] = $employ_data->updator->name;
            $row1[] = $employ_data->updated_at;

            $data = join("\t", $row1) . "\n";


            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Employees.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data";
        }
    }

}
