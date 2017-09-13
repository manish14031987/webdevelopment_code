<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;

use App\Timesheet_approver;
use App\Employee_records;
use App\Roleauth;

class TimesheetapproverController extends Controller
{
    public function index()
    {
		Roleauth::check('time.timesheetapprovals.index');

        $timesheet_approver = Timesheet_approver::where('company_id', Auth::user()->company_id)->get();
		return view('admin/timesheet_approval/timesheet_approval_list',array('timesheet_approver'=>$timesheet_approver));		
    }
	
	public function timesheet_approver_form()
	{
		Roleauth::check('time.timesheetapprovals.create');

		$employees_list = Employee_records::where('company_id', Auth::user()->company_id)->get();
		return view('admin/timesheet_approval/timesheet_approver_form',array('employees_list'=>$employees_list));
	}
	
	public function timesheetapprover_save(Request $request)
	{
		Roleauth::check('time.timesheetapprovals.create');

		$this->validate($request, [
        'time_sheet_user_id' => 'required',
        'time_sheet_approver_id' => 'required'
		]);
		
		//dd( $request->all() );
		
		Timesheet_approver::create([
			'time_sheet_user_id' => $request->input('time_sheet_user_id'),
			'time_sheet_approver_id' => $request->input('time_sheet_approver_id'),
			'created_by' => Auth::id(),
			'company_id' => Auth::user()->company_id
		]);
		
		$request->session()->flash('alert-success', 'Timesheet Approver was successful added!');
		return redirect('admin/timesheetapprovals');
	}	
	
	public function timesheet_approver_edit_form(Request $request, $timesheet_approver)
	{
		Roleauth::check('time.timesheetapprovals.edit');
		$timesheet_approver = Timesheet_approver::where('company_id', Auth::user()->company_id)->find($timesheet_approver);
		if(!isset($timesheet_approver)) {
			return redirect('admin/timesheetapprovals');
		}

		$employees_list = Employee_records::where('company_id', Auth::user()->company_id)->get();
		return view('admin/timesheet_approval/timesheet_approver_edit_form',array('timesheet_approver'=>$timesheet_approver,'employees_list'=>$employees_list));
	}
	
	public function timesheetapprover_edit_save(Request $request, $timesheet_approver)
	{
		Roleauth::check('time.timesheetapprovals.edit');
		$timesheet_approver = Timesheet_approver::where('company_id', Auth::user()->company_id)->find($timesheet_approver);
		if(!isset($timesheet_approver)) {
			return redirect('admin/timesheetapprovals');
		}

		$this->validate($request, [
        'time_sheet_user_id' => 'required',
        'time_sheet_approver_id' => 'required'
		]);
		
		//dd( $request->all() );
				
		$timesheet_approver->update([
			'time_sheet_user_id' => $request->input('time_sheet_user_id'),
			'time_sheet_approver_id' => $request->input('time_sheet_approver_id'),
			'created_by' => Auth::id()
		]);
		
		$request->session()->flash('alert-success', 'Timesheet Approver was successful updated!');
		return redirect('admin/timesheetapprovals');
	}
	
	public function timesheetapprover_delete(Request $request, $timesheet_approver)
	{
		Roleauth::check('time.timesheetapprovals.delete');
		$timesheet_approver = Timesheet_approver::where('company_id', Auth::user()->company_id)->find($timesheet_approver);
		if(!isset($timesheet_approver)) {
			return redirect('admin/timesheetapprovals');
		}

		$timesheet_approver_delete=$timesheet_approver->delete();
		if($timesheet_approver_delete){
		
			$request->session()->flash('alert-success', 'Timesheet Approver was successful deleted!');
		}
		else {
			$request->session()->flash('alert-danger', 'Timesheet Approver was not successfully deleted!');
		}
		return redirect('admin/timesheetapprovals');
	}
	
}
