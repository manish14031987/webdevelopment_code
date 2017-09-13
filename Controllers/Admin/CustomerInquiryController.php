<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\customerinquiry;
use App\customer_master;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\unitofmeasure;
use App\materialmaster;
use Illuminate\Support\Facades\DB;
use App\materialgroup;
use App\Cost_centres;
use App\Employee_records;
use App\inquiry_type;
use App\salesregion;
use App\purchaseorder_item;
use App\customer_inquiry_item;
use PDF;
use App\Inquirynumber_range;

class CustomerInquiryController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $customer_inquiry = customerinquiry::all();
        foreach ($customer_inquiry as $key => $value) {

            $customer_item[$key] = customer_inquiry_item::where('inquiry_number', $value->inquiry_number)->first();
        }
        return view('admin.customerinquiry.index', compact('customer_inquiry', 'customer_item'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        $max_inquirynumber = DB::table('customer_inquiry')->MAX('inquiry_number');
        $inquiry_number = array();
        $range = Inquirynumber_range::all();
        foreach ($range as $value) {
            $start_range = $value->start_range;
            $end_range = $value->end_range;
        }
        if ($max_inquirynumber == null || $max_inquirynumber == 0) {
            $inquiry_number = $start_range;
        } else {
            $inquiry_number = $max_inquirynumber + 1;
            if ($inquiry_number > $end_range) {
                session()->flash('flash_message', 'Please change end range of inquiry number in settings...');
                $inquiry_number = '';
            }
        }

        $created_on = date('Y-m-d');
        //get login details
        $user = Auth::user();
        if (Auth::check()) {
            $username = $user->name;
        } else {
            $username = 'you are not logged in';
        }
        $inquirytype = array();
        $temp = null;
        $temp = inquiry_type::all();
        foreach ($temp as $value) {
            $inquirytype [$value['id']] = $value['inquiry_type'];
        }
        $project_data = DB::table("project")
                ->select('project_Id')
                ->get();
        $pid = array();
        foreach ($project_data as $key => $projectdata) {
            $pid[$projectdata->project_Id] = isset($projectdata->project_Id) ? $projectdata->project_Id : '';
        }
        $salesregion = array();
        $temp = null;
        $temp = salesregion::all();
        foreach ($temp as $value) {
            $salesregion [$value['id']] = $value['sales_region'];
        }
        $unit_of_measure = unitofmeasure::all();
        $unitmeasure = array();
        foreach ($unit_of_measure as $unitofmeasure) {
            $unitmeasure[$unitofmeasure->unitofmeasure] = isset($unitofmeasure->unitofmeasure) ? $unitofmeasure->unitofmeasure : '';
        }
        //get customer
        $customer_data = customer_master::all();
        $customer_id = array();
        foreach ($customer_data as $customer) {

            $customer_id[$customer->customer_id] = isset($customer->name) ? $customer->name : '';
        }
        return view('admin.customerinquiry.create', compact('pid', 'unitmeasure', 'inquirytype', 'salesregion', 'customer_id', 'inquiry_number', 'created_on', 'username'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $customer_inquiry_data = Input::all();
        $validationmessages = [
            'inquiry_number.required' => "Please change end range of inquiry number in settings.",
            'inquiry_type.required' => "Please select inquiry type",
            'customer.required' => "Please select customer",
            'sales_region.required' => "Please select sales region",
            'purchase_order_number.required' => "Please enter purchase order number",
        ];
        $validator = Validator::make($customer_inquiry_data, [
                    'inquiry_number' => "required",
                    'inquiry_type' => "required",
                    'customer' => "required",
                    'sales_region' => "required",
                    'purchase_order_number' => "required",
                        ], $validationmessages);
        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/customer_inquiry/create')->withErrors($validator)->withInput(Input::all());
//            return response()->json($msgs);
        }

        customerinquiry::create($customer_inquiry_data);

        session()->flash('flash_message', 'Customer Inquiry Created successfully...');
        return redirect('admin/customer_inquiry');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        //get customer
        $customer_data = customer_master::all();
        foreach ($customer_data as $customer) {
            $customer_id[$customer->customer_id] = isset($customer->name) ? $customer->name : '';
        }
        $inquirytype = array();
        $temp = null;
        $temp = inquiry_type::all();
        foreach ($temp as $value) {
            $inquirytype [$value['id']] = $value['inquiry_type'];
        }
        $customer_inquiry = customerinquiry::find($id);
        $salesregion = array();
        $temp = null;
        $temp = salesregion::all();
        foreach ($temp as $value) {
            $salesregion [$value['id']] = $value['sales_region'];
        }
        $material = array();
        $temp = materialmaster::all();
        foreach ($temp as $value) {
            $material[$value['material_number']] = $value['material_name'];
        }
        $po_item = array();
        $temp = purchaseorder_item::all();
        foreach ($temp as $value) {
            $po_item[$value['item_no']] = $value['item_no'];
        }
        //get unit of measure
        $unit_of_measure = unitofmeasure::all();

        foreach ($unit_of_measure as $unitofmeasure) {
            $unitmeasure[$unitofmeasure->unitofmeasure] = isset($unitofmeasure->unitofmeasure) ? $unitofmeasure->unitofmeasure : '';
        }
        $customer_item_data = customer_inquiry_item::where('inquiry_number', $customer_inquiry->inquiry_number)->get();
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
        $temp = Employee_records::all();
        foreach ($temp as $value) {

            $requestedby[$value->employee_first_name] = isset($value->employee_first_name) ? $value->employee_first_name : '';
        }
        return view('admin.customerinquiry.edit', compact('customer_item_data', 'id', 'material', 'po_item', 'salesregion', 'inquirytype', 'customer_inquiry', 'customer_id', 'unitmeasure', 'material_no', 'pid', 'tid', 'materialgrp', 'cost', 'requestedby', 'quotation_id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $customer_inquiry_data = Input::all();
        $elementdata = $customer_inquiry_data['elementdata'];
        $customer_inquiry = $customer_inquiry_data['obj'];
        $validationmessages = [
            'inquiry_type.required' => "Please select inquiry type",
            'customer.required' => "Please select customer",
            'sales_region.required' => "Please select sales region",
            'purchase_order_number.required' => "Please enter purchase order number",
        ];
        $validator = Validator::make($customer_inquiry, [
                    'inquiry_type' => "required",
                    'customer' => "required",
                    'sales_region' => "required",
                    'purchase_order_number' => "required",
                        ], $validationmessages);
        if ($validator->fails()) {
            $msgs = $validator->messages();
            return response()->json($msgs);
        }
        if (isset($elementdata))
            foreach ($elementdata as $index => $row) {
                $row['inquiry_number'] = $customer_inquiry['inquiry_number'];
                unset($row['optradio']);
                $validationmsgitem = [
                    'status.required' => 'Please enter select Status ' . ($index + 1) . ' record',
                    'item_no.required' => 'Please enter Item number on ' . ($index + 1) . ' record',
                    'material.required' => 'Please enter Material on ' . ($index + 1) . ' record',
                    'material_group.required' => 'Please enter Material group on ' . ($index + 1) . ' record',
                    'material_description.required' => 'Please enter Material description on ' . ($index + 1) . ' record',
                ];
                $validator = Validator::make($row, [
                            'status' => 'required',
                            'item_no' => 'required',
                            'material' => 'required',
                            'material_group' => 'required',
                            'material_description' => 'required|max:255',
                                ], $validationmsgitem);
                if ($validator->fails()) {
                    $msgs = $validator->messages();
                    return response()->json($msgs);
                }
                $matchThese = array('inquiry_number' => $customer_inquiry['inquiry_number'], 'item_no' => $row['item_no']);
                customer_inquiry_item::updateOrCreate($matchThese, $row);
            }
        unset($customer_inquiry['_token']);
        unset($customer_inquiry['_method']);
        customerinquiry::where('id', $id)
                ->update($customer_inquiry);
        session()->flash('flash_message', 'cutsomer inquiry updated successfully...');
        return response()->json(array('redirect_url' => 'admin/customer_inquiry'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $inquiry_id = customerinquiry::find($id);
        $inquiry_id->delete();
        session()->flash('flash_message', 'Customer Inquiry deleted successfully...');
        return redirect('admin/customer_inquiry');
    }

    public function deleteItem($id) {
        $inquiry_id = customer_inquiry_item::find($id);
        $inquiry_id->delete($id);
        session()->flash('flash_message', 'customer inquiry item deleted successfully...');
        return redirect('admin/customer_inquiry');
    }

    public function export_cs() {
        $inquiry = customerinquiry::all();
        $header = "InquiryID" . ",";
        $header .= "InquiryDescription" . ",";
        $header .= "Quotation" . ",";
        $header .= "InvoiceNumber" . ",";
        $header .= "InquiryType" . ",";
        $header .= "Customer" . ",";
        $header .= "SalesRegion" . ",";
        $header .= "PurchaseOrderNumber" . ",";
        $header .= "PurchaseOrderDate" . ",";
        $header .= "ReqDeliveryDate" . ",";
        $header .= "Weight" . ",";
        $header .= "unit" . ",";
        $header .= "ValidFrom" . ",";
        $header .= "ValidTo" . ",";
        $header .= "InquiryText" . ",";
        $header .= "TotalValue" . ",";
        $header .= "NetAmount" . ",";
        $header .= "Item" . ",";
        $header .= "MaterialNumber" . ",";
        $header .= "OrderQty" . ",";
        $header .= "CustomerMaterialNumber" . ",";
        $header .= "CostPerUnit" . ",";
        $header .= "TotalAmount" . ",";
        $header .= "PoItem" . ",";
        $header .= "ProjectNumber" . ",";
        $header .= "Task" . ",";
        $header .= "CostCenter" . ",";
        $header .= "MaterialGroup" . ",";
        $header .= "ReasonForRejection" . ",";
        $header .= "CreatedOn" . ",";
        $header .= "CreatedBy" . ",";
        $header .= "RequestedBy" . ",";
        $header .= "Status" . ",";
        print "$header\n";
        foreach ($inquiry as $inquiry_data) {
            if ($inquiry_data->onetime_customer == 'yes') {
                $onetimecustomer = 'yes';
            } else {
                $onetimecustomer = 'no';
            }
            if ($inquiry_data->approved_customer == 'yes') {

                $approvedcustomer = 'yes';
            } else {
                $approvedcustomer = 'no';
            }
            if ($inquiry_data->e_invoice == 'yes') {
                $einvoice = 'yes';
            } else {
                $einvoice = 'no';
            }
            $row1 = array();
            $row1[] = '"' . $inquiry_data->inquiry_number . '"';
            $row1[] = '"' . $inquiry_data->inquiry_description . '"';
            $row1[] = '"' . $inquiry_data->quotation . '"';
            $row1[] = '"' . $inquiry_data->invoice_number . '"';
            $row1[] = '"' . $inquiry_data->inquiry_type . '"';
            $row1[] = '"' . $inquiry_data->customer . '"';
            $row1[] = '"' . $inquiry_data->sales_region . '"';
            $row1[] = '"' . $inquiry_data->purchase_order_number . '"';
            $row1[] = '"' . $inquiry_data->purchase_order_date . '"';
            $row1[] = '"' . $inquiry_data->req_delivery_date . '"';
            $row1[] = '"' . $inquiry_data->weight . '"';
            $row1[] = '"' . $inquiry_data->unit . '"';
            $row1[] = '"' . $inquiry_data->valid_from . '"';
            $row1[] = '"' . $inquiry_data->valid_to . '"';
            $row1[] = '"' . $inquiry_data->inquiry_text . '"';
            $row1[] = '"' . $inquiry_data->total_value . '"';
            $row1[] = '"' . $inquiry_data->net_amount . '"';
            $row1[] = '"' . $inquiry_data->item . '"';
            $row1[] = '"' . $inquiry_data->material_number . '"';
            $row1[] = '"' . $inquiry_data->material_number . '"';
            $row1[] = '"' . $inquiry_data->customer_material_number . '"';
            $row1[] = '"' . $inquiry_data->cost_per_unit . '"';
            $row1[] = '"' . $inquiry_data->total_amount . '"';
            $row1[] = '"' . $inquiry_data->po_item . '"';
            $row1[] = '"' . $inquiry_data->project_number . '"';
            $row1[] = '"' . $inquiry_data->task . '"';
            $row1[] = '"' . $inquiry_data->cost_center . '"';
            $row1[] = '"' . $inquiry_data->material_group . '"';
            $row1[] = '"' . $inquiry_data->reason_for_rejection . '"';
            $row1[] = '"' . $inquiry_data->created_on . '"';
            $row1[] = '"' . $inquiry_data->created_by . '"';
            $row1[] = '"' . $inquiry_data->requested_by . '"';
            $row1[] = '"' . $inquiry_data->status . '"';
            $data = join(",", $row1) . "\n";
            header("Content-type: application/vnd.ms-excel");
            header("Content-Disposition: attachment; filename=Inquiry.csv");
            header("Pragma: no-cache");
            header("Expires: 0");
            print "$data";
        }
    }

    public function pdfview(Request $request) {
        $items = DB::table("customer_inquiry")->get()->toArray();
        view()->share('items', $items);
        $headers = ['Content-Type: application/pdf'];
        if ($request->has('download')) {
            $pdf = PDF::loadView('admin/customerinquiry/pdfview');
            return $pdf->download('pdfview.pdf');
        }
        return view('admin.customerinquiry.pdfview');
    }

}
