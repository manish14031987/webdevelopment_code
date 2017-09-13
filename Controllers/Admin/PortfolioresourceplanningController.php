<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Portfolio;
use App\Buckets;
use App\Portfolioresourceplanning;
use App\Planningtype;
use App\Costingtype;
use App\Collectiontype;
use App\Viewtype;
use App\Planningunit;
use Illuminate\Support\Facades\Validator;

class PortfolioresourceplanningController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
	 
    public function index()
    {
        $portfolioresourceplanning = Portfolioresourceplanning::all();
        //$portfolioresourceplanning = Portfolioresourceplanning::join('portfolio', 'portfolio.id', '=', 'portfolio_resource_planning.portfolio_id')->get();
		
        return view('admin.portfolioresourceplanning.index', compact('portfolioresourceplanning'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {        
        $planning_type = Planningtype::pluck('name','id')->prepend('Please select', '');
        $costing_type = Costingtype::pluck('name','id')->prepend('Please select', '');
        $collection_type = Collectiontype::pluck('name','id')->prepend('Please select', '');
        $view_type = Viewtype::pluck('name','id')->prepend('Please select', '');
	$buckets = Buckets::pluck("name", "id")->prepend('Please select', '');
	$portfolio = Portfolio::pluck("name", "id")->prepend('Please select', '');
        $Planningunit = Planningunit::pluck("name", "id")->prepend('Please select', '');
        return view('admin.portfolioresourceplanning.create', compact('portfolio','buckets','planning_type','costing_type','collection_type','view_type','Planningunit'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {   
        $portfolioResourcePlanning = $request->all();                
        
        $validationmessages = [
             'portfolio_id.required' => 'Please select portfolio',
                    'bucket.required' => 'Please select bucket',
                    'planning_type.required' => 'Please select planning type',
                    'costing_type.required' => 'Please select costing type',
                    'collection_type.required' => 'Please select collection type',
                    'view_type.required' => 'Please select view type',
                    'planning_start.required' => 'Please select start date',
                    'planning_end.required' => 'Please select end date', 
                    'planning_end.after' => 'Please select end date greater then start date', 
                    'total_period.required' => 'Please enter total', 
                    'distribute.required' => 'Please enter distribute', 
                    'planning_unit.required' => 'Please enter planning unit', 
        ];

        $validator = Validator::make($portfolioResourcePlanning, [
                    'portfolio_id' => 'required',
                    'bucket' => 'required',
                    'planning_type' => 'required',
                    'costing_type' => 'required',
                    'collection_type' => 'required',
                    'view_type' => 'required',
                    'planning_start' => 'required|date',
                    'planning_end' => 'required|date|after:planning_start',
                    'total_period' => 'required', 
                    'distribute' => 'required', 
                    'planning_unit' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/portfolioresourceplanning/create')->withErrors($validator)->withInput($request->all());
        }
        
        $portfolio_name = Portfolio::select('name')->where('id',$portfolioResourcePlanning['portfolio_id'])->first();
        $portfolioResourcePlanning['portfolio_name'] = $portfolio_name->name;
        Portfolioresourceplanning::create($portfolioResourcePlanning);
        
        session()->flash('flash_message', 'Portfolio resource planning created successfully...');
        return redirect('admin/portfolioresourceplanning');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {           
        $portfolioresourceplanning = Portfolioresourceplanning::find($id);                        
        
        $planning_type = Planningtype::pluck('name','id')->prepend('Please select', '');
        $costing_type = Costingtype::pluck('name','id')->prepend('Please select', '');
        $collection_type = Collectiontype::pluck('name','id')->prepend('Please select', '');
        $view_type = Viewtype::pluck('name','id')->prepend('Please select', '');
        $buckets = Buckets::pluck("name", "id")->prepend('Please select', '');
        $portfolio = Portfolio::pluck("name", "id")->prepend('Please select', '');
        $Planningunit = Planningunit::pluck("name", "id")->prepend('Please select', '');
            
        return view('admin.portfolioresourceplanning.create', compact('portfolioresourceplanning','buckets','portfolio','planning_type','costing_type','collection_type','view_type','Planningunit'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $get_portfolioresourceplanning = Portfolioresourceplanning::find($id);
        
        $portfolioResourcePlanning = $request->all();
        
        $validationmessages = [
                    'portfolio_id.required' => 'Please select portfolio',
                    'bucket.required' => 'Please select bucket',
                    'planning_type.required' => 'Please select planning type',
                    'costing_type.required' => 'Please select costing type',
                    'collection_type.required' => 'Please select collection type',
                    'view_type.required' => 'Please select view type',
                    'planning_start.required' => 'Please select start date',
                    'planning_end.required' => 'Please select end date', 
                    'planning_end.after' => 'Please select end date greater then start date', 
                    'total_period.required' => 'Please enter total', 
                    'distribute.required' => 'Please enter distribute', 
                    'planning_unit.required' => 'Please enter planning unit', 
        ];

        $validator = Validator::make($portfolioResourcePlanning, [
                    'portfolio_id' => 'required',
                    'bucket' => 'required',
                    'planning_type' => 'required',
                    'costing_type' => 'required',
                    'collection_type' => 'required',
                    'view_type' => 'required',
                    'planning_start' => 'required|date',
                    'planning_end' => 'required|date|after:planning_start',
                    'total_period' => 'required', 
                    'distribute' => 'required', 
                    'planning_unit' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/portfolioresourceplanning/create')->withErrors($validator)->withInput($request->all());
        }
        
        $get_portfolioresourceplanning->update($portfolioResourcePlanning);
        session()->flash('flash_message', 'Portfolio resource planning updated successfully...');
        return redirect('admin/portfolioresourceplanning');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {             
        $portfolioresourceplanning = Portfolioresourceplanning::find($id);
        $portfolioresourceplanning->delete();
        session()->flash('flash_message', 'Portfolio resource planning deleted successfully...');
        return redirect('admin/portfolioresourceplanning');
    }
}
