<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Report;
use App\Currency;
use App\Reporttype;
use App\Buckets;
use App\Project;
use App\projectchecklist;
use App\Sales_order;
use App\Risk;
use App\User;
use App\TasksSubtask;
use App\Projectphase;
use App\Projectmilestone;
use App\Capacityunits;
use App\Planningunit;
use App\purchase_order;
use App\OriginalBudget;
use App\Portfolio;
use App\Roleauth;
// use App\TasksSubtask;
use App\qualitative_risk_analysis;
use App\purchase_requisition;
use Illuminate\Support\Facades\DB;
use Redirect;
use View;

use App\Http\Requests;
// use App\Product as Product;
use PDF;

class ReportController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   
	public function timesheetpdf($reportProject_to = null,$reportProject_from = null)
	{	
		
		$query = Project::query();
		$query->select('project.*','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.id');
		$query->leftJoin('budget_original','budget_original.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'project.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'project.id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'project.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->orderBy('project.id', 'desc');
		
		if(isset($reportProject_from)){ 
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		

		$request = $query->get();

		view()->share('request',$request);
		$pdf = PDF::loadView('admin.report.timesheetpdf');
		return $pdf->download('TimeSheet.pdf');
	
	}
	
	public function costbudgetpdf($reportProject_to = null,$reportProject_from = null,$reportStart_date = null,$reportEnd_date = null)
	{	
		$query = OriginalBudget::query();
		$query->select('budget_original.*','project.project_name','project.project_desc','project.p_start_date','project.p_end_date','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost');
		$query->leftJoin('project','project.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'budget_original.project_Id');
		$query->orderBy('budget_original.id', 'desc');
		
		
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('budget_original.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('budget_original.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}

		if(isset($reportStart_date) && $reportStart_date != "-"){			
			$query->where('project.p_start_date', '>=',$reportStart_date);
			$start_date = "reportStart_date=$reportStart_date";
		}else{
			$start_date = "reportStart_date=$reportStart_date";
		}

		if(isset($reportEnd_date) && $reportEnd_date != "-"){			
			$query->where('project.p_end_date', '<=',$reportEnd_date);
			$end_date = "reportEnd_date=$reportEnd_date";
		}else{
			$end_date = "reportEnd_date=$reportEnd_date";
		}
		
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/costbudget?'.$from.'&'.$to.'&'.$start_date.'&'.$end_date.'&e=*p-');
			
		}	
		
		// echo'<pre>';print_r($request);die;
		

		view()->share('request',$request);
		$pdf = PDF::loadView('admin.report.costbudgetpdf');
		return $pdf->download('CostBudget.pdf');
		
	}

	public function checklistpdf($reportProject_to = null,$reportProject_from = null,$reportName = null,$reportChecklist_id = null)
	{	
		$query = projectchecklist::query();
		$query->select('project_checklist.*','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id');	
		$query->leftJoin('project', 'project.project_id', '=', 'project_checklist.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_checklist.id', 'desc');
		
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
			}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}

		if(isset($reportChecklist_id) && $reportChecklist_id != "-"){			
			$query->where('project_checklist.checklist_id', '=',$reportChecklist_id);		
			$check_id = "check_id=$reportChecklist_id";
		}else{
			$check_id = "check_id=$reportChecklist_id";
		}

		if(isset($reportName) && $reportName != "-"){		
			$query->where('project.p_end_date', '<=',$reportName);
			$name = "name=$reportName";
		}else{
			$name = "name=$reportName";
		}
		
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/checklistreport?'.$from.'&'.$to.'&'.$name.'&'.$check_id.'&e=*p-');
			
		}
		
		view()->share('request',$request);
		$pdf = PDF::loadView('admin.report.checklistpdf');
		return $pdf->download('CheckList.pdf');
	
	}

	public function milestonepdf($reportProject_to = null,$reportProject_from=null,$project_phase_id=null,$project_task_id=null,$project_milestone_Id=null)
	{
	
		$query = Projectmilestone::query();
		$query->select('project_milestone.*','project_milestone.milestone_Id as project_milestone_Id','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','tasks_subtask.task_Id as project_task_id');	
		$query->leftJoin('project', 'project.project_id', '=', 'project_milestone.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('tasks_subtask','tasks_subtask.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_milestone.id', 'desc');

		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
			}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}

		if(isset($project_phase_id) && $project_phase_id != "-"){			
			$query->where('project_phase.phase_Id', '=',$project_phase_id);
			$project_phase_id = "project_phase_id=$project_phase_id";
		}else{
			$project_phase_id = "project_phase_id=$project_phase_id";
		}

		if(isset($project_task_id) && $project_task_id != "-"){		
			$query->where('tasks_subtask.task_Id', '=',$project_task_id);
			$project_task_id = "project_task_id=$project_task_id";
		}else{
			$project_task_id = "project_task_id=$project_task_id";
		}

		if(isset($project_milestone_Id) && $project_milestone_Id != "-"){		
			$query->where('project_milestone.milestone_Id', '=',$project_milestone_Id);
			$project_milestone_Id = "project_milestone_Id=$project_milestone_Id";
		}else{
			$project_milestone_Id = "project_milestone_Id=$project_milestone_Id";
		}
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/milestonereport?'.$from.'&'.$to.'&'.$project_phase_id.'&'.$project_task_id.'&'.$project_milestone_Id.'&e=*p-');
			
		}

		view()->share('request',$report);
		$pdf = PDF::loadView('admin.report.milestonepdf');
		return $pdf->download('MilestoneReport.pdf');
		
	}

	public function riskanalysispdf($reportProject_to = null,$reportProject_from=null,$status=null,$risk_status=null){
		$query = Risk::query();
		$query->select('risk_analysis.*','project.id as project_uid','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name');	
		$query->leftJoin('project', 'project.project_id', '=', 'risk_analysis.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		
		if(isset($reportProject_from) && $reportProject_from != "-"){
			$query->where('risk_analysis.project_id', '>=',$reportProject_from);
		}
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('risk_analysis.project_id', '<=',$reportProject_to);
		}
		if(isset($status) && $status != "-"){
			$query->where('risk_analysis.status', '=',$status);
		}
		$query->orderBy('risk_analysis.id', 'desc');		
		$request = $query->get();
		view()->share('request',$request);
		$pdf = PDF::loadView('admin.report.riskanalysispdf');
		return $pdf->download('RiskanalysisReport.pdf');

	}
	
	public function projectdefinitiondetailpdf($reportProject_to = null,$reportProject_from = null,$reportStart_date = null,$reportEnd_date=null){
	
		$query = Project::query();

		$query->select('project.*','portfolio.name as portfolio_name','buckets.name as bucket_name','department_type.name as department_name','users.name as name','createrole.role_name');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project.id', 'desc');
		
		if(isset($reportProject_from) && $reportProject_from != "-"){
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		
		if(isset($reportStart_date) && $reportStart_date != "-"){
			$query->where('project.p_start_date', '>=',$reportStart_date);
		}
		
		if(isset($reportEnd_date) && $reportEnd_date != "-"){			
			$query->where('project.p_end_date', '<=',$reportEnd_date);
		}
		$request = $query->get();
		
		view()->share('request',$request);
		$pdf = PDF::loadView('admin.report.projectdefinitiondetailpdf');
		return $pdf->download('ProjectDefinitionDetail.pdf');

	}

	public function phasedetailpdf($reportProject_to = null,$reportProject_from = null,$phase_id = null,$portfolio_id=null,$bucket_id=null){
	
		$query = Projectphase::query();		
		$query->select('project_phase.*','project.id as project_uid','project.project_Id','project.project_name','project.project_desc','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','buckets.name as bucket_name','department_type.name as department_name','users.name as name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name');
		$query->leftJoin('project', 'project.project_Id', '=', 'project_phase.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_phase.id', 'desc');
		
		if(isset($reportProject_from) && $reportProject_from != "-"){
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
	
		if(isset($phase_id) && $phase_id != "-"){			
			$query->where('project_phase.phase_id', '=',$phase_id);
		}
		
		if(isset($portfolio_id) && $portfolio_id != "-"){			
			$query->where('project.portfolio_id', '=',$portfolio_id);
		}
		
		if(isset($bucket_id) && $bucket_id != "-"){			
			$query->where('project.bucket_id', '=',$bucket_id);
		} 
		
		$request = $query->get();
		
		view()->share('request',$request);
		$pdf = PDF::loadView('admin.report.pasedetailpdf');
		return $pdf->download('PhaseDetail.pdf');

	}

	public function taskdetailpdf($reportProject_to = null,$reportProject_from = null){

		$query = TasksSubtask::query();
	
		$query->select('tasks_subtask.*','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','project_phase.phase_name');	
		$query->leftJoin('project', 'project.project_id', '=', 'tasks_subtask.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->orderBy('tasks_subtask.id', 'desc');

		if(isset($reportProject_from) && $reportProject_from != "-"){
			$query->where('project.project_id', '>=',$reportProject_from);
		}
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		$request = $query->get();
	
		
		
		view()->share('request',$request);
		$pdf = PDF::loadView('admin.report.taskdetailpdf');
		return $pdf->download('TaskDetail.pdf');

	}
	
	public function projectportfoliopdf($reportProject_to = null,$reportProject_from = null,$reportbucket_id = null,$reportportfolio_id = null){
	
		$query = Project::query();
		$query->select('project.*','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost','buckets.name as bucket_name','buckets.bucket_id','portfolio.name as portfolio_name');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.id');
		$query->leftJoin('budget_original','budget_original.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'project.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'project.id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'project.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->orderBy('project.id', 'desc');
	
		if(isset($reportbucket_id) && $reportbucket_id != "-"){			
			$query->where('buckets.bucket_id', '=',$reportbucket_id);
			$bucket_id = "bucket_id=$reportbucket_id";
		}else{
			$bucket_id = "bucket_id=$reportbucket_id";
		}
		
		if(isset($reportportfolio_id) && $reportportfolio_id != "-"){			
			$query->where('project.portfolio_id', '=',$reportportfolio_id);
			$portfolio_id = "portfolio_id=$reportportfolio_id";
		}else{
			$portfolio_id = "portfolio_id=$reportportfolio_id";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}
		
		$request = $query->get();
		
		if(count($request) == 0){
			
			return Redirect::to('admin/projectportfolio?'.$from.'&'.$to.'&'.$bucket_id.'&'.$portfolio_id.'&e=*p-');
			
		}	
		
		
		
		view()->share('request',$request);
		$pdf = PDF::loadView('admin.report.projectportfoliopdf');
		return $pdf->download('ProjectPortfolio.pdf');

	}  
	
	public function costbudget(Request $request){

		
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }
		
		
		$reportStart_date = $request->reportStart_date;
		$reportEnd_date = $request->reportEnd_date;
		$reportProject_from = $request->reportProject_from;
		$reportProject_to = $request->reportProject_to;
		$reportProject_desc = $request->reportProject_desc;
		$request_p = $request->e;
		
		$query = OriginalBudget::query();
		
		
		$query->select('budget_original.*','project.project_name','project.project_desc','project.p_start_date','project.p_end_date','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost');
		$query->leftJoin('project','project.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'budget_original.project_Id');
		$query->orderBy('budget_original.id', 'desc');
		
		if(isset($reportProject_from)){
			$query->where('budget_original.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to)){			
			$query->where('budget_original.project_id', '<=',$reportProject_to);
		}
		
		if(isset($reportStart_date)){
			$query->where('project.p_start_date', '>=',$reportStart_date);
		}
		
		if(isset($reportEnd_date)){			
			$query->where('project.p_end_date', '<=',$reportEnd_date);
		}

		$project = $query->paginate(200);
		
		
		
		return view('admin.report.costbudget', compact('project','projectlist','reportProject_from','reportProject_to','reportEnd_date','reportStart_date','request_p'));	
	
	}
	public function salesreport(Request $request){

		$sales_order = $request->sales_orderno;
		
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }
		
		
		$query = Sales_order::query();
		$query->select('sales_order.*','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id');	
		$query->leftJoin('project', 'project.project_id', '=', 'sales_order.project_number');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('sales_order.sales_orderno', 'desc');
		
		if(isset($sales_order)){
			$query->where('sales_order.sales_orderno', '=',$sales_order);
		}

		

		$report = $query->paginate(200);
		return view('admin.report.salesreport', compact('projectlist','report','sales_order'));	
		
		
	
	}
	
	
	
	
	
	public function projectprocurement(){
	
		$project = DB::table('project')               
		->select('project.*','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name')
		->leftJoin('project_phase', 'project_phase.project_id', '=', 'project.id')
		->leftJoin('taskassign', 'taskassign.project_id', '=', 'project.id')
		->leftJoin('users', 'users.id', '=', 'project.person_responsible')
		->get();
		return view('admin.report.projectprocurement', compact('project'));
		
	}
	
	public function checklistreport(Request $request){	
	
		Roleauth::check('project.create');
		
		$reportProject_from = $request->reportProject_from;
		$reportProject_to = $request->reportProject_to;
		$reportProject_desc = $request->project_desc;
		$reportName = $request->name;
		$reportChecklist_id = $request->checklist_id;
		$reportChecklist_name = $request->checklist_name;
		$request_p = $request->e;
		// echo'<ptr>';print_r($request_p);die;
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }
		
		$checklist = array();
        $projectCheck_data = projectchecklist::all();
        foreach ($projectCheck_data as $key => $checkdata) {
			
            $checklist[$checkdata->checklist_id] = $checkdata->checklist_id . ' ( ' . $checkdata->checklist_name . ' )';
        }
		
		$checklist = array();
        $projectCheck_data = projectchecklist::all();
        foreach ($projectCheck_data as $key => $checkdata) {
			
            $checklist[$checkdata->checklist_id] = $checkdata->checklist_id . ' ( ' . $checkdata->checklist_name . ' )';
        }
		
		$checklist = array();
        $user_data = User::all();
        foreach ($user_data as $key => $data) {
			
            $userlist[$data->name] = $data->name;
        }
		
		
		$query = projectchecklist::query();
		$query->select('project_checklist.*','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id');	
		$query->leftJoin('project', 'project.project_id', '=', 'project_checklist.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_checklist.id', 'desc');
		
		if(isset($reportProject_from)){
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		
		if(isset($reportChecklist_name)){
			$query->where('project_checklist.checklist_name', 'like','%'.$reportChecklist_name .'%');
		}
		
		if(isset($reportChecklist_id)){
			$query->where('project_checklist.checklist_id', '=',$reportChecklist_id);			
		}
		
		if(isset($reportProject_desc)){			
			$query->where('project.project_desc', 'like', '%'.$reportProject_desc .'%');	
		}

		if(isset($reportName)){	
			$query->where('users.name', 'like', '%'.$reportName .'%');			
		}	
		

		$report = $query->paginate(200);
		return view('admin.report.checklistreport', compact('userlist','projectlist','checklist','report','reportProject_from','reportProject_to','reportProject_desc','reportName','reportChecklist_id','reportChecklist_name','request_p'));	
	
	}
	
	public function timesheetreport(Request $request){	
		
		Roleauth::check('project.create');
		$reportStart_date = $request->reportStart_date;
		$reportEnd_date = $request->reportEnd_date;
		$reportProject_from = $request->reportProject_from;
		$reportProject_to = $request->reportProject_to;
		$reportProject_desc = $request->reportProject_desc;
		$request_p = $request->e;
		
		
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }
		
		
		$query = Project::query();
		$query->select('project.*','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.id');
		$query->leftJoin('budget_original','budget_original.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'project.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'project.id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'project.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->orderBy('project.id', 'desc');
		
		if(isset($reportProject_from)){ 
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		

		$report = $query->paginate(200);
		
		return view('admin.report.timesheet', compact('report','projectlist','reportProject_desc','reportProject_from','reportProject_to','reportEnd_date','reportStart_date','request_p'));	
	
	}
	
	public function projectportfolio(Request $request){	
	
		Roleauth::check('project.create');
		
		$reportbucket_id = $request->bucket_id;
		$reportportfolio_id = $request->portfolio_id;
		$reportProject_to = $request->reportProject_to;
		$reportProject_from = $request->reportProject_from;
		$request_p = $request->e;
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }
		
		$portfoliolist = array();
        $project_data = Portfolio::all();
        foreach ($project_data as $key => $portfolio) {
            $portfoliolist[$portfolio->port_id] = $portfolio->port_id;
        }
		
		$bucketlist = array();
        $bucket_data = Buckets::all();
        foreach ($bucket_data as $key => $bucket) {
            $bucketlist[$bucket->bucket_id] = $bucket->bucket_id;
        }
		
		$query = Project::query();
		$query->select('project.*','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost','buckets.name as bucket_name','buckets.bucket_id','portfolio.name as portfolio_name');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.id');
		$query->leftJoin('budget_original','budget_original.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'project.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'project.id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'project.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->orderBy('project.id', 'desc');
	
		if(isset($reportbucket_id)){
			$query->where('buckets.bucket_id', '=',$reportbucket_id);
		}
		
		if(isset($reportportfolio_id)){
			$query->where('project.portfolio_id', '=',$reportportfolio_id);
		}

		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}

		if(isset($reportProject_from)){			
			$query->where('project.project_id', '<=',$reportProject_from);
		}
		
		$report = $query->paginate(200);
		
		return view('admin.report.projectportfolio', compact('bucketlist','portfoliolist','projectlist','report','reportbucket_id','reportProject_from','reportProject_to','reportportfolio_id','request_p'));
	
	}
	
	
	public function purchaserequisition(Request $request){
	
		$reportProject_to = $request->reportProject_to;
		$reportProject_from = $request->reportProject_from;
		$request_p = $request->e;
		
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }
	
		$query = purchase_requisition::query();
		$query->select('purchase_requisition.*','purchase_item.item_no','purchase_item.project_id','purchase_item.vendor','purchase_item.delivery_date','purchase_item.item_cost','purchase_item.status','project.id as project_uid','project.id as project_uid','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.project_Id','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name as user_name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id','users.name as vendor_name');	
		$query->leftJoin('purchase_item', 'purchase_item.requisition_number', '=', 'purchase_requisition.requisition_number');
		// $query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('project', 'project.project_id', '=', 'purchase_item.project_Id');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users as us', 'project.person_responsible', '=', 'us.id');		
		$query->leftJoin('users', 'users.id', '=', 'purchase_item.vendor');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('purchase_requisition.id', 'desc');
	
	
		Roleauth::check('project.create');
		
		$reportProject_to = $request->reportProject_to;
		$reportProject_from = $request->reportProject_from;


		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}

		if(isset($reportProject_from)){			
			$query->where('project.project_id', '<=',$reportProject_from);
		}
		
		$report = $query->paginate(200);
		
		return view('admin.report.purchaserequisition', compact('projectlist','report','reportbucket_id','reportProject_from','reportProject_to','reportportfolio_id','request_p'));
	
	}
	
	public function purchaseorder(Request $request){	
	
		$reportProject_to = $request->reportProject_to;
		$reportProject_from = $request->reportProject_from;
		$request_p = $request->e;
		
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }
	
		$query = purchase_order::query();
		$query->select('purchase_order.*','purchaseorder_item.item_no','purchaseorder_item.project_id','purchaseorder_item.vendor','purchaseorder_item.delivery_date','purchaseorder_item.item_cost','purchaseorder_item.status','project.id as project_uid','project.id as project_uid','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.project_Id','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','us.name as user_name','users.name as vendor_name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id');	
		$query->leftJoin('purchaseorder_item', 'purchaseorder_item.purchase_order_number', '=', 'purchase_order.purchase_order_number');
		$query->leftJoin('project', 'project.project_id', '=', 'purchaseorder_item.project_Id');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users as us', 'project.person_responsible', '=', 'us.id');
		$query->leftJoin('users', 'users.id', '=', 'purchaseorder_item.vendor');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('purchase_order.id', 'desc');
	
		
		Roleauth::check('project.create');
		
		$reportProject_to = $request->reportProject_to;
		$reportProject_from = $request->reportProject_from;


		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}

		if(isset($reportProject_from)){			
			$query->where('project.project_id', '<=',$reportProject_from);
		}
		
		$report = $query->paginate(200);
		
		return view('admin.report.purchaseorder', compact('projectlist','report','reportbucket_id','reportProject_from','reportProject_to','reportportfolio_id','request_p'));
	
	}
	
	public function purchaserequisitionpdf($reportProject_to = null,$reportProject_from = null){
	
		$query = purchase_requisition::query();
		$query->select('purchase_requisition.*','purchase_item.item_no','purchase_item.project_id','purchase_item.vendor','purchase_item.delivery_date','purchase_item.item_cost','purchase_item.status','project.id as project_uid','project.id as project_uid','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.project_Id','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name as user_name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id','users.name as vendor_name');	
		$query->leftJoin('purchase_item', 'purchase_item.requisition_number', '=', 'purchase_requisition.requisition_number');
		// $query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('project', 'project.project_id', '=', 'purchase_item.project_Id');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users as us', 'project.person_responsible', '=', 'us.id');		
		$query->leftJoin('users', 'users.id', '=', 'purchase_item.vendor');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('purchase_requisition.id', 'desc');
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}
		
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/purchaserequisition?'.$from.'&'.$to.'&e=*p-');
			
		}		
		
		
		
		
		view()->share('request',$request);
		$pdf = PDF::loadView('admin.report.purchaserequisitionpdf');
		return $pdf->download('PurchaseRequisitionpdf.pdf');
	
	}
	
	public function purchaseorderpdf($reportProject_to = null,$reportProject_from = null){
	
		$query = purchase_order::query();
		$query->select('purchase_order.*','purchaseorder_item.item_no','purchaseorder_item.project_id','purchaseorder_item.vendor','purchaseorder_item.delivery_date','purchaseorder_item.item_cost','purchaseorder_item.status','project.id as project_uid','project.id as project_uid','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.project_Id','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','us.name as user_name','users.name as vendor_name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id');	
		$query->leftJoin('purchaseorder_item', 'purchaseorder_item.purchase_order_number', '=', 'purchase_order.purchase_order_number');
		$query->leftJoin('project', 'project.project_id', '=', 'purchaseorder_item.project_Id');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users as us', 'project.person_responsible', '=', 'us.id');
		$query->leftJoin('users', 'users.id', '=', 'purchaseorder_item.vendor');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('purchase_order.id', 'desc');
		
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}
		
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/purchaseorder?'.$from.'&'.$to.'&e=*p-');
			
		}
		
		view()->share('request',$request);
		$pdf = PDF::loadView('admin.report.purchaseorderpdf');
		return $pdf->download('PurchaseOrder.pdf');
	
	}
	
	public function milestonereport(Request $request){	
	
		Roleauth::check('project.create');
		$reportProject_from = $request->reportProject_from;
		$reportProject_to = $request->reportProject_to;		
		$reportStart_date = $request->reportStart_date;
		$reportEnd_date = $request->reportEnd_date;		
		$project_phase_id = $request->project_phase_id;		
		$project_task_id = $request->project_task_id;
		$project_milestone_Id = $request->project_milestone_Id;
		$project_desc = $request->project_desc;
		$milestone_name = $request->milestone_name;
		$request_p = $request->e;
		
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }
		
		$projectMilestoneList = array();
        $projectMilestone_data = Projectmilestone::all();
        foreach ($projectMilestone_data as $key => $milestone) {
            $projectMilestoneList[$milestone->milestone_Id] = $milestone->milestone_Id;
        }
		
		$Projectphase = array();
        $projectPhase_data = Projectphase::all();
        foreach ($projectPhase_data as $key => $phaselist) {
            $projectPhaseList[$phaselist->phase_Id] = $phaselist->phase_Id;
        }
		
		$Projectphase = array();
        $projectPhase_data = Projectphase::all();
        foreach ($projectPhase_data as $key => $phaselist) {
            $projectPhaseList[$phaselist->phase_Id] = $phaselist->phase_Id;
        }
		
		$Projectphase = array();
        $taskSubtask_data = TasksSubtask::all();
        foreach ($taskSubtask_data as $key => $tasklist) {
            $projectTaskList[$tasklist->task_Id] = $tasklist->task_Id;
        }
		
		$query = Projectmilestone::query();
		$query->select('project_milestone.*','project_milestone.milestone_Id as project_milestone_Id','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','tasks_subtask.task_Id as project_task_id');	
		$query->leftJoin('project', 'project.project_id', '=', 'project_milestone.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('tasks_subtask','tasks_subtask.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_milestone.id', 'desc');

		
		if(isset($reportProject_from)){
			$query->where('project.project_id', '>=',$reportProject_from);
		}
		
		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		
		if(isset($project_phase_id)){			
			$query->where('project_phase.phase_Id', '=',$project_phase_id);
		}
		
		if(isset($project_task_id)){			
			$query->where('tasks_subtask.task_Id', '=',$project_task_id);
		}
		
		if(isset($project_milestone_Id)){			
			$query->where('project_milestone.milestone_Id', '=',$project_milestone_Id);
		}
		$report = $query->paginate(200);
		return view('admin.report.milestonereport', compact('projectTaskList','projectPhaseList','projectMilestoneList','projectlist','report','reportProject_from','reportProject_to','reportEnd_date','reportStart_date','project_phase_id','project_task_id','project_milestone_Id','milestone_name','project_desc','request_p'));	
	
	}
	
	
	
	
	public function riskanalysis(Request $request){
	
		$reportProject_from = $request->reportProject_from;
		$reportProject_to = $request->reportProject_to;
		$status = $request->status;
		$reportProjectRisktype = $request->reportProject_risktype;
		$request_p = $request->e;
		
		
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }       
        	$resk_type = qualitative_risk_analysis::where('id', Auth::user()->company_id)->get();
                $query = Risk::query();
		$query->select('risk_analysis.*','project.id as project_uid','project.person_responsible',
                        'project.project_name','project.bucket_id','project.cost_centre','project.project_desc',
                        'project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date',
                        'project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name',
                        'createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id',
                        'portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id',
                        'project_type.name as project_type_name');	
		$query->leftJoin('project', 'project.project_Id', '=', 'risk_analysis.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		//$query->orderBy('risk_analysis.id', 'desc');

//		if(isset($reportProject_from)){
//			$query->where('risk_analysis.project_id', '>=',$reportProject_from);
//		}
//		if(isset($reportProject_to)){			
//			$query->where('risk_analysis.project_id', '<=',$reportProject_to);
//		}
//		if(isset($status)){
//			$query->where('risk_analysis.status', '=',$status);
//		}
		$report = $query->get();
		// echo'<pre>';print_r($report);die;
		return view('admin.report.riskanalysis', compact('resk_type','projectlist','report','status','reportProjectRisktype','reportProject_from','reportProject_to','request_p'));	
	
	}
	
		public function taskdetailreport(Request $request){
	
		$reportProject_from = $request->reportProject_from;
		$reportProject_to = $request->reportProject_to;
		$status = $request->status;
		$project_manager = $request->name;
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }
		$query = TasksSubtask::query();
	
		$query->select('tasks_subtask.*','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','project_phase.phase_name');	
		$query->leftJoin('project', 'project.project_id', '=', 'tasks_subtask.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->orderBy('tasks_subtask.id', 'desc');

		if(isset($reportProject_from)){
			$query->where('project.project_id', '>=',$reportProject_from);
		}
		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		if(isset($status)){	 echo $status;		
			$query->where('tasks_subtask.status', '=',$status);
		}
		$report = $query->paginate(200);
		return view('admin.report.taskdetailreport', compact('projectlist','report','status','reportProject_from','reportProject_to'));	
	
	}
	
	
	public function projectdefinitiondetail(Request $request){ 	
	
		$reportProject_from = $request->reportProject_from;
		$reportProject_to = $request->reportProject_to;
		$reportStart_date = $request->reportStart_date;
		$reportEnd_date = $request->reportEnd_date;
		$name = $request->name;
		
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }
		
		$query = Project::query();

		$query->select('project.*','portfolio.name as portfolio_name','buckets.name as bucket_name','department_type.name as department_name','users.name as name','createrole.role_name');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project.id', 'desc');
		
		if(isset($reportProject_from)){
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		
		if(isset($reportStart_date)){
			$query->where('project.p_start_date', '>=',$reportStart_date);
		}
		
		if(isset($reportEnd_date)){			
			$query->where('project.p_end_date', '<=',$reportEnd_date);
		}
		
		if(isset($name)){			
			$query->where('users.name', 'like', '%'.$name .'%');
		} 

		$report = $query->paginate(200);
		
		return view('admin.report.projectdefinitiondetail', compact('projectlist','report','cost_centre','department_name','bucket_name','bucket_id','portfolio_name','portfolio_id','name','project_desc','reportEnd_date','reportStart_date','reportProject_to','reportProject_from'));
	}
	
	public function phasedetail(Request $request){
	
	
		$export_data  = $request->input('download');

		$reportStart_date = $request->reportStart_date;
		$reportEnd_date = $request->reportEnd_date;
		$reportProject_from = $request->reportProject_from;
		$reportProject_to = $request->reportProject_to;
		$name = $request->name;
		$portfolio_id = $request->portfolio_id;		
		$bucket_id = $request->bucket_id;
		$phase_id = $request->phase_id;
		
		$projectlist = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projectlist[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }
		
		$projectMilestoneList = array();
        $projectMilestone_data = Projectmilestone::all();
        foreach ($projectMilestone_data as $key => $milestone) {
            $projectMilestoneList[$milestone->milestone_Id] = $milestone->milestone_Id;
        }
		
		$Projectphase = array();
        $projectPhase_data = Projectphase::all();
        foreach ($projectPhase_data as $key => $phaselist) {
            $projectPhaseList[$phaselist->phase_Id] = $phaselist->phase_Id;
        }
		
		$projectTaskList = array();
        $taskSubtask_data = TasksSubtask::all();
        foreach ($taskSubtask_data as $key => $tasklist) {
            $projectTaskList[$tasklist->task_Id] = $tasklist->task_Id;
        }
		$query = Projectphase::query();		
		$query->select('project_phase.*','project.id as project_uid','project.project_Id','project.project_name','project.project_desc','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','buckets.name as bucket_name','department_type.name as department_name','users.name as name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name');
		$query->leftJoin('project', 'project.project_Id', '=', 'project_phase.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_phase.id', 'desc');
		
		if(isset($reportProject_from)){
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		
		if(isset($phase_id)){			
			$query->where('project_phase.phase_id', '=',$phase_id);
		}
		
		if(isset($portfolio_id)){			
			$query->where('project.portfolio_id', '=',$portfolio_id);
		}
		
		if(isset($bucket_id)){			
			$query->where('project.bucket_id', '=',$bucket_id);
		} 
		$report = $query->paginate(200);
		// echo'<pre>';print_r($report);
		return view('admin.report.phasedetail', compact('projectTaskList','projectPhaseList','projectMilestoneList','projectlist','report','cost_centre','department_name','bucket_name','bucket_id','phase_name','phase_id','portfolio_name','portfolio_id','project_desc','reportProject_to','reportProject_from'));
		
		
	}
	
	public function export_checklist_html($reportProject_to = null,$reportProject_from = null,$reportName = null,$reportChecklist_id = null)
	{	
		$query = projectchecklist::query();
		$query->select('project_checklist.*','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id');	
		$query->leftJoin('project', 'project.project_id', '=', 'project_checklist.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_checklist.id', 'desc');
		
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
			}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}

		if(isset($reportChecklist_id) && $reportChecklist_id != "-"){			
			$query->where('project_checklist.checklist_id', '=',$reportChecklist_id);		
			$check_id = "check_id=$reportChecklist_id";
		}else{
			$check_id = "check_id=$reportChecklist_id";
		}

		if(isset($reportName) && $reportName != "-"){		
			$query->where('project.p_end_date', '<=',$reportName);
			$name = "name=$reportName";
		}else{
			$name = "name=$reportName";
		}
		
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/checklistreport?'.$from.'&'.$to.'&'.$name.'&'.$check_id.'&e=*h-');
			
		}

			
		$file="Checklist.html";
		echo '

		<div style="width:100%;float:left"><h2>Project Report: Check List Report</h2></div>
		<table width="100%">

		<tr style="border:1px solid #ccc";>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Description</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Manager</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Checklist ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Checklist Description</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Checklist Status</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Checklist Date</b></th>
		</tr>
		'; 
		foreach ($request as $data) { 
			if(isset($data->created_on)){
				$created_on = $data->created_on;
			}else{
				$created_on = "";			
			}
			if($data->checklist_status == 'active'){
				$status = "Active";
			}else{
				$status = "Not active";
			}
			echo '
			<tr style="border:1px solid #ccc";>
			<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_Id .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->project_desc.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->name.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->checklist_id.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->checklist_name.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$status.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$created_on.'</b></td>
			</tr>
			';
		}
		echo '</table>';
		header("Content-type: application/vnd.html");
		header("Content-Disposition: attachment; filename=$file");

	}
	public function export_timesheet_html($reportProject_to = null,$reportProject_from = null){
	
		$query = Project::query();
		$query->select('project.*','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.id');
		$query->leftJoin('budget_original','budget_original.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'project.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'project.id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'project.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->orderBy('project.id', 'desc');
		
		if(isset($reportProject_from)){ 
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		

		$request = $query->get();
			
		$file="TimeSheet.html";
		
		$header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Planned Cost" . ",";
        $header .= "Actual Cost" . ",";
        $header .= "Overall Budget" . ",";
        $header .= "Overall Supplement" . ",";
        $header .= "Overall Return" . ",";
        $header .= "Available Budget" . ",";
        $header .= "Value" . ",";
        $header .= "Start Date" . ",";
        $header .= "End Date" . ",";
		
		
		echo '

		<div style="width:100%;float:left"><h2>Project Report: Timesheet Report</h2></div>
		<table width="100%">

		<tr style="border:1px solid #ccc";>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Description</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Planned Cost</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Actual Cost</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Overall Budget</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Annual Budget</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Available Budget</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Resource Costs</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Total Time Entered</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>%Of Total Cost</b></th>
		</tr>
		'; 
		foreach ($request as $data) { 

			if(isset($data->budget_org_overall) && isset($data->budget_return_overall) && isset($data->budget_supplement_overall)){
				$annual_cost = ($data->budget_org_overall + $data->budget_supplement_overall) - $data->budget_return_overall; 
			}else{
			
			$annual_cost = "";
			}


			echo '
			<tr style="border:1px solid #ccc";>
			<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_Id .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->project_desc.'</b></td><td><b>-</b></td><td><b>---</b></td><td><b>'.$data->budget_org_overall.'</b></td><td><b>'.$annual_cost.'</b></td>
			</tr>
			';
		}
		echo '</table>';
		header("Content-type: application/vnd.html");
		header("Content-Disposition: attachment; filename=$file");

	}
	public function export_costbudget_html($reportProject_to = null,$reportProject_from = null,$reportStart_date = null,$reportEnd_date = null){
	
	
		$query = OriginalBudget::query();
		$query->select('budget_original.*','project.project_name','project.project_desc','project.p_start_date','project.p_end_date','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost');
		$query->leftJoin('project','project.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'budget_original.project_Id');
		$query->orderBy('budget_original.id', 'desc');
		
		
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('budget_original.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('budget_original.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}

		if(isset($reportStart_date) && $reportStart_date != "-"){			
			$query->where('project.p_start_date', '>=',$reportStart_date);
			$start_date = "reportStart_date=$reportStart_date";
		}else{
			$start_date = "reportStart_date=$reportStart_date";
		}

		if(isset($reportEnd_date) && $reportEnd_date != "-"){			
			$query->where('project.p_end_date', '<=',$reportEnd_date);
			$end_date = "reportEnd_date=$reportEnd_date";
		}else{
			$end_date = "reportEnd_date=$reportEnd_date";
		}
		
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/costbudget?'.$from.'&'.$to.'&'.$start_date.'&'.$end_date.'&e=*h-');
			
		}
	
		$file="CostBudget.html";
		echo '
		<div style="width:100%;float:left"><h2>Project Report: Cost Budget Report</h2></div>
		<table width="100%">
		<tr style="border:1px solid #ccc";>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Description</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Planned Cost</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Actual Cost</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Overall Budget</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Available Budget</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Start Date</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>End Date</b></th>
		</tr>
		'; 
		foreach ($request as $data) { 
				if(isset($data->budget_org_overall) && isset($data->budget_return_overall) && isset($data->budget_supplement_overall)){
				$available_budget = ($data->budget_org_overall + $data->budget_supplement_overall) - $data->budget_return_overall; 
			}else{
				$available_budget = "";
			}			
			if(isset($data->p_start_date)){
				$start_date = $data->p_start_date;
			}else{
				$start_date = "";			
			}
			
			if(isset($data->p_end_date)){
				$end_date = $data->p_end_date;
			}else{
				$end_date = "";			
			}
			
			echo '
			<tr style="border:1px solid #ccc";>
			<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_Id .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->project_desc.'</b></td><td><b>-</b></td><td><b>'.$data->actuall_cost.'</b></td><td><b>'.$data->budget_org_overall.'</b></td><td><b>'.$available_budget.'</b></td><td><b>'.date('Y-m-d',strtotime($data->p_start_date)).'</b></td><td><b>'.date('Y-m-d',strtotime($data->p_end_date)).'</b></td>
			</tr>
			';
		}		
		echo '</table>';
		header("Content-type: application/vnd.html");
		header("Content-Disposition: attachment; filename=$file");
		
	}
	public function export_milestone_html($reportProject_to = null,$reportProject_from=null,$project_phase_id=null,$project_task_id=null,$project_milestone_Id=null)
	{
	
		$query = Projectmilestone::query();
		$query->select('project_milestone.*','project_milestone.milestone_Id as project_milestone_Id','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','tasks_subtask.task_Id as project_task_id');	
		$query->leftJoin('project', 'project.project_id', '=', 'project_milestone.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('tasks_subtask','tasks_subtask.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_milestone.id', 'desc');	
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
			}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}

		if(isset($project_phase_id) && $project_phase_id != "-"){			
			$query->where('project_phase.phase_Id', '=',$project_phase_id);
			$project_phase_id = "project_phase_id=$project_phase_id";
		}else{
			$project_phase_id = "project_phase_id=$project_phase_id";
		}

		if(isset($project_task_id) && $project_task_id != "-"){		
			$query->where('tasks_subtask.task_Id', '=',$project_task_id);
			$project_task_id = "project_task_id=$project_task_id";
		}else{
			$project_task_id = "project_task_id=$project_task_id";
		}

		if(isset($project_milestone_Id) && $project_milestone_Id != "-"){		
			$query->where('project_milestone.milestone_Id', '=',$project_milestone_Id);
			$project_milestone_Id = "project_milestone_Id=$project_milestone_Id";
		}else{
			$project_milestone_Id = "project_milestone_Id=$project_milestone_Id";
		}
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/milestonereport?'.$from.'&'.$to.'&'.$project_phase_id.'&'.$project_task_id.'&'.$project_milestone_Id.'&e=*h-');
			
		}
		
		$file="Milestone.html";
		echo '

		<div style="width:100%;float:left"><h2>Project Report: Milestone Report</h2></div>
		<table width="100%">

		<tr style="border:1px solid #ccc";>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Description</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Phase ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Task ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Milestone ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Milestone Description</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Scheduled Date</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Actual Date</b></th>
		</tr>
		'; 
		foreach ($request as $data) {
		
			if(isset($data->schedule_date)){
				$phpdate = strtotime($data->schedule_date);
				$schedule_date = date('d/M/Y', $phpdate);
				
				}else{
				$schedule_date = "";
			}
			
			if(isset($data->actual_date)){
				$phpdate = strtotime($data->actual_date);
				$actual_date = date('d/M/Y', $phpdate);
				
			}else{
				 $actual_date = "";
			}
			echo '
			<tr style="border:1px solid #ccc";>
			<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_Id .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->project_desc.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->project_phase_id.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->project_task_id.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->project_milestone_Id.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->milestone_name.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$schedule_date.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$actual_date.'</b></td>
			</tr>
			';
		}
		echo '</table>';
		header("Content-type: application/vnd.html");
		header("Content-Disposition: attachment; filename=$file");

	}
	
	public function export_phasedetail_html($reportProject_to = null,$reportProject_from = null,$phase_id = null,$portfolio_id=null,$bucket_id=null){
	
		
	
		$query = Projectphase::query();		
		$query->select('project_phase.*','project.id as project_uid','project.project_Id','project.project_name','project.project_desc','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','buckets.name as bucket_name','department_type.name as department_name','users.name as name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name');
		$query->leftJoin('project', 'project.project_Id', '=', 'project_phase.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_phase.id', 'desc');
		
		if(isset($reportProject_from) && $reportProject_from != "-"){
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
	
		if(isset($phase_id) && $phase_id != "-"){			
			$query->where('project_phase.phase_id', '=',$phase_id);
		}
		
		if(isset($portfolio_id) && $portfolio_id != "-"){			
			$query->where('project.portfolio_id', '=',$portfolio_id);
		}
		
		if(isset($bucket_id) && $bucket_id != "-"){			
			$query->where('project.bucket_id', '=',$bucket_id);
		} 
		
		$request = $query->get();
			
		$file="PhaseDetail.html";
		echo '

		<div style="width:100%;float:left"><h2>Project Report: Phase Detail Report</h2></div>
		<table width="100%">		
		<tr style="border:1px solid #ccc";>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Description</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Phase ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Phase Description</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Portfolio ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Portfolio Name</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Bucket ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Bucket Name</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Cost Center</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Person Responsible</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Department</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Created On</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Start Date</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>End Date</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Status</b></th>
		</tr>
		'; 		
		foreach ($request as $data) {
			
			if(isset($data->p_start_date)){
				$p_start_date = '2017-08-05';
			}else{
				$p_start_date = '2017-08-05';
			}
			if(isset($data->p_end_date)){
				$p_end_date = '2017-08-05';
			}else{
				$p_end_date = '2017-08-05';
			}			
			if($data->status == 'active'){
					$status = "active";
			}else{
					$status = "not active";
			}

			echo '
			<tr style="border:1px solid #ccc";>
			<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_Id .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->project_desc.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->phase_Id.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->phase_name.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->portfolio_id.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->portfolio_name.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->bucket_id.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->bucket_name .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->cost_centre .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->name .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->department_name .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->role_name .'</b></td><td><b>'.date('Y-m-d',strtotime($data->p_start_date)) .'</b></td><td><b>'.date('Y-m-d',strtotime($data->p_end_date)) .'</b></td><td><b>'. $data->status .'</b></td>
			</tr>';
		}
		echo '</table>';
		header("Content-type: application/vnd.html");
		header("Content-Disposition: attachment; filename=$file");

	}
	public function export_riskanalysis_html($reportProject_to = null,$reportProject_from=null,$status=null,$risk_status=null){
	
		$query = Risk::query();
		$query->select('risk_analysis.*','project.id as project_uid','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name');	
		$query->leftJoin('project', 'project.project_id', '=', 'risk_analysis.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		
		if(isset($reportProject_from) && $reportProject_from != "-"){
			$query->where('risk_analysis.project_id', '>=',$reportProject_from);
		}
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('risk_analysis.project_id', '<=',$reportProject_to);
		}
		if(isset($status) && $status != "-"){
			$query->where('risk_analysis.status', '=',$status);
		}
		$query->orderBy('risk_analysis.id', 'desc');		
		$request = $query->get();
			
		$file="Riskanalysis.html";
		echo '

		<div style="width:100%;float:left"><h2>Project Report: Risk Analysis Report</h2></div>
		<table width="100%">

		<tr style="border:1px solid #ccc";>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Manager</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Risk ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Risk Type</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Risk Score</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Status</b></th>
		</tr>
		'; 
		foreach ($request as $data) {
		
			if($data->status == 1){
				$status =  'Active';
			}else{
				$status =  'Not Active';
			}
			echo '
			<tr style="border:1px solid #ccc";>
			<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_id .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->name.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->risk_id.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>-</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>-</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$status.'</b></td>
			</tr>
			';
		}
		echo '</table>';
		header("Content-type: application/vnd.html");
		header("Content-Disposition: attachment; filename=$file");

	}
	
	public function export_purchaserequisition_html($reportProject_to = null,$reportProject_from = null){
	
		$query = purchase_requisition::query();
		$query->select('purchase_requisition.*','purchase_item.item_no','purchase_item.project_id','purchase_item.vendor','purchase_item.delivery_date','purchase_item.item_cost','purchase_item.status','project.id as project_uid','project.id as project_uid','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.project_Id','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name as user_name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id','users.name as vendor_name');	
		$query->leftJoin('purchase_item', 'purchase_item.requisition_number', '=', 'purchase_requisition.requisition_number');
		// $query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('project', 'project.project_id', '=', 'purchase_item.project_Id');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users as us', 'project.person_responsible', '=', 'us.id');		
		$query->leftJoin('users', 'users.id', '=', 'purchase_item.vendor');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('purchase_requisition.id', 'desc');
	
	
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}
		
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/purchaserequisition?'.$from.'&'.$to.'&e=*h-');
			
		}
		

	
	 $header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Purchase Requisition" . ",";
        $header .= "Purchase Requisition Item" . ",";
        $header .= "Total Price" . ",";
        $header .= "Project Manager" . ",";
        $header .= "Vendor" . ",";
        $header .= "Delivery Date" . ",";
			
        $header .= "Status" . ",";
		$file="PurchaseRequisition.html";
		echo '

		<div style="width:100%;float:left"><h2>Project Report: Purchase Requisition For A Project</h2></div>
		<table width="100%">

		<tr style="border:1px solid #ccc";>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Manager</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Purchase Requisition</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Purchase Requisition Item</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Total Price</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Manager</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Vendor</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Delivery Date</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Status</b></th>
		</tr>
		</tr>
		'; 
		foreach ($request as $data) {
		
		
				if (isset($data->delivery_date)){
					$phpdate = strtotime($data->delivery_date);
					$delivery_date = date('d/M/Y', $phpdate);
					}else{
					$delivery_date = "";
				}
				if (isset($data->status) && $data->status == "active"){
					$status = "Active";
				}else{
					$status = "Not Active";
				}
					
			if($data->status == 1){
				$status =  'Active'; 
			}else{
				$status =  'Not Active';
			}
			echo '
			<tr style="border:1px solid #ccc";>
			<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_Id .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->project_desc.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->requisition_number.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->item_no.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->item_cost.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->user_name.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->vendor_name.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$delivery_date.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$status.'</b></td>
			</tr>
			';
		}
		echo '</table>';
		header("Content-type: application/vnd.html");
		header("Content-Disposition: attachment; filename=$file");

	}
	
	public function export_purchaseorder_html($reportProject_to = null,$reportProject_from = null){
	
		$query = purchase_order::query();
		$query->select('purchase_order.*','purchaseorder_item.item_no','purchaseorder_item.project_id','purchaseorder_item.vendor','purchaseorder_item.delivery_date','purchaseorder_item.item_cost','purchaseorder_item.status','project.id as project_uid','project.id as project_uid','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.project_Id','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','us.name as user_name','users.name as vendor_name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id');	
		$query->leftJoin('purchaseorder_item', 'purchaseorder_item.purchase_order_number', '=', 'purchase_order.purchase_order_number');
		$query->leftJoin('project', 'project.project_id', '=', 'purchaseorder_item.project_Id');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users as us', 'project.person_responsible', '=', 'us.id');
		$query->leftJoin('users', 'users.id', '=', 'purchaseorder_item.vendor');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('purchase_order.id', 'desc');
	
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}
		
		$request = $query->get();
		
		if(count($request) == 0){
			
			return Redirect::to('admin/purchaseorder?'.$from.'&'.$to.'&e=*h-');
			
		}
		view()->share('request',$request);
		
		if(count($request) == 0){
			
			return Redirect::to('admin/purchaserequisition?'.$from.'&'.$to.'&e=*h-');
			
		}	
		
		$file="PurchaseOrder.html";
		echo '

		<div style="width:100%;float:left"><h2>Project Report: Purchase Order For A Project</h2></div>
		<table width="100%">

		<tr style="border:1px solid #ccc";>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Manager</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Purchase Order</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Purchase Order Item</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Total Price</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Manager</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Vendor</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Delivery Date</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Status</b></th>
		</tr>
		</tr>
		'; 
		foreach ($request as $data) {
		
		
				if (isset($data->delivery_date)){
					$phpdate = strtotime($data->delivery_date);
					$delivery_date = date('d/M/Y', $phpdate);
					}else{
					$delivery_date = "";
				}
				if (isset($data->status) && $data->status == "active"){
					$status = "Active";
				}else{
					$status = "Not Active";
				}
					
			if($data->status == 1){
				$status =  'Active';
			}else{
				$status =  'Not Active';
			}
			echo '
			<tr style="border:1px solid #ccc";>
			<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_Id .'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->project_desc.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->purchase_order_number.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->item_no.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->item_cost.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->user_name.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->vendor_name.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$delivery_date.'</b></td><td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$status.'</b></td>
			</tr>
			';
		}
		echo '</table>';
		header("Content-type: application/vnd.html");
		header("Content-Disposition: attachment; filename=$file");

	}
	public function export_projectdefinitiondetail_html($reportProject_to = null,$reportProject_from = null,$reportStart_date = null,$reportEnd_date=null){
		
		$query = Project::query();

		$query->select('project.*','portfolio.name as portfolio_name','buckets.name as bucket_name','department_type.name as department_name','users.name as name','createrole.role_name');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project.id', 'desc');
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}
		
		if(isset($reportStart_date) && $reportStart_date != "-"){
			$query->where('project.p_start_date', '>=',$reportStart_date);
			$start_date = "reportStart_date=$reportStart_date";
		}else{
			$start_date = "reportStart_date=$reportStart_date";
		}
		
		if(isset($reportEnd_date) && $reportEnd_date != "-"){			
			$query->where('project.p_end_date', '<=',$reportEnd_date);
			$end_date = "reportEnd_date=$reportEnd_date";
		}else{
			$end_date = "reportEnd_date=$reportEnd_date";
		}
		
		$request = $query->get();
		
		if(count($request) == 0){
			
			return Redirect::to('admin/projectdefinitiondetail?'.$from.'&'.$to.'&'.$start_date.'&'.$end_date.'&e=*h-');
			
		}
		
    
		$file="ProjectDefinitionDetail.html";
		echo '
		<div style="width:100%;float:left"><h2>Project Definition Detail Report</h2></div>
		<table width="100%">

		<tr style="border:1px solid #ccc";>
		<th><b>Project ID</b></th>
		<th><b>Project Description</b></th>
		<th><b>Portfolio ID</b></th>
		<th><b>Portfolio Name</b></th>		
		<th><b>Bucket ID</b></th>		
		<th><b>Bucket Name</b></th>		
		<th><b>Cost Center</b></th>		
		<th><b>Person Responsible</b></th>		
		<th><b>Department</b></th>		
		<th><b>Created On</b></th>		
		<th><b>Start Date</b></th>		
		<th><b>End Date</b></th>		
		<th><b>Status</b></th>
		</tr>
		'; 
		foreach ($request as $data) {
		
			echo  '
				<tr style="border:1px solid #ccc";>
					<td><b>'. $data->project_Id .'</b></td>
					<td><b>'. $data->project_desc .'</b></td>
					<td><b>'. $data->portfolio_id .'</b></td>
					<td><b>'. $data->portfolio_name .'</b></td>
					<td><b>'. $data->bucket_id .'</b></td>
					<td><b>'. $data->bucket_name .'</b></td>
					<td><b>'. $data->cost_centre .'</b></td>
					<td><b>'. $data->name .'</b></td>
					<td><b>'. $data->department_name .'</b></td>
					<td><b>'.$data->role_name .'</b></td>
					<td><b>'.$data->status .'</b></td>
					<td><b>'.date('Y-m-d',strtotime($data->p_start_date)) .'</b></td>
					<td><b>'.date('Y-m-d',strtotime($data->p_end_date)) .'</b></td>
					
				</tr>';
		}
		echo '</table>';
		header("Content-type: application/ProjectDefinitionDetail.html");
		header("Content-Disposition: attachment; filename=$file");

	}
	
	public function export_projectportfolio_html($reportProject_to = null,$reportProject_from = null,$reportbucket_id = null,$reportportfolio_id = null){
	
		$query = Project::query();
		$query->select('project.*','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost','buckets.name as bucket_name','buckets.bucket_id','portfolio.name as portfolio_name');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.id');
		$query->leftJoin('budget_original','budget_original.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'project.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'project.id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'project.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->orderBy('project.id', 'desc');
		
		if(isset($reportbucket_id) && $reportbucket_id != "-"){			
			$query->where('buckets.bucket_id', '=',$reportbucket_id);
			$bucket_id = "bucket_id=$reportbucket_id";
		}else{
			$bucket_id = "bucket_id=$reportbucket_id";
		}
		
		if(isset($reportportfolio_id) && $reportportfolio_id != "-"){			
			$query->where('project.portfolio_id', '=',$reportportfolio_id);
			$portfolio_id = "portfolio_id=$reportportfolio_id";
		}else{
			$portfolio_id = "portfolio_id=$reportportfolio_id";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}
		
		$request = $query->get();
		
		if(count($request) == 0){
			
			return Redirect::to('admin/projectportfolio?'.$from.'&'.$to.'&'.$bucket_id.'&'.$portfolio_id.'&e=*h-');
			
		}	
		
		
		
		
		$file="ProjectPortfolio.html";
		echo '
		<div style="width:100%;float:left"><h2>Project Report: Task Detail Report</h2></div>
		<table width="100%">
		<tr style="border:1px solid #ccc";>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Description</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Bucket ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Bucket Name</b></th>		
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Portfolio ID</b></th>		
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Portfolio Name</b></th>		
		</tr>
		'; 
		foreach ($request as $data) {					
			echo  '
				<tr style="border:1px solid #ccc";>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_Id .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_desc .'</b></td>
					<td><b>'. $data->bucket_id .'</b></td>
					<td><b>'. $data->bucket_name .'</b></td>
					<td><b>'. $data->portfolio_id .'</b></td>
					<td><b>'. $data->portfolio_name .'</b></td>
					
				</tr>';
		}
		echo '</table>';
		header("Content-type: application/ProjectPortfolio.html");
		header("Content-Disposition: attachment; filename=$file");

	}
	
	
	public function export_taskdetail_html($reportProject_to = null,$reportProject_from = null){
	
		$query = TasksSubtask::query();
	
		$query->select('tasks_subtask.*','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','project_phase.phase_name');	
		$query->leftJoin('project', 'project.project_id', '=', 'tasks_subtask.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->orderBy('tasks_subtask.id', 'desc');

		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}
		
		$request = $query->get();
		
		if(count($request) == 0){
			
			return Redirect::to('admin/taskdetail?'.$from.'&'.$to.'&e=*h-');
			
		}
		
		
		$file="TaskDetail.html";
		echo '
		<div style="width:100%;float:left"><h2>Project Report: Task Detail Report</h2></div>
		<table width="100%">
		<tr style="border:1px solid #ccc";>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Project Description</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Phase ID</b></th>
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Phase Description</b></th>		
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Task ID</b></th>		
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Task Description</b></th>		
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Start Date</b></th>		
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>End Date</b></th>		
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Percent Complete</b></th>		
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Duration</b></th>		
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Resource Assigned</b></th>		
		<th style="background:#eee;padding:15px;text-align: center;border:1px solid #ccc"><b>Status</b></th>
		</tr>
		'; 
		foreach ($request as $data) {
			if(isset($data->start_date)){
				$phpdate = strtotime($data->start_date);
				$start_date = date('d/M/Y', $phpdate);
			}else{
				$start_date = "";
			}
			if(isset($data->end_date)){
				$phpdate = strtotime($data->end_date);
				$end_date = date('d/M/Y', $phpdate);
			}else{
				$end_date = "";
			}			
			if($data->status == 'active'){
				$phpdate = strtotime($data->end_date);
				$end_date = date('d/M/Y', $phpdate);
			}else{
				$end_date = "";
			}			
			echo  '
				<tr style="border:1px solid #ccc";>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_Id .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_desc .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->project_phase_id .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->phase_name .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->task_Id .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->task_name .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $start_date .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $end_date .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'. $data->task_name .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->duration .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->resource_name .'</b></td>
					<td style="text-align:center;border:1px solid #ccc;padding:15px"><b>'.$data->status .'</b></td>
					
				</tr>';
		}
		echo '</table>';
		header("Content-type: application/TaskDetail.html");
		header("Content-Disposition: attachment; filename=$file");

	}
	
	public function export_purchaseorder_cs($reportProject_to = null,$reportProject_from = null){
	
		$query = purchase_order::query();
		$query->select('purchase_order.*','purchaseorder_item.item_no','purchaseorder_item.project_id','purchaseorder_item.vendor','purchaseorder_item.delivery_date','purchaseorder_item.item_cost','purchaseorder_item.status','project.id as project_uid','project.id as project_uid','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.project_Id','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','us.name as user_name','users.name as vendor_name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id');	
		$query->leftJoin('purchaseorder_item', 'purchaseorder_item.purchase_order_number', '=', 'purchase_order.purchase_order_number');
		$query->leftJoin('project', 'project.project_id', '=', 'purchaseorder_item.project_Id');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users as us', 'project.person_responsible', '=', 'us.id');
		$query->leftJoin('users', 'users.id', '=', 'purchaseorder_item.vendor');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('purchase_order.id', 'desc');
	
		
		Roleauth::check('project.create');
	
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}
		
		$request = $query->get();
		
		if(count($request) == 0){
			
			return Redirect::to('admin/purchaseorder?'.$from.'&'.$to.'&e=*c-');
			
		}

        $header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Purchase Order" . ",";
        $header .= "Purchase Order Item" . ",";
        $header .= "Total Price" . ",";		
        $header .= "Project Manager" . ",";
        $header .= "Vendor" . ",";
        $header .= "Delivery Date" . ",";
        $header .= "Status" . ",";
       
        print "$header\n";
        foreach ($request as $data) {	

				if (isset($data->delivery_date)){
					$phpdate = strtotime($data->delivery_date);
					$delivery_date = date('d/M/Y', $phpdate);
				}else{
					$delivery_date = "";
				}
				if (isset($data->status) && $data->status == "active"){
					$status = "Active";
				}else{
					$status = "Not Active";
				}
							
		
		
            $row1 = array();
            $row1[] = '"' . $data->project_Id . '"';
            $row1[] = '"' . $data->project_desc . '"';
            $row1[] = '"' . $data->purchase_order_number . '"';
            $row1[] = '"' . $data->item_no . '"';
            $row1[] = '"' . $data->item_cost . '"';
            $row1[] = '"' . $data->user_name . '"';
            $row1[] = '"' . $data->vendor_name . '"';
            $row1[] = '"' . $delivery_date . '"';
            $row1[] = '"' . $status . '"';
           
            $data = join(",", $row1) . "\n";

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=ProjectPortfolio.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data"; 
        } 
    }
	
	public function export_purchaserequisition_cs($reportProject_to = null,$reportProject_from = null){
	
		$query = purchase_requisition::query();
		$query->select('purchase_requisition.*','purchase_item.item_no','purchase_item.project_id','purchase_item.vendor','purchase_item.delivery_date','purchase_item.item_cost','purchase_item.status','project.id as project_uid','project.id as project_uid','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.project_Id','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name as user_name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id','users.name as vendor_name');	
		$query->leftJoin('purchase_item', 'purchase_item.requisition_number', '=', 'purchase_requisition.requisition_number');
		// $query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('project', 'project.project_id', '=', 'purchase_item.project_Id');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users as us', 'project.person_responsible', '=', 'us.id');		
		$query->leftJoin('users', 'users.id', '=', 'purchase_item.vendor');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('purchase_requisition.id', 'desc');
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}
		
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/purchaserequisition?'.$from.'&'.$to.'&e=*c-');
			
		}		
		
        $header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Purchase Requisition" . ",";
        $header .= "Purchase Requisition Item" . ",";
        $header .= "Total Price" . ",";
        $header .= "Project Manager" . ",";
        $header .= "Vendor" . ",";
        $header .= "Delivery Date" . ",";
        $header .= "Status" . ",";
       
        print "$header\n";
        foreach ($request as $data) {	

				if (isset($data->delivery_date)){
					$phpdate = strtotime($data->delivery_date);
					$delivery_date = date('d/M/Y', $phpdate);
				}else{
					$delivery_date = "";
				}
				if (isset($data->status) && $data->status == "active"){
					$status = "Active";
				}else{
					$status = "Not Active";
				}
							
		
		
            $row1 = array();
            $row1[] = '"' . $data->project_Id . '"';
            $row1[] = '"' . $data->project_desc . '"';
            $row1[] = '"' . $data->requisition_number . '"';
            $row1[] = '"' . $data->item_no . '"';
            $row1[] = '"' . $data->item_cost . '"';
            $row1[] = '"' . $data->user_name . '"';
            $row1[] = '"' . $data->vendor_name . '"';
            $row1[] = '"' . $delivery_date . '"';
            $row1[] = '"' . $status . '"';
           
            $data = join(",", $row1) . "\n";

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=ProjectPortfolio.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data"; 
        } 
    }
	
	public function export_projectportfolio_cs($reportProject_to = null,$reportProject_from = null,$reportbucket_id = null,$reportportfolio_id = null) {
		

		$query = Project::query();
		$query->select('project.*','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost','buckets.name as bucket_name','buckets.bucket_id','portfolio.name as portfolio_name');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.id');
		$query->leftJoin('budget_original','budget_original.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'project.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'project.id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'project.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->orderBy('project.id', 'desc');
	
		if(isset($reportbucket_id) && $reportbucket_id != "-"){			
			$query->where('buckets.bucket_id', '=',$reportbucket_id);
			$bucket_id = "bucket_id=$reportbucket_id";
		}else{
			$bucket_id = "bucket_id=$reportbucket_id";
		}
		
		if(isset($reportportfolio_id) && $reportportfolio_id != "-"){			
			$query->where('project.portfolio_id', '=',$reportportfolio_id);
			$portfolio_id = "portfolio_id=$reportportfolio_id";
		}else{
			$portfolio_id = "portfolio_id=$reportportfolio_id";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}
		
		$request = $query->get();		
		if(count($request) == 0){
			
			return Redirect::to('admin/projectportfolio?'.$from.'&'.$to.'&'.$bucket_id.'&'.$portfolio_id.'&e=*c-');
			
		}
		

        $header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Bucket ID" . ",";
        $header .= "Bucket Name" . ",";
        $header .= "Portfolio ID" . ",";
        $header .= "Portfolio Name" . ",";
        
		
        print "$header\n";
        foreach ($request as $data) {	

            $row1 = array();
            $row1[] = '"' . $data->project_Id . '"';
            $row1[] = '"' . $data->project_desc . '"';
            $row1[] = '"' . $data->bucket_id . '"';
            $row1[] = '"' . $data->bucket_name . '"';
            $row1[] = '"' . $data->portfolio_id . '"';
            $row1[] = '"' . $data->portfolio_name . '"';
           
            $data = join(",", $row1) . "\n";

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=ProjectPortfolio.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data"; 
        } 
    }
	
	public function export_taskdetail_cs($reportProject_to = null,$reportProject_from = null){

		$query = TasksSubtask::query();
	
		$query->select('tasks_subtask.*','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','project_phase.phase_name');	
		$query->leftJoin('project', 'project.project_id', '=', 'tasks_subtask.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->orderBy('tasks_subtask.id', 'desc');

		if(isset($reportProject_from) && $reportProject_from != "-"){
			$query->where('project.project_id', '>=',$reportProject_from);
		}
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		$request = $query->get();

        $header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Phase ID" . ",";
        $header .= "Phase Description" . ",";
        $header .= "Task ID" . ",";
        $header .= "Task Description" . ",";
        $header .= "Start Date" . ",";
        $header .= "End Date" . ",";
        $header .= "Percent Complete" . ",";
        $header .= "Duration" . ",";
        $header .= "Resource Assigned" . ",";
        $header .= "Status" . ",";
		
        print "$header\n";
		
        foreach ($request as $data) {	
		
			if(isset($data->budget_org_overall) && isset($data->budget_return_overall) && isset($data->budget_supplement_overall)){
				$available_budget = ($data->budget_org_overall + $data->budget_supplement_overall) - $data->budget_return_overall; 
			}else{
				$available_budget = "";
			}									
						
			if(isset($data->start_date)){
				$start_date = $data->start_date;
			}else{
				$start_date = "";			
			}
			
			if(isset($data->end_date)){
				$end_date = $data->end_date;
			}else{
				$end_date = "";			
			}
			
	
            $row1 = array();
            $row1[] = '"' . $data->project_Id . '"';
            $row1[] = '"' . $data->project_desc . '"';
            $row1[] = '"' . $data->project_phase_id . '"';
            $row1[] = '"' . $data->phase_name . '"';
            $row1[] = '"' . $data->task_Id . '"';
            $row1[] = '"' . $data->task_name . '"';
            $row1[] = '"' . $start_date . '"';            
            $row1[] = '"' . $end_date . '"';
            $row1[] = '"' . $data->task_name . '"';
            $row1[] = '"' . $data->duration . '"';
            $row1[] = '"' . $data->resource_name . '"';
            $row1[] = '"' . $data->status . '"';
            $data = join(",", $row1) . "\n";
			// echo'<pre>';print_r($data);die;
            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=TaskDetail.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data"; 
        } 
    }
	public function export_costbudget_cs($reportProject_to = null,$reportProject_from = null,$reportStart_date = null,$reportEnd_date = null) {
	
		$query = OriginalBudget::query();
		$query->select('budget_original.*','project.project_name','project.project_desc','project.p_start_date','project.p_end_date','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost');
		$query->leftJoin('project','project.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'budget_original.project_id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'budget_original.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'budget_original.project_Id');
		$query->orderBy('budget_original.id', 'desc');
		
		
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('budget_original.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
		}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('budget_original.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}

		if(isset($reportStart_date) && $reportStart_date != "-"){			
			$query->where('project.p_start_date', '>=',$reportStart_date);
			$start_date = "reportStart_date=$reportStart_date";
		}else{
			$start_date = "reportStart_date=$reportStart_date";
		}

		if(isset($reportEnd_date) && $reportEnd_date != "-"){			
			$query->where('project.p_end_date', '<=',$reportEnd_date);
			$end_date = "reportEnd_date=$reportEnd_date";
		}else{
			$end_date = "reportEnd_date=$reportEnd_date";
		}
			
		
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/costbudget?'.$from.'&'.$to.'&'.$start_date.'&'.$end_date.'&e=*c-');
			
		}

        $header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Planned Cost" . ",";
        $header .= "Actual Cost" . ",";
        $header .= "Overall Budget" . ",";
        $header .= "Overall Supplement" . ",";
        $header .= "Overall Return" . ",";
        $header .= "Available Budget" . ",";
        $header .= "Value" . ",";
        $header .= "Start Date" . ",";
        $header .= "End Date" . ",";
		
        print "$header\n";
        foreach ($request as $data) {	
		
			if(isset($data->budget_org_overall) && isset($data->budget_return_overall) && isset($data->budget_supplement_overall)){
				$available_budget = ($data->budget_org_overall + $data->budget_supplement_overall) - $data->budget_return_overall; 
			}else{
				$available_budget = "";
			}									
						
			if(isset($data->p_start_date)){
				$start_date = $data->p_start_date;
			}else{
				$start_date = "";			
			}
			
			if(isset($data->p_end_date)){
				$end_date = $data->p_end_date;
			}else{
				$end_date = "";			
			}
	
            $row1 = array();
            $row1[] = '"' . $data->project_Id . '"';
            $row1[] = '"' . $data->project_desc . '"';
            $row1[] = '"' . '-' . '"';
            $row1[] = '"' . $data->actuall_cost . '"';
            $row1[] = '"' . $data->budget_org_overall . '"';
            $row1[] = '"' . $data->budget_supplement_overall . '"';
            $row1[] = '"' . $data->budget_return_overall . '"';            
            $row1[] = '"' . $available_budget . '"';
            $row1[] = '"' . '-' . '"';
            $row1[] = '"' . $start_date . '"';
            $row1[] = '"' . $end_date . '"';
            $data = join(",", $row1) . "\n";

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Costbudget.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data"; 
        } 
    }
		
	public function export_timesheet_cs($reportProject_to = null,$reportProject_from = null){
	
		$query = Project::query();
		$query->select('project.*','project_phase.phase_Id','taskassign.id as task_id','users.name as first_name','users.lname as last_name','budget_original.overall as budget_org_overall','budget_return.overall as budget_return_overall','budget_supplement.overall as budget_supplement_overall','project_gr_cost.value as actuall_cost');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.id');
		$query->leftJoin('budget_original','budget_original.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_return','budget_return.project_id', '=', 'project.project_Id');
		$query->leftJoin('budget_supplement','budget_supplement.project_id', '=', 'project.project_Id');
		$query->leftJoin('taskassign', 'taskassign.project_id', '=', 'project.id');
		$query->leftJoin('project_gr_cost', 'project_gr_cost.project_id', '=', 'project.project_Id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->orderBy('project.id', 'desc');
		
		if(isset($reportProject_from)){ 
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to)){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		

		$request = $query->get();
	
		$header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Project Cost" . ",";
        $header .= "Actual Cost" . ",";
        $header .= "Overall Budget" . ",";
        $header .= "Annual Budget" . ",";
        $header .= "Available Budget" . ",";
        $header .= "Resource Cost" . ",";
        $header .= "Total Time Entered" . ","; 
        $header .= "% Of Total Cost" . ","; 

        print "$header\n";
        foreach ($request as $data) {	
			if(isset($data->budget_org_overall) && isset($data->budget_return_overall) && isset($data->budget_supplement_overall)){
				$annual_budget =  ($data->budget_org_overall + $data->budget_supplement_overall) - $data->budget_return_overall; 
			}else{
				$annual_budget =  "-";
			}
			
            $row1 = array();
            $row1[] = '"' . $data->project_Id . '"';
            $row1[] = '"' . $data->project_desc . '"';
            $row1[] = '"-"';
            $row1[] = '"' . $data->actuall_cost . '"';
            $row1[] = '"' . $data->budget_org_overall . '"';
            $row1[] = '"' . $annual_budget . '"';
            $row1[] = '"-"';
            $row1[] = '"-"';
            $row1[] = '"-"';
            $row1[] = '"-"';
            $row1[] = '"-"';
            $data = join(",", $row1) . "\n";

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=TimeSheet.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data"; 
        }
	}	

	
	public function export_checklist_cs($reportProject_to = null,$reportProject_from = null,$reportName = null,$reportChecklist_id = null)
	{	
		$query = projectchecklist::query();
		$query->select('project_checklist.*','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','taskassign.id as project_task_id');	
		$query->leftJoin('project', 'project.project_id', '=', 'project_checklist.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('taskassign','taskassign.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_checklist.id', 'desc');
		
		
		
		
if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
			}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}

		if(isset($reportChecklist_id) && $reportChecklist_id != "-"){			
			$query->where('project_checklist.checklist_id', '=',$reportChecklist_id);		
			$check_id = "check_id=$reportChecklist_id";
		}else{
			$check_id = "check_id=$reportChecklist_id";
		}

		if(isset($reportName) && $reportName != "-"){		
			$query->where('project.p_end_date', '<=',$reportName);
			$name = "name=$reportName";
		}else{
			$name = "name=$reportName";
		}
		
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/checklistreport?'.$from.'&'.$to.'&'.$name.'&'.$check_id.'&e=*c-');
			
		}

		
		
		
		
		
	
		
		$query->orderBy('project_checklist.id', 'desc');
		$request = $query->get();

        $header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Project Manager" . ",";
        $header .= "Checklist ID" . ",";
        $header .= "Check List Description" . ",";
        $header .= "Checklist Status" . ",";
        $header .= "Checklist Date" . ","; 

        print "$header\n";
        foreach ($request as $data) {	
		
			if(isset($data->budget_org_overall) && isset($data->budget_return_overall) && isset($data->budget_supplement_overall)){
				$available_budget = ($data->budget_org_overall + $data->budget_supplement_overall) - $data->budget_return_overall; 
			}else{
				$available_budget = "";
			}									 
						
			if(isset($data->created_on)){
				$created_on = $data->created_on;
			}else{
				$created_on = "";			
			}
				if($data->checklist_status == 'active'){
					$status = "active";
				}else{
					$status = "not active";
				}
	
            $row1 = array();
            $row1[] = '"' . $data->project_Id . '"';
            $row1[] = '"' . $data->project_desc . '"';
            $row1[] = '"' . $data->name . '"';
            $row1[] = '"' . $data->checklist_id . '"';
            $row1[] = '"' . $data->checklist_name . '"';
            $row1[] = '"' . $status . '"';
            $row1[] = '"' . $created_on . '"';
            $data = join(",", $row1) . "\n";

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Checklist.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data"; 
        } 
    }
	
	public function export_milestonereport_cs($reportProject_to = null,$reportProject_from=null,$project_phase_id=null,$project_task_id=null,$project_milestone_Id=null) {
	
	
		$query = Projectmilestone::query();
		$query->select('project_milestone.*','project_milestone.milestone_Id as project_milestone_Id','project.id as project_uid','project.project_Id','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name','project_phase.phase_Id as project_phase_id','tasks_subtask.task_Id as project_task_id');	
		$query->leftJoin('project', 'project.project_id', '=', 'project_milestone.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('tasks_subtask','tasks_subtask.project_id', '=', 'project.project_id');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('project_phase','project_phase.project_id', '=', 'project.project_id');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_milestone.id', 'desc');

		
		
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
			$to = "reportProject_to=$reportProject_to";
			}else{
			$to = "reportProject_to=$reportProject_to";
		}

		if(isset($reportProject_from) && $reportProject_from != "-"){			
			$query->where('project.project_id', '<=',$reportProject_from);
			$from = "reportProject_from=$reportProject_from";
		}else{
			$from = "reportProject_from=$reportProject_from";
		}

		if(isset($project_phase_id) && $project_phase_id != "-"){			
			$query->where('project_phase.phase_Id', '=',$project_phase_id);
			$project_phase_id = "project_phase_id=$project_phase_id";
		}else{
			$project_phase_id = "project_phase_id=$project_phase_id";
		}

		if(isset($project_task_id) && $project_task_id != "-"){		
			$query->where('tasks_subtask.task_Id', '=',$project_task_id);
			$project_task_id = "project_task_id=$project_task_id";
		}else{
			$project_task_id = "project_task_id=$project_task_id";
		}

		if(isset($project_milestone_Id) && $project_milestone_Id != "-"){		
			$query->where('project_milestone.milestone_Id', '=',$project_milestone_Id);
			$project_milestone_Id = "project_milestone_Id=$project_milestone_Id";
		}else{
			$project_milestone_Id = "project_milestone_Id=$project_milestone_Id";
		}
		$request = $query->get();	
		if(count($request) == 0){
			
			return Redirect::to('admin/milestonereport?'.$from.'&'.$to.'&'.$project_phase_id.'&'.$project_task_id.'&'.$project_milestone_Id.'&e=*c-');
			
		}
	


        $header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Phase ID" . ",";
        $header .= "Task ID" . ",";
        $header .= "Milestone ID" . ",";
        $header .= "Milestone Description" . ",";
        $header .= "Scheduled Date" . ","; 
        $header .= "Actual Date" . ","; 

        print "$header\n";
        foreach ($request as $data) {
			
			if(isset($data->schedule_date)){
				$phpdate = strtotime($data->schedule_date);
				$schedule_date = date('d/M/Y', $phpdate);
				
			}else{
				$schedule_date = "";
			}
			
			if(isset($data->actual_date)){
				$phpdate = strtotime($data->actual_date);
				$actual_date = date('d/M/Y', $phpdate);
				
			}else{
				 $actual_date = "";
			}
		
		
	
            $row1 = array();
            $row1[] = '"' . $data->project_Id . '"';
            $row1[] = '"' . $data->project_desc . '"';
            $row1[] = '"' . $data->project_phase_id . '"';
            $row1[] = '"' . $data->project_task_id . '"';
            $row1[] = '"' . $data->project_milestone_Id . '"';
            $row1[] = '"' . $data->milestone_name . '"';
            $row1[] = '"' . $schedule_date . '"';
            $row1[] = '"' . $actual_date .'"';           
            
            $data = join(",", $row1) . "\n";

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Milestone.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data"; 
        } 
    }
	
	public function export_riskanalysis_cs($reportProject_to = null,$reportProject_from=null,$status=null,$risk_status=null){
		$query = Risk::query();
		$query->select('risk_analysis.*','project.id as project_uid','project.person_responsible','project.project_name','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','users.name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name');	
		$query->leftJoin('project', 'project.project_id', '=', 'risk_analysis.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		
		if(isset($reportProject_from) && $reportProject_from != "-"){
			$query->where('risk_analysis.project_id', '>=',$reportProject_from);
		}
		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('risk_analysis.project_id', '<=',$reportProject_to);
		}
		if(isset($status) && $status != "-"){
			$query->where('risk_analysis.status', '=',$status);
		}
		$query->orderBy('risk_analysis.id', 'desc');		
		$request = $query->get();	


        $header = "Project ID" . ",";
        $header .= "Project Manager" . ",";
        $header .= "Risk ID" . ",";
        $header .= "Risk Type" . ",";
        $header .= "Risk Score" . ",";
        $header .= "Status" . ",";

        print "$header\n";
        foreach ($request as $data) { 
			
			if($data->status == 1){
				$status =  'Active';
			}else{
				$status =  'Not Active';
			}
            $row1 = array();
            $row1[] = '"' . $data->project_id . '"';
            $row1[] = '"' . $data->name . '"';
            $row1[] = '"' . $data->risk_id . '"';
            $row1[] = '"' . '-' . '"';
            $row1[] = '"' . '-' . '"';
            $row1[] = '"' . $status . '"';          
            
            $data = join(",", $row1) . "\n";

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Riskanalysis.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data"; 
        } 
    }
	
	public function export_projectdefinitiondetail_cs($reportProject_to = null,$reportProject_from = null,$reportStart_date = null,$reportEnd_date=null){
		
		$query = Project::query();

		$query->select('project.*','portfolio.name as portfolio_name','buckets.name as bucket_name','department_type.name as department_name','users.name as name','createrole.role_name');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project.id', 'desc');
		
		if(isset($reportProject_from) && $reportProject_from != "-"){
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
		
		if(isset($reportStart_date) && $reportStart_date != "-"){
			$query->where('project.p_start_date', '>=',$reportStart_date);
		}
		
		if(isset($reportEnd_date) && $reportEnd_date != "-"){			
			$query->where('project.p_end_date', '<=',$reportEnd_date);
		}
		$request = $query->get();

        $header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Portfolio ID" . ",";
        $header .= "Portfolio Name" . ",";
        $header .= "Bucket ID" . ",";
        $header .= "Bucket Name" . ",";
        $header .= "Cost Center" . ","; 
        $header .= "Person Responsible" . ","; 
        $header .= "Department" . ","; 
        $header .= "Created On" . ","; 
        $header .= "Start Date" . ","; 
        $header .= "End Date" . ","; 
        $header .= "Status" . ",";  

        print "$header\n";
        foreach ($request as $data) {
			if(isset($data->p_start_date)){
				$p_start_date = $data->p_start_date;
			}else{
				$p_start_date = "";			
			}
			if(isset($data->p_end_date)){
				$p_end_date = $data->p_end_date;
			}else{
				$p_end_date = "";			
			}
			
			if($data->status == 'active'){
					$status = "active";
			}else{
					$status = "not active";
			}
	
	
            $row1 = array();
            $row1[] = '"' . $data->project_Id . '"';
            $row1[] = '"' . $data->project_desc . '"';
            $row1[] = '"' . $data->portfolio_id . '"';
            $row1[] = '"' . $data->portfolio_name . '"';
            $row1[] = '"' . $data->bucket_id . '"';
            $row1[] = '"' . $data->bucket_name . '"';
            $row1[] = '"' . $data->cost_centre . '"';
            $row1[] = '"' . $data->name . '"';
            $row1[] = '"' . $data->department_name . '"';
            $row1[] = '"' . $data->role_name . '"';
            $row1[] = '"' . $data->p_start_date . '"';
            $row1[] = '"' . $data->p_end_date . '"'; 
            $row1[] = '"' . $data->status . '"'; 
			
			
            
            $data = join(",", $row1) . "\n";

            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=ProjectDefinitionDetail.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data"; 
        } 
    }
	
	public function export_phasedetail_cs($reportProject_to = null,$reportProject_from = null,$phase_id = null,$portfolio_id=null,$bucket_id=null){
	
		
	
		$query = Projectphase::query();		
		$query->select('project_phase.*','project.id as project_uid','project.project_Id','project.project_name','project.project_desc','project.bucket_id','project.cost_centre','project.project_desc','project.a_start_date','project.a_end_date','project.f_start_date','project.f_end_date','project.sch_date','project.p_end_date','project.created_by','project.p_start_date','buckets.name as bucket_name','department_type.name as department_name','users.name as name','createrole.role_name','portfolio.name as portfolio_name','portfolio.port_id as portfolio_id','portfolio_type.name as portfolio_type','portfolio.port_id as portfolio_id','project.bucket_id','project_type.name as project_type_name');
		$query->leftJoin('project', 'project.project_Id', '=', 'project_phase.project_id');
		$query->leftJoin('project_type', 'project_type.id', '=', 'project.project_type');
		$query->leftJoin('portfolio', 'portfolio.port_id', '=', 'project.portfolio_id');
		$query->leftJoin('portfolio_type', 'portfolio_type.id', '=', 'portfolio.type');
		$query->leftJoin('department_type', 'department_type.id', '=', 'project.department');
		$query->leftJoin('buckets', 'buckets.bucket_id', '=', 'project.bucket_id');
		$query->leftJoin('users', 'users.id', '=', 'project.person_responsible');
		$query->leftJoin('createrole', 'createrole.id', '=', 'project.created_by');
		$query->orderBy('project_phase.id', 'desc');
		
		if(isset($reportProject_from) && $reportProject_from != "-"){
			$query->where('project.project_id', '>=',$reportProject_from);
		}

		if(isset($reportProject_to) && $reportProject_to != "-"){			
			$query->where('project.project_id', '<=',$reportProject_to);
		}
	
		if(isset($phase_id) && $phase_id != "-"){			
			$query->where('project_phase.phase_id', '=',$phase_id);
		}
		
		if(isset($portfolio_id) && $portfolio_id != "-"){			
			$query->where('project.portfolio_id', '=',$portfolio_id);
		}
		
		if(isset($bucket_id) && $bucket_id != "-"){			
			$query->where('project.bucket_id', '=',$bucket_id);
		} 
		
		$request = $query->get();

        $header = "Project ID" . ",";
        $header .= "Project Description" . ",";
        $header .= "Phase ID" . ",";
        $header .= "Phase Description" . ",";
        $header .= "Portfolio ID" . ",";
        $header .= "Portfolio Name" . ",";
        $header .= "Bucket ID" . ",";
        $header .= "Bucket Name" . ",";
        $header .= "Cost Center" . ","; 
        $header .= "Person Responsible" . ","; 
        $header .= "Department" . ","; 
        $header .= "Created On" . ","; 
        $header .= "Start Date" . ","; 
        $header .= "End Date" . ","; 
        $header .= "Status" . ",";  

        print "$header\n";
        foreach ($request as $data) {
			
			if(isset($data->p_start_date)){
				$p_start_date = $data->p_start_date;
			}else{
				$p_start_date = "";			
			}
			if(isset($data->p_end_date)){
				$p_end_date = $data->p_end_date;
			}else{
				$p_end_date = "";			
			}			
			if($data->status == 'active'){
					$status = "active";
			}else{
					$status = "not active";
			}	
	
            $row1 = array();
            $row1[] = '"' . $data->project_Id . '"';
            $row1[] = '"' . $data->project_desc . '"';
            $row1[] = '"' . $data->portfolio_id . '"';
            $row1[] = '"' . $data->portfolio_name . '"';
			$row1[] = '"' . $data->phase_Id . '"';
            $row1[] = '"' . $data->phase_name . '"';
            $row1[] = '"' . $data->bucket_id . '"';
            $row1[] = '"' . $data->bucket_name . '"';
            $row1[] = '"' . $data->cost_centre . '"';
            $row1[] = '"' . $data->name . '"';
            $row1[] = '"' . $data->department_name . '"';
            $row1[] = '"' . $data->role_name . '"';
            $row1[] = '"' . $data->p_start_date . '"';
            $row1[] = '"' . $data->p_end_date . '"'; 
            $row1[] = '"' . $data->status . '"'; 
			$data = join(",", $row1) . "\n";
			header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=PhaseDetail.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data"; 
        } 
    }
	
	
	

}
