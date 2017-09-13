<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use App\Roleauth;
use App\Timesheet_profile;

class TimesheetProfilesController extends Controller
{
	
    public function index()
    {
		Roleauth::check('time.timesheetp.index');

        $timesheet_profile = Timesheet_profile::where('company_id', Auth::user()->company_id)->get();
		return view('admin/timesheet_profile/timesheet_profile_list',array('timesheet_profile'=>$timesheet_profile));
    }
	
	public function timesheet_profile_form()
	{
		Roleauth::check('time.timesheetp.create');

		return view('admin/timesheet_profile/timesheet_profile_form');
	}
	
	public function timesheetprofile_save(Request $request)
	{
		Roleauth::check('time.timesheetp.create');

		$this->validate($request, [
        'time_sheet_entry_period' => 'required',
		'time_sheet_number_days'=> 'required',
		'status'=> 'required'
		]);
		
		Timesheet_profile::create([
			'time_sheet_entry_period' => $request->input('time_sheet_entry_period'),
			'time_sheet_number_days' => $request->input('time_sheet_number_days'),
			'time_sheet_description' => $request->input('time_sheet_description'),
			'status' => $request->input('status'),
			'created_by' => Auth::id(),
			'company_id' => Auth::user()->company_id
		]);
		
		$request->session()->flash('alert-success', 'Timesheet Profile was successful added!');
		return redirect('admin/timesheetprofiles');
	}
	
	public function timesheet_profile_edit_form($timesheet_profile)
	{
		Roleauth::check('time.timesheetp.edit');

		$timesheet_profile = Timesheet_profile::where('company_id', Auth::user()->company_id)->find($timesheet_profile);
        if (!isset($timesheet_profile)) {
            return redirect('admin/timesheetprofiles');
        }

		return view('admin/timesheet_profile/timesheet_profile_edit_form',compact('timesheet_profile'));
	}
	
	public function timesheetprofile_edit_save(Request $request, $timesheet_profile)
	{
		Roleauth::check('time.timesheetp.edit');

		$timesheet_profile = Timesheet_profile::where('company_id', Auth::user()->company_id)->find($timesheet_profile);
        if (!isset($timesheet_profile)) {
            return redirect('admin/timesheetprofiles');
        }
		
		$this->validate($request, [
        'time_sheet_entry_period' => 'required',
		'time_sheet_number_days'=> 'required',
		'status'=> 'required'
		]);

		$timesheet_profile->update([
			'time_sheet_entry_period' => $request->input('time_sheet_entry_period'),
			'time_sheet_number_days' => $request->input('time_sheet_number_days'),
			'time_sheet_description' => $request->input('time_sheet_description'),
			'status' => $request->input('status'),
			'updated_by' => Auth::id()
		]);
		
		$request->session()->flash('alert-success', 'Timesheet Profile was successful updated!');
		return redirect('admin/timesheetprofiles');
	}
	
	public function timesheetprofile_delete(Request $request, $timesheet_profile)
	{
		Roleauth::check('time.timesheetp.delete');
		
		$timesheet_profile = Timesheet_profile::where('company_id', Auth::user()->company_id)->find($timesheet_profile);
        if (!isset($timesheet_profile)) {
            return redirect('admin/timesheetprofiles');
        }

		$timesheet_profile_delete=$timesheet_profile->delete();
		if($timesheet_profile_delete){
		
			$request->session()->flash('alert-success', 'Timesheet Profile was successful deleted!');
		}
		else {
			$request->session()->flash('alert-danger', 'Timesheet Profile was not successfully deleted!');
		}
		return redirect('admin/timesheetprofiles');
	}
}
