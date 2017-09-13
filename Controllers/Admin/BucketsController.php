<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Portfolio;
use App\Buckets;
use App\Currency;
use App\Departmenttype;
use App\Cost_centres;
use Illuminate\Support\Facades\DB;
use App\Costcentretype;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use App\Roleauth;
use App\Project;

class BucketsController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($bucketId = null) { 
        Roleauth::check('portfolio.bucket.index');

        $buckets = Buckets::where('company_id', Auth::user()->company_id)->with('children')->with('portfolio')->with('department_name')->with('costcentre_name')->with('currencyname')->get();
        $portId = null;
        if($bucketId != null) {
            $bucket = Buckets::where('company_id', Auth::user()->company_id)->find($bucketId);
            if($bucket)
                $portId = $bucket->portfolio_id;
        }
        return view('admin.buckets.index', compact('buckets', 'bucketId', 'portId'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        Roleauth::check('portfolio.bucket.create');

        $buck = Buckets::where('company_id', Auth::user()->company_id)->with('children')->get();
        $temp = Currency::where('company_id', Auth::user()->company_id)->get();
        foreach ($temp as $key => $value) {
            $currency[$value->id] = $value->short_code;
        }

        $portfolio = Portfolio::where('company_id', Auth::user()->company_id)->where('status','active')->pluck("port_id", "id")->prepend('Please select', '');
        $department = Departmenttype::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $costcentretype = Cost_centres::where('company_id', Auth::user()->company_id)->pluck("cost_centre", "cost_id")->prepend('Please select', '');

        return view('admin.buckets.create', compact('buck', 'currency', 'portfolio', 'department', 'costcentretype'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        Roleauth::check('portfolio.bucket.create');
        
        $this->validate($request, [
            'bucket_id' => 'required',
            'name' => 'required',
            'description' => 'required',
            'portfolio_id' => 'required',
            'costcentretype' => 'required',
            'department' => 'required',
            'currency' => 'required',
        ]);

        Buckets::create([
            'parent_bucket' => $request->input('parent_bucket'),
            'bucket_id' => $request->input('bucket_id'),
            'name' => $request->input('name'),
            'portfolio_id' => $request->input('portfolio_id'),
            'costcentretype' => $request->input('costcentretype'),
            'department' => $request->input('department'),
            'currency' => $request->input('currency'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
            'created_by' => Auth::id(),
            'company_id' => Auth::user()->company_id
        ]);

        session()->flash('flash_message', 'Bucket created successfully...');
        return redirect('admin/buckets');
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
        Roleauth::check('portfolio.bucket.edit');
        $buckets = Buckets::where('company_id', Auth::user()->company_id)->find($id);
        if(!isset($buckets)) {
            return redirect('admin/buckets');
        }
        
        $costcentretype = '';
        $buck = Buckets::where('company_id', Auth::user()->company_id)->with('children')->get();
        $temp = Currency::where('company_id', Auth::user()->company_id)->get();
        foreach ($temp as $key => $value) {
            $currency[$value->id] = $value->short_code;
        }

        $department = Departmenttype::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $costcentretype = Cost_centres::where('company_id', Auth::user()->company_id)->pluck("cost_centre", "cost_id")->prepend('Please select', '');
        $portfolio = Portfolio::where('company_id', Auth::user()->company_id)->pluck("port_id", "id")->prepend('Please select', '');

        return view('admin.buckets.create', compact('buckets', 'buck', 'currency', 'costcentretype', 'department', 'portfolio'));
    }

    public function getportfolioname(Request $request) {


        $pname = DB::table('portfolio')->select('name')->where('company_id', Auth::user()->company_id)->where('id', $request->porfolio_id)->first();

        return response()->json($pname);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        Roleauth::check('portfolio.bucket.edit');
        $buckets = Buckets::where('company_id', Auth::user()->company_id)->find($id);
        if(!isset($buckets)) {
            return redirect('admin/buckets');
        }

        $buckets_get = $request->all();

        $validationmessages = [
            'name.required' => "Please enter name",
            'portfolio_id.required' => "Please select portfolio ID",
            'costcentretype.required' => "Please select cost centre",
            'department.required' => "Please select department",
            'currency.required' => "Please select currency",
            'description.required' => "Please enter description",
        ];

        $validator = Validator::make($buckets_get, [
                    'name' => "required",
                    'portfolio_id' => "required",
                    'costcentretype' => "required",
                    'department' => "required",
                    'currency' => "required",
                    'description' => "required",
                        ], $validationmessages);


        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/buckets/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }

        $buckets->update([
            'name' => $request->input('name'),
            'portfolio_id' => $request->input('portfolio_id'),
            'costcentretype' => $request->input('costcentretype'),
            'department' => $request->input('department'),
            'currency' => $request->input('currency'),
            'description' => $request->input('description'),
            'status' => $request->input('status'),
            'updated_by' => Auth::id()
        ]);
        session()->flash('flash_message', 'Bucket updated successfully...');
        return redirect('admin/buckets');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        Roleauth::check('portfolio.bucket.delete');
        $buckets = Buckets::where('company_id', Auth::user()->company_id)->find($id);
        if(!isset($buckets)) {
            return redirect('admin/buckets');
        }
        
        $bucket_id = Buckets::select('id')->where('parent_bucket',$id)->pluck('id', 'id')->toArray();
        $project = Project::select('id')->where('bucket_id',$id)->pluck('id', 'id')->toArray();
        if(count($bucket_id) > 0 || count($project) > 0){
            session()->flash('flash_message', 'Bucket cannot delete...');
            return redirect('admin/buckets');
        } else {
            $buckets->delete();
            session()->flash('flash_message', 'Bucket deleted successfully...');
            return redirect('admin/buckets');
        }
        
    }
    
    public function getportfolio($id){
      $portfolio = DB::table('portfolio')
                ->select('portfolio.id')
                ->where('portfolio.id', '=', $id)
                ->where('portfolio.status', '=', 'inactive')
                ->get();
        return response()->json([$portfolio]);
    }

}
