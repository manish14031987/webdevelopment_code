<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\purchase_order;
use App\GoodsReceipt;
use Illuminate\Support\Facades\DB;
use App\purchaseorder_item;
use App\goodsReceiptsItem;
use App\User;
use App\project_gr_cost;
use App\cost_centre_cost;
use App\gr_ir;
use App\materialmaster;
use App\gl;
use App\gl_records;

class GoodsReceiptController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $goods_receipt = GoodsReceipt::all();
        $createdby = array();
        foreach ($goods_receipt as $key => $value) {
            $createdby[$key] = ($value['created_by'] != '') ? User::where('id', $value['created_by'])->first()['original']['name'] : '';
        }

        return view('admin.goodsreceipt.index', compact('goods_receipt', 'createdby'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //fetch value for purchase order no.
        $purchase_no = array();
        $purchase_order_data = purchase_order::all();

        foreach ($purchase_order_data as $value) {

            $purchase_no[$value->purchase_order_number] = $value->purchase_order_number;
        }

        return view('admin.goodsreceipt.create', compact('purchase_no'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @credit decreases the account -> spend money 
     * @debit increases the account -> save money
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (Auth::check()) {
            $userid = $user->id;
        }

        $goods_receipt_data = Input::except('_token');

        $created_by = $userid;
        $created_on = date('Y-m-d');
        $changed_on = date('Y-m-d');
        $changed_by = $userid;
        $purchase_order_number = isset($goods_receipt_data['purchase_order_number']) ? $goods_receipt_data['purchase_order_number'] : '';

        if ($purchase_order_number == '') {
            $msgs = ['Please select Purchase Order No'];
            session()->flash('error_message', 'plz select Purchase order...');
            return redirect('admin/goods_receipt/create')->withErrors($msgs)->withInput($goods_receipt_data);
        }

        $item = array();

        if (isset($goods_receipt_data['purchase_order_item_no']))
            foreach ($goods_receipt_data['purchase_order_item_no'] as $key => $value) {

                $item[$key]['purchase_order_item_no'] = $value;
            }

        if (isset($goods_receipt_data['item_description']))
            foreach ($goods_receipt_data['item_description'] as $key => $value) {

                $item[$key]['item_description'] = $value;
            }

        if (isset($goods_receipt_data['vendor_number']))
            foreach ($goods_receipt_data['vendor_number'] as $key => $value) {

                $item[$key]['vendor_number'] = $value;
            }

        if (isset($goods_receipt_data['vendor_name']))
            foreach ($goods_receipt_data['vendor_name'] as $key => $value) {

                $item[$key]['vendor_name'] = $value;
            }

        if (isset($goods_receipt_data['purchase_order_quantity']))
            foreach ($goods_receipt_data['purchase_order_quantity'] as $key => $value) {

                $item[$key]['purchase_order_quantity'] = $value;
            }

        if (isset($goods_receipt_data['quantity_received']))
            foreach ($goods_receipt_data['quantity_received'] as $key => $value) {

                $item[$key]['quantity_received'] = $value;
            }


        if (isset($goods_receipt_data['quantity_remaining']))
            foreach ($goods_receipt_data['quantity_remaining'] as $key => $value) {

                $item[$key]['quantity_remaining'] = $value;
            }

        if (isset($goods_receipt_data['bill_of_lading']))
            foreach ($goods_receipt_data['bill_of_lading'] as $key => $value) {

                $item[$key]['bill_of_lading'] = $value;
            }

        if (isset($goods_receipt_data['delivery_note']))
            foreach ($goods_receipt_data['delivery_note'] as $key => $value) {

                $item[$key]['delivery_note'] = $value;
            }

        if (isset($goods_receipt_data['status']))
            foreach ($goods_receipt_data['status'] as $key => $value) {
                $item[$key]['status'] = $value;
            }

        if (count($item) == 0) {
            $msgs = ['Please Valid Purchase Order No'];
            session()->flash('error_message', 'Goods Receipt is empty , no item(s) found...');
            session()->flash('purchase_order', $purchase_order_number);
            return redirect('admin/goods_receipt/create')->withErrors($msgs)->withInput($goods_receipt_data);
        }

        foreach ($item as $goods_receipt_item) {


            $goods_receipt_item['purchase_order_number'] = $purchase_order_number;


            $validationmessages = [
                'purchase_order_number.required' => 'Please select Purchase Order Number',
                'purchase_order_item_no.required' => 'Please enter Item No',
                'item_description.required' => 'Please enter Item Description',
                'vendor_number.required' => 'Please enter Vendor Number',
                'vendor_name.unique' => 'please enter Vendor Name',
                'purchase_order_quantity.required' => 'Please enter Purchase Order Quantity',
                'quantity_received.required' => 'Please enter Quantity Received',
                'quantity_remaining.required' => 'Please enter Quantity Remaining',
                'bill_of_lading.required' => 'Please enter Bill of Lading',
                'delivery_note.required' => 'Please enter Delivery Note',
            ];

            $validator = Validator::make($goods_receipt_item, [
                        'purchase_order_number' => 'required|filled',
                        'purchase_order_item_no' => 'required|filled',
                        'item_description' => 'required|filled',
                        'vendor_number' => 'required|filled',
                        'vendor_name' => 'required|filled',
                        'purchase_order_quantity' => 'required|filled',
                        'quantity_received' => 'required|filled',
                        'quantity_remaining' => 'required|filled',
                        'bill_of_lading' => 'required|filled',
                        'delivery_note' => 'required|filled',
                            ], $validationmessages);

            if ($validator->fails()) {
                $msgs = $validator->messages();
                session()->flash('purchase_order', $purchase_order_number);
                return redirect('admin/goods_receipt/create')->withErrors($validator)->withInput($goods_receipt_data);
            }
        }

        // insert in GR table
        $goodsbill = GoodsReceipt::create(['purchase_order_number' => $purchase_order_number, 'posting_date' => $goods_receipt_data['posting_date'], 'document_date' => $goods_receipt_data['document_date'], 'created_on' => $created_on, 'created_by' => $created_by, 'changed_by' => $changed_by, 'changed_on' => $changed_on]);
        $goodsbill = $goodsbill->toArray();

        // insert in GRI table
        foreach ($item as $goods_receipt_item) {

            $goods_receipt_item['goods_receipt_no'] = $goodsbill['id'];
            $po_item = purchaseorder_item::where(['purchase_order_number' => $purchase_order_number, 'item_no' => $goods_receipt_item['purchase_order_item_no']])->first();

            $goods_receipt_item['company_id'] = Auth::user()->company_id;
            if ($po_item != null) {
                $goods_receipt_item['task'] = $po_item->task_id;
                $goods_receipt_item['phase'] = $po_item->phase_id;
                $goods_receipt_item['project'] = $po_item->project_id;
                $goods_receipt_item['item_cost'] = $po_item->item_cost;
                $goods_receipt_item['gl_account'] = $po_item->g_l_account;
                $goods_receipt_item['cost_center'] = $po_item->cost_center;
                $goods_receipt_item['purchase_order_number'] = $purchase_order_number;
            }
            goodsReceiptsItem::create($goods_receipt_item);
        }


        


        /*  Start of Posting code to other tables   
         *         
         * list of tables to be posted to 
         * 
         * use App\project_gr_cost;
         * use App\cost_centre_cost;
         * use App\gr_ir;
         * use App\materialmaster;
         * use App\gl;
         *  
         */

        $item = goodsReceiptsItem::where(['goods_receipt_no' => $goodsbill['id'], 'purchase_order_number' => $purchase_order_number])->get();
       
        foreach ($item as $goods_receipt_item) {
            $po_item = purchaseorder_item::where(['purchase_order_number' => $purchase_order_number, 'item_no' => $goods_receipt_item['purchase_order_item_no']])->first();

            if (isset($po_item->phase_id) && isset($po_item->task_id) && isset($po_item->project_id))
                if (count($po_item) > 0) {
                    $matchArray = ['project_id' => $po_item->project_id, 'phase' => $po_item->phase_id, 'task_id' => $po_item->task_id, 'item_number' => $po_item->item_no];

                    $data = ['project_id' => $po_item->project_id,
                        'phase' => $po_item->phase_id,
                        'task_id' => $po_item->task_id,
                        'purchase_order_number' => $goods_receipt_data['purchase_order_number'],
                        'item_number' => $po_item->item_no,
                        'value' => ($po_item->item_cost * $goods_receipt_item['quantity_received']),
                        'currency' => $po_item->currency,
                        'material_documber_number' => $goodsbill['id'],
                        'posting_date' => date('Y-m-d'),
                        'created_at' => date('Y-m-d'),
                        'updated_at' => date('Y-m-d'),
                        'posted_by' => $userid
                    ];
                    $test = project_gr_cost::where($matchArray)->first();
                    if (count($test) > 0) {
                        unset($data['created_at']);
                        unset($data['posted_by']);
                        unset($data['posting_date']);
                        project_gr_cost::where($matchArray)->update($data);
                    } else {
                            project_gr_cost::create($data);
                    }
                }

            if (isset($po_item->cost_center))
                if (count($po_item) > 0) {
                    $matchArray = ['item_number' => $po_item->item_no, 'purchase_order_number' => $goods_receipt_data['purchase_order_number'], 'cost_centre' => $po_item->cost_center];

                    $data = [
                        'cost_centre' => $po_item->cost_center,
                        'purchase_order_number' => $goods_receipt_data['purchase_order_number'],
                        'item_number' => $po_item->item_no,
                        'value' => ($po_item->item_cost * $goods_receipt_item['quantity_received']),
                        'currency' => $po_item->currency,
                        'material_documber_number' => $goodsbill['id'],
                        'posting_date' => date('Y-m-d'),
                        'created_at' => date('Y-m-d'),
                        'updated_at' => date('Y-m-d'),
                        'posted_by' => $userid
                    ];
                    $test = cost_centre_cost::where($matchArray)->first();
                    if (count($test) > 0) {
                        unset($data['created_at']);
                        unset($data['posted_by']);
                        unset($data['posting_date']);
                        cost_centre_cost::where($matchArray)->update($data);
                    } else {
                        cost_centre_cost::create($data);
                    }
                }


            if (count($po_item) > 0) {
                $matchArray = ['item' => $po_item->item_no, 'po_number' => $goods_receipt_data['purchase_order_number']];

                $data = [
                    'vendor_id' => $po_item->vendor,
                    'po_number' => $goods_receipt_data['purchase_order_number'],
                    'item' => $po_item->item_no,
                    'gr_value' => ($po_item->item_cost * $goods_receipt_item['quantity_received']),
                    'currency' => $po_item->currency,
                    'material_documber_number' => $goodsbill['id'],
                    'posting_date' => date('Y-m-d'),
                    'created_at' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d'),
                    'posted_by' => $userid
                ];
                $test = gr_ir::where($matchArray)->first();
                if (count($test) > 0) {
                    unset($data['created_at']);
                    unset($data['posted_by']);
                    unset($data['posting_date']);
                    gr_ir::where($matchArray)->update($data);
                } else {
                    gr_ir::create($data);
                }
            }


            //update the inventory
            materialmaster::where('material_number', $po_item->material)->increment('stock_item', $goods_receipt_item['quantity_received']);

            //gl_account update remaining
            $gl_account_number = gl::where('id', $goods_receipt_item['gl_account'])->select('gl_account_number')->get()->toArray();
          
            
            gl_records::insert(['remark'=>'goods_receipt Cr','ref_id'=>$goods_receipt_item['gl_account'],'gl_account_number'=>$gl_account_number[0]['gl_account_number'],'debit'=>0,'credit'=>($goods_receipt_item['quantity_received'] * $goods_receipt_item['item_cost']),'created_by'=>  Auth::user()->id,'company_id'=>  Auth::user()->company_id]);
           
            purchaseorder_item::where(['purchase_order_number' => $goods_receipt_item['purchase_order_number'], 'item_no' => $goods_receipt_item['purchase_order_item_no']])->increment('item_quantity', -($goods_receipt_item['quantity_received']));
        }








        session()->flash('flash_message', 'Goods Receipt Added successfully...');
        return redirect('admin/goods_receipt');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
//    public function edit($id)
//    {
//        $purchase_no = array();
//        $purchase_order_data = purchase_order::all();
//
//        foreach ($purchase_order_data as $value) {
//
//            $purchase_no[$value->purchase_order_number] = $value->purchase_order_number;
//        }
//        $goods_receipt = GoodsReceipt::find($id);
//        $goods_receipt_item = goodsReceiptsItem::where('goods_receipt_no', $id)->get();
//
//        return view('admin.goodsreceipt.create', compact('purchase_no', 'goods_receipt_item', 'goods_receipt'));
//    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
//    public function update(Request $request, $id)
//    {
//
//        $user = Auth::user();
//
//        if (Auth::check()) {
//            $userid = $user->id;
//        }
//
//        $goods_receipt_data = Input::except('_token');
//        $purchase_order_number = isset($goods_receipt_data['purchase_order_number']) ? $goods_receipt_data['purchase_order_number'] : '';
//        $changed_on = date('Y-m-d');
//        $changed_by = $userid;
//        $goods_receipt_no = $id;
//
//        $item = array();
//
//        if (isset($goods_receipt_data['purchase_order_item_no']))
//            foreach ($goods_receipt_data['purchase_order_item_no'] as $key => $value) {
//
//                $item[$key]['purchase_order_item_no'] = $value;
//            }
//
//        if (isset($goods_receipt_data['item_description']))
//            foreach ($goods_receipt_data['item_description'] as $key => $value) {
//
//                $item[$key]['item_description'] = $value;
//            }
//
//        if (isset($goods_receipt_data['vendor_number']))
//            foreach ($goods_receipt_data['vendor_number'] as $key => $value) {
//
//                $item[$key]['vendor_number'] = $value;
//            }
//
//        if (isset($goods_receipt_data['vendor_name']))
//            foreach ($goods_receipt_data['vendor_name'] as $key => $value) {
//
//                $item[$key]['vendor_name'] = $value;
//            }
//
//        if (isset($goods_receipt_data['purchase_order_quantity']))
//            foreach ($goods_receipt_data['purchase_order_quantity'] as $key => $value) {
//
//                $item[$key]['purchase_order_quantity'] = $value;
//            }
//
//        if (isset($goods_receipt_data['quantity_received']))
//            foreach ($goods_receipt_data['quantity_received'] as $key => $value) {
//
//                $item[$key]['quantity_received'] = $value;
//            }
//
//
//        if (isset($goods_receipt_data['quantity_remaining']))
//            foreach ($goods_receipt_data['quantity_remaining'] as $key => $value) {
//
//                $item[$key]['quantity_remaining'] = $value;
//            }
//
//        if (isset($goods_receipt_data['bill_of_lading']))
//            foreach ($goods_receipt_data['bill_of_lading'] as $key => $value) {
//
//                $item[$key]['bill_of_lading'] = $value;
//            }
//
//        if (isset($goods_receipt_data['delivery_note']))
//            foreach ($goods_receipt_data['delivery_note'] as $key => $value) {
//
//                $item[$key]['delivery_note'] = $value;
//            }
//
//
//        if (isset($goods_receipt_data['status']))
//            foreach ($goods_receipt_data['status'] as $key => $value) {
//
//                $item[$key]['status'] = $value;
//            }
//
//        $goodsbill = GoodsReceipt::find($id)->updateOrCreate([ 'posting_date' => $goods_receipt_data['posting_date'], 'document_date' => $goods_receipt_data['document_date'], 'changed_by' => $changed_by, 'changed_on' => $changed_on]);
//        $goodsbill = $goodsbill->toArray();
//        foreach ($item as $goods_receipt_item) {
//            $po_item = purchaseorder_item::where(['purchase_order_number' => $purchase_order_number, 'item_no' => $goods_receipt_item['purchase_order_item_no']])->first();
//            $goods_receipt_item['goods_receipt_no'] = $goodsbill['id'];
//
//            $validationmessages = [
//                'purchase_order_item_no.required' => 'Please enter Item No',
//                'item_description.required' => 'Please enter Item Description',
//                'vendor_number.required' => 'Please enter Vendor Number',
//                'vendor_name.unique' => 'please enter Vendor Name',
//                'purchase_order_quantity.required' => 'Please enter Purchase Order Quantity',
//                'quantity_received.required' => 'Please enter Quantity Received',
//                'quantity_remaining.required' => 'Please enter Quantity Remaining',
//                'bill_of_lading.required' => 'Please enter Bill of Lading',
//                'delivery_note.required' => 'Please enter Delivery Note',
//            ];
//
//            $validator = Validator::make($goods_receipt_item, [
//                        'purchase_order_item_no' => 'required',
//                        'item_description' => 'required',
//                        'vendor_number' => 'required',
//                        'vendor_name' => 'required',
//                        'purchase_order_quantity' => 'required',
//                        'quantity_received' => 'required',
//                        'quantity_remaining' => 'required',
//                        'bill_of_lading' => 'required',
//                        'delivery_note' => 'required',
//                            ], $validationmessages);
//
//            if ($validator->fails()) {
//                $msgs = $validator->messages();
//                return redirect('admin/goods_receipt/' . $id . '/edit')->withErrors($validator);
//            }
//            $goods_receipt_item['task'] = $po_item->task_id;
//            $goods_receipt_item['phase'] = $po_item->phase_id;
//            $goods_receipt_item['project'] = $po_item->project_id;
//            $goods_receipt_item['item_cost'] = $po_item->item_cost;
//            $goods_receipt_item['gl_account'] = $po_item->g_l_account;
//            $goods_receipt_item['cost_center'] = $po_item->cost_center;
//            $goods_receipt_item['purchase_order_number'] = $purchase_order_number;
//            $goods_receipt_item['company_id'] = Auth::user()->company_id;
//
//            goodsReceiptsItem::where(['goods_receipt_no' => $id, 'purchase_order_item_no' => $goods_receipt_item['purchase_order_item_no']])
//                    ->updateOrCreate($goods_receipt_item);
//        }
//
//
//        /*  Start of Posting code to other tables   
//         *         
//         * list of tables to be posted to 
//         * 
//         * use App\project_gr_cost;
//         * use App\cost_centre_cost;
//         * use App\gr_ir;
//         * use App\materialmaster;
//         * use App\gl;
//         *  
//         */
//        foreach ($item as $goods_receipt_item) {
//            $po_item = purchaseorder_item::where(['purchase_order_number' => $goodsbill['purchase_order_number'], 'item_no' => $goods_receipt_item['purchase_order_item_no']])->first();
//
//            if (isset($po_item->phase_id) && isset($po_item->task_id) && isset($po_item->project_id))
//                if (count($po_item) > 0) {
//                    $matchArray = ['project_id' => $po_item->project_id, 'phase' => $po_item->phase_id, 'task_id' => $po_item->task_id, 'item_number' => $po_item->item_no];
//
//                    $data = ['project_id' => $po_item->project_id,
//                        'phase' => $po_item->phase_id,
//                        'task_id' => $po_item->task_id,
//                        'purchase_order_number' => $goodsbill['purchase_order_number'],
//                        'item_number' => $po_item->item_no,
//                        'value' => ($po_item->item_cost * $goods_receipt_item['quantity_received']),
//                        'currency' => $po_item->currency,
//                        'material_documber_number' => $goodsbill['id'],
//                        'posting_date' => date('Y-m-d'),
//                        'created_at' => date('Y-m-d'),
//                        'updated_at' => date('Y-m-d'),
//                        'posted_by' => $userid
//                    ];
//                    $test = project_gr_cost::where($matchArray)->first();
//                    if (count($test) > 0) {
//                        unset($data['created_at']);
//                        unset($data['posted_by']);
//                        unset($data['posting_date']);
//                        project_gr_cost::where($matchArray)->update($data);
//                    } else {
//                        project_gr_cost::create($data);
//                    }
//                }
//
//            if (isset($po_item->cost_center))
//                if (count($po_item) > 0) {
//                    $matchArray = ['item_number' => $po_item->item_no, 'purchase_order_number' => $goodsbill['purchase_order_number'], 'cost_centre' => $po_item->cost_center];
//
//                    $data = [
//                        'cost_centre' => $po_item->cost_center,
//                        'purchase_order_number' => $goodsbill['purchase_order_number'],
//                        'item_number' => $po_item->item_no,
//                        'value' => ($po_item->item_cost * $goods_receipt_item['quantity_received']),
//                        'currency' => $po_item->currency,
//                        'material_documber_number' => $goodsbill['id'],
//                        'posting_date' => date('Y-m-d'),
//                        'created_at' => date('Y-m-d'),
//                        'updated_at' => date('Y-m-d'),
//                        'posted_by' => $userid
//                    ];
//                    $test = cost_centre_cost::where($matchArray)->first();
//                    if (count($test) > 0) {
//                        unset($data['created_at']);
//                        unset($data['posted_by']);
//                        unset($data['posting_date']);
//                        cost_centre_cost::where($matchArray)->update($data);
//                    } else {
//                        cost_centre_cost::create($data);
//                    }
//                }
//
//
//            if (count($po_item) > 0) {
//                $matchArray = ['item' => $po_item->item_no, 'po_number' => $goodsbill['purchase_order_number']];
//
//                $data = [
//                    'vendor_id' => $po_item->vendor,
//                    'po_number' => $goodsbill['purchase_order_number'],
//                    'item' => $po_item->item_no,
//                    'gr_value' => ($po_item->item_cost * $goods_receipt_item['quantity_received']),
//                    'currency' => $po_item->currency,
//                    'material_documber_number' => $goodsbill['id'],
//                    'posting_date' => date('Y-m-d'),
//                    'created_at' => date('Y-m-d'),
//                    'updated_at' => date('Y-m-d'),
//                    'posted_by' => $userid
//                ];
//                $test = gr_ir::where($matchArray)->first();
//                if (count($test) > 0) {
//                    unset($data['created_at']);
//                    unset($data['posted_by']);
//                    unset($data['posting_date']);
//                    gr_ir::where($matchArray)->update($data);
//                } else {
//                    gr_ir::create($data);
//                }
//            }
//
//
//            //update the inventory
//            materialmaster::where('material_number', $po_item->material)->increment('stock_item', $goods_receipt_item['quantity_received']);
//
//            //gl_account update remaining
//            gl::where('id', $goods_receipt_item['gl_account'])->increment('debit', ($goods_receipt_item['quantity_received'] * $goods_receipt_item['item_cost']));
//
//            purchaseorder_item::where(['purchase_order_number' => $goodsbill['purchase_order_number'], 'item_no' => $goods_receipt_item['purchase_order_item_no']])->increment('item_quantity', -($goods_receipt_item['quantity_received']));
//        }
//
//
//        session()->flash('flash_message', 'Goods Receipt Added successfully...');
//        return redirect('admin/goods_receipt');
//    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        // fetch from GRI table
        $item = goodsReceiptsItem::where('goods_receipt_no', $id)->get()->toArray();
        $goods_receipt = GoodsReceipt::find($id)->first();
        $purchase_order_number = $goods_receipt->purchase_order_number;


        /*  Start of Posting code to other tables   
         *         
         * list of tables to be posted to 
         * 
         * use App\project_gr_cost;
         * use App\cost_centre_cost;
         * use App\gr_ir;
         * use App\materialmaster;
         * use App\gl;
         *  
         */
        foreach ($item as $goods_receipt_item) {

            $po_item = purchaseorder_item::where(['purchase_order_number' => $goods_receipt_item['purchase_order_number']])->first();

            if (isset($po_item->phase_id) && isset($po_item->task_id) && isset($po_item->project_id))
                if (count($po_item) > 0) {
                    $matchArray = ['project_id' => $po_item->project_id, 'phase' => $po_item->phase_id, 'task_id' => $po_item->task_id, 'item_number' => $po_item->item_no];

                    $test = project_gr_cost::where($matchArray)->delete();
                }

            if (isset($po_item->cost_center))
                if (count($po_item) > 0) {
                    $matchArray = ['item_number' => $po_item->item_no, 'purchase_order_number' => $goods_receipt_item['purchase_order_number'], 'cost_centre' => $po_item->cost_center];

                    $test = cost_centre_cost::where($matchArray)->delete();
                }


            if (count($po_item) > 0) {
                $matchArray = ['item' => $po_item->item_no, 'po_number' => $goods_receipt_item['purchase_order_number']];


                $test = gr_ir::where($matchArray)->delete();
            }


            if (count($po_item) > 0) {
                $matchArray = ['item' => $po_item->item_no, 'po_number' => $goods_receipt_item['purchase_order_number']];


                $test = gr_ir::where($matchArray)->delete();
            }


            //update the inventory
            materialmaster::where('material_number', $po_item->material)->decrement('stock_item', $goods_receipt_item['quantity_received']);

            //gl_account update remaining
            
            $gl_account_number = gl::where('id', $goods_receipt_item['gl_account'])->select('gl_account_number')->get()->toArray();
            gl_records::insert(['remark'=>'goods_receipt Db','ref_id'=>$goods_receipt_item['gl_account'],'gl_account_number'=>$gl_account_number[0]['gl_account_number'],'debit'=>($goods_receipt_item['quantity_received'] * $goods_receipt_item['item_cost']),'credit'=>0,'created_by'=>  Auth::user()->id,'company_id'=>  Auth::user()->company_id]);
           
            purchaseorder_item::where(['purchase_order_number' => $goods_receipt_item['purchase_order_number'], 'item_no' => $goods_receipt_item['purchase_order_item_no']])->increment('item_quantity', ($goods_receipt_item['quantity_received']));
        }


        GoodsReceipt::find($id)->update(['reversed'=>'1']);

        session()->flash('flash_message', 'Goods Receipt Deleted successfully... And Posted data reverted to initial value');
        return redirect('admin/goods_receipt');
    }

    public function getPurchaseitemList($purchase_order_number)
    {
        try {

            $goods_recept_item_qty = purchaseorder_item::groupBy('purchase_order_number')
                            ->selectRaw('sum(item_quantity) as sum')
                            ->where('purchase_order_number', $purchase_order_number)
                            ->pluck('sum')->first();

            if ($goods_recept_item_qty == 0)
                return response()->json(array('status' => 'msg', 'results' => 'All item for this purchase order received ...'));


            if (count($goods_recept_item_qty) > 0) {
                $purchaseItemdata = DB::table("purchaseorder_item")
                        ->where("purchaseorder_item.purchase_order_number", $purchase_order_number)
                        ->where("purchaseorder_item.item_quantity", "<>", 0)
                        ->join('vendor', 'purchaseorder_item.vendor', '=', 'vendor.id')
                        ->select('purchaseorder_item.*', 'vendor.name')
                        ->get();

                $Itemdata = DB::table("purchaseorder_item")
                        ->where("purchase_order_number", $purchase_order_number)
                        ->where('item_quantity', '<>', 0)
                        ->where('company_id', Auth::user()->company_id)
                        ->select('id', 'item_no')
                        ->pluck('item_no', 'id')
                        ->prepend('Select All Item', 0);


                if (count($purchaseItemdata) > 0) {
                    return response()->json(array('status' => true, 'results' => $purchaseItemdata->toArray(), 'item' => $Itemdata));
                } else {
                    return response()->json(array('status' => 'msg', 'results' => 'No Purchase order item(s) found...Plz add items to Purchase order'));
                }
            }
        } catch (\Exception $ex) {
            return response()->json(array('status' => false, 'message' => $ex->getMessage()));
        }
    }

    public function getPurchaseitem($item)
    {

        if (intval($item) > 10000000) {
            $purchaseItemdata = DB::table("purchaseorder_item")
                    ->where("purchaseorder_item.purchase_order_number", $item)
                    ->where("purchaseorder_item.item_quantity", "<>", 0)
                    ->join('vendor', 'purchaseorder_item.vendor', '=', 'vendor.id')
                    ->select('purchaseorder_item.*', 'vendor.name')
                    ->get();

            if (count($purchaseItemdata) > 0) {
                return response()->json(array('status' => true, 'results' => $purchaseItemdata->toArray()));
            } else {
                return response()->json(array('status' => 'msg', 'results' => 'No Purchase order item(s) found...Plz add items to Purchase order'));
            }
        }
        try {


            $goods_recept_item_qty = purchaseorder_item::
                            selectRaw('item_quantity')
                            ->where('id', $item)
                            ->where('item_quantity', '<>', 0)
                            ->pluck('item_quantity')->first();

            if ($goods_recept_item_qty == 0)
                return response()->json(array('status' => 'msg', 'results' => 'All item for this purchase order received ...'));


            if (count($goods_recept_item_qty) > 0) {
                $purchaseItemdata = DB::table("purchaseorder_item")
                        ->where("purchaseorder_item.id", $item)
                        ->join('vendor', 'purchaseorder_item.vendor', '=', 'vendor.id')
                        ->select('purchaseorder_item.*', 'vendor.name')
                        ->get();

                if (count($purchaseItemdata) > 0) {
                    return response()->json(array('status' => true, 'results' => $purchaseItemdata->toArray()));
                } else {
                    return response()->json(array('status' => 'msg', 'results' => 'No Purchase order item(s) found...Plz add items to Purchase order'));
                }
            }
        } catch (\Exception $ex) {
            return response()->json(array('status' => false, 'message' => $ex->getMessage()));
        }
    }

}
