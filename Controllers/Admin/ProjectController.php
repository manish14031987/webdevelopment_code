<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use App\Portfolio;
use App\Project;
use App\Portfoliotype;
use App\Buckets;
use App\projecttype;
use App\location;
use App\Currency;
use App\Personresponsible;
use App\Factorycalendar;
use App\Costcentretype;
use App\Departmenttype;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Roleauth;
use App\Projectphase;

class ProjectController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($projectId = null) {
        Roleauth::check('project.index');

        $project = Project::where('company_id', Auth::user()->company_id)->with('projectType')->with('portfolioId')->with('portfolioType')->with('bucketId')->with('locationId')->with('costCentre')->with('departmentType')->with('user')->get();
        $portId = null;
        if ($projectId != null) {
            $proj = Project::where('company_id', Auth::user()->company_id)->find($projectId);
            if ($proj)
                $portId = $proj->portfolio_id;
        }
        return view('admin.project.index', compact('project', 'projectId', 'portId'));
    }
	
	public function dashboard() {
	
		return view('admin.project.dashboard');
    
	}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        Roleauth::check('project.create');

        $portfolio = DB::table('portfolio')
                        ->where('company_id', Auth::user()->company_id)->get();

        $ptype = array();
        foreach ($portfolio as $project) {
            $ptype[$project->id] = $project->port_id . ' ( ' . $project->description . ' )';
        }

        $buckt = DB::table('buckets')->where('company_id', Auth::user()->company_id)->get();
		
        $buckets = array();
        foreach ($buckt as $project) {
            $buckets[$project->id] = $project->bucket_id . '(' . $project->description . ')';
        }
		
        $projectType = Projecttype::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $location = Location::where('company_id', Auth::user()->company_id)->pluck("subrub", "id")->prepend('Please select', '');
        $currency = Currency::where('company_id', Auth::user()->company_id)->pluck("fullname", "short_code")->prepend('Please select', '');
        $personresponsible = Personresponsible::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $factorycalendar = Factorycalendar::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $cost_centre = Costcentretype::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $department = Departmenttype::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');



        $port = array();
        $temp = Portfoliotype::where('company_id', Auth::user()->company_id)->pluck('name', 'id');
        foreach ($temp as $key => $value) {
            $port[$key] = $value;
        }
        return view('admin.project.create', compact('port', 'ptype', 'buckets', 'projectType', 'location', 'currency', 'personresponsible', 'factorycalendar', 'cost_centre', 'department'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        Roleauth::check('project.create');
        
        $data_get = $request->all();

        $validationmessages = [
            'project_id.required' => 'please select project id',
            'project_type.required' => 'Please select project type',
            'project_desc.required' => 'Please enter description less than 191 character',
            'bucket_id.required' => 'Please select bucket id',
            'start_date.required' => 'Please select start date',
        ];

        $validator = Validator::make($data_get, [
                    'project_Id' => 'required',
                    'project_type' => 'required',
                    'project_desc' => 'required | max:191',
                    'bucket_id' => 'required',
                    'start_date' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/project/create')->withErrors($validator)->withInput(Input::all());
        }

        Project::create([
            'project_Id' => $request->input('project_Id'),
            'project_name' => $request->input('project_name'),
            'project_type' => $request->input('project_type'),
            'project_desc' => $request->input('project_desc'),
            'portfolio_id' => $request->input('portfolio_id'),
            'portfolio_type' => $request->input('portfolio_type'),
            'bucket_id' => $request->input('bucket_id'),
            'start_date' => $request->input('start_date'),
            'created_by' => Auth::id(),
            'status' => $request->input('status'),
            'company_id' => Auth::user()->company_id
        ]);

        session()->flash('flash_message', 'Project created successfully...');
        return redirect('admin/project');
    }

    public function getportname(Request $request) {

        $portname = DB::table('portfolio')->select('name')->where('id', $request->port_id)->where('company_id', Auth::user()->company_id)->first();


        return response()->json($portname);
    }

    public function getbucketname(Request $request) {

        $name = DB::table('buckets')->select('name')->where('id', $request->bucket_id)->where('company_id', Auth::user()->company_id)->first();


        return response()->json($name);
    }

    public function getpdesc(Request $request) {
        $desc = DB::table('portfolio')->select('description')->where('id', $request->port_id)->where('company_id', Auth::user()->company_id)->first();

        return response()->json($desc);
    }

    public function getbdesc(Request $request) {
        $desc = DB::table('buckets')->select('description')->where('id', $request->bucket_id)->where('company_id', Auth::user()->company_id)->first();

        return response()->json($desc);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        Roleauth::check('project.edit');
        $project = Project::where('company_id', Auth::user()->company_id)->find($id);
        if (!isset($project)) {
            return redirect('admin/project');
        }

//        print_r($project);exit;
        $createdby = $project->created_by != '' ? User::where('company_id', Auth::user()->company_id)->where('id', $project->created_by)->first()['original']['name'] : '';
        $modifiedby = $project->modified_by != '' ? User::where('company_id', Auth::user()->company_id)->where('id', $project->modified_by)->first()['original']['name'] : '';

        $currency = Currency::where('company_id', Auth::user()->company_id)->pluck("fullname", "short_code")->prepend('Please select', '');

        $projectType = projecttype::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $location = Location::where('company_id', Auth::user()->company_id)->pluck("subrub", "id")->prepend('Please select', '');
        $personresponsible = Personresponsible::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $factorycalendar = Factorycalendar::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $cost_centre = Costcentretype::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $department = Departmenttype::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');


        $portfolio = DB::table('portfolio')
                        ->where('company_id', Auth::user()->company_id)->get();

        $ptype = array();
        foreach ($portfolio as $port) {

            $ptype[$port->id] = $port->port_id . ' ( ' . $port->description . ' )';
        }

        $buckt = DB::table('buckets')->where('company_id', Auth::user()->company_id)->get();

        $buckets = array();
        foreach ($buckt as $pro) {
            $buckets[$pro->id] = $pro->bucket_id . '(' . $pro->description . ')';
        }

        $port = array();
        $temp = Portfoliotype::where('company_id', Auth::user()->company_id)->pluck('name', 'id');
        foreach ($temp as $key => $value) {
            $port[$key] = $value;
        }

        return view('admin.project.create', compact('modifiedby', 'createdby', 'port', 'project', 'ptype', 'buckets', 'projectType', 'currency', 'location', 'personresponsible', 'factorycalendar', 'cost_centre', 'department'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        Roleauth::check('project.edit');
        $project = Project::where('company_id', Auth::user()->company_id)->find($id);
        if (!isset($project)) {
            return redirect('admin/project');
        }

        $data_get = $request->only($project->getEditable());

        $start_date = strtotime($data_get['start_date']);
        $end_date = strtotime($data_get['end_date']);
        if ($start_date > $end_date) {
            $msgs = ['start_date' => 'Start Date can`t be greater than End Date'];
            return redirect('admin/project/' . $id . '/edit')->withErrors($msgs)->withInput(Input::all());
        }

        $validationmessages = [
            'project_id.required' => 'please select project id',
            'project_type.required' => 'Please select project type',
            'project_desc.required' => 'Please enter description less than 191 character',
            'bucket_id.required' => 'Please select bucket id',
            'start_date.required' => 'Please select start date',
        ];

        $validator = Validator::make($data_get, [
                    'project_Id' => 'required',
                    'project_type' => 'required',
                    'project_desc' => 'required | max:191',
                    'bucket_id' => 'required',
                    'start_date' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/project/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }
        $project->update([
            'project_name' => $request->input('project_name'),
            'project_type' => $request->input('project_type'),
            'project_desc' => $request->input('project_desc'),
            'portfolio_id' => $request->input('portfolio_id'),
            'portfolio_type' => $request->input('portfolio_type'),
            'bucket_id' => $request->input('bucket_id'),
            'location_id' => $request->input('location_id'),
            'cost_centre' => $request->input('cost_centre'),
            'department' => $request->input('department'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'a_start_date' => $request->input('a_start_date'),
            'a_end_date' => $request->input('a_end_date'),
            'f_start_date' => $request->input('f_start_date'),
            'f_end_date' => $request->input('f_end_date'),
            'sch_date' => $request->input('sch_date'),
            'p_start_date' => $request->input('p_start_date'),
            'p_end_date' => $request->input('p_end_date'),
            'person_responsible' => $request->input('person_responsible'),
            'factory_calendar' => $request->input('factory_calendar'),
            'currency' => $request->input('currency'),
            'modified_by' => Auth::id(),
            'status' => $request->input('status'),
        ]);
        session()->flash('flash_message', 'Project updated successfully...');
        return redirect('admin/project');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        Roleauth::check('project.delete');
        $project = Project::where('company_id', Auth::user()->company_id)->find($id);
        if (!isset($project)) {
            return redirect('admin/project');
        }
        
        $phase = Projectphase::select('id')->where('project_id',$id)->pluck('id', 'id')->toArray();
        if(count($phase) > 0){
            session()->flash('flash_message', "Phase exits for project can't deleted...");
            return redirect('admin/project');
        } else {
            $project->delete();
            session()->flash('flash_message', 'Project deleted successfully...');
            return redirect('admin/project');
        }
    }

}
