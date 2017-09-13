<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\Projectcostplan;
use App\project_material_cost;
use App\project_miscellanous_cost;
use App\project_hardware_cost;
use App\project_software_cost;
use App\project_travel_cost;
use App\project_contingency_cost;
use App\project_facilities_cost;
use App\project_service_cost;
use App\project_internal_cost;
use App\project_external_cost;
use App\Project;
use App\Currency;
use App\Projecttask;
use App\materialmaster;
use App\purchaseorder_item;
use App\purchase_order;
use App\purchase_item;
use App\Activity_types;
use App\Activity_rates;
use App\Createrole;
use App\Roleauth;
use App\vendor;

class ProjectcostplanController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id = null)
    {
        Roleauth::check('project.costplan.index');

        $projectcostplan = Projectcostplan::all();
        $materialcostplan = project_material_cost::all();


        $projects = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projects[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }



        $currency = array();
        $currency_data = Currency::all();
        foreach ($currency_data as $key => $curr) {
            $currency[$curr->short_code] = $curr->short_code;
        }

        $tasks = array();
        $task_data = Projecttask::all();
        foreach ($task_data as $key => $task) {
            $tasks[$task->task_Id] = $task->task_Id . ' ( ' . $task->task_name . ' )';
        }

        $material = array();
        $material_data = materialmaster::all();
        foreach ($material_data as $key => $item) {
            $material[$item->material_number] = $item->material_number . ' ( ' . $item->material_name . ' ) ';
        }



        return view('admin.projectcostplan.index', compact('id', 'projectcostplan', 'materialcostplan', 'projects', 'currency', 'tasks', 'material'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function get_material_details($number)
    {
        $material_data = materialmaster::where('material_number', $number)->select(['material_description', 'standard_price', 'currency'])->get()->toArray();

        if (count($material_data) > 0) {
            $currency = Currency::find($material_data[0]['currency']);
            $material_data[0]['currency'] = ($currency != null) ? $currency['short_code'] : '';
            return response()->json(['status' => 'ok', 'data' => $material_data[0]]);
        } else {
            return response()->json(['status' => 'error']);
        }
    }

    public function get_activity_rate($activity)
    {
        $activity_id = Activity_types::where('activity_type', $activity)->select('activity_id')->first();
        $activity_rate = Activity_rates::where('activity_type_id', $activity_id->activity_id)->select('activity_actual_rate')->get()->toArray();
        return response()->json(['status' => 'ok', 'data' => (isset($activity_rate[0]) ? $activity_rate[0] : 'Not Set')]);
    }

    public function get_po_details($number)
    {
        $purchase_data = \App\purchase_item::where('requisition_number', $number)->select('item_no')->get()->toArray();
        $purchase_item = array();
        foreach ($purchase_data as $key => $item) {
            $purchase_item[$item['item_no']] = $item['item_no'];
        }
        if (count($purchase_data) > 0)
            return response()->json(['status' => 'ok', 'data' => $purchase_item]);
        else
            return response()->json(['status' => 'error']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($module)
    {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store($module)
    {
        Roleauth::check('project.costplan.create');

        try {
            switch ($module) {
                case 'material':
                    $material_data = Input::except('_token');
                    $material = $material_data['data'];

                    $material['total_price'] = ((int) $material['quantity']) * ((float) $material['unit_price']);

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'material_number.required' => 'Please select Matarial Number',
                        'description.required' => 'Please enter Short Description',
                        'quantity.required' => 'Please select Quantity',
                        'unit_price.required' => 'Please select Unit Price',
                        'type' => 'Please select Type',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];


                    $validator = Validator::make($material, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'material_number' => 'required',
                                'description' => 'required|max:200',
                                'unit_price' => 'required',
                                'quantity' => 'required',
                                'type' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }

                    project_material_cost::create($material);


                    break;
                case 'internal':

                    $internal_data = Input::except('_token');
                    $internal = $internal_data['data'];
                    $total_hours = 0;
                    $filtred = array();

                    foreach ($internal as $key => $value) {
                        if (preg_match('/hours-/', $key)) {
                            $key = str_replace("hours-", "", $key);
                            $filtred[$key] = $value;
                            $total_hours += (int) $value;
                        }
                    }

                    $internal['total_price'] = ($total_hours) * ((float) $internal['unit_rate']);
                    $internal['no_hours'] = json_encode($filtred);

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'resource role.required' => 'Please select Matarial Number',
                        'resource_id.required' => 'Please enter Short Description',
                        'resource_name.required' => 'Please select Resopurce Name',
                        'band.required' => 'Please select Band',
                        'no_hours' => 'Please enter Hours',
                        'unit_rate' => 'Please enter Unit Rate',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];


                    $validator = Validator::make($internal, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'resource role' => 'required',
                                'resource_id' => 'required|max:200',
                                'resource_name' => 'required',
                                'band' => 'required',
                                'no_hours' => 'required',
                                'unit_rate' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }

                    project_internal_cost::create($internal);

                    break;
                case 'external':
                    $external_data = Input::except('_token');
                    $external = $external_data['data'];
                    $filtred = array();
                    foreach ($external as $key => $value) {
                        if (preg_match('/noHour_*/', $key)) {
                            $key = explode(' ', $key)[0];
                            $key = str_replace("noHour_", "", $key);
                            $filtred[$key] = $value;
                        }
                    }
                    $external['no_hours'] = json_encode($filtred);

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'resource role.required' => 'Please Add Resource Role',
                        'resource_id.required' => 'Please Add Resource Id',
                        'resource_name.required' => 'Please Add Resource Name',
                        'contract_vendor.required' => 'Please Add Contract Vendor',
                        'purchase_order.required' => 'Please Select Purchase Order',
                        'unit_rate.required' => 'Please select Unit Rate',
                        'currency.required' => 'Please select Currency'
//                        'total_price' => 'Please enter the Total Cost'
                    ];


                    $validator = Validator::make($external, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'resource role' => 'required',
                                'resource_id' => 'required',
                                'resource_name' => 'required',
                                'contract_vendor' => 'required',
                                'purchase_order' => 'required',
                                'currency' => 'required'
//                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }
                    project_external_cost::create($external);

                    break;

                case 'software':
                    $software_data = Input::except('_token');
                    $software = $software_data['data'];
                    $purchase_order_item = purchase_item::where(['project_id' => $software['project_number'], 'requisition_number' => $software['purchase_order'], 'item_no' => $software['po_item_no']])->get()->toArray();
                    if (count($purchase_order_item)) {
                        $total_cost = $purchase_order_item[0]['item_cost'] * $purchase_order_item[0]['item_quantity'];
                        $quantity = $purchase_order_item[0]['item_quantity'];
                    }

                    $software['total_price'] = isset($total_cost) ? $total_cost : '';
                    $software['quantity'] = isset($quantity) ? $quantity : '';

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'purchase_order.required' => 'Please select Travel Request Number',
                        'currency.required' => 'Please select Currency',
                        'quantity.required' => 'Please enter Quantity',
                        'total_price.required' => 'Please Select correct Requisition Item and Requisition No'
                    ];

                    $validator = Validator::make($software, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'purchase_order' => 'required',
                                'quantity' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }
                    project_software_cost::create($software);


                    break;

                case 'service':
                    $service_data = Input::except('_token');
                    $service = $service_data['data'];

                    $purchase_order_item = purchase_item::where(['project_id' => $service['project_number'], 'requisition_number' => $service['purchase_order'], 'item_no' => $service['po_item_no']])->get()->toArray();
                    if (count($purchase_order_item)) {
                        $total_cost = $purchase_order_item[0]['item_cost'] * $purchase_order_item[0]['item_quantity'];
                        $quantity = $purchase_order_item[0]['item_quantity'];
                    }

                    $service['total_price'] = isset($total_cost) ? $total_cost : '';
                    $service['quantity'] = isset($quantity) ? $quantity : '';

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'purchase_order.required' => 'Please select Travel Request Number',
                        'currency.required' => 'Please select Currency',
                        'quantity.required' => 'Please enter Quantity',
                        'total_price.required' => 'Please Select correct Requisition Item and Requisition No'
                    ];

                    $validator = Validator::make($service, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'purchase_order' => 'required',
                                'quantity' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }
                    project_service_cost::create($service);

                    break;

                case 'hardware':
                    $hardware_data = Input::except('_token');
                    $hardware = $hardware_data['data'];

                    $purchase_order_item = purchase_item::where(['project_id' => $hardware['project_number'], 'requisition_number' => $hardware['purchase_order'], 'item_no' => $hardware['po_item_no']])->get()->toArray();
                    if (count($purchase_order_item)) {
                        $total_cost = $purchase_order_item[0]['item_cost'] * $purchase_order_item[0]['item_quantity'];
                        $quantity = $purchase_order_item[0]['item_quantity'];
                    }

                    $hardware['total_price'] = isset($total_cost) ? $total_cost : '';
                    $hardware['quantity'] = isset($quantity) ? $quantity : '';

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'purchase_order.required' => 'Please select Travel Request Number',
                        'currency.required' => 'Please select Currency',
                        'quantity.required' => 'Please enter Quantity',
                        'total_price.required' => 'Please Select correct Purchase Item and Purchase orders'
                    ];

                    $validator = Validator::make($hardware, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'purchase_order' => 'required',
                                'quantity' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }
                    project_hardware_cost::create($hardware);


                    break;
                case 'travel':
                    $travel_data = Input::except('_token');
                    $travel = $travel_data['data'];

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'travel_request_number.required' => 'Please select Travel Request Number',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];

                    $validator = Validator::make($travel, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'travel_request_number' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }
                    project_travel_cost::create($travel);

                    break;
                case 'contingency':
                    $contingency_data = Input::except('_token');
                    $contingency = $contingency_data['data'];

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'contingency.required' => 'Please Enter Number',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];

                    $validator = Validator::make($contingency, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'contingency' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }
                    project_contingency_cost::create($contingency);

                    break;
                case 'facilities':
                    $facilities_data = Input::except('_token');
                    $facilities = $facilities_data['data'];

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'facilities.required' => 'Please Enter Facilities',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];

                    $validator = Validator::make($facilities, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'facilities' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }
                    project_facilities_cost::create($facilities);

                    break;
                case 'misc':
                    $misc_data = Input::except('_token');
                    $misc = $misc_data['data'];

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'miscellanous.required' => 'Please select Matarial Number',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];


                    $validator = Validator::make($misc, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'miscellanous' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }

                    project_miscellanous_cost::create($misc);

                    break;

                default:
                    break;
            }
        } catch (Exception $e) {
            if ($flag != TRUE) {
                session()->flash('flash_message', $module . ' Error occured while inserting data ...');
                return response()->json(['status' => 'error', 'error' => $e->getMessage()]);
            }
        }
        session()->flash('flash_message', $module . ' cost added successfully...');
        return response()->json(['status' => 'ok']);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $materialCostData = project_material_cost::selectRaw('sum(unit_price * quantity) as total_price,currency')->groupBy('currency')->where('project_number', $id)->get()->toArray();
        $internalCostData = project_internal_cost::selectRaw('sum(total_price) as total_price,currency')->groupBy('currency')->where('project_number', $id)->get()->toArray();
        $externalCostData = project_external_cost::selectRaw('sum(total_price) as total_price,currency')->groupBy('currency')->where('project_number', $id)->get()->toArray();
        $serviceCostData = project_service_cost::selectRaw('sum(total_price) as total_price,currency')->groupBy('currency')->where('project_number', $id)->get()->toArray();
        $softwareCostData = project_software_cost::selectRaw('sum(total_price) as total_price,currency')->groupBy('currency')->where('project_number', $id)->get()->toArray();
        $hardwareCostData = project_hardware_cost::selectRaw('sum(total_price) as total_price,currency')->groupBy('currency')->where('project_number', $id)->get()->toArray();
        $facilitiesCostData = project_facilities_cost::selectRaw('sum(total_price) as total_price,currency')->groupBy('currency')->where('project_number', $id)->get()->toArray();
        $travelCostData = project_travel_cost::selectRaw('sum(total_price) as total_price,currency')->groupBy('currency')->where('project_number', $id)->get()->toArray();
        $miscellaenousCostData = project_miscellanous_cost::selectRaw('sum(total_price) as total_price,currency')->groupBy('currency')->where('project_number', $id)->get()->toArray();
        $contingencyCostData = project_contingency_cost::selectRaw('sum(total_price) as total_price,currency')->groupBy('currency')->where('project_number', $id)->get()->toArray();

        $Net_Total = (float) (isset($materialCostData[0]['total_price']) ? $materialCostData[0]['total_price'] : 0);
        $Net_Total += (isset($internalCostData[0]['total_price']) ? $internalCostData[0]['total_price'] : 0); //internal
        $Net_Total += (isset($externalCostData[0]['total_price']) ? $externalCostData[0]['total_price'] : 0); //external
        $Net_Total += (isset($serviceCostData[0]['total_price']) ? $serviceCostData[0]['total_price'] : 0);
        $Net_Total += (isset($softwareCostData[0]['total_price']) ? $softwareCostData[0]['total_price'] : 0);
        $Net_Total += (isset($hardwareCostData[0]['total_price']) ? $hardwareCostData[0]['total_price'] : 0);
        $Net_Total += (isset($facilitiesCostData[0]['total_price']) ? $facilitiesCostData[0]['total_price'] : 0);
        $Net_Total += (isset($travelCostData[0]['total_price']) ? $travelCostData[0]['total_price'] : 0);
        $Net_Total += (isset($miscellaenousCostData[0]['total_price']) ? $miscellaenousCostData[0]['total_price'] : 0);
        $Net_Total += (isset($contingencyCostData[0]['total_price']) ? $contingencyCostData[0]['total_price'] : 0);


        $result = [];
        $result['project_cost'] = [
            ['id' => 1, 'values' => ['code' => 'Z-101', 'type' => 'Material Cost', 'amount' => isset($materialCostData[0]['total_price']) ? $materialCostData[0]['total_price'] : '', 'currency' => isset($materialCostData[0]['currency']) ? $materialCostData[0]['currency'] : '']],
            ['id' => 2, 'values' => ['code' => 'Z-102', 'type' => 'Labour Internal', 'amount' => isset($internalCostData[0]['total_price']) ? $internalCostData[0]['total_price'] : '', 'currency' => isset($internalCostData[0]['currency']) ? $internalCostData[0]['currency'] : '']],
            ['id' => 3, 'values' => ['code' => 'Z-103', 'type' => 'Labour External', 'amount' => isset($externalCostData[0]['total_price']) ? $externalCostData[0]['total_price'] : '', 'currency' => isset($externalCostData[0]['currency']) ? $externalCostData[0]['currency'] : '']],
            ['id' => 4, 'values' => ['code' => 'Z-104', 'type' => 'Services (Contractor)', 'amount' => isset($serviceCostData[0]['total_price']) ? $serviceCostData[0]['total_price'] : '', 'currency' => isset($serviceCostData[0]['currency']) ? $serviceCostData[0]['currency'] : '']],
            ['id' => 5, 'values' => ['code' => 'Z-105', 'type' => 'Software', 'amount' => isset($softwareCostData[0]['total_price']) ? $softwareCostData[0]['total_price'] : '', 'currency' => isset($softwareCostData[0]['currency']) ? $softwareCostData[0]['currency'] : '']],
            ['id' => 6, 'values' => ['code' => 'Z-106', 'type' => 'Hardware', 'amount' => isset($hardwareCostData[0]['total_price']) ? $hardwareCostData[0]['total_price'] : '', 'currency' => isset($hardwareCostData[0]['currency']) ? $hardwareCostData[0]['currency'] : '']],
            ['id' => 7, 'values' => ['code' => 'Z-107', 'type' => 'Facilities', 'amount' => isset($facilitiesCostData[0]['total_price']) ? $facilitiesCostData[0]['total_price'] : '', 'currency' => isset($facilitiesCostData[0]['currency']) ? $facilitiesCostData[0]['currency'] : '']],
            ['id' => 8, 'values' => ['code' => 'Z-108', 'type' => 'Travel', 'amount' => isset($travelCostData[0]['total_price']) ? $travelCostData[0]['total_price'] : '', 'currency' => isset($travelCostData[0]['currency']) ? $travelCostData[0]['currency'] : '']],
            ['id' => 9, 'values' => ['code' => 'Z-109', 'type' => 'Miscellaenous', 'amount' => isset($miscellaenousCostData[0]['total_price']) ? $miscellaenousCostData[0]['total_price'] : '', 'currency' => isset($miscellaenousCostData[0]['currency']) ? $miscellaenousCostData[0]['currency'] : '']],
            ['id' => 10, 'values' => ['code' => 'Z-110', 'type' => 'Contingency', 'amount' => isset($contingencyCostData[0]['total_price']) ? $contingencyCostData[0]['total_price'] : '', 'currency' => isset($contingencyCostData[0]['currency']) ? $contingencyCostData[0]['currency'] : '']],
            ['id' => 11, 'values' => ['code' => 'Z-000', 'type' => 'Amount', 'amount' => $Net_Total, 'currency' => '']],
        ];

        return response()->json($result);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($module, $id)
    {
        
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update($module, $id)
    {
        Roleauth::check('project.costplan.edit');

        try {
            switch ($module) {
                case 'material':


                    $material_data = Input::except('_token');
                    $material = $material_data['data'];

                    $material['total_price'] = ((int) $material['quantity']) * ((float) $material['unit_price']);

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'material_number.required' => 'Please select Matarial Number',
                        'description.required' => 'Please enter Short Description',
                        'quantity.required' => 'Please select Quantity',
                        'unit_price.required' => 'Please select Unit Price',
                        'type' => 'Please select Type',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];


                    $validator = Validator::make($material, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'material_number' => 'required',
                                'description' => 'required|max:200',
                                'unit_price' => 'required',
                                'quantity' => 'required',
                                'type' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }

                    unset($material['id']);

                    project_material_cost::find($id)->update($material);

                    break;

                case 'internal':

                    $internal_data = Input::except('_token');
                    $internal = $internal_data['data'];
                    $total_hours = 0;
                    $filtred = array();

                    foreach ($internal as $key => $value) {
                        if (preg_match('/hours-/', $key)) {
                            $key = str_replace("hours-", "", $key);
                            $filtred[$key] = $value;
                            $total_hours += (int) $value;
                        }
                    }

                    $internal['total_price'] = ($total_hours) * ((float) $internal['unit_rate']);
                    $internal['no_hours'] = json_encode($filtred);

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'resource role.required' => 'Please select Matarial Number',
                        'resource_id.required' => 'Please enter Short Description',
                        'resource_name.required' => 'Please select Resopurce Name',
                        'band.required' => 'Please select Band',
                        'no_hours' => 'Please enter Hours',
                        'unit_rate' => 'Please enter Unit Rate',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];


                    $validator = Validator::make($internal, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'resource role' => 'required',
                                'resource_id' => 'required|max:200',
                                'resource_name' => 'required',
                                'band' => 'required',
                                'no_hours' => 'required',
                                'unit_rate' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }

                    project_internal_cost::find($id)->update($internal);

                    break;
                case 'external':
                    $external_data = Input::except('_token');
                    $external = $external_data['data'];
                    $filtred = array();
                    foreach ($external as $key => $value) {
                        if (preg_match('/noHour_*/', $key)) {
                            $key = explode(' ', $key)[0];
                            $key = str_replace("noHour_", "", $key);
                            $filtred[$key] = $value;
                        }
                    }
                    $external['no_hours'] = json_encode($filtred);
                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'resource role.required' => 'Please Add Resource Role',
                        'resource_id.required' => 'Please Add Resource Id',
                        'resource_name.required' => 'Please Add Resource Name',
                        'contract_vendor.required' => 'Please Add Contract Vendor',
                        'purchase_order.required' => 'Please Select Purchase Order',
                        'unit_rate.required' => 'Please select Unit Rate',
                        'currency.required' => 'Please select Currency'
//                        'total_price' => 'Please enter the Total Cost'
                    ];


                    $validator = Validator::make($external, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'resource role' => 'required',
                                'resource_id' => 'required',
                                'resource_name' => 'required',
                                'contract_vendor' => 'required',
                                'purchase_order' => 'required',
                                'currency' => 'required'
//                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }
                    project_external_cost::find($id)->update($external);

                    break;
                case 'software':
                    $software_data = Input::except('_token');
                    $software = $software_data['data'];

                    $purchase_order_item = purchase_item::where(['project_id' => $software['project_number'], 'requisition_number' => $software['purchase_order'], 'item_no' => $software['po_item_no']])->get()->toArray();
                    if (count($purchase_order_item)) {
                        $total_cost = $purchase_order_item[0]['item_cost'] * $purchase_order_item[0]['item_quantity'];
                        $quantity = $purchase_order_item[0]['item_quantity'];
                    } else {
                        session()->flash('flash_error', 'Requisition Number and item No do not match... Please select correct Correct Item number!');
                        return response()->json(['status' => 'error', 'error' => 'Requisition Number and item No do not match... Please select correct Correct Item number!']);
                    }

                    $software['total_price'] = isset($total_cost) ? $total_cost : '';
                    $software['quantity'] = isset($quantity) ? $quantity : '';

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'purchase_order.required' => 'Please select Requsiition Number',
                        'currency.required' => 'Please select Currency'
                    ];

                    $validator = Validator::make($software, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'purchase_order' => 'required',
                                'currency' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }
                    project_software_cost::find($id)->update($software);

                    break;

                case 'service':

                    $service_data = Input::except('_token');
                    $service = $service_data['data'];


                    $purchase_order_item = purchase_item::where(['project_id' => $service['project_number'], 'requisition_number' => $service['purchase_order'], 'item_no' => $service['po_item_no']])->get()->toArray();
                    if (count($purchase_order_item)) {
                        $total_cost = $purchase_order_item[0]['item_cost'] * $purchase_order_item[0]['item_quantity'];
                        $quantity = $purchase_order_item[0]['item_quantity'];
                    } else {
                        session()->flash('flash_error', 'Requisition Number and item No do not match... Please select correct Correct Item number!');
                        return response()->json(['status' => 'error', 'error' => 'Requisition Number and item No do not match... Please select correct Correct Item number!']);
                    }


                    $service['total_price'] = isset($total_cost) ? $total_cost : '';
                    $service['quantity'] = isset($quantity) ? $quantity : '';

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'purchase_order.required' => 'Please select Requisition Number',
                        'currency.required' => 'Please select Currency'
                    ];

                    $validator = Validator::make($service, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'purchase_order' => 'required',
                                'currency' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }
                    project_service_cost::find($id)->update($service);
                    break;

                case 'hardware':
                    $hardware_data = Input::except('_token');
                    $hardware = $hardware_data['data'];

                    $purchase_order_item = purchase_item::where(['project_id' => $hardware['project_number'], 'requisition_number' => $hardware['purchase_order'], 'item_no' => $hardware['po_item_no']])->get()->toArray();
                    if (count($purchase_order_item)) {
                        $total_cost = $purchase_order_item[0]['item_cost'] * $purchase_order_item[0]['item_quantity'];
                        $quantity = $purchase_order_item[0]['item_quantity'];
                    } else {
                        session()->flash('flash_error', 'Requisition Number and item No do not match... Please select correct Correct Item number!');
                        return response()->json(['status' => 'error', 'error' => 'Requisition Number and item No do not match... Please select correct Correct Item number!']);
                    }

                    $hardware['total_price'] = isset($total_cost) ? $total_cost : '';
                    $hardware['quantity'] = isset($quantity) ? $quantity : '';

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'purchase_order.required' => 'Please select Travel Request Number',
                        'currency.required' => 'Please select Currency'
                    ];

                    $validator = Validator::make($hardware, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'purchase_order' => 'required',
                                'currency' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }
                    project_hardware_cost::find($id)->update($hardware);

                    break;

                case 'travel':
                    $travel_data = Input::except('_token');
                    $travel = $travel_data['data'];

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'travel_request_number.required' => 'Please Enter Travel Request Number',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];


                    $validator = Validator::make($travel, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'travel_request_number' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }

                    unset($travel['id']);

                    project_travel_cost::find($id)->update($travel);


                    break;

                case 'contingency':
                    $contingency_data = Input::except('_token');
                    $contingency = $contingency_data['data'];

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'contingency.required' => 'Please Enter Contingency Number',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];


                    $validator = Validator::make($contingency, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'contingency' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }

                    unset($contingency['id']);

                    project_contingency_cost::find($id)->update($contingency);


                    break;
                case 'facilities':
                    $facilities_data = Input::except('_token');
                    $facilities = $facilities_data['data'];

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'facilities.required' => 'Please Enter Facilities Number',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];


                    $validator = Validator::make($facilities, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'facilities' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }

                    unset($facilities['id']);

                    project_facilities_cost::find($id)->update($facilities);


                    break;
                case 'misc':


                    $misc_data = Input::except('_token');
                    $misc = $misc_data['data'];

                    $validationmessages = [
                        'project_number.required' => 'Please Select Project Number',
                        'task.required' => 'Please Select Task Number',
                        'miscellanous.required' => 'Please Enter miscellanous details',
                        'currency.required' => 'Please select Currency',
                        'total_price' => 'Please enter the Total Cost'
                    ];


                    $validator = Validator::make($misc, [
                                'project_number' => 'required',
                                'task' => 'required',
                                'miscellanous' => 'required',
                                'currency' => 'required',
                                'total_price' => 'required'
                                    ], $validationmessages);

                    if ($validator->fails()) {
                        $msgs = $validator->messages();
                        return response()->json(['status' => 'error', 'error' => $msgs]);
                    }

                    unset($misc['id']);

                    project_miscellanous_cost::find($id)->update($misc);


                    break;

                default:
                    break;
            }
        } catch (Exception $e) {
            if ($flag != TRUE) {
                session()->flash('flash_message', $module . ' Error occured while inserting data ...');
                return response()->json(['status' => 'error', 'error' => $e->getMessage()]);
            }
        }
        session()->flash('flash_message', $module . ' cost added successfully...');
        return response()->json(['status' => 'ok']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($module, $id)
    {
        Roleauth::check('project.costplan.delete');

        try {
            switch ($module) {
                case 'material':
                    $projectcostplan = project_material_cost::find($id);
                    $projectcostplan->delete();

                    break;
                case 'internal':
                    $projectcostplan = project_internal_cost::find($id);
                    $projectcostplan->delete();

                    break;
                case 'external':
                    $projectcostplan = project_external_cost::find($id);
                    $projectcostplan->delete();

                    break;
                case 'software':
                    $projectcostplan = project_software_cost::find($id);
                    $projectcostplan->delete();

                    break;
                case 'service':
                    $projectcostplan = project_service_cost::find($id);
                    $projectcostplan->delete();

                    break;
                case 'hardware':
                    $projectcostplan = project_hardware_cost::find($id);
                    $projectcostplan->delete();

                    break;
                case 'travel':
                    $projectTravelCost = project_travel_cost::find($id);
                    $projectTravelCost->delete();

                    break;
                case 'contingency':
                    $projectContingencyCost = project_contingency_cost::find($id);
                    $projectContingencyCost->delete();

                    break;
                case 'facilities':
                    $projectFacilitiesCostPlan = project_facilities_cost::find($id);
                    $projectFacilitiesCostPlan->delete();

                    break;
                case 'misc':
                    $projectcostplan = project_miscellanous_cost::find($id);
                    $projectcostplan->delete();

                    break;

                default:
                    break;
            }
        } catch (Exception $e) {
            if ($flag != TRUE) {
                session()->flash('flash_message', $module . ' Error occured while deleting data ...');
                return response()->json(['status' => 'error', 'error' => $e->getMessage()]);
            }
        }


        session()->flash('flash_message', $module . 'cost deleted successfully...');
        return response()->json(['status' => $module . 'cost deleted successfully...']);
    }

    public function getModule($module, $id)
    {
        $projects = array();
        $project_data = Project::all();
        foreach ($project_data as $key => $project) {
            $projects[$project->project_Id] = $project->project_Id . ' ( ' . $project->project_name . ' )';
        }



        $currency = array();
        $currency_data = Currency::all();
        foreach ($currency_data as $key => $curr) {
            $currency[$curr->short_code] = $curr->short_code;
        }

        $tasks = array();
        $task_data = Projecttask::where('project_id', $id)->get();
        foreach ($task_data as $key => $task) {
            $tasks[$task->task_Id] = $task->task_Id . ' ( ' . $task->task_name . ' )';
        }

        $material = array();
        $material_data = materialmaster::all();
        foreach ($material_data as $key => $item) {
            $material[$item->material_number] = $item->material_number . ' ( ' . $item->material_name . ' ) ';
        }

        $activity = array();
        $activity_data = Activity_types::all();
        foreach ($activity_data as $key => $item) {
            $activity[$item->activity_type] = $item->activity_type;
        }

        $purchase_order = array();
        $purchase_item = array();
        $project_id = Project::where('project_Id', $id)->select('id')->first();
        $purchase_item_data = purchase_item::where('project_id', $project_id->id)
                ->groupby('requisition_number')
                ->select('requisition_number')
                ->get()
                ->toArray();
       
        foreach ($purchase_item_data as $key => $item) {
            if ($item['requisition_number'] != null || $item['requisition_number'] != "")
                $purchase_order[$item['requisition_number']] = $item['requisition_number'];
            
            foreach (\App\purchase_item::where('requisition_number', $item['requisition_number'])->select('item_no')->get()->toArray() as $key => $value) {
                $purchase_item[$item['requisition_number']][$value['item_no']] = $value['item_no'];
            }
        }
       
        switch ($module) {
            case 'material':

                $materialCostData = project_material_cost::where('project_number', $project_id->id)->get()->toArray();
                $result = [];
                foreach ($materialCostData as $key => $value) {
                    array_push($result, ['id' => $value['id'], 'values' => array_diff_key($value, array_flip(["id"]))]);
                }
                array_push($result, ['id' => 'total_cost', 'values' => ['unit_price' => 'Total Cost', 'total_price' => project_material_cost::where('project_number', $project_id->id)->sum('total_price'), 'action' => 'blank']]);
                return view('admin.projectcostplan.module', compact('purchase_item_data', 'purchase_order', 'purchase_item', 'id', 'result', 'module', 'projects', 'currency', 'tasks', 'material', 'purchase_order'));

                break;

            case 'internal':
                $internalCostData = project_internal_cost::where('project_number', $id)->get()->toArray();
                $project_id = Project::where('project_Id', $id)->select('id')->first();

                $roles = Createrole::where('project_id', $project_id->id)->where('company_id', Auth::user()->company_id)->pluck('role_name', 'id');
                $result = [];
                foreach ($internalCostData as $key => $value) {
                    $total_hours = 0;
                    $months = json_decode($value['no_hours'], true);
                    foreach ($months as $key => $val) {
                        $value['hours-' . $key] = $val;
                        $total_hours +=$val;
                    }
                    $value['total_hours'] = $total_hours;
                    array_push($result, ['id' => $value['id'], 'values' => array_diff_key($value, array_flip(["id"]))]);
                }
                array_push($result, ['id' => 'total_cost', 'values' => ['unit_rate' => 'Total Cost', 'total_price' => project_internal_cost::where('project_number', $id)->sum('total_price'), 'action' => 'blank']]);
                return view('admin.projectcostplan.module', compact('roles', 'activity', 'purchase_item_data', 'purchase_order', 'purchase_item', 'id', 'result', 'module', 'projects', 'currency', 'tasks', 'material', 'purchase_order'));


                break;
            case 'external':
                $externalCostData = project_external_cost::where('project_number', $id)->get()->toArray();
                $vendors = vendor::all()->pluck('name','id');
                $project_id = Project::where('project_Id', $id)->select('id')->first();

                $roles = Createrole::where('project_id', $project_id->id)->where('company_id', Auth::user()->company_id)->pluck('role_name', 'id');

                $result = [];
                $totalHours = 0;
                foreach ($externalCostData as $key => $value) {
                    $total = 0;
                    if (isset($value['no_hours']) && $value['no_hours'] !== '' && $value['no_hours'] !== null) {
                        $months = json_decode($value['no_hours'], true);
                        foreach ($months as $month => $val) {
                            $total += (int) $val;
                            $value['noHour_' . $month] = $val;
                        }
                    }
                    $value['noOfHour'] = (int) $total;
                    $totalHours += (int) $total;
                    $value['total_price'] = $total * (float) $value['unit_rate'];
                    array_push($result, ['id' => $value['id'], 'values' => array_diff_key($value, array_flip(["id"]))]);
                }
                array_push($result, ['id' => 'total_cost', 'values' => ['noOfHour' => $totalHours, 'unit_rate' => 'Total Cost', 'total_price' => project_external_cost::where('project_number', $id)->sum('total_price'), 'action' => 'blank']]);
                return view('admin.projectcostplan.module', compact('vendors','roles','purchase_item_data', 'purchase_order', 'purchase_item', 'id', 'result', 'module', 'projects', 'currency', 'tasks', 'material', 'purchase_order'));

                break;
            case 'software':
                $softwareCostData = project_software_cost::where('project_number', $id)->get()->toArray();
                $result = [];
                foreach ($softwareCostData as $key => $value) {
                    array_push($result, ['id' => $value['id'], 'values' => array_diff_key($value, array_flip(["id"]))]);
                }
                array_push($result, ['id' => 'total_cost', 'values' => ['po_item_no' => 'Total Cost', 'total_price' => project_software_cost::where('project_number', $id)->sum('total_price'), 'action' => 'blank']]);
                return view('admin.projectcostplan.module', compact('purchase_item_data', 'purchase_order', 'purchase_item', 'id', 'result', 'module', 'projects', 'currency', 'tasks', 'material', 'purchase_order'));


                break;
            case 'service':
                $serviceCostData = project_service_cost::where('project_number', $id)->get()->toArray();
                $result = [];
                foreach ($serviceCostData as $key => $value) {
                    array_push($result, ['id' => $value['id'], 'values' => array_diff_key($value, array_flip(["id"]))]);
                }
                array_push($result, ['id' => 'total_cost', 'values' => ['po_item_no' => 'Total Cost', 'total_price' => project_service_cost::where('project_number', $id)->sum('total_price'), 'action' => 'blank']]);
                return view('admin.projectcostplan.module', compact('purchase_item_data', 'purchase_order', 'purchase_item', 'id', 'result', 'module', 'projects', 'currency', 'tasks', 'material', 'purchase_order'));

                break;
            case 'hardware':
                $hardwareCostData = project_hardware_cost::where('project_number', $id)->get()->toArray();
                $result = [];
                foreach ($hardwareCostData as $key => $value) {
                    array_push($result, ['id' => $value['id'], 'values' => array_diff_key($value, array_flip(["id"]))]);
                }
                array_push($result, ['id' => 'total_cost', 'values' => ['po_item_no' => 'Total Cost', 'total_price' => project_hardware_cost::where('project_number', $id)->sum('total_price'), 'action' => 'blank']]);
                return view('admin.projectcostplan.module', compact('purchase_item_data', 'purchase_order', 'purchase_item', 'id', 'result', 'module', 'projects', 'currency', 'tasks', 'material', 'purchase_order'));

                break;
            case 'travel':
                $travelCostData = project_travel_cost::where('project_number', $id)->get()->toArray();
                $result = [];
                foreach ($travelCostData as $key => $value) {
                    array_push($result, ['id' => $value['id'], 'values' => array_diff_key($value, array_flip(["id"]))]);
                }
                array_push($result, ['id' => 'total_cost', 'values' => ['travel_request_number' => 'Total Cost', 'total_price' => project_travel_cost::where('project_number', $id)->sum('total_price'), 'action' => 'blank'], 'rowAttribute' => ['editable' => false]]);

                return view('admin.projectcostplan.module', compact('purchase_item_data', 'purchase_order', 'purchase_item', 'id', 'result', 'module', 'projects', 'currency', 'tasks', 'material'));

                break;
            case 'contingency':
                $contingencyCostData = project_contingency_cost::where('project_number', $id)->get()->toArray();
                $result = [];
                foreach ($contingencyCostData as $key => $value) {
                    array_push($result, ['id' => $value['id'], 'values' => array_diff_key($value, array_flip(["id"]))]);
                }
                array_push($result, ['id' => 'total_cost', 'values' => ['contingency' => 'Total Cost', 'total_price' => project_contingency_cost::where('project_number', $id)->sum('total_price'), 'action' => 'blank'], 'rowAttribute' => ['editable' => false]]);

                return view('admin.projectcostplan.module', compact('purchase_item_data', 'purchase_order', 'purchase_item', 'id', 'result', 'module', 'projects', 'currency', 'tasks', 'material'));

                break;
            case 'facilities':
                $facilitiesCostData = project_facilities_cost::where('project_number', $id)->get()->toArray();
                $result = [];
                foreach ($facilitiesCostData as $key => $value) {
                    array_push($result, ['id' => $value['id'], 'values' => array_diff_key($value, array_flip(["id"]))]);
                }
                array_push($result, ['id' => 'total_cost', 'values' => ['facilities' => 'Total Cost', 'total_price' => project_facilities_cost::where('project_number', $id)->sum('total_price'), 'action' => 'blank'], 'rowAttribute' => ['editable' => false]]);

                return view('admin.projectcostplan.module', compact('purchase_item_data', 'purchase_order', 'purchase_item', 'id', 'result', 'module', 'projects', 'currency', 'tasks', 'material'));

                break;
            case 'miscellanous':
                $miscCostData = project_miscellanous_cost::where('project_number', $id)->get()->toArray();
                $result = [];
                foreach ($miscCostData as $key => $value) {
                    array_push($result, ['id' => $value['id'], 'values' => array_diff_key($value, array_flip(["id"]))]);
                }
                array_push($result, ['id' => 'total_cost', 'values' => ['miscellanous' => 'Total Cost', 'total_price' => project_miscellanous_cost::where('project_number', $id)->sum('total_price'), 'action' => 'blank'], 'rowAttribute' => ['editable' => false]]);

                return view('admin.projectcostplan.module', compact('purchase_item_data', 'purchase_order', 'purchase_item', 'id', 'result', 'module', 'projects', 'currency', 'tasks', 'material', 'purchase_order'));

                break;

            default:
                break;
        }
    }

}
