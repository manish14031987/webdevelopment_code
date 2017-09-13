<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
//use Auth;
use App\Cost_centres;
use App\Activity_types;

class AdminController extends Controller {

    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function costcentre_list() {
        $cost_centre = Cost_centres::all();
        return view('admin/cost_centre/cost_centres_list', array('cost_centre' => $cost_centre));
    }

    public function costcentre_form() {
        return view('admin/cost_centre/cost_centre_form');
    }

    public function costcentre_save() {
        $this->validate($this->request, [
            'cost_centre' => 'required',
            'company_code' => 'required',
            'validity_start' => 'required',
            'validity_end' => 'required'
        ]);

        $cost_centre = new Cost_centres;
        $cost_centre->fill([
            'cost_centre' => $this->request->input('cost_centre'),
            'cost_description' => $this->request->input('cost_description'),
            'company_code' => $this->request->input('company_code'),
            'company_code_description' => $this->request->input('company_code_description'),
            'validity_start' => $this->request->input('validity_start'),
            'validity_end' => $this->request->input('validity_end'),
            'status' => $this->request->input('status'),
            'created_by' => Auth::id(),
            'company_id' => Auth::user()->company_id
        ]);
        $costcentre_saved = $cost_centre->save();

        if ($costcentre_saved) {

            $this->request->session()->flash('alert-success', 'Cost Centre was successful added!');
        } else {
            $this->request->session()->flash('alert-danger', 'Cost Centre was not successfully added!');
        }

        return redirect('admin/costcentres');
    }

    public function costcentre_edit_form(Cost_centres $cost_centre) {
        return view('admin/cost_centre/cost_centre_edit_form', array('cost_centre' => $cost_centre));
    }

    public function costcentre_edit_save(Cost_centres $cost_centre) {
        $this->validate($this->request, [
            'cost_centre' => 'required',
            'company_code' => 'required',
            'validity_start' => 'required',
            'validity_end' => 'required'
        ]);

        $cost_centre->fill($this->request->all());
        $costcentre_updated = $cost_centre->save();

        if ($costcentre_updated) {

            $this->request->session()->flash('alert-success', 'Cost Centre was successful updated!');
        } else {
            $this->request->session()->flash('alert-danger', 'Cost Centre was not successfully updated!');
        }

        return redirect('admin/costcentres');
    }

    public function costcentre_delete(Cost_centres $cost_centre) {
        $costcentre_delete = $cost_centre->delete();
        if ($costcentre_delete) {

            $this->request->session()->flash('alert-success', 'Cost Centre was successful deleted!');
        } else {
            $this->request->session()->flash('alert-danger', 'Cost Centre was not successfully deleted!');
        }
        return redirect('admin/costcentres');
    }

    public function activity_list() {
        $activity_type = Activity_types::all();
        return view('admin/activity_type/activity_list', array('activity_list' => $activity_type));
    }

    public function activity_type_form() {
        return view('admin/activity_type/activity_type_form');
    }

    public function activitytype_save() {
        $this->validate($this->request, [
            'activity_type' => 'required',
            'cost_element' => 'required',
            'validity_start' => 'required',
            'validity_end' => 'required'
        ]);

        $activity_type = new Activity_types;
        $activity_type->fill($this->request->all());
        $activity_type->company_id = Auth::user()->company_id;
        $activity_type->created_by = Auth::id();
        $activity_saved = $activity_type->save();

        if ($activity_saved) {

            $this->request->session()->flash('alert-success', 'Activity Type was successful added!');
        } else {
            $this->request->session()->flash('alert-danger', 'Activity Type was not successfully added!');
        }

        return redirect('admin/activitytypes');
    }

    public function activity_type_edit_form(Activity_types $activity_type) {
        return view('admin/activity_type/activity_type_edit_form', array('activity_type' => $activity_type));
    }

    public function activitytype_edit_save(Activity_types $activity_type) {
        $this->validate($this->request, [
            'activity_type' => 'required',
            'cost_element' => 'required',
            'validity_start' => 'required',
            'validity_end' => 'required'
        ]);

        $activity_type->fill($this->request->all());
        $activity_updated = $activity_type->save();

        if ($activity_updated) {

            $this->request->session()->flash('alert-success', 'Activity Type was successful updated!');
        } else {
            $this->request->session()->flash('alert-danger', 'Activity Type was not successfully updated!');
        }

        return redirect('admin/activitytypes');
    }

    public function activitytype_delete(Activity_types $activity_type) {
        $activity_delete = $activity_type->delete();
        if ($activity_delete) {

            $this->request->session()->flash('alert-success', 'Activity Type was successful deleted!');
        } else {
            $this->request->session()->flash('alert-danger', 'Activity Type was not successfully deleted!');
        }
        return redirect('admin/activitytypes');
    }

}
