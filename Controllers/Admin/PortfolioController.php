<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Portfolio;
use App\Currency;
use App\Portfoliotype;
use App\Buckets;
use App\Project;
use App\Capacityunits;
use App\Planningunit;
use App\Roleauth;
use Redirect;

class PortfolioController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        Roleauth::check('portfolio.index');

        $portfolio = Portfolio::where('company_id', Auth::user()->company_id)->with('portfolio_type')->with('capacity_units')->with('planning_units')->get();
        //echo "<pre>";print_r($portfolio);die;

        return view('admin.portfolio.index', compact('portfolio'));
    }

    public function export_cs() {
        Roleauth::check('portfolio.export');

        $portfolio = Portfolio::where('company_id', Auth::user()->company_id)->get();
        // echo "<pre>";
        // print_r($portfolio);exit;

        $header = "ID" . "\t";
        $header .= "Name" . "\t";
        $header .= "Type" . "\t";
        $header .= "Bucket" . "\t";
        $header .= "Items" . "\t";
        $header .= "Projects" . "\t";
        $header .= "Curreny" . "\t";
        $header .= "Description" . "\t";
        $header .= "Created By" . "\t";
        $header .= "Created At" . "\t";
        $header .= "Updated By" . "\t";
        $header .= "Updated At" . "\t";

        foreach ($portfolio as $port_data) {
            // echo "<pre>";

            $row1 = array();
            $row1[] = $port_data->id;
            $row1[] = $port_data->name;
            $row1[] = $port_data->type;
            $row1[] = $port_data->buckets;
            $row1[] = $port_data->items;
            $row1[] = $port_data->projects;
            $row1[] = $port_data->currency;
            $row1[] = $port_data->description;
            $row1[] = $port_data->created_by;
            $row1[] = $port_data->created_at;
            $row1[] = $port_data->updated_by;
            $row1[] = $port_data->updated_at;

            $data = join("\t", $row1) . "\n";
        }
        //print_r($data); 
        //exit;   

        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=Portfolio.csv");
        header("Pragma: no-cache");
        header("Expires: 0");
        print "$header\n$data";
        exit();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        Roleauth::check('portfolio.create');

//        $currency = Currency::pluck("fullname", "short_code")->prepend('Please select', '');

        $temp = Currency::where('company_id', Auth::user()->company_id)->get();
        $currency = array();
        foreach ($temp as $key => $value) {
            $currency[$value->id] = $value->short_code;
        }

        $ptype = Portfoliotype::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $buck = Buckets::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $project = Project::where('company_id', Auth::user()->company_id)->pluck("project_Id", "Id")->prepend('Please select', '');

        return view('admin.portfolio.create', compact('currency', 'ptype', 'buck', 'project'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        Roleauth::check('portfolio.create');
        
        $this->validate($request, [
            'name' => 'required',
            'port_id' => 'required',
            'type' => 'required',
        ]);

        Portfolio::create([
            'name' => $request->input('name'),
            'port_id' => $request->input('port_id'),
            'type' => $request->input('type'),
            'currency' => $request->input('currency'),
            'description' => $request->input('description'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'company_id' => Auth::user()->company_id
        ]);

        session()->flash('flash_message', 'Portfolio created successfully...');
        return redirect('admin/portfolio');
    }

    /**
      'buckets' => 'required',
      'items' => 'required',
      'projects' => 'required',

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
        Roleauth::check('portfolio.edit');
        $portfolio = Portfolio::where('company_id', Auth::user()->company_id)->find($id);
        if (!isset($portfolio)) {
            return redirect('admin/portfolio');
        }

//        $currency = Currency::pluck("fullname", "short_code")->prepend('Please select', null);
        $temp = Currency::where('company_id', Auth::user()->company_id)->get();
        foreach ($temp as $key => $value) {
            $currency[$value->id] = $value->short_code;
        }


        $ptype = Portfoliotype::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $buck = Buckets::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $capacity_unit = Capacityunits::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');
        $project = Project::where('company_id', Auth::user()->company_id)->pluck("project_Id", "Id")->prepend('Please select', '');
        $planning_unit = Planningunit::where('company_id', Auth::user()->company_id)->pluck("name", "id")->prepend('Please select', '');

        return view('admin.portfolio.create', compact('portfolio', 'currency', 'ptype', 'buck', 'project', 'capacity_unit', 'planning_unit'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        Roleauth::check('portfolio.edit');
        $portfolio = Portfolio::where('company_id', Auth::user()->company_id)->find($id);
        if (!isset($portfolio)) {
            return redirect('admin/portfolio');
        }
        
        // print($request);die;
        $this->validate($request, [
            'name' => 'required',
            'type' => 'required',
        ]);

        $portfolio->update([
            'name' => $request->input('name'),
            'type' => $request->input('type'),
            'currency' => $request->input('currency'),
            'description' => $request->input('description'),
            'planning_unit' => $request->input('planning_unit'),
            'capacity_unit' => $request->input('capacity_unit'),
            'status' => $request->input('status'),
            'updated_by' => Auth::id()
        ]);
        session()->flash('flash_message', 'Portfolio updated successfully...');
        return redirect('admin/portfolio');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        Roleauth::check('portfolio.delete');
        $portfolio = Portfolio::where('company_id', Auth::user()->company_id)->find($id);
        if (!isset($portfolio)) {
            return redirect('admin/portfolio');
        }
        
        $portfolio_id = Portfolio::select('id')->where('id',$id)->pluck('id', 'id')->toArray();
        $bucket_id = Buckets::select('portfolio_id')->where('portfolio_id',$portfolio_id)->pluck('portfolio_id', 'id')->toArray();
        if(count($bucket_id) > 0){
            session()->flash('flash_message', 'Bucket exits for Portfolio cannot delete...');
            return redirect('admin/portfolio');
        } else {
            $portfolio->delete();
            session()->flash('flash_message', 'Portfolio deleted successfully...');
            return redirect('admin/portfolio');
        }
    }
	public function dashboard() {
	
		return view('admin.portfolio.dashboard');
    
	}

}
