<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\quotation;
use Illuminate\Support\Facades\Auth;
use App\customer_master;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\unitofmeasure;
use App\materialmaster;
use Illuminate\Support\Facades\DB;
use App\materialgroup;
use App\Cost_centres;
use App\Employee_records;
use App\customerinquiry;
use Illuminate\Support\Facades\Session;

class QuotationController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {

        $quotation_data = quotation::all();

        return view('admin.quotation.index', compact('quotation_data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
        $rand_number = substr(md5(microtime()), rand(0, 26), 6);

        $created_on = date('Y-m-d');

        //get login details
        $user = Auth::user();

        if (Auth::check()) {
            $username = $user->name;
        } else {
            $username = 'you are not logged in';
        }

        //get customer
        $customer_data = customer_master::all();
        $customer_id = array();
        foreach ($customer_data as $customer) {

            $customer_id[$customer->customer_id] = isset($customer->name) ? $customer->name : '';
        }
        return view('admin.quotation.create', compact('customer_id', 'rand_number', 'created_on', 'username'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {

        $quotation = Input::all();
        $validationmessages = [
            'quotation_type.required' => "Please select inquiry type",
            'customer.required' => "Please select customer",
            'sales_region.required' => "Please select sales region",
            'purchase_order_number.required' => "Please enter purchase order number",
        ];

        $validator = Validator::make($quotation, [
                    'quotation_type' => "required",
                    'customer' => "required",
                    'sales_region' => "required",
                    'purchase_order_number' => "required",
                        ], $validationmessages);


        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/quotation/create')->withErrors($validator)->withInput(Input::all());
        }

        quotation::create($quotation);
        session()->flash('flash_message', 'Quotation created successfully...');
        return redirect('admin/quotation');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {

        $quotation_data = quotation::where('quotation_number', $id)->get();        
        
       
        if (count($quotation_data) > 0) {
            Session::forget('flash_message');
             $id = $quotation_data[0]->id;
            return view('admin.quotation.index', compact('quotation_data', 'id'));
        } else {
            session()->flash('flash_message', 'Quotation No has been deleted...');
            return view('admin.quotation.index', compact('quotation_data', 'id'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        //
        $customer_data = customer_master::all();
        
        $customer_id = array();
        foreach ($customer_data as $customer) {
            $customer_id[$customer->customer_id] = isset($customer->name) ? $customer->name : '';
        }

        $customer_inquiry = customerinquiry::pluck("inquiry_number", "inquiry_number");

        $inquiry_data = DB::table("customer_inquiry")
                ->select('inquiry_number')
                ->get();


        $ci = '';
        foreach ($inquiry_data as $key => $inquirydata) {
            $ci[$inquirydata->inquiry_number] = $inquirydata->inquiry_number;
        }

        //get unit of measure
        $unit_of_measure = unitofmeasure::all();
        $unitmeasure = array();
        foreach ($unit_of_measure as $unitofmeasure) {
            $unitmeasure[$unitofmeasure->unitofmeasure] = isset($unitofmeasure->unitofmeasure) ? $unitofmeasure->unitofmeasure : '';
        }

        //get material number
        $material_number = materialmaster::all();

        $material_no = array();

        foreach ($material_number as $material) {
            $material_no[$material->material_number] = isset($material->material_name) ? $material->material_name : '';
        }

        //get project number
        $project_data = DB::table("project")
                ->select('project_Id')
                ->get();
        $pid = '';
        foreach ($project_data as $key => $projectdata) {
            $pid[$projectdata->project_Id] = isset($projectdata->project_Id) ? $projectdata->project_Id : '';
        }

        //get task id

        $task_data = DB::table("tasks_subtask")
                ->select('task_Id')
                ->get();

        $tid = '';
        foreach ($task_data as $key => $taskdata) {
            $tid[$taskdata->task_Id] = isset($taskdata->task_Id) ? $taskdata->task_Id : '';
        }

        //get material group
        $material_group = materialgroup::all();
        $materialgrp = array();
        foreach ($material_group as $group) {
            $materialgrp[$group->materialgroup] = isset($group->materialgroup) ? $group->materialgroup : '';
        }

        //get cost_center
        $cost_centre = Cost_centres::all();
        $cost = array();
        foreach ($cost_centre as $costcenter) {
            $cost[$costcenter->cost_centre] = isset($costcenter->cost_centre) ? $costcenter->cost_centre : '';
        }

        //get requestedby value
        $requestedby = array();
        $temp = Employee_records::all();

        foreach ($temp as $value) {

            $requestedby[$value->employee_first_name] = isset($value->employee_first_name) ? $value->employee_first_name : '';
        }

        $quotation = quotation::find($id);
        return view('admin.quotation.edit', compact('quotation', 'customer_id', 'unitmeasure', 'ci', 'material_no', 'pid', 'tid', 'materialgrp', 'cost', 'requestedby', 'customer_inquiry'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $quotation = quotation::find($id);
        $dataInputs = Input::all();

        $validationmessages = [
            'quotation_type.required' => "Please select inquiry type",
            'customer.required' => "Please select customer",
            'sales_region.required' => "Please select sales region",
            'purchase_order_number.required' => "Please enter purchase order number",
            'total_value.required' => 'Please enter total value',
            'net_amount.required' => 'Please enter net amount',
            'item.required' => 'Please enter item',
            'material_number.required' => 'Please select material number',
            'order_qty.required' => 'Please enter order quantity in number',
            'cost_per_unit.required' => 'Please enter cost per unit in number',
            'total_amount.required' => 'Please enter total amount in number',
            'po_item.required' => 'Please enter purchase order item',
            'project_number.required' => 'Please select project',
            'task.required' => 'Please select task'
        ];

        $validator = Validator::make($dataInputs, [
                    'quotation_type' => "required",
                    'customer' => "required",
                    'sales_region' => "required",
                    'purchase_order_number' => "required",
                    'total_value' => "required",
                    'net_amount' => "required",
                    'item' => "required",
                    'material_number' => "required",
                    'order_qty' => "required",
                    'cost_per_unit' => "required",
                    'total_amount' => "required",
                    'po_item' => "required",
                    'project_number' => "required",
                    'task' => "required",
                        ], $validationmessages);


        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/customer_inquiry/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }

        $quotation->update($dataInputs);
        session()->flash('flash_message', 'Quotation updated successfully...');
        return redirect('admin/quotation');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $quotation_id = quotation::find($id);
        $quotation_id->delete();
        session()->flash('flash_message', 'Quatition deleted successfully...');
        return redirect('admin/quotation');
    }

    public function create_ref_inquiry() {
        $rand_number = substr(md5(microtime()), rand(0, 26), 6);

        //get inquiry number 
        $inquiry_number = DB::table('customer_inquiry')
                ->get();
        $inquiry = array();
        foreach ($inquiry_number as $inquiryid) {

            $inquiry[$inquiryid->inquiry_number] = $inquiryid->inquiry_number . ' ( ' . $inquiryid->inquiry_description . ' )';
        }


        return view('admin.quotation.create_ref_form', compact('rand_number', 'inquiry'));
    }

    public function insert_inquiry_to_quotation(Request $request) {
        $inquiry_number = $request['inquiry'];
        $status = $request['status'];


        $inquiry_data = DB::table('customer_inquiry')
                ->select('customer_inquiry.*')
                ->where('inquiry_number', $inquiry_number)
                ->first();

        $created_on = date('Y-m-d');

        //get login details
        $user = Auth::user();

        if (Auth::check()) {
            $username = $user->name;
        } else {
            $username = 'you are not logged in';
        }
        DB::table('quotation')
                ->insert(array('quotation_number' => $request['quotation_number'], 'quotation_type' => $request['quotation_type'], 'customer' => $inquiry_data->customer, 'inquiry' => $inquiry_data->inquiry_number, 'sales_order' => $inquiry_data->sales_order, 'sales_region' => $inquiry_data->sales_region, 'purchase_order_number' => $inquiry_data->purchase_order_number, 'purchase_order_date' => $inquiry_data->purchase_order_date, 'req_delivery_date' => $inquiry_data->req_delivery_date, 'invoice_number' => $inquiry_data->invoice_number, 'weight' => $inquiry_data->weight, 'unit' => $inquiry_data->unit, 'valid_from' => $inquiry_data->valid_from, 'valid_to' => $inquiry_data->valid_to, 'total_value' => $inquiry_data->total_value, 'net_amount' => $inquiry_data->net_amount, 'item' => $inquiry_data->item, 'material_number' => $inquiry_data->material_number, 'order_qty' => $inquiry_data->order_qty, 'customer_material_number' => $inquiry_data->customer_material_number, 'cost_per_unit' => $inquiry_data->cost_per_unit, 'total_amount' => $inquiry_data->total_amount, 'po_item' => $inquiry_data->po_item, 'project_number' => $inquiry_data->project_number, 'task' => $inquiry_data->task, 'cost_center' => $inquiry_data->cost_center, 'material_group' => $inquiry_data->material_group, 'reason_for_rejection' => $inquiry_data->reason_for_rejection, 'requested_by' => $inquiry_data->requested_by, 'status' => 'active', 'created_on' => $created_on, 'created_by' => $username));


        $quotation_no = DB::table('quotation')
                ->select('quotation.quotation_number')
                ->where('inquiry', $inquiry_number)
                ->first();

        DB::table('customer_inquiry')
                ->where('inquiry_number', $inquiry_number)
                ->update(array('quotation' => $quotation_no->quotation_number));


        session()->flash('flash_message', 'Quotation created with ref successfully...');
        return redirect('admin/quotation');
    }

}
