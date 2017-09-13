<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;

//use Auth;
use App\Cost_centres;
use App\Activity_types;
use App\Activity_rates;
use App\Employee_records;

class ActivityRatesController extends Controller
{
    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }
	
    public function index()
    {
        $activity_rates = Activity_rates::all();
		return view('admin/activity_rates/activity_rates_list',array('activity_rates'=>$activity_rates));		
    }
	
	public function activity_rate_form()
	{
		$cost_centre = Cost_centres::all();
		$activity_type = Activity_types::all();
		$employee_data = Employee_records::all();
		
		return view('admin/activity_rates/activity_rate_form',array('cost_centre'=>$cost_centre,'activity_type'=>$activity_type,'employee_data'=>$employee_data));
	}
	
	public function activityrate_save()
	{
		$this->validate($this->request, [
        'activity_actual_rate' => 'required',
        'activity_plan_rate' => 'required',
		'activity_validity_start'=> 'required',
		'activity_validity_end'=> 'required'
		]);
		
		$activity_rate = new Activity_rates;
		$activity_rate->fill($this->request->all());
        $activity_rate_saved=$activity_rate->save();
		//dd( $request->all() );
		//$input = $request->all();
		
		if($activity_rate_saved){
		
			$this->request->session()->flash('alert-success', 'Activity Rate was successful added!');
		}
		else {
			$this->request->session()->flash('alert-danger', 'Activity Rate was not successfully added!');
		}
		
		return redirect('admin/activityrates');
	}
	
	public function activity_rate_edit_form(Activity_rates $activity_rate)
	{
		$cost_centre = Cost_centres::all();
		$activity_type = Activity_types::all();
		$employee_data = Employee_records::all();
		
		return view('admin/activity_rates/activity_rate_edit_form',array('cost_centre'=>$cost_centre,'activity_type'=>$activity_type,'employee_data'=>$employee_data,'activity_rate'=>$activity_rate));
	}
	
	public function activityrate_edit_save(Activity_rates $activity_rate)
	{
		$this->validate($this->request, [
        'activity_actual_rate' => 'required',
        'activity_plan_rate' => 'required',
		'activity_validity_start'=> 'required',
		'activity_validity_end'=> 'required'
		]);
		
		$activity_rate->fill($this->request->all());
        $activity_rate_updated=$activity_rate->save();
		//dd( $request->all() );
		//$input = $request->all();
		
		if($activity_rate_updated){
		
			$this->request->session()->flash('alert-success', 'Activity Rate was successful updated!');
		}
		else {
			$this->request->session()->flash('alert-danger', 'Activity Rate was not successfully updated!');
		}
		
		return redirect('admin/activityrates');
	}
	
	public function activityrate_delete(Activity_rates $activity_rate)
	{
		$activity_rate_delete=$activity_rate->delete();
		if($activity_rate_delete){
		
			$this->request->session()->flash('alert-success', 'Activity Rate was successful deleted!');
		}
		else {
			$this->request->session()->flash('alert-danger', 'Activity Rate was not successfully deleted!');
		}
		return redirect('admin/activityrates');
	}
}
