<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\riskanalysis;
use App\Currency;
use App\project;
use App\quantitative_risk_analysis;
use App\qualitative_risk_analysis;
use App\Portfolio;
use App\Buckets;

class QuantitativeriskController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
		$pro = Project::all();
		$quantitative_risk =  quantitative_risk_analysis::all();
		$qualitative_risk = qualitative_risk_analysis::all();
		
		if(count($quantitative_risk) > 0 && count($qualitative_risk) > 0 & count($pro) > 0){ 
		foreach ($quantitative_risk as $value){
				$quantitative_find[] = $value->project_id;
			}
		foreach ($qualitative_risk as $val){
			   $qualitative_find[]  = $val->project_id;
		    } 
		foreach($pro as $var)
		{
		if(in_array($var->Id,$quantitative_find) && in_array($var->Id,$quantitative_find)){
				$data[] = $var; 
			}
		}
	}	else{
				$project = null;
        return view('admin.quantitative_risk.index', compact('project'));
	}	
		$project = $data;
        return view('admin.quantitative_risk.index', compact('project'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
		
                
                $currency = array();
        $temp = null;
        $temp = Currency::all();
        foreach ($temp as $value) {
            $currency[$value['id']] = $value['short_code'];
        }
        return view('admin.quantitative_risk.risk_analysis', compact('currency'));
    }
    
        

	    public function autocomplete(Request $request)
    {
        $name = $request->term;
        $data = project::where('project_Id','LIKE','%'.$name.'%')
						->where('risk_status','0')->get();
        $result = array();
        foreach ($data as $value) {
            $result[] = ['id'=>$value->Id,'value'=>$value->project_Id];
        }
        return response()->json($result);
    }


    function getProjectDetail(Request $request)
    {
        $id   = $request->projectId;
        $data   = project::join('qualitative_risk_analysis','project.id','=','qualitative_risk_analysis.project_id','left')->join('quantitative_risk_analysis','project.id','=','quantitative_risk_analysis.project_id','left')->where('project.id',$id)->get();
		$port = Portfolio::select('name')->find($data[0]['portfolio_id']);
		$bucket = Buckets::select('name','parent_bucket')->find($data[0]['bucket_id']);
		$prnt_bucket = Buckets::select('name')->find($bucket['parent_bucket']);
		$data['port'] = $port;
		$data['bucket'] = $bucket;
		$data['prnt_bucket'] = $prnt_bucket;
        return response()->json($data);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
		//dd($request->all());
		if($request->risk_type == 'quan1'){
        quantitative_risk_analysis::create($request->all());
		$data = Project::where('Id',$request->project_id)->update(['risk_status' => 1]);
		}else if($request->risk_type == 'qual1'){
		$data = qualitative_risk_analysis::where('project_id',$request->project_id)->get();
			if(count($data) == 0){
			qualitative_risk_analysis::create($request->all());
			return response()->json(['status'=>'success']);
		}
			return response()->json(['status'=>'success']);
		}
    }

    /**
     * Display the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
		  $data['project'] = Project::find($id);
                  
                    $currency = array();
        $temp = null;
        $temp = Currency::all();
        foreach ($temp as $value) {
            $currency[$value['id']] = $value['short_code'];
        }

                  
		  return view('admin.quantitative_risk.risk_analysis_update',$data,$currency);
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
		//dd($request->all(),$id);die;
		if($request->risk_type == 'quan1'){
        $project = quantitative_risk_analysis::where('quan_id',$id);
        $project->update($request->all());
		}else if($request->risk_type == 'qual1'){
        $project = qualitative_risk_analysis::find($id);
        $project->update($request->all());	
		}		
        return response()->json(['status'=>'success']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
		$request = quantitative_risk_analysis::where('project_id',$id)->get();	
		foreach($request as $req)
		{Project::where('Id',$req->project_id)->update(['risk_status' => 0]);}		
		quantitative_risk_analysis::where('project_id',$id)->delete();	
		qualitative_risk_analysis::where('project_id',$id)->delete();

		return redirect('admin/quantitative_risk');
    }
}
