<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Currency;
use App\quantitative_risk_analysis;
use App\qualitative_risk_analysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use App\QualitativeMatrix;
use App\User;
use App\Quantitative_riskscore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Roleauth;

class RiskAnalysisController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        Roleauth::check('risk.all.index');

        $qualitativeData = qualitative_risk_analysis::where('company_id', Auth::user()->company_id)->get();
        foreach ($qualitativeData as $key => $value) {
            $createdby = $value['qual_created_by'] != '' ? User::where('id', $value['qual_created_by'])->first()['original']['name'] : '';
            $changedby = $value['qual_changed_by'] != '' ? User::where('id', $value['qual_changed_by'])->first()['original']['name'] : '';

            $createdon = date("Y-m-d", strtotime($value['created_at']));

            $updated_date = isset($value['updated_at']) ? $value['updated_at'] : '';
            if (empty($updated_date)) {
                $updatedon = null;
            } else {
                $updatedon = date("Y-m-d", strtotime($updated_date));
            }
        }
        $data = DB::table('qualitative_matrix as q1')
                ->orderBy('qr.id')
                ->select('q1.risk_score')
                ->leftjoin('qualitative_risk_analysis as qr', 'q1.qualitative_likelihood', '=', 'qr.qual_likelihood')
                ->whereColumn([
                    ['q1.qualitative_likelihood', '=', 'qr.qual_likelihood'],
                    ['q1.qualitative_consequence', '=', 'qr.qual_consequence']
                ])
                ->where('q1.company_id', Auth::user()->company_id)
                ->get();
        $score = array();
        foreach ($data as $value1) {
            $score[] = isset($value1->risk_score) ? $value1->risk_score : '';
        }

        //for quantitative
        $quantitativeData = DB::table('quantitative_risk_analysis')
                ->select('quantitative_risk_analysis.*', 'currencies.short_code')
                ->leftjoin('currencies', 'quantitative_risk_analysis.quan_currency', '=', 'currencies.id')
                ->where('quantitative_risk_analysis.company_id', Auth::user()->company_id)
                ->get();
        foreach ($quantitativeData as $key1 => $value1) {
            $createdby = $value1->quan_created_by != '' ? User::where('id', $value1->quan_created_by)->first()['original']['name'] : '';
            $changedby = $value1->quan_changed_by != '' ? User::where('id', $value1->quan_changed_by)->first()['original']['name'] : '';

            $createdon = date("Y-m-d", strtotime($value1->created_at));

            $updated_date = isset($value1->updated_at) ? $value1->updated_at : '';
            if (empty($updated_date)) {
                $updatedon = null;
            } else {
                $updatedon = date("Y-m-d", strtotime($updated_date));
            }
        }

        return view('admin.riskAnalysis.index', compact('quantitativeData', 'score', 'qualitativeData', 'createdby', 'changedby', 'createdon', 'updatedon'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createQualitative() {
        Roleauth::check('risk.qualitative.create');

        //get project id and its description
        $project = DB::table('project')
                ->where('company_id', Auth::user()->company_id)
                ->get();

        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->project_Id] = $projectid->project_Id . ' ( ' . $projectid->project_desc . ' )';
        }

        return view('admin.qualitative_risk.risk_analysis', compact('project_id'));
    }

    public function createQuantitative() {
        Roleauth::check('risk.quantitative.create');

        //get currency
        $currency = array();
        $temp = null;
        $temp = Currency::all();
        foreach ($temp as $value) {
            $currency[$value['id']] = $value['short_code'];
        }

        //get project id and its description
        $project = DB::table('project')
                ->where('company_id', Auth::user()->company_id)
                ->get();

        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->project_Id] = $projectid->project_Id . ' ( ' . $projectid->project_desc . ' )';
        }
        return view('admin.quantitative_risk.risk_analysis', compact('currency', 'project_id'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        Roleauth::check('risk.qualitative.create');

        $qualitative_data = Input::all();
        //get user id
        $user = \Auth::User()->id;
        //get company id
        $company_id = \Auth::User()->company_id;
        $qualitative_data['qual_created_by'] = $user;
        $qualitative_data['company_id'] = $company_id;
        $qualitative_data['risk_type'] = 'Qualitative';
        $qualitative_data['created_at'] = date('Y-m-d h:m:s');
        //serverside validation
        $validator = qualitative_risk_analysis::validateQualitative($qualitative_data);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('/admin/qualitative_risk')->withErrors($validator)->withInput(Input::all());
        }

        qualitative_risk_analysis::create($qualitative_data);
        session()->flash('flash_message', 'Qualitative Risk created successfully...');
        return redirect('admin/riskAnalysis');
    }

    public function storeQuantitative(Request $request) {
        Roleauth::check('risk.quantitative.create');

        $quantitative_data = Input::all();
        //get user id
        $user = \Auth::User()->id;
        //get company id
        $company_id = \Auth::User()->company_id;
        $quantitative_data['quan_created_by'] = $user;
        $quantitative_data['company_id'] = $company_id;
        $quantitative_data['risk_type'] = 'Quantitative';
        $quantitative_data['created_at'] = date('Y-m-d h:m:s');

        //serverside validation
        $validator = quantitative_risk_analysis::validateQuantitative($quantitative_data);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('/admin/quantitative_risk')->withErrors($validator)->withInput(Input::all());
        }

        quantitative_risk_analysis::create($quantitative_data);
        session()->flash('flash_message', 'Quantitative Risk created successfully...');
        return redirect('admin/riskAnalysis');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        Roleauth::check('risk.qualitative.edit');

        $qualitative_data = qualitative_risk_analysis::where('company_id', Auth::user()->company_id)->find($id);
        $createdby = $qualitative_data['qual_created_by'] != '' ? User::where('id', $qualitative_data['qual_created_by'])->first()['original']['name'] : '';
        $changedby = $qualitative_data['qual_changed_by'] != '' ? User::where('id', $qualitative_data['qual_changed_by'])->first()['original']['name'] : '';

        $createdon = date("Y-m-d", strtotime($qualitative_data['created_at']));

        $updated_date = isset($qualitative_data['updated_at']) ? $qualitative_data['updated_at'] : '';
        if (empty($updated_date)) {
            $updatedon = null;
        } else {
            $updatedon = date("Y-m-d", strtotime($updated_date));
        }

        $risk_score = array();
        $projectID = $qualitative_data['project_id'];
        $allrisk_score = DB::table('qualitative_risk_analysis')
                ->select('qualitative_risk_analysis.risk_score')
                ->where('project_id', $projectID)
                ->where('company_id', Auth::user()->company_id)
                ->get();
        foreach ($allrisk_score as $key => $value) {
            $risk_score[] = $value->risk_score;
        }

        $total_risk = array_sum($risk_score);
        $current_riskscore = $total_risk - $qualitative_data['risk_score'];
        //get project id and its description
        $project = DB::table('project')
                ->where('company_id', Auth::user()->company_id)
                ->get();

        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->project_Id] = $projectid->project_Id . ' ( ' . $projectid->project_desc . ' )';
        }
        return view('admin.qualitative_risk.risk_analysis', compact('updatedon', 'createdon', 'changedby', 'createdby', 'total_risk', 'current_riskscore', 'qualitative_data', 'project_id'));
    }

    public function editQuantitative($quanId) {
        Roleauth::check('risk.quantitative.edit');

        $quantitativeData = DB::table('quantitative_risk_analysis')->where('quan_id', '=', $quanId)->where('company_id', Auth::user()->company_id)->first();
        $createdby = $quantitativeData->quan_created_by != '' ? User::where('id', $quantitativeData->quan_created_by)->first()['original']['name'] : '';
        $changedby = $quantitativeData->quan_changed_by != '' ? User::where('id', $quantitativeData->quan_changed_by)->first()['original']['name'] : '';

        $createdon = date("Y-m-d", strtotime($quantitativeData->created_at));

        $updated_date = isset($quantitativeData->updated_at) ? $quantitativeData->updated_at : '';
        if (empty($updated_date)) {
            $updatedon = null;
        } else {
            $updatedon = date("Y-m-d", strtotime($updated_date));
        }

        //get currency
        $currency = array();
        $temp = null;
        $temp = Currency::where('company_id', Auth::user()->company_id)->get();
        foreach ($temp as $value) {
            $currency[$value['id']] = $value['short_code'];
        }

        $loss = array();
        $projectID = $quantitativeData->project_id;
        $expected_loss = DB::table('quantitative_risk_analysis')
                ->select('quantitative_risk_analysis.quan_expected_loss')
                ->where('project_id', $projectID)
                ->where('company_id', Auth::user()->company_id)
                ->get();
        foreach ($expected_loss as $key => $value) {
            $loss[] = $value->quan_expected_loss;
        }

        $total_loss = array_sum($loss);
        $all_projectloss = $total_loss - $quantitativeData->quan_expected_loss;
        //get project id and its description
        $project = DB::table('project')
                ->where('company_id', Auth::user()->company_id)
                ->get();

        $project_id = array();
        foreach ($project as $projectid) {

            $project_id[$projectid->project_Id] = $projectid->project_Id . ' ( ' . $projectid->project_desc . ' )';
        }
        return view('admin.quantitative_risk.risk_analysis', compact('all_projectloss', 'currency', 'updatedon', 'createdon', 'changedby', 'createdby', 'quantitativeData', 'project_id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        Roleauth::check('risk.qualitative.edit');

        $qualitativeId = qualitative_risk_analysis::where('company_id', Auth::user()->company_id)->find($id);
        $qualitativeInputs = Input::all();
        //get user id
        $user = \Auth::User()->id;
        $qualitativeInputs['qual_changed_by'] = $user;
        $qualitativeInputs['updated_at'] = date('Y-m-d h:m:s');
        //serverside validation
        $validator = qualitative_risk_analysis::validateQualitative($qualitativeInputs);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('/admin/qualitative_risk/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }

        $qualitativeId->update($qualitativeInputs);
        session()->flash('flash_message', 'Qualitative Risk updated successfully...');
        return redirect('admin/riskAnalysis');
    }

    public function updateQuantitative(Request $request, $quanId) {
        Roleauth::check('risk.quantitative.edit');

        $quantitativeId = DB::table('quantitative_risk_analysis')->where('quan_id', '=', $quanId)->where('company_id', Auth::user()->company_id)->first();

        $quantitativeInputs = Input::except('_method', '_token', 'created_on', 'created_by');
        //get user id
        $user = \Auth::User()->id;
        $quantitativeInputs['quan_changed_by'] = $user;
        $quantitativeInputs['updated_at'] = date('Y-m-d h:m:s');

        //serverside validation
        $validator = quantitative_risk_analysis::validateQuantitative($quantitativeInputs);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('/admin/quantitative_risk/' . $quanId . '/edit')->withErrors($validator)->withInput(Input::all());
        }

        DB::table('quantitative_risk_analysis')
                ->where('quan_id', $quantitativeId->quan_id)
                ->where('company_id', Auth::user()->company_id)
                ->update($quantitativeInputs);

        session()->flash('flash_message', 'Quantitative Risk updated successfully...');
        return redirect('admin/riskAnalysis');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function deleteQualitative($id) {
        Roleauth::check('risk.qualitative.edit');

        qualitative_risk_analysis::where('id', $id)->where('company_id', Auth::user()->company_id)->delete();
        session()->flash('flash_message', 'Qualitative Risk deleted successfully...');
        return redirect('admin/riskAnalysis');
    }

    public function deleteQuantitative($quanId) {
        Roleauth::check('risk.quantitative.edit');

        DB::table('quantitative_risk_analysis')->where('quan_id', '=', $quanId)->where('company_id', Auth::user()->company_id)->delete();
        session()->flash('flash_message', 'Quantitative Risk deleted successfully...');
        return redirect('admin/riskAnalysis');
    }

    public function getProjectDesc($pid) {
        //get project description based on project id
        $projectData = DB::table('project')
                ->select('project.*', 'portfolio.name as portfolioname', 'buckets.name as bucketname')
                ->leftJoin('portfolio', 'portfolio.id', '=', 'project.portfolio_id')
                ->leftjoin('buckets', 'buckets.id', '=', 'project.bucket_id')
                ->where('project.project_Id', $pid)
                ->where('company_id', Auth::user()->company_id)
                ->first();
        return response()->json(['status' => true, 'data' => $projectData]);
    }

    public function addMatrix() {

        $qualitativeriskscore_data = QualitativeMatrix::where('company_id', Auth::user()->company_id)->get();
        return view('admin.riskAnalysis.addMatrix', compact('qualitativeriskscore_data'));
    }

    public function updateQualitativeRiskScore(Request $request, $id) {
        $qualitativeId = QualitativeMatrix::where('company_id', Auth::user()->company_id)->find($id);
        $qualitativeId->update(['risk_score' => $request->risk_score]);
        return Response::json([
                    'flash_message' => 'Qualitative Risk Score Updated successfully...']);
    }

    public function getRiskScore($impact, $probability) {
        //get risk score based on impact and probability
        $riskScoreData = QualitativeMatrix::where('qualitative_likelihood', $impact)
                ->where('qualitative_consequence', $probability)
                ->where('company_id', Auth::user()->company_id)
                ->first();
        return response()->json(['status' => true, 'data' => $riskScoreData]);
    }

    public function getQuantitativeRiskScore($expectedloss) {
        $risk = DB::table('quantitative_riskscore')->where('company_id', Auth::user()->company_id)->MAX('end_range');
        if ($risk < intval($expectedloss)) {
        return response()->json(['status' => 'msg', 'data' => 'Expected loss is out of range. Please change your quantitative risk score range first.']);
        }
        $risk_score = array();
        $riskScore = DB::table('quantitative_riskscore as qr')
                ->where(function ($query) use ($expectedloss) {
                    $query->where('qr.start_range', '<=', intval($expectedloss))
                    ->where('qr.end_range', '>=', intval($expectedloss));
                })
                ->where('company_id', Auth::user()->company_id)
                ->get();

        foreach ($riskScore as $key => $value) {
            $risk_score = $value;
        }
        return response()->json(['status' => true, 'data' => $risk_score]);
    }

    public function QuantitativeRiskScore() {

        $quantitative_riskscoredata = Quantitative_riskscore::where('company_id', Auth::user()->company_id)->get();
        return view('admin.quantitative_risk.quantitaive_riskscore', compact('quantitative_riskscoredata'));
    }

    public function updateQuantitaiveRiskScore(Request $request, $id) {
        $data = Quantitative_riskscore::where('company_id', Auth::user()->company_id)->find($id);
        $dataInputs = Input::all();
        $data->update($dataInputs);
        session()->flash('flash_message', 'Quantitative Risk Score updated successfully...');
        return redirect('admin/QuantitativeRiskScore');
    }

}
