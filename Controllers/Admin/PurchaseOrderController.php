<?php

namespace App\Http\Controllers\Admin;

use \Illuminate\Support\Facades\Auth;
use App\Mail\purchase_order_approval;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\purchase_order;
use App\purchaseorder_item;
use App\User;
use App\Employee_records;
use App\materialmaster;
use App\Currency;
use App\country;
use App\Projectphase;
use App\Project;
use App\Projecttask;
use App\purchase_requisition;
use Illuminate\Support\Facades\Mail;
use App\gl;

class PurchaseOrderController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {


        $purchase_order_data = purchase_order::all();

        $createdon = array();
        $createdby = array();
        $changedby = array();
        $country = array();


        foreach ($purchase_order_data as $key => $value) {

            $purchase_item[$key] = purchaseorder_item::where('purchase_order_number', $value->purchase_order_number)->first();

            $created_date[$key] = isset($purchase_item[$key]->created_on) ? $purchase_item[$key]->created_on : '';
            if (empty($created_date[$key])) {
                $createdon = null;
            } else {
                $createdon = date("Y-m-d", strtotime($created_date[$key]));
            }

            $createdby[$key] = ($purchase_item[$key]['created_by'] != '') ? User::where('id', $purchase_item[$key]['created_by'])->first()['original']['name'] : '';

            $changedby[$key] = ($purchase_item[$key]['changed_by'] != '') ? User::where('id', $purchase_item[$key]['changed_by'])->first()['original']['name'] : '';

            $country[$key] = ($purchase_item[$key]['country'] != '') ? country::where('id', $purchase_item[$key]['country'])->first()['original']['country_name'] : '';

            $requestedby[$key] = ($purchase_item[$key]['requestor'] != '') ? Employee_records::where('employee_id', $purchase_item[$key]['requestor'])->first()['original']['employee_first_name'] : '';



            if (isset($value->approver_1)) {
                $approver[$key][0] = Employee_records::where('employee_id', $value->approver_1)->first()['original']['employee_first_name'];
            }
            if (isset($value->approver_2)) {
                $approver[$key][1] = Employee_records::where('employee_id', $value->approver_2)->first()['original']['employee_first_name'];
            }
            if (isset($value->approver_3)) {
                $approver[$key][2] = Employee_records::where('employee_id', $value->approver_3)->first()['original']['employee_first_name'];
            }
            if (isset($value->approver_4)) {
                $approver[$key][3] = Employee_records::where('employee_id', $value->approver_4)->first()['original']['employee_first_name'];
            }
        }
        return view('admin.purchase_order.index', compact('createdon', 'purchase_item', 'approver', 'country', 'purchase_order_data', 'createdby', 'changedby', 'requestedby'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        //get purchase requistion no
        $purchase_reqno = purchase_requisition::where('company_id', Auth::user()->company_id)->get();

        $purchase_no = array();
        foreach ($purchase_reqno as $prno) {


            $purchase_no[$prno->requisition_number] = $prno->requisition_number;
        }

        return view('admin.purchase_order.create', compact('purchase_order', 'created_by', 'requestedby', 'purchase_no'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $requisition_id = $request['purchase_requistion'];
        $status = $request['status'];
        $purchase_order = $request['purchase_orderno'];



        if ($status == 'yes') {
            $requisition_data = DB::table('purchase_requisition')
                    ->select('purchase_requisition.*')
                    ->where('requisition_number', $requisition_id)
                    ->where('company_id', Auth::user()->company_id)
                    ->first();

            $item = DB::table('purchase_item')
                    ->select('purchase_item.*')
                    ->where('requisition_number', $requisition_id)
                    ->where('company_id', Auth::user()->company_id)
                    ->get();


            $created_on = date('Y-m-d');

            //get login details
            $user = Auth::user();

            if (Auth::check()) {
                $userid = $user->id;
            } else {
                $userid = 'you are not logged in';
            }

            DB::table('purchase_order')
                    ->insert(array('purchase_order_number' => $purchase_order, 'header_note' => $requisition_data->header_note, 'approver_1' => $requisition_data->approver_1, 'approver_2' => $requisition_data->approver_2, 'approver_3' => $requisition_data->approver_3, 'approver_4' => $requisition_data->approver_4, 'company_id' => Auth::user()->company_id));

            foreach ($item as $key => $item_data) {
                DB::table('purchaseorder_item')
                        ->insert(array('status' => $item_data->status, 'purchase_order_number' => $purchase_order,
                            'item_no' => $item_data->item_no, 'item_category' => $item_data->item_category,
                            'material' => $item_data->material, 'material_description' => $item_data->material_description,
                            'item_quantity' => $item_data->item_quantity, 'quantity_unit' => $item_data->quantity_unit,
                            'item_cost' => $item_data->item_cost, 'currency' => $item_data->currency,
                            'delivery_date' => $item_data->delivery_date, 'material_group' => $item_data->material_group,
                            'vendor' => $item_data->vendor, 'requestor' => $item_data->requestor,
                            'contract_number' => $item_data->contract_number, 'contract_item_number' => $item_data->contract_item_number,
                            'project_id' => $item_data->project_id, 'phase_id' => $item_data->phase_id, 'task_id' => $item_data->task_id,
                            'g_l_account' => $item_data->g_l_account, 'cost_center' => $item_data->cost_center, 'created_by' => $userid,
                            'created_on' => $created_on, 'processing_status' => $item_data->processing_status, 'title' => $item_data->title,
                            'name' => $item_data->name, 'add1' => $item_data->add1, 'add2' => $item_data->add2, 'postal_code' => $item_data->postal_code,
                            'country' => $item_data->country, 'requisition_number' => $requisition_id, 'company_id' => Auth::user()->company_id));
            }


            $purchase_orderno = DB::table('purchaseorder_item')
                    ->select('purchaseorder_item.purchase_order_number')
                    ->where('requisition_number', $requisition_id)
                    ->where('company_id', Auth::user()->company_id)
                    ->first();

            DB::table('purchase_item')
                    ->where('requisition_number', $requisition_id)
                    ->where('company_id', Auth::user()->company_id)
                    ->update(array('purchase_order_number' => $purchase_orderno->purchase_order_number));

            session()->flash('flash_message', 'Purchase order created successfully...');
            return redirect('admin/purchase_order');
        } else {
            return redirect('admin/purchase_order');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, $token = null)
    {
        $purchase_order = purchase_order::find($id);

        if (($token == null) && ($purchase_order->approved_indicator == '1' || $purchase_order->approved_indicator == '2' || $purchase_order->approved_indicator == '3' || $purchase_order->approved_indicator == '4' || $purchase_order->approved_indicator == '5')) {
            session()->flash('flash_message', 'Approver Mail Cycle has already started, It can`t be updated ...');
            return redirect('admin/purchase_order');
        }

        //get material records
        $material = array();
        $temp = materialmaster::all();
        foreach ($temp as $value) {
            $material[$value['material_number']] = $value['material_name'];
        }

        // get the requestor id 
        $user = Auth::user();
        $username = 'you are not logged in';
        if (Auth::check()) {
            $username = $user->name;
        }
        $requestedby = array();
        $temp = Employee_records::where('company_id',  Auth::user()->company_id)->where('status', 'active')->get();

        foreach ($temp as $value) {

            $requestedby[$value->employee_id] = $value->employee_id . ' ( ' . (isset($value->employee_first_name) ? $value->employee_first_name : '') . ' ) ';
        }

        $requestor = User::where('name', $username)->first();
        $requestor_data = User::all();
        foreach ($requestor_data as $value) {
            $requestors[$value->id] = $value->name;
        }

        $requestor = $requestor['id'];
        $changed_by = $requestor;
        $created_by = $requestor;


        //get currency from currency table
        $currency = array();
        $temp = null;
        $temp = Currency::all();
        foreach ($temp as $value) {
            $currency[$value['id']] = $value['short_code'];
        }

        $phase_ids = array();
        $project_ids = array();
        $task_ids = array();

        //make associative arrays
        $phase_data = Projectphase::all();
        foreach ($phase_data as $value) {
            $phase_ids[$value['id']] = $value['phase_Id'] . ' (' . $value['phase_name'] . ') ';
        }

        $project_data = Project::all();
        foreach ($project_data as $value) {
            $project_ids[$value['id']] = $value['project_Id'] . ' (' . $value['project_name'] . ') ';
        }

        $tasks = Projecttask::all();
        foreach ($tasks as $value) {
            $task_ids[$value['id']] = $value['task_Id'] . ' (' . $value['task_name'] . ') ';
        }


        $country = array();
        $country_data = country::all();
        foreach ($country_data as $value) {
            $country[$value->id] = $value->country_name;
        }


        $purchaseorder_item_data = purchaseorder_item::where('purchase_order_number', $purchase_order->purchase_order_number)
                        ->where('company_id', Auth::user()->company_id)->get();
        //value are static as module not 
        $purchase_order_data = \App\purchase_order::all();
        foreach ($purchase_order_data as $value) {

            $purchase_order_number[$value->purchase_order_no] = $value->purchase_order_no;
        }

        $vendor_data = \App\vendor::all();
        foreach ($vendor_data as $value) {
            $vendor[$value->id] = $value->name;
        }
        //check for status
        $status = '';

        ## demo purposes only once modules are ready use actual data
        $g_l_account = array('1' => '1231', '2' => '1321', '3' => '3232');

        $cost_center_data = \App\Cost_centres::all();
        foreach ($cost_center_data as $value) {

            $cost_center[$value->cost_id] = $value->cost_centre;
        }

        $user = Auth::user();

        $userid = 'you are not logged in';
        if (Auth::check()) {
            $userid = $user->id;
        }


        $approvers = Employee_records::where('company_id',  Auth::user()->company_id)->get();
        foreach ($approvers as $key => $value) {
            $approver[$value->employee_id] = $value->employee_first_name;
        }

        if ($token == null) {
            return view('admin.purchase_order.approval', compact('id', 'approver', 'userid', 'country', 'requestors', 'cost_center', 'g_l_account', 'task_ids', 'project_ids', 'phase_ids', 'changed_by', 'created_by', 'purchase_order_number', 'requestedby', 'vendor', 'currency', 'purchase_order', 'purchaseorder_item_data', 'material'));
        } else {

            if (count(purchase_order::where('approver_token', $token)->get()) < 1) {


                session()->flash('flash_message', 'Request already been approved by you  Or token has expired ...');
                return redirect('admin/purchase_order');
            } else {
                return view('admin.purchase_order.approval', compact('id', 'approver', 'userid', 'country', 'requestors', 'cost_center', 'g_l_account', 'task_ids', 'project_ids', 'phase_ids', 'changed_by', 'created_by', 'purchase_order_number', 'requestedby', 'vendor', 'currency', 'purchase_order', 'purchaseorder_item_data', 'material', 'token'));
            }
        }
    }

    public function reject($id)
    {
        $purchase_order = purchase_order::find($id);
        $purchase_order->approved_indicator = 'rejected';
        $purchase_order->approver_token = '';
        $purchase_order->save();

        session()->flash('flash_message', 'Request has been rejected ...');
        return redirect('admin/purchase_order');
    }

    public function approval(Request $request, $id, $token = null)
    {

        $approval_data = Input::except('_token');



        foreach ($approval_data['data'] as $data) {
            $approval[$data['name']] = $data['value'];
        }
        unset($approval['id']);
        purchase_order::find($id)->update($approval);


        $purchase_order = purchase_order::find($id);

        if ($token != null) {
            if (count(purchase_order::where('approver_token', $token)
                                    ->where('company_id', Auth::user()->company_id)->get()) < 1) {
                session()->flash('flash_message', 'Request already been approved by you Or token has expired ...');
                return redirect('admin/purchase_order');
            }
        }


        if ($purchase_order->approved_indicator == '' || $purchase_order->approved_indicator == 'rejected') {
            $purchase_order->approved_indicator = 1;
            $purchase_order->save();
        }
        $purchase_order->update($approval_data);

        $count = 0;
        if ($purchase_order->approver_1 != '') {
            $count++;
            if ($purchase_order->approver_2 != '') {
                $count++;
                if ($purchase_order->approver_3 != '') {
                    $count++;
                    if ($purchase_order->approver_4 != '') {
                        $count++;
                    }
                }
            }
        } else {
            session()->flash('flash_message', 'Approver not set ...please set Approvers');
            return response()->json(array('redirect_url' => 'admin/purchase_order'));
        }



        if ($purchase_order->approved_indicator !== 'approved' && $purchase_order->approver_1 != '') {

            $purchase_order = purchase_order::find($id);
            switch ($purchase_order->approved_indicator) {
                case 1:
                    $user_id = isset($purchase_order->approver_1) ? $purchase_order->approver_1 : '';
                    $to = Employee_records::where('employee_id', $user_id)
                                    ->where('company_id', Auth::user()->company_id)->first();

                    if (isset($to)) {
                        $purchase_order->approver_token = md5($to->email_id . time()) . '1';
                        Mail::to($to->email_id)->send(new purchase_order_approval($purchase_order));


                        $purchase_order->approved_indicator = 2;
                        $purchase_order->save();

                        session()->flash('flash_message', 'Approval Request sent successfully to 1st approver...');
                        return response()->json(array('redirect_url' => 'admin/purchase_order'));
                    } else {
                        session()->flash('flash_message', 'Approver not set ...pls edit approval settings ');
                        return response()->json(array('redirect_url' => 'admin/purchase_order'));
                    }

                    break;
                case 2:
                    $user_id = isset($purchase_order->approver_2) ? $purchase_order->approver_2 : '';
                    $to = Employee_records::where('employee_id', $user_id)
                                    ->where('company_id', Auth::user()->company_id)->first();
                    ;

                    if ($user_id != '' && $token == $purchase_order->approver_token && isset($to)) {
                        //$token = md5($user_mail['Email']) . time();
                        $purchase_order->approver_token = md5($to->email_id . time()) . '2';
                        Mail::to($to->email_id)->send(new purchase_order_approval($purchase_order));

                        $purchase_order->approved_indicator = 3;
                        $purchase_order->save();
                        // set the approvers email_id address in place of testers


                        session()->flash('flash_message', 'Approval Request sent successfully to 2nd approver...');
                        return response()->json(array('redirect_url' => 'admin/purchase_order'));
                    } else {
                        $purchase_order->approver_token = '';
                        $purchase_order->approved_indicator = 'approved';
                        $purchase_order->save();

                        session()->flash('flash_message', 'Purchase Order Approved ...');
                        return response()->json(array('redirect_url' => 'admin/purchase_order'));
                    }

                    break;
                case 3:

                    $user_id = isset($purchase_order->approver_3) ? $purchase_order->approver_3 : '';
                    $to = Employee_records::where('employee_id', $user_id)
                                    ->where('company_id', Auth::user()->company_id)->first();
                    ;

                    if ($user_id !== '' && $token == $purchase_order->approver_token && isset($to)) {
                        //$token = md5($user_mail['Email']) . time();
                        // set the approvers email_id address in place of testers
                        $purchase_order->approver_token = md5($to->email_id . time()) . '3';
                        Mail::to($to->email_id)->send(new purchase_order_approval($purchase_order));

                        $purchase_order->approved_indicator = 4;
                        $purchase_order->save();

                        session()->flash('flash_message', 'Approval Request sent successfully to 3rd approver...');
                        return response()->json(array('redirect_url' => 'admin/purchase_order'));
                    } else {
                        $purchase_order->approver_token = '';
                        $purchase_order->approved_indicator = 'approved';
                        $purchase_order->save();

                        session()->flash('flash_message', 'Purchase Order Approved ...');
                        return response()->json(array('redirect_url' => 'admin/purchase_order'));
                    }
                    break;
                case 4:

                    $user_id = isset($purchase_order->approver_4) ? $purchase_order->approver_4 : '';
                    $to = Employee_records::where('employee_id', $user_id)
                                    ->where('company_id', Auth::user()->company_id)->first();
                    ;

                    if ($user_id !== '' && $token == $purchase_order->approver_token && isset($to)) {
                        $purchase_order->approver_token = md5($to->email_id . time()) . '4';
                        Mail::to($to->email_id)->send(new purchase_order_approval($purchase_order));

                        $purchase_order->approved_indicator = 5;
                        $purchase_order->save();

                        session()->flash('flash_message', 'Approval Request sent successfully to 4th approver...');
                        return response()->json(array('redirect_url' => 'admin/purchase_order'));
                    } else {
                        $purchase_order->approver_token = '';
                        $purchase_order->approved_indicator = 'approved';
                        $purchase_order->save();

                        session()->flash('flash_message', 'Purchase order Approved ...');
                        return response()->json(array('redirect_url' => 'admin/purchase_order'));
                    }
                    break;
                case 5:
                    if ($token == $purchase_order->approver_token) {
                        $purchase_order->approver_token = '';
                        $purchase_order->approved_indicator = 'approved';
                        $purchase_order->save();

                        session()->flash('flash_message', 'Purchase Order Approved ...');
                        return response()->json(array('redirect_url' => 'admin/purchase_order'));
                        break;
                    } else {
                        session()->flash('flash_message', 'Approval Token expired ... ');
                        return response()->json(array('redirect_url' => 'admin/purchase_order'));
                    }
                default :

                    break;
            }
        } else {
            session()->flash('flash_message', 'Purchase order is already approved...');
            return response()->json(array('redirect_url' => 'admin/purchase_order'));
        }


        session()->flash('flash_message', 'Error occurred...');
        return response()->json(array('redirect_url' => 'admin/purchase_order'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    //
    public function edit($id)
    {

        $purchase_order = purchase_order::find($id);


        //get material records
        $material = array();
        $temp = materialmaster::all();
        foreach ($temp as $value) {
            $material[$value['material_number']] = $value['material_name'];
        }

        // get the requestor id 
        $user = Auth::user();
        $username = 'you are not logged in';
        if (Auth::check()) {
            $username = $user->name;
        }
        $requestedby = array();
        $temp = Employee_records::where('company_id',Auth::user()->company_id)->where('status', 'active')->get();

        foreach ($temp as $value) {

            $requestedby[$value->employee_id] = $value->employee_id . ' ( ' . (isset($value->employee_first_name) ? $value->employee_first_name : '') . ' ) ';
        }

        $requestor = User::where('name', $username)->first();
        $requestor_data = User::all();
        foreach ($requestor_data as $value) {
            $requestors[$value->id] = $value->name;
        }

        $requestor = $requestor['id'];
        $changed_by = $requestor;
        $created_by = $requestor;


        //get currency from currency table
        $currency = array();
        $temp = null;
        $temp = Currency::all();
        foreach ($temp as $value) {
            $currency[$value['id']] = $value['short_code'];
        }

        $phase_ids = array();
        $project_ids = array();
        $task_ids = array();

        //make associative arrays
        $phase_data = Projectphase::get();
        foreach ($phase_data as $value) {
            $phase_ids[$value['id']] = $value['phase_Id'] . ' (' . $value['phase_name'] . ') ';
        }

        $project_data = Project::get();
        foreach ($project_data as $value) {
            $project_ids[$value['id']] = $value['project_Id'] . ' (' . $value['project_name'] . ') ';
        }

        $tasks = Projecttask::get();
        foreach ($tasks as $value) {
            $task_ids[$value['id']] = $value['task_Id'] . ' (' . $value['task_name'] . ') ';
        }


        $country = array();
        $country_data = country::all();
        foreach ($country_data as $value) {
            $country[$value->id] = $value->country_name;
        }


        $purchaseorder_item_data = purchaseorder_item:: where('company_id', Auth::user()->company_id)->where('purchase_order_number', $purchase_order->purchase_order_number)->get();
        //value are static as module not 
        $purchase_order_number = array();
        $purchase_order_data = \App\purchase_order:: where('company_id', Auth::user()->company_id)->get();
        foreach ($purchase_order_data as $value) {

            $purchase_order_number[$value->purchase_order_no] = $value->purchase_order_no;
        }

        $vendor = array();
        $vendor_data = \App\vendor::all();
        foreach ($vendor_data as $value) {
            $vendor[$value->id] = $value->name;
        }
        //check for status
        $status = '';

        $gl = gl::all();
        $g_l_account = array();
        foreach ($gl as $value) {
            $g_l_account[$value->id] = $value->gl_account_number;
        }

        $cost_center = array();
        $cost_center_data = \App\Cost_centres::all();
        foreach ($cost_center_data as $value) {

            $cost_center[$value->cost_id] = $value->cost_centre;
        }


        return view('admin.purchase_order.edit', compact('country', 'requestors', 'cost_center', 'g_l_account', 'task_ids', 'project_ids', 'phase_ids', 'changed_by', 'created_by', 'purchase_order_number', 'requestedby', 'vendor', 'currency', 'purchase_order', 'purchaseorder_item_data', 'material'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {

        $purchase_order_data = Input::all();
        $elementdata = $purchase_order_data['elementdata'];
        $purchase = $purchase_order_data['obj'];

        $validationmessages = [

            'header_note.required' => 'Please enter short description',
            'approver_1.required' => 'Please select approver 1',
        ];


        $validator = Validator::make($purchase, [

                    'approver_1' => 'required',
                    'header_note' => 'required|max:255',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return response()->json($msgs);
        }

        foreach ($elementdata as $index => $row) {
            $row['purchase_order_number'] = $purchase['purchase_order_number'];
            unset($row['optradio']);
            $validationmsgitem = [
                'processing_status.required' => 'Please select a Processing Status on ' . ($index + 1) . ' record',
                'status.required' => 'Please enter select Status ' . ($index + 1) . ' record',
                'item_no.required' => 'Please enter Item number on ' . ($index + 1) . ' record',
                'item_quantity.required' => 'Please enter Item quantity on ' . ($index + 1) . ' record',
                'item_cost.required' => 'Please enter Item cost on ' . ($index + 1) . ' record',
                'currency.required' => 'Please select Currency type on ' . ($index + 1) . ' record',
                'delivery_date.required' => 'Please select Delevery date on ' . ($index + 1) . ' record',
                'requestor.required' => 'Please select a Requestor on ' . ($index + 1) . ' record',
                'item_category.required' => 'Please select Item category on ' . ($index + 1) . ' record',
                'material.required' => 'Please enter Material on ' . ($index + 1) . ' record',
                'quantity_unit.required' => 'Please select a Quantity unit on ' . ($index + 1) . ' record',
                'material_group.required' => 'Please enter Material group on ' . ($index + 1) . ' record',
                'vendor.required' => 'Please select a Vendor on ' . ($index + 1) . ' record',
                'contract_number.required' => 'Please enter Contract number on ' . ($index + 1) . ' record',
                'contract_item_number.required' => 'Please enter Contract Item number on ' . ($index + 1) . ' record',
                'purchase_order_number.required' => 'Please enter purchase order number on ' . ($index + 1) . ' record',
                'material_description.required' => 'Please enter Material description on ' . ($index + 1) . ' record',
                'project_id.required' => 'Please select a project on ' . ($index + 1) . ' record',
                'phase_id.required' => 'Please select a phase on ' . ($index + 1) . ' record',
                'task_id.required' => 'Please select a task on ' . ($index + 1) . ' record',
                'g_l_account.required' => 'Please select a requisition g/l account on ' . ($index + 1) . ' record',
                'cost_center.required' => 'Please select cost center on ' . ($index + 1) . ' record',
                'add1.required' => 'Please enter your street address',
                'postal_code.required' => 'Please enter your Postal code / Zip code',
            ];
            $validator = Validator::make($row, [
                        'processing_status' => 'required',
                        'add1' => 'required',
                        'cost_center' => 'required',
                        'g_l_account' => 'required',
                        'project_id' => 'required',
                        'phase_id' => 'required',
                        'task_id' => 'required',
                        'status' => 'required',
                        'item_no' => 'required',
                        'item_quantity' => 'required',
                        'item_cost' => 'required',
                        'currency' => 'required',
                        'delivery_date' => 'required',
                        'requestor' => 'required',
                        'item_category' => 'required',
                        'material' => 'required',
                        'quantity_unit' => 'required',
                        'material_group' => 'required',
                        'vendor' => 'required',
                        'contract_number' => 'required',
                        'contract_item_number' => 'required',
                        'purchase_order_number' => 'required',
                        'material_description' => 'required|max:255',
                        'postal_code' => 'required|numeric|digits_between:5,10',
                            ], $validationmsgitem);


            if ($validator->fails()) {
                $msgs = $validator->messages();
                return response()->json($msgs);
            }
            $row['company_id'] = Auth::user()->company_id;
            $matchThese = array('purchase_order_number' => $purchase['purchase_order_number'], 'item_no' => $row['item_no']);
            purchaseorder_item::updateOrCreate($matchThese, $row);
        }


        purchase_order::where('purchase_order_number', $purchase['purchase_order_number'])
                ->update($purchase);
        session()->flash('flash_message', 'Purchase Order updated successfully...');
        return response()->json(array('redirect_url' => 'admin/purchase_order'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $purchase_orderno = DB::table('purchase_order')
                ->select('purchase_order.purchase_order_number')
//                ->where('company_id', Auth::user()->company_id)
                ->where('id', $id)
                ->first();
        
        foreach ($purchase_orderno as $pid) {
            $order_no = $pid;
        }


        $purchase_item = DB::table('purchaseorder_item')
                ->select('purchaseorder_item.purchase_order_number')
                //->where('company_id', Auth::user()->company_id)
                ->where('purchase_order_number', $order_no)
                ->first();
        if (empty($purchase_item)) {

            //delete purchase order
            $purchaseorder = purchase_order::find($id);
            $purchaseorder->delete($id);

            session()->flash('flash_message', 'Purchase order deleted successfully...');
            return redirect('admin/purchase_order');
        } else {
            foreach ($purchase_item as $pitem) {
                $porderno = $pitem;
            }

            if ($porderno == $order_no) {
                //delete purchase order item
                DB::table('purchaseorder_item')->where('purchase_order_number', '=', $order_no)
                        //->where('company_id', Auth::user()->company_id)
                        ->delete();
                $purchaseorder = purchase_order::where('company_id', Auth::user()->company_id)->find($id);
                $purchaseorder->delete($id);
                session()->flash('flash_message', 'Purchase order deleted successfully...');
                return redirect('admin/purchase_order');
            }
        }
    }

    public function deleteItem($id)
    {
        $purchaseorder_id = purchaseorder_item::where('company_id', Auth::user()->company_id)->find($id);
        $purchaseorder_id->delete($id);
        session()->flash('flash_message', 'Purchase order item deleted successfully...');
        return redirect('admin/purchase_order');
    }

    public function getApproverName()
    {
        $id = Input::all()['id'];
        $employee_id = Employee_records::where('employee_id', $id)
                ->where('company_id', Auth::user()->company_id)
                ->select('employee_first_name')
                ->first();

        $employees = $employee_id->toArray();
        return response()->json($employees);
    }

}
