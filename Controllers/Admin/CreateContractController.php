<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Create_Contract;
use App\contract_item;
use App\Admin_employees;
use App\purchase_order;
use App\materialmaster;
use App\User;
use App\Currency;
use Illuminate\Support\Facades\DB;

class CreateContractController extends Controller {

    public function index() {
        $contract_data = Create_Contract::all();


        $createdon = array();

        $createdby = array();

        foreach ($contract_data as $key => $value) {

            $created_date[$key] = isset($value->created_on) ? $value->created_on : '';
            if (empty($created_date[$key])) {
                $createdon[$key] = null;
            } else {
                $createdon[$key] = date("Y-m-d", strtotime($created_date[$key]));
            }

            $createdby[$key] = isset($value->created_by) ? User::where('id', $value->created_by)->first()['name'] : '';
        }

        return view('admin.contract.index', compact('contract_data', 'createdby'));
    }

    public function create() {
        $contract_data = Create_Contract::all();

        foreach ($contract_data as $key => $value) {

            $contract = $value;
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




        $temp = Admin_employees::where('status', '1')->get();

        foreach ($temp as $value) {

            $requestedby[$value->employee_personnel_number] = $value->employee_personnel_number . ' ( ' . (isset($value->employee_first_name) ? $value->employee_first_name : '') . ' ) ';
        }


        $requestor = User::where('name', $username)->first();
        $requestor_data = User::all();
        foreach ($requestor_data as $value) {
            $requestors[$value->id] = $value->name;
        }

        $requestor = $requestor['id'];
        $changed_by = $requestor;


        //get currency from currency table
        $currency = array();
        $temp = null;
        $temp = Currency::all();
        foreach ($temp as $value) {
            $currency[$value['id']] = $value['short_code'];
        }

        //  $contract_item_data = contract_item::where('aggreement_number', $contract['agreement_number'])->get();
        //value are static as module not 
        $purchase_order_number = array();
        $purchase_order_data = \App\purchase_order::all();
        foreach ($purchase_order_data as $value) {

            $purchase_order_number[$value->purchase_order_number] = $value->purchase_order_number;
        }


        //value are static as module not 
        $purchase_order_number = array();
        $purchase_order_data = \App\purchase_order::all();
        foreach ($purchase_order_data as $value) {

            $purchase_order_number[$value->purchase_order_number] = $value->purchase_order_number;
        }

        $vendor = array();
        $vendor_data = \App\vendor::all();
        foreach ($vendor_data as $value) {
            $vendor[$value->id] = $value->name;
        }


        return view('admin.contract.create', compact('requestors', 'contract', 'changed_by', 'purchase_order_number', 'requestedby', 'vendor', 'currency', 'material'));
    }

    public function store(Request $request) {

        $create_contract_data = Input::all();

        $elementdata = $create_contract_data['elementdata'];
        $contract = $create_contract_data['obj'];
        
          $validationmessages = [
            'agreement_number.required' => 'Please enter Agreement number',
            'Description.required' => 'Please enter short description',
        ];


        $validator = Validator::make($contract, [
                    'agreement_number' => 'required',
                    'description' => 'required|max:255',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return response()->json($msgs);
        }

      
        

        foreach ($elementdata as $index => $row) {
            unset($row['_token']);
            unset($row['_method']);

            $row['aggreement_number'] = $contract['agreement_number'];
            $row['purchase_orderno'] = isset($contract['purchase_orderno'])?$contract['purchase_orderno']:'';
   
           
            
            
            $validationmsgitem = [
                'processing_status.required' => 'Please select a Processing Status on ' . ($index + 1) . ' record',
                'status.required' => 'Please enter select Status ' . ($index + 1) . ' record',
                'requisition_number.required' => 'Please enter Requisition number on ' . ($index + 1) . ' record',
                'item_no.required' => 'Please enter Item number on ' . ($index + 1) . ' record',
                'item_quantity.required' => 'Please enter Item quantity on ' . ($index + 1) . ' record',
                'item_cost.required' => 'Please enter Item cost on ' . ($index + 1) . ' record',
                'currency.required' => 'Please select Currency type on ' . ($index + 1) . ' record',
                'requestor.required' => 'Please select a Requestor on ' . ($index + 1) . ' record',
                'item_category.required' => 'Please select Item category on ' . ($index + 1) . ' record',
                'quantity_unit.required' => 'Please select a Quantity unit on ' . ($index + 1) . ' record',
                'material_group.required' => 'Please enter Material group on ' . ($index + 1) . ' record',
                'vendor.required' => 'Please select a Vendor on ' . ($index + 1) . ' record',
                'aggreement_number.required' => 'Please enter Agreement number on ' . ($index + 1) . ' record',
                'contract_item_number.required' => 'Please enter Contract Item number on ' . ($index + 1) . ' record',
                'material_description.required' => 'Please enter Material description on ' . ($index + 1) . ' record',
            ];
            $validator = Validator::make($row, [
                        'processing_status' => 'required',
                        'status' => 'required',
                        'item_no' => 'required',
                        'item_quantity' => 'required',
                        'item_cost' => 'required',
                        'currency' => 'required',
                        'requestor' => 'required',
                        'item_category' => 'required',

                        'quantity_unit' => 'required',
                        'material_group' => 'required',
                        'vendor' => 'required',
                        'aggreement_number' => 'required',
                        'material_description' => 'required|max:255',
                            ], $validationmsgitem);

  

            if ($validator->fails()) {
                $msgs = $validator->messages();
                return response()->json($msgs);
            }
            
            
            $matchThese = array('aggreement_number' => $row['aggreement_number'], 'item_no' => $row['item_no']);
            contract_item::create($row);
        }


        unset($contract['purchase_orderno']);

        if (Auth::check()) {
            $user = Auth::user();
        }      
        $contract['created_by'] = $user->id;
        $contract['requested_by'] = $user->id;

        Create_Contract::create($contract);

        session()->flash('flash_message', 'Contract updated successfully...');
        return response()->json(array('redirect_url' => 'admin/contract'));
    }

    public function show($id) {
        //
    }

    public function edit($id) {
        $contract = Create_Contract::find($id);

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
        $temp = Admin_employees::where('status', '1')->get();

        foreach ($temp as $value) {

            $requestedby[$value->employee_personnel_number] = $value->employee_personnel_number . ' ( ' . (isset($value->employee_first_name) ? $value->employee_first_name : '') . ' ) ';
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

        $contract_item_data = contract_item::where('aggreement_number', $contract['agreement_number'])->get();
        //value are static as module not 
        $purchase_order_number = array();
        $purchase_order_data = \App\purchase_order::all();
        foreach ($purchase_order_data as $value) {

            $purchase_order_number[$value->purchase_order_number] = $value->purchase_order_number;
        }
        $vendor = array();
        $vendor_data = \App\vendor::all();
        foreach ($vendor_data as $value) {
            $vendor[$value->id] = $value->name;
        }

        return view('admin.contract.edit', compact('requestors', 'changed_by', 'purchase_order_number', 'requestedby', 'vendor', 'currency', 'contract', 'contract_item_data', 'material'));
    }

    public function update(Request $request) {
        $create_contract_data = Input::all();
        $elementdata = $create_contract_data['elementdata'];
        $contract = $create_contract_data['obj'];


        $validationmessages = [
            'agreement_number.required' => 'Please enter Agreement number',
            'Description.required' => 'Please enter short description',
        ];


        $validator = Validator::make($contract, [
                    'agreement_number' => 'required',
                    'description' => 'required|max:255',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return response()->json($msgs);
        }

        foreach ($elementdata as $index => $row) {
            unset($row['_token']);
            unset($row['_method']);

            $row['aggreement_number'] = $contract['agreement_number'];
//            $row['purchase_orderno'] = $contract['purchase_orderno'];


            $validationmsgitem = [
                'processing_status.required' => 'Please select a Processing Status on ' . ($index + 1) . ' record',
                'status.required' => 'Please enter select Status ' . ($index + 1) . ' record',
                'requisition_number.required' => 'Please enter Requisition number on ' . ($index + 1) . ' record',
                'item_no.required' => 'Please enter Item number on ' . ($index + 1) . ' record',
                'item_quantity.required' => 'Please enter Item quantity on ' . ($index + 1) . ' record',
                'item_cost.required' => 'Please enter Item cost on ' . ($index + 1) . ' record',
                'currency.required' => 'Please select Currency type on ' . ($index + 1) . ' record',
                'requestor.required' => 'Please select a Requestor on ' . ($index + 1) . ' record',
                'item_category.required' => 'Please select Item category on ' . ($index + 1) . ' record',
//                'material.required' => 'Please enter Material on ' . ($index + 1) . ' record',
                'quantity_unit.required' => 'Please select a Quantity unit on ' . ($index + 1) . ' record',
                'material_group.required' => 'Please enter Material group on ' . ($index + 1) . ' record',
                'vendor.required' => 'Please select a Vendor on ' . ($index + 1) . ' record',
                'aggreement_number.required' => 'Please enter Agreement number on ' . ($index + 1) . ' record',
                'contract_item_number.required' => 'Please enter Contract Item number on ' . ($index + 1) . ' record',
                'material_description.required' => 'Please enter Material description on ' . ($index + 1) . ' record',
            ];
            $validator = Validator::make($row, [
                        'processing_status' => 'required',
                        'status' => 'required',
                        'item_no' => 'required',
                        'item_quantity' => 'required',
                        'item_cost' => 'required',
                        'currency' => 'required',
                        'requestor' => 'required',
                        'item_category' => 'required',
//                        'material' => 'required',
                        'quantity_unit' => 'required',
                        'material_group' => 'required',
                        'vendor' => 'required',
                        'aggreement_number' => 'required',
                        'material_description' => 'required|max:255',
                            ], $validationmsgitem);


            if ($validator->fails()) {
                $msgs = $validator->messages();
                return response()->json($msgs);
            }
            $matchThese = array('aggreement_number' => $row['aggreement_number'], 'item_no' => $row['item_no']);
            contract_item::updateOrCreate($matchThese, $row);
        }

        unset($contract['purchase_orderno']);

        Create_Contract::where('agreement_number', $contract['agreement_number'])
                ->update($contract);
        session()->flash('flash_message', 'Contract updated successfully...');
        return response()->json(array('redirect_url' => 'admin/contract'));
    }

    public function destroy($id) {
        $purchase_orderno = DB::table('contract')
                ->select('contract.agreement_number')
                ->where('id', $id)
                ->first();
        foreach ($purchase_orderno as $pid) {
            $order_no = $pid;
        }


        $purchase_item = DB::table('contract_item')
                ->select('contract_item.purchase_orderno')
                ->where('purchase_orderno', $order_no)
                ->first();
        if (empty($purchase_item)) {

            //delete purchase order
            $purchaseorder = Create_Contract::find($id);
            $purchaseorder->delete($id);

            session()->flash('flash_message', 'contract deleted successfully...');
            return redirect('admin/contract');
        } else {
            foreach ($purchase_item as $pitem) {
                $porderno = $pitem;
            }

            if ($porderno == $order_no) {
                //delete purchase order item
                DB::table('contract_item')->where('purchase_orderno', '=', $order_no)->delete();
                $purchaseorder = Create_Contract::find($id);
                $purchaseorder->delete($id);
                session()->flash('flash_message', 'contract deleted successfully...');
                return redirect('admin/contract');
            }
        }
    }

    public function create_ref_purchase_order() {


        $contract_number = Create_Contract::all();

        $contract_no = array();

        foreach ($contract_number as $cno) {


            $contract_no[$cno->agreement_number] = $cno->agreement_number;
        }


        return view('admin.contract.create_ref', compact('contract_number', 'created_by', 'requestedby', 'contract_no'));
    }

    public function insert_Purchase_Order_to_contract(Request $request) {
        $purchase_id = $request['super_contract_no'];
        $status = $request['status'];
        $agreement_number = $request['agreement_number'];
        $super_agreement_number = $request['super_agreement_no'];




        if ($status == 'yes') {
            $contract_item_data = DB::table('contract_item')
                    ->where('aggreement_number', $purchase_id)
                    ->get();



            $contract_data = DB::table('contract')
                    ->select('*')
                    ->where('agreement_number', $purchase_id)
                    ->first();
//          

            $created_on = date('Y-m-d');

            //get login details
            $user = Auth::user();

            if (Auth::check()) {
                $userid = $user->id;
            } else {
                $userid = 'you are not logged in';
            }

//           

            foreach ($contract_item_data as $key => $item_data) {

                DB::table('contract_item')
                        ->insert(array('status' => $item_data->status, 'purchase_orderno' => $item_data->purchase_orderno, 'item_no' => $item_data->item_no, 'item_category' => $item_data->item_category, 'material' => $item_data->material, 'material_description' => $item_data->material_description, 'item_quantity' => $item_data->item_quantity, 'quantity_unit' => $item_data->quantity_unit, 'item_cost' => $item_data->item_cost,'currency'=>$item_data->currency ,'material_group' => $item_data->material_group, 'vendor' => $item_data->vendor, 'requestor' => $item_data->requestor, 'aggreement_number' => $agreement_number, 'created_by' => $userid, 'created_on' => $created_on, 'processing_status' => $item_data->processing_status));
            }

            DB::table('contract')
                    ->insert(array('agreement_number' => $agreement_number, 'description' => $contract_data->description, 'created_on' => $contract_data->created_on, 'created_by' => $contract_data->created_by, 'status' => $contract_data->status, 'agreement_type' => $contract_data->agreement_type, 'target_value' => $contract_data->target_value, 'value_unit' => $contract_data->value_unit, 'agreement_date' => $contract_data->agreement_date, 'validity_start' => $contract_data->validity_start, 'validity_end' => $contract_data->validity_end, 'quotation_date' => $contract_data->quotation_date, 'quotation_no' => $contract_data->quotation_no, 'sales_contact' => $contract_data->sales_contact, 'super_agreement_no' => $purchase_id));

            session()->flash('flash_message', 'contract created successfully...');
            return redirect('admin/contract');
        } else {
            return redirect('admin/contract');
        }
    }

}
