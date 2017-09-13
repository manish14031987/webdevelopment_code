<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Mail;
use App\Mail\sales_order_customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Sales_order;
use Illuminate\Support\Facades\Auth;
use App\customer_master;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\quotation;
use App\unitofmeasure;
use App\materialmaster;
use App\materialgroup;
use App\Cost_centres;
use App\Admin_employees;
use App\sales_order_item_price;
use App\sales_order_item;
use App\Currency;
use App\gl;
use App\glaccounttax;
use App\glaccountfreight;
use App\glaccountpayment;
use Illuminate\Support\Facades\Session;

class SalesOrderController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //
        $salesorder_data = Sales_order::all();
        return view('admin.sales_order.index', compact('salesorder_data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {

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

        //get gl Account  from currency table
        $gl = array();
        $temp = null;
        $temp = gl::all();
        foreach ($temp as $value) {
            $gl[$value['id']] = $value['gl_account_number'];
        }

        $gltax = array();
        $temp = null;
        $temp = glaccounttax::all();
        foreach ($temp as $value) {
            $gltax[$value['id']] = $value['glaccount_tax'];
        }

        $glfreight = array();
        $temp = null;
        $temp = glaccountfreight::all();
        foreach ($temp as $value) {
            $glfreight[$value['id']] = $value['glaccount_freight'];
        }

        $glpayment = array();
        $temp = null;
        $temp = glaccountpayment::all();
        foreach ($temp as $value) {
            $glpayment[$value['id']] = $value['glaccount_payment'];
        }

        //get quotation
        $quotation_data = quotation::all();
        $quotation = array();
        foreach ($quotation_data as $quotationid) {

            $quotation[$quotationid->quotation_number] = $quotationid->quotation_number;
        }
        //get customer inquiry
        $inquiry_data = DB::table("customer_inquiry")
                ->select('inquiry_number')
                ->get();


        $customer_inquiry = array();
        foreach ($inquiry_data as $key => $inquirydata) {
            $customer_inquiry[$inquirydata->inquiry_number] = $inquirydata->inquiry_number;
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
        $pid = array();
        foreach ($project_data as $key => $projectdata) {
            $pid[$projectdata->project_Id] = isset($projectdata->project_Id) ? $projectdata->project_Id : '';
        }

        //get task id
        $task_data = DB::table("tasks_subtask")
                ->select('task_Id')
                ->get();

        $tid = array();
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
        $temp = Admin_employees::all();
        $requestedby = array();
        foreach ($temp as $value) {

            $requestedby[$value->employee_first_name] = isset($value->employee_first_name) ? $value->employee_first_name : '';
        }

        $temp = Currency::all();
        $currency = array();
        foreach ($temp as $key => $value) {
            $currency[$value->id] = $value->short_code;
        }

        return view('admin.sales_order.create', compact('gl', 'glpayment', 'glfreight', 'gltax', 'username', 'created_on', 'customer_id', 'currency', 'requestedby', 'quotation', 'customer_inquiry', 'customer_id', 'unitmeasure', 'material_no', 'pid', 'tid', 'materialgrp', 'cost'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $sales_order_data = Input::except('_token');
        $sales_order_item_data = $sales_order_data['elementdata'];
        $sales_order_item_price_data = $sales_order_data['pricedata'];
        $sales_order = $sales_order_data['obj'];
        $salesorderdata = Sales_order::where('sales_orderno', $sales_order['sales_orderno'])->get();

        $validationmessages = [
            'sales_orderno.required' => "Please enter sales orderno",
            'sales_order_type.required' => "Please select sales order type",
            'customer.required' => "Please select customer",
            'sales_region.required' => "Please select sales region",
            'purchase_order_number.required' => "Please enter purchase order number",
            'purchase_order_date.required' => "Please select purchase order date",
            'req_delivery_date.required' => "Please select request delivery date",
            'valid_from.required' => "Please select valid from date",
            'valid_to.required' => "Please select valid to date",
            'total_value.required' => "Please enter total value",
            'net_amount.required' => "Please enter net amount",
            'material_number.required' => "Please select material",
            'order_qty.required' => "Please enter order quantity in number",
            'ex_works.required' => "Please enter ex-works city",
            'cost_per_unit.required' => "Please enter cost per unit",
            'total_amount.required' => "Please enter total amount",
            'po_item.required' => "Please enter purchase order item",
            'project_number.required' => "Please select project",
            'task.required' => "Please select task"
        ];

        $validator = Validator::make($sales_order, [
                    'sales_orderno' => "required",
                    'sales_order_type' => "required",
                    'customer' => "required",
                    'sales_region' => "required",
                    'purchase_order_number' => "required",
                    'purchase_order_date' => "required",
                    'req_delivery_date' => "required",
                    'valid_from' => "required",
                    'valid_to' => "required",
                    'total_value' => "required",
                    'net_amount' => "required",
                    'material_number' => "required",
                    'order_qty' => "required",
                    'ex_works' => "required",
                    'cost_per_unit' => "required",
                    'total_amount' => "required",
                    'po_item' => "required",
                    'project_number' => "required",
                    'task' => "required"
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return response()->json($msgs);
        }

        $sales_order = Sales_order::create($sales_order);

        foreach (array_map(null, $sales_order_item_data, $sales_order_item_price_data) as $key => list($item, $itemprice)) {
            $index = $key + 1;
            $item['sales_orderno'] = $sales_order->sales_orderno;

            $itemprice['item_no'] = $item['item'];
            $itemprice['sales_orderno'] = $sales_order->sales_orderno;

            unset($item['optradio']);
            unset($itemprice['_method']);
            unset($itemprice['_token']);

            $validationmsgitem = [
                'item.required' => 'Please enter Item No for ' . $index . ' Item',
                'material_number.required' => 'Please select Material No for ' . $index . ' Item',
                'order_qty.required' => 'Please enter Order Quantity for ' . $index . ' Item',
                'material_description.required' => 'Please enter Matterial Description for ' . $index . ' Item',
                'first_delivery_date.required' => 'Please enter First Delivery Date for ' . $index . ' Item',
                'net_value.required' => 'Please enter Net Value for ' . $index . ' Item',
                'currency.required' => 'Please select Currency for ' . $index . ' Item',
                'pricing date.required' => 'Please enter Priceing Date for ' . $index . ' Item',
            ];

            $validator1 = Validator::make($item, [
                        'item' => 'required',
                        'material_number' => 'required',
                        'order_qty' => 'required',
                        'material_description' => 'required|max:191',
                        'first_delivery_date' => 'required',
                        'net_value' => 'required',
                        'currency' => 'required',
                        'pricing_date' => 'required',
                            ], $validationmsgitem);

            if ($validator1->fails()) {
                $msgs = $validator1->messages();
                return response()->json($msgs);
            }

            $validationmsgitem = [
                'sales_orderno.required' => 'Please enter Sales Order No for ' . $index . ' Item',
                'base_price.required' => 'Please enter Base Price for ' . $index . ' Item',
                'net_value.required' => 'Please enter Net Value for ' . $index . ' Item',
                'output_tax.required' => 'Please enter OutPut Tax for ' . $index . ' Item',
            ];

            $validator2 = Validator::make($itemprice, [
                        'base_price' => 'required',
                        'net_value' => 'required',
                        'down_payment' => 'required',
                        'output_tax' => 'required',
                            ], $validationmsgitem);


            if ($validator2->fails()) {
                $msgs = $validator2->messages();
                return response()->json($msgs);
            }


            $matchThese = array('sales_orderno' => $item['sales_orderno'], 'item' => $item['item']);
            sales_order_item::updateOrCreate($matchThese, $item);

            $matchThese1 = array('sales_orderno' => $itemprice['sales_orderno'], 'item_no' => $itemprice['item_no']);
            sales_order_item_price::updateOrCreate($matchThese1, $itemprice);
        }



        session()->flash('flash_message', 'Sales Order created successfully...');
        return response()->json(array('redirect_url' => 'admin/sales_order'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
          $salesorder_data = Sales_order::where('sales_orderno',$id)->get();
//        
          if( count($salesorder_data) > 0 )
            { Session::forget('flash_message');
              return view('admin.sales_order.index', compact('salesorder_data','id'));
            }
            else
            {
                session()->flash('flash_message', 'Sales Order No has been deleted...');
                 return view('admin.sales_order.index', compact('salesorder_data','id'));
            }
       
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {


        //get quotation
        $quotation_data = quotation::all();

        foreach ($quotation_data as $quotationid) {

            $quotation[$quotationid->quotation_number] = $quotationid->quotation_number;
        }
        //get customer inquiry
        $inquiry_data = DB::table("customer_inquiry")
                ->select('inquiry_number')
                ->get();


        $customer_inquiry = '';
        foreach ($inquiry_data as $key => $inquirydata) {
            $customer_inquiry[$inquirydata->inquiry_number] = $inquirydata->inquiry_number;
        }


        //get gl Account  from currency table
        $gl = array();
        $temp = null;
        $temp = gl::all();
        foreach ($temp as $value) {
            $gl[$value['id']] = $value['gl_account_number'];
        }

        $gltax = array();
        $temp = null;
        $temp = glaccounttax::all();
        foreach ($temp as $value) {
            $gltax[$value['id']] = $value['glaccount_tax'];
        }

        $glpayment = array();
        $temp = null;
        $temp = glaccountpayment::all();
        foreach ($temp as $value) {
            $glpayment[$value['id']] = $value['glaccount_payment'];
        }

        $glfreight = array();
        $temp = null;
        $temp = glaccountfreight::all();
        foreach ($temp as $value) {
            $glfreight[$value['id']] = $value['glaccount_freight'];
        }


        //get customer
        $customer_data = customer_master::all();

        foreach ($customer_data as $customer) {

            $customer_id[$customer->id] = isset($customer->name) ? $customer->name : '';
        }

        $sales_order = DB::table('sales_order')->where('sales_orderno', '=', $id)->first();


        //get unit of measure
        $unit_of_measure = unitofmeasure::all();

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
        foreach ($material_group as $group) {
            $materialgrp[$group->materialgroup] = isset($group->materialgroup) ? $group->materialgroup : '';
        }

        //get cost_center
        $cost_centre = Cost_centres::all();
        foreach ($cost_centre as $costcenter) {
            $cost[$costcenter->cost_centre] = isset($costcenter->cost_centre) ? $costcenter->cost_centre : '';
        }

        //get requestedby value
        $requestedby = array();
        $temp = Admin_employees::all();

        foreach ($temp as $value) {

            $requestedby[$value->employee_first_name] = isset($value->employee_first_name) ? $value->employee_first_name : '';
        }

        $temp = Currency::all();
        foreach ($temp as $key => $value) {
            $currency[$value->id] = $value->short_code;
        }



        $sales_order_item_data = DB::table('sales_item')->where('sales_item.sales_orderno', $id)
                ->join('sales_pricing', 'sales_item.item', '=', 'sales_pricing.item_no')
                ->select('*')
                ->get();


        return view('admin.sales_order.edit', compact('currency','glpayment','glfreight', 'gl', 'gltax', 'sales_order_item_data', 'requestedby', 'quotation', 'customer_inquiry', 'sales_order', 'customer_id', 'unitmeasure', 'material_no', 'pid', 'tid', 'materialgrp', 'cost'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request) {

        $sales_order_data = Input::except('_token');
        $sales_order_item_data = $sales_order_data['elementdata'];
        $sales_order_item_price_data = $sales_order_data['pricedata'];
        $sales_order = $sales_order_data['obj'];
        $salesorderdata = Sales_order::where('sales_orderno', $sales_order['sales_orderno'])->get();



        $validationmessages = [
            'sales_orderno.required' => "Please enter sales orderno",
            'sales_order_type.required' => "Please select sales order type",
            'customer.required' => "Please select customer",
            'sales_region.required' => "Please select sales region",
            'purchase_order_number.required' => "Please enter purchase order number",
            'purchase_order_date.required' => "Please select purchase order date",
            'req_delivery_date.required' => "Please select request delivery date",
            'valid_from.required' => "Please select valid from date",
            'valid_to.required' => "Please select valid to date",
            'total_value.required' => "Please enter total value",
            'net_amount.required' => "Please enter net amount",
            'material_number.required' => "Please select material",
            'order_qty.required' => "Please enter order quantity in number",
            'ex_works.required' => "Please enter ex-works city",
            'cost_per_unit.required' => "Please enter cost per unit",
            'total_amount.required' => "Please enter total amount",
            'po_item.required' => "Please enter purchase order item",
            'project_number.required' => "Please select project",
            'task.required' => "Please select task"
        ];

        $validator = Validator::make($sales_order, [
                    'sales_orderno' => "required",
                    'sales_order_type' => "required",
                    'customer' => "required",
                    'sales_region' => "required",
                    'purchase_order_number' => "required",
                    'purchase_order_date' => "required",
                    'req_delivery_date' => "required",
                    'valid_from' => "required",
                    'valid_to' => "required",
                    'total_value' => "required",
                    'net_amount' => "required",
                    'material_number' => "required",
                    'order_qty' => "required",
                    'ex_works' => "required",
                    'cost_per_unit' => "required",
                    'total_amount' => "required",
                    'po_item' => "required",
                    'project_number' => "required",
                    'task' => "required"
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return response()->json($msgs);
        }

        // parallel loop iteration php 5.4 compatiblity isssue in 7 may be.
        foreach (array_map(null, $sales_order_item_data, $sales_order_item_price_data) as $key => list($item, $itemprice)) {
            $index = $key + 1;
            $item['sales_orderno'] = $sales_order['sales_orderno'];

            $itemprice['item_no'] = $item['item'];
            $itemprice['sales_orderno'] = $sales_order['sales_orderno'];

            unset($item['optradio']);
            unset($itemprice['_method']);
            unset($itemprice['_token']);

            $validationmsgitem = [
                'item.required' => 'Please enter Item No for ' . $index . ' Item',
                'material_number.required' => 'Please select Material No for ' . $index . ' Item',
                'order_qty.required' => 'Please enter Order Quantity for ' . $index . ' Item',
                'material_description.required' => 'Please enter Matterial Description for ' . $index . ' Item',
                'first_delivery_date.required' => 'Please enter First Delivery Date for ' . $index . ' Item',
                'net_value.required' => 'Please enter Net Value for ' . $index . ' Item',
                'currency.required' => 'Please select Currency for ' . $index . ' Item',
                'pricing date.required' => 'Please enter Priceing Date for ' . $index . ' Item',
            ];

            $validator1 = Validator::make($item, [
                        'item' => 'required',
                        'material_number' => 'required',
                        'order_qty' => 'required',
                        'material_description' => 'required|max:191',
                        'first_delivery_date' => 'required',
                        'net_value' => 'required',
                        'currency' => 'required',
                        'pricing_date' => 'required',
                            ], $validationmsgitem);

            if ($validator1->fails()) {
                $msgs = $validator1->messages();
                return response()->json($msgs);
            }

            $validationmsgitem = [
                'sales_orderno.required' => 'Please enter Sales Order No for ' . $index . ' Item',
                'base_price.required' => 'Please enter Base Price for ' . $index . ' Item',
                'net_value.required' => 'Please enter Net Value for ' . $index . ' Item',
                'output_tax.required' => 'Please enter OutPut Tax for ' . $index . ' Item',
            ];

            $validator2 = Validator::make($itemprice, [
                        'base_price' => 'required',
                        'net_value' => 'required',
                        'down_payment' => 'required',
                        'output_tax' => 'required',
                            ], $validationmsgitem);


            if ($validator2->fails()) {
                $msgs = $validator2->messages();
                return response()->json($msgs);
            }


            $matchThese = array('sales_orderno' => $item['sales_orderno'], 'item' => $item['item']);
            sales_order_item::updateOrCreate($matchThese, $item);

            $matchThese1 = array('sales_orderno' => $itemprice['sales_orderno'], 'item_no' => $itemprice['item_no']);
            sales_order_item_price::updateOrCreate($matchThese1, $itemprice);
        }


        Sales_order::where('sales_orderno', $sales_order['sales_orderno'])->update($sales_order);

        session()->flash('flash_message', 'Sales Order updated successfully...');
        return response()->json(array('redirect_url' => 'admin/sales_order'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        DB::table('sales_order')->where('sales_orderno', '=', $id)->delete();
        session()->flash('flash_message', 'Sales Order deleted successfully...');
        return redirect('admin/sales_order');
    }

    public function create_ref_quotation() {

        //get quotation
        $quotation_data = DB::table('quotation')
                ->select('quotation.quotation_number')
                ->where('sales_order', null)
                ->get();

        foreach ($quotation_data as $quotationid) {

            $quotation[$quotationid->quotation_number] = $quotationid->quotation_number;
        }

        return view('admin.sales_order.create_ref_form', compact('quotation'));
    }

    public function insert_quotation_to_salesorder(Request $request) {
        $quotation_id = $request['quotation'];
        $status = $request['status'];

        if ($status == 'yes') {
            $quotation_data = DB::table('quotation')
                    ->select('quotation.*')
                    ->where('quotation_number', $quotation_id)
                    ->first();

            $created_on = date('Y-m-d');

            //get login details
            $user = Auth::user();

            if (Auth::check()) {
                $username = $user->name;
            } else {
                $username = 'you are not logged in';
            }

            DB::table('sales_order')
                    ->insert(array('sales_order_type' => $request['sales_order_type'], 'inquiry' => $quotation_data->inquiry, 'quotation' => $quotation_id, 'customer' => $quotation_data->customer, 'sales_region' => $quotation_data->sales_region, 'purchase_order_number' => $quotation_data->purchase_order_number, 'purchase_order_date' => $quotation_data->purchase_order_date, 'req_delivery_date' => $quotation_data->req_delivery_date, 'invoice_number' => $quotation_data->invoice_number, 'weight' => $quotation_data->weight, 'unit' => $quotation_data->unit, 'valid_from' => $quotation_data->valid_from, 'valid_to' => $quotation_data->valid_to, 'total_value' => $quotation_data->total_value, 'net_amount' => $quotation_data->net_amount, 'material_number' => $quotation_data->material_number, 'order_qty' => $quotation_data->order_qty, 'customer_material_number' => $quotation_data->customer_material_number, 'cost_per_unit' => $quotation_data->cost_per_unit, 'total_amount' => $quotation_data->total_amount, 'po_item' => $quotation_data->po_item, 'project_number' => $quotation_data->project_number, 'task' => $quotation_data->task, 'cost_center' => $quotation_data->cost_center, 'material_group' => $quotation_data->material_group, 'reason_for_rejection' => $quotation_data->reason_for_rejection, 'requested_by' => $quotation_data->requested_by, 'status' => 'active', 'created_on' => $created_on, 'created_by' => $username));

            $sales_orderno = DB::table('sales_order')
                    ->select('sales_order.sales_orderno')
                    ->where('quotation', $quotation_id)
                    ->first();

            DB::table('quotation')
                    ->where('quotation_number', $quotation_id)
                    ->update(array('sales_order' => $sales_orderno->sales_orderno));

            DB::table('customer_inquiry')
                    ->where('quotation', $quotation_id)
                    ->update(array('sales_order' => $sales_orderno->sales_orderno));

            session()->flash('flash_message', 'Sales Order created with ref successfully...');
            return redirect('admin/sales_order');
        } else {
            return redirect('admin/sales_order/create');
        }
    }

    public function send($sales_order_id) {
        $sales_item = DB::table('sales_item')
                ->where('sales_item.sales_orderno', '=', $sales_order_id)
                ->join('sales_pricing', 'sales_item.item', '=', 'sales_pricing.item_no')
                ->get();
        $sales_item = $sales_item->toArray();

        $sales_order = \App\Sales_order::where('sales_orderno', $sales_order_id)->first();
        $sales_order = $sales_order->toArray();

        if (count($sales_order) > 0 && count($sales_item) > 0) {
            $to = \App\customer_master::find($sales_order['customer']);
            if (isset($to) == true) {
                Mail::to($to->email)->send(new sales_order_customer($sales_order, $sales_item));

                session()->flash('flash_message', 'Mail Sent Succesfuly to customer ...');
                return redirect('admin/sales_order');
            } else {
                session()->flash('flash_message', 'Unable to send mail, No such customer found ...');
                return redirect('admin/sales_order');
            }
        } else {
            session()->flash('flash_message', 'Unable to send mail, No such sales order found ...');
            return redirect('admin/sales_order');
        }
    }

}
