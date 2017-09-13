<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Planningunit;
use Illuminate\Support\Facades\Validator;
use App\Project;
use Illuminate\Support\Facades\DB;
use App\BudgetReturn;
use App\BudgetSupplement;
use App\OriginalBudget;
use Illuminate\Support\Facades\Input;
use Carbon;
use Illuminate\Http\JsonResponse;
use App\User;
use App\Roleauth;

class ProjectBudgetController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        Roleauth::check('budget.original.index');
        
        $returnBudget = DB::table('budget_return')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                ->where('status', '=', 'active')
                ->groupBy(DB::raw('project_id'))
                ->get();

        $supplementBudget = DB::table('budget_supplement')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                ->where('status', '=', 'active')
                ->groupBy(DB::raw('project_id'))
                ->get();

        $originalBudget = DB::table('budget_original')
                ->select('budget_original.*', 'project.project_name', 'users.name', 'project.project_id as pid')
                ->leftJoin('project', 'budget_original.project_id', '=', 'project.id')
                ->leftJoin('users', 'budget_original.changed_by', '=', 'users.id')
                ->where('budget_original.status', '=', 'active')
                ->get();


        foreach ($originalBudget as $budget) {
            $budget->current = $budget->overall;
            $budget->return = 0;
            $budget->supplement = 0;
            foreach ($supplementBudget as $supplement) {
                if ($budget->project_id == $supplement->project_id) {
                    $budget->supplement = $supplement->total;
                    $budget->current += $supplement->total;
                }
            }
            foreach ($returnBudget as $return) {
                if ($budget->project_id == $return->project_id) {
                    $budget->return = $return->total;
                    $budget->current -= $return->total;
                }
            }
        }
        return view('admin.budget_original.index', compact('originalBudget'));
    }

    public function create($module) {
        Roleauth::check('budget.' . $module . '.create');

        $Date = new \DateTime();
        $year = $Date->format('Y');
        if ($module == 'original') {
            $project_id = Project:: select(DB::raw("CONCAT(id ,'  (',project_name,')') AS project_name"), 'id')->pluck('project_name', 'id')->toArray();
            if ($project_id == null)
                $project_id = array();
        } else {
            $project = DB::table('budget_original')
                            ->select(DB::raw("CONCAT(budget_original.project_id ,'  (',project.project_name,')') AS project_name"), 'budget_original.project_id')
                            ->leftJoin('project', 'budget_original.project_id', '=', 'project.id')
                            ->where('budget_original.status', '=', 'active')
                            ->get()->toArray();

            $project_id = [];
            foreach ($project as $array) {
                $project_id[$array->project_id] = $array->project_name;
            }
            if ($project_id == null)
                $project_id = array();
        }
        switch ($module) {
            case 'original':
                return view('admin.budget_original.create', compact('project_id', 'year'));
                break;

            case 'supplement':
                return view('admin.budget_supplement.create', compact('project_id', 'year'));
                break;

            case 'returns':
                return view('admin.budget_returns.create', compact('project_id', 'year'));
                break;

            default:
                break;
        }
    }

    public function store($module) {
        Roleauth::check('budget.' . $module . '.create');

        $post = Input::all();
        $post['status'] = 'active';
        $user = \Auth::User()->id;
        $company_id = \Auth::User()->company_id;
        $post['company_id'] = $company_id;
        $post['created_by'] = $user;
        $post['changed_by'] = $user;
        switch ($module) {
            case 'original':
                $validationmessages = [
                    'project_id.required' => "Please select project",
                    'project_id.unique' => 'Project must be unique',
                    'period_from.required' => "Please select period",
                    'period_to.required' => "Please select period",
                    'overall.required' => "Please enter overall budget",
                    'overall.numeric' => "Please enter only number",
                ];
                $validator = Validator::make($post, [
                            'project_id' => "required|unique:budget_original",
                            'period_from' => "required",
                            'period_to' => "required",
                            'overall' => "required|numeric",
                                ], $validationmessages);

                if ($validator->fails()) {
                    $msgs = $validator->messages();
                    return redirect('admin/projectbudget/original')->withErrors($validator)->withInput(Input::all());
                }
                $original = OriginalBudget::create($post);
                return redirect('admin/originalbudget');
                break;

            case 'supplement':
                $validator = BudgetSupplement::validateBudgetSupplement($post);

                if ($validator->fails()) {
                    $msgs = $validator->messages();
                    return redirect('admin/projectbudget/supplement')->withErrors($validator)->withInput(Input::all());
                }
                $supplement = BudgetSupplement::create($post);
                return redirect('admin/supplementbudget');
                break;

            case 'returns':
                $validationmessages = [
                    'project_id.required' => "Please select project",
                    'period_from.required' => "Please select period",
                    'period_to.required' => "Please select period",
                    'overall.required' => "Please enter overall budget",
                    'overall.numeric' => "Please enter only number",
                ];
                $validator = Validator::make($post, [
                            'project_id' => "required",
                            'period_from' => "required",
                            'period_to' => "required",
                            'overall' => "required|numeric",
                                ], $validationmessages);

                if ($validator->fails()) {
                    $msgs = $validator->messages();
                    return redirect('admin/projectbudget/returns')->withErrors($validator)->withInput(Input::all());
                }
                $returnsStored = BudgetReturn::create($post);
                return redirect('admin/returnbudget');
                break;

            default:
                break;
        }
    }

    public function edit($module, $id) {
        Roleauth::check('budget.' . $module . '.edit');

        $Date = new \DateTime();
        $year = $Date->format('Y');
        $project_id = Project:: select(DB::raw("CONCAT(id ,'  (',project_name,')') AS project_name"), 'id')->pluck('project_name', 'id')->toArray();
        if ($project_id == null)
            $project_id = array();
        switch ($module) {
            case 'original':
                $getOriginalBudget = OriginalBudget::find($id);
                $years = intval($getOriginalBudget->period_from);
                return view('admin.budget_original.create', compact('years', 'project_id', 'year', 'getOriginalBudget'));
                break;

            case 'supplement':
                $getsupplementBudget = BudgetSupplement::find($id);
                $years = intval($getsupplementBudget->period_from);

                $supplementBudget = DB::table('budget_supplement')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                        ->where('status', '=', 'active')
                        ->where('project_id', '=', $getsupplementBudget->project_id)
                        ->groupBy(DB::raw('project_id'))
                        ->get();
                $originalBudget = DB::table('budget_original')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                        ->where('status', '=', 'active')
                        ->where('project_id', '=', $getsupplementBudget->project_id)
                        ->groupBy(DB::raw('project_id'))
                        ->get();

                $returnBudget = DB::table('budget_return')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                        ->where('status', '=', 'active')
                        ->where('project_id', '=', $getsupplementBudget->project_id)
                        ->groupBy(DB::raw('project_id'))
                        ->get();
                if (count($supplementBudget) > 0)
                    $supplement = $supplementBudget[0]->total;
                else
                    $supplement = 0;
                if (count($originalBudget) > 0)
                    $original = $originalBudget[0]->total;
                else
                    $original = 0;
                if (count($returnBudget) > 0)
                    $return = $returnBudget[0]->total;
                else
                    $return = 0;

                $current = $original + $supplement - $return;
                return view('admin.budget_supplement.create', compact('years', 'project_id', 'year', 'getsupplementBudget', 'current'));
                break;

            case 'returns':
                $getReturnBudget = BudgetReturn::find($id);
                $years = intval($getReturnBudget->period_from);

                $supplementBudget = DB::table('budget_supplement')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                        ->where('status', '=', 'active')
                        ->where('project_id', '=', $getReturnBudget->project_id)
                        ->groupBy(DB::raw('project_id'))
                        ->get();
                $originalBudget = DB::table('budget_original')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                        ->where('status', '=', 'active')
                        ->where('project_id', '=', $getReturnBudget->project_id)
                        ->groupBy(DB::raw('project_id'))
                        ->get();

                $returnBudget = DB::table('budget_return')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                        ->where('status', '=', 'active')
                        ->where('project_id', '=', $getReturnBudget->project_id)
                        ->groupBy(DB::raw('project_id'))
                        ->get();
                if (count($supplementBudget) > 0)
                    $supplement = $supplementBudget[0]->total;
                else
                    $supplement = 0;
                if (count($originalBudget) > 0)
                    $original = $originalBudget[0]->total;
                else
                    $original = 0;
                if (count($returnBudget) > 0)
                    $return = $returnBudget[0]->total;
                else
                    $return = 0;

                $current = $original + $supplement - $return;
                return view('admin.budget_returns.create', compact('years','project_id', 'year', 'getReturnBudget', 'current'));
                break;

            default:
                break;
        }
    }

    public function update($module, $id) {
        Roleauth::check('budget.' . $module . '.edit');

        $post = Input::all();
        for ($year = 1; $year <= 5; $year++)
            $post['year' . $year] = (!array_key_exists('year' . $year, $post)) ? NULL : $post['year' . $year];
        $user = \Auth::User()->id;
        $post['changed_by'] = $user;
        switch ($module) {
            case 'original':
                $getOriginalBudget = OriginalBudget::find($id);
                $validationmessages = [
                    'period_from.required' => "Please select period",
                    'period_to.required' => "Please select period",
                    'overall.required' => "Please enter overall budget",
                    'overall.numeric' => "Please enter only number",
                ];
                $validator = Validator::make($post, [
                            'period_from' => "required",
                            'period_to' => "required",
                            'overall' => "required|numeric",
                                ], $validationmessages);

                if ($validator->fails()) {
                    $msgs = $validator->messages();
                    return redirect('admin/projectbudget/original')->withErrors($validator)->withInput(Input::all());
                }
                $getOriginalBudget->update($post);
                return redirect('admin/originalbudget');
                break;

            case 'supplement':
                $getsupplementBudget = BudgetSupplement::find($id);
                $validationmessages = [
                    'project_id.required' => "Please select project",
                    'period_from.required' => "Please select period",
                    'period_to.required' => "Please select period",
                    'overall.required' => "Please enter overall budget",
                    'overall.numeric' => "Please enter only number",
                ];
                $validator = Validator::make($post, [
                            'project_id' => "required",
                            'period_from' => "required",
                            'period_to' => "required",
                            'overall' => "required|numeric",
                                ], $validationmessages);

                if ($validator->fails()) {
                    $msgs = $validator->messages();
                    return redirect('admin/projectbudget/supplement')->withErrors($validator)->withInput(Input::all());
                }
                $getsupplementBudget->update($post);
                return redirect('admin/supplementbudget');
                break;

            case 'returns':
                $getReturnBudget = BudgetReturn::find($id);

                $validationmessages = [
                    'project_id.required' => "Please select project",
                    'period_from.required' => "Please select period",
                    'period_to.required' => "Please select period",
                    'overall.required' => "Please enter overall budget",
                    'overall.numeric' => "Please enter only number",
                ];
                $validator = Validator::make($post, [
                            'project_id' => "required",
                            'period_from' => "required",
                            'period_to' => "required",
                            'overall' => "required|numeric",
                                ], $validationmessages);

                if ($validator->fails()) {
                    $msgs = $validator->messages();
                    return redirect('admin/projectbudget/returns')->withErrors($validator)->withInput(Input::all());
                }
                $getReturnBudget->update($post);
                return redirect('admin/returnbudget');
                break;

            default:
                break;
        }
    }

    public function destroy($module, $id) {
        Roleauth::check('budget.' . $module . '.delete');

        switch ($module) {
            case 'original':
                $project_id = OriginalBudget::select('project_id')->where('id', $id)->where('status', '=', 'active')->pluck('project_id', 'id')->toArray();
                $return = BudgetReturn::where('project_id', $project_id)->where('status', '=', 'active')->select('project_id')->pluck('project_id');
                $supplement = BudgetSupplement::where('project_id', $project_id)->where('status', '=', 'active')->select('project_id')->pluck('project_id');
                if (count($supplement) || count($return) > 0) {
                    return response()->json(['status' => 'Supplement and Return budget exist for this project , so Original budget cannot be deleted.']);
                } else {
                    DB::table('budget_original')->where('id', $id)->update(['status' => 'inactive']);
                    return response()->json(['status' => 'Original Budget Deleted Successfully.']);
                }
                break;

            case 'supplement':
                DB::table('budget_supplement')->where('id', $id)->update(['status' => 'inactive']);
                return response()->json(['status' => 'Supplement Budget Deleted Successfully.']);
                break;

            case 'returns':
                DB::table('budget_return')->where('id', $id)->update(['status' => 'inactive']);
                return response()->json(['status' => 'Return Budget Deleted Successfully.']);
                break;

            default:
                break;
        }
    }

    public function returnBudget() {
        Roleauth::check('budget.returns.index');

        $returnBudget = DB::table('budget_return')
                ->select('budget_return.*', 'project.project_name', 'users.name', 'project.project_id as pid')
                ->leftJoin('project', 'budget_return.project_id', '=', 'project.id')
                ->leftJoin('users', 'budget_return.changed_by', '=', 'users.id')
                ->where('budget_return.status', '=', 'active')
                ->get();

        $supplementBudget = DB::table('budget_supplement')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                ->where('status', '=', 'active')
                ->groupBy(DB::raw('project_id'))
                ->get();

        $originalBudget = DB::table('budget_original')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                ->where('status', '=', 'active')
                ->groupBy(DB::raw('project_id'))
                ->get();

        $return_Budget = DB::table('budget_return')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                ->where('status', '=', 'active')
                ->groupBy(DB::raw('project_id'))
                ->get();

        foreach ($returnBudget as $budget) {
            $budget->current = 0;
            foreach ($supplementBudget as $supplement) {
                if ($budget->project_id == $supplement->project_id) {

                    $budget->current += $supplement->total;
                }
            }
            foreach ($originalBudget as $original) {
                if ($budget->project_id == $original->project_id) {

                    $budget->current += $original->total;
                }
            }
            foreach ($return_Budget as $return) {
                if ($budget->project_id == $return->project_id) {
                    $budget->current -= $return->total;
                }
            }
        }
        return view('admin.budget_returns.index', compact('returnBudget'));
    }

    public function supplementBudget() {
        Roleauth::check('budget.supplement.index');

        $returnBudget = DB::table('budget_return')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                ->where('status', '=', 'active')
                ->groupBy(DB::raw('project_id'))
                ->get();
        $originalBudget = DB::table('budget_original')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                ->where('status', '=', 'active')
                ->groupBy(DB::raw('project_id'))
                ->get();
        $currentBudget = DB::table('budget_supplement')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                ->where('status', '=', 'active')
                ->groupBy(DB::raw('project_id'))
                ->get();
        $supplementBudget = DB::table('budget_supplement')
                ->select('budget_supplement.*', 'project.project_name', 'users.name', 'project.project_id as pid')
                ->leftJoin('project', 'budget_supplement.project_id', '=', 'project.id')
                ->leftJoin('users', 'budget_supplement.changed_by', '=', 'users.id')
                ->where('budget_supplement.status', '=', 'active')
                ->get();

        foreach ($supplementBudget as $budget) {
            $budget->current = 0;
            foreach ($originalBudget as $original) {
                if ($budget->project_id == $original->project_id) {

                    $budget->current += $original->total;
                }
            }
            foreach ($currentBudget as $current) {
                if ($budget->project_id == $current->project_id) {
                    $budget->current += $current->total;
                }
            }
            foreach ($returnBudget as $return) {
                if ($budget->project_id == $return->project_id) {
                    $budget->current -= $return->total;
                }
            }
        }
        return view('admin.budget_supplement.index', compact('supplementBudget'));
    }

    public function getcurrentbudget($project_id) {
        $original = DB::table('budget_original')->select(DB::raw('project_id'), DB::raw('sum(overall) as overall'))
                ->where('status', '=', 'active')
                ->where('project_id', '=', $project_id)
                ->groupBy(DB::raw('project_id'))
                ->get();
        $supplement = DB::table('budget_supplement')->select(DB::raw('project_id'), DB::raw('sum(overall) as overall'))
                ->where('status', '=', 'active')
                ->where('project_id', '=', $project_id)
                ->groupBy(DB::raw('project_id'))
                ->get();
        $return = DB::table('budget_return')->select(DB::raw('project_id'), DB::raw('sum(overall) as overall'))
                ->where('status', '=', 'active')
                ->where('project_id', '=', $project_id)
                ->groupBy(DB::raw('project_id'))
                ->get();
        return response()->json(array('original' => $original, 'supplement' => $supplement, 'return' => $return));
    }

    public function getproject($project_id) {
        $project = DB::table('budget_original')
                ->select('budget_original.project_id')
                ->where('budget_original.project_id', $project_id)
                ->where('budget_original.status', '=', 'active')
                ->get();
        return response()->json([$project]);
    }

    public function currentBudget() {
        //get all budget details
        $originalBudget = DB::table('budget_original')
                ->select('budget_original.*', 'project.project_name', 'users.name', 'project.project_id as pid')
                ->leftJoin('project', 'budget_original.project_id', '=', 'project.id')
                ->leftJoin('users', 'budget_original.changed_by', '=', 'users.id')
                ->where('budget_original.status', '=', 'active')
                ->where('budget_original.company_id', Auth::user()->company_id)
                ->get();

        //calculate current budget for supplement
        $supplementBudget = DB::table('budget_supplement')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                ->where('status', '=', 'active')
                ->where('company_id', Auth::user()->company_id)
                ->groupBy(DB::raw('project_id'))
                ->get();

        //calculate current budget for return
        $returnBudget = DB::table('budget_return')->select(DB::raw('project_id'), DB::raw('sum(overall) as total'))
                ->where('status', '=', 'active')
                ->where('company_id', Auth::user()->company_id)
                ->groupBy(DB::raw('project_id'))
                ->get();

        //get current budget year wise of original budget
        $currentOBudget = DB::table('budget_original')
                        ->select(DB::raw('project_id,period_from , period_to, sum(year1) as year1,sum(year2) as year2,sum(year3) as year3,sum(year4) as year4 ,sum(year5) as year5'))
                        ->where('status', '=', 'active')
                        ->where('company_id', Auth::user()->company_id)
                        ->groupBy('project_id', 'period_from', 'period_to')
                        ->get()->toArray();

        //get current budget year wise of supplement budget
        $currentSBudget = DB::table('budget_supplement')
                        ->select(DB::raw('project_id,period_from,period_to,sum(year1) as year1,sum(year2) as year2,sum(year3) as year3,sum(year4) as year4 ,sum(year5) as year5'))
                        ->where('status', '=', 'active')
                        ->where('company_id', Auth::user()->company_id)
                        ->groupBy('project_id', 'period_from', 'period_to')
                        ->get()->toArray();

        //get current budget year wise of return budget
        $currentRBudget = DB::table('budget_return')
                        ->select(DB::raw('project_id,period_from,period_to,sum(year1) as year1,sum(year2) as year2,sum(year3) as year3,sum(year4) as year4 ,sum(year5) as year5'))
                        ->where('status', '=', 'active')
                        ->where('company_id', Auth::user()->company_id)
                        ->groupBy('project_id', 'period_from', 'period_to')
                        ->get()->toArray();

        $project_data = [];
        foreach ($currentOBudget as $key => $Obudget) {
            array_push($project_data, ['project_id' => $Obudget->project_id]);
            $temp = json_decode(json_encode($Obudget), true);
            for ($i = intval($Obudget->period_from), $j = 1; $i <= intval($Obudget->period_to); $i++, $j++) {
                if (isset($project_data[$key][$i]) == true)
                    $project_data[$key][$i] += $temp['year' . $j];
                else
                    $project_data[$key][$i] = $temp['year' . $j];
            }

            foreach ($currentSBudget as $Sbudget) {
                if ($Obudget->project_id == $Sbudget->project_id) {

                    $temp = json_decode(json_encode($Sbudget), true);
                    for ($i = intval($Sbudget->period_from), $j = 1; $i <= intval($Sbudget->period_to); $i++, $j++) {
                        if (isset($project_data[$key][$i]) == true)
                            $project_data[$key][$i] += $temp['year' . $j];
                        else
                            $project_data[$key][$i] = $temp['year' . $j];
                    }
                }
            }
            foreach ($currentRBudget as $Rbudget) {
                if ($Obudget->project_id == $Rbudget->project_id) {
                    $temp = json_decode(json_encode($Rbudget), true);
                    for ($i = intval($Rbudget->period_from), $j = 1; $i <= intval($Rbudget->period_to); $i++, $j++) {
                        if (isset($project_data[$key][$i]) == true)
                            $project_data[$key][$i] -= $temp['year' . $j];
                        else
                            $project_data[$key][$i] = -($temp['year' . $j]);
                    }
                }
            }
        }
        //get current budget of all
        foreach ($originalBudget as $budget) {
            $budget->current = $budget->overall;
            $budget->return = 0;
            $budget->supplement = 0;
            foreach ($supplementBudget as $supplement) {
                if ($budget->project_id == $supplement->project_id) {
                    $budget->supplement = $supplement->total;
                    $budget->current += $supplement->total;
                }
            }
            foreach ($returnBudget as $return) {
                if ($budget->project_id == $return->project_id) {
                    $budget->return = $return->total;
                    $budget->current -= $return->total;
                }
            }
        }
        return view('admin.budget_current.index', compact('originalBudget', 'project_data'));
    }

}
