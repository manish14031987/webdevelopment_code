<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\servicemaster;
use App\Currency;
use App\unitofmeasure;
use App\orderingunit;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {

        $service_master = DB::table('service_master')
                ->select('service_master.*', 'vendor.name')
                ->leftJoin('vendor', 'service_master.supplier', '=', 'vendor.vendor_id')
                ->get();

        return view('admin.servicemaster.index', compact('service_master'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {


        //get unit of measure
        $unit_of_measure = unitofmeasure::all();

        foreach ($unit_of_measure as $unitofmeasure) {
            $unitmeasure[$unitofmeasure->unitofmeasure] = $unitofmeasure->unitofmeasure;
        }

        //get ordering unit
        $ordering_unit = orderingunit::all();
        foreach ($ordering_unit as $orderingunit) {
            $orderingut[$orderingunit->orderingunit] = $orderingunit->orderingunit;
        }

        //get currency
        $currency_data = currency::all();

        foreach ($currency_data as $currencydata) {
            $currency[$currencydata->short_code] = $currencydata->short_code;
        }

        //get vendorid and name
        $vendor_data = DB::table("vendor")
                ->select('vendor_id', 'name')
                ->get();

        $vid = '';
        foreach ($vendor_data as $key => $vendordata) {
            $vid[$vendordata->vendor_id] = $vendordata->name;
        }

        return view('admin.servicemaster.create', compact('unitmeasure', 'orderingut', 'currency', 'vid'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {

        $service_master = Input::all();


        $validationmessages = [
            'service_name.required' => "Please enter service name",
            'service_category.required' => "Please select service category",
            'service_group.required' => "Please select service group ",
            'unit_of_measure.required' => "Please select unit of measure",
            'ordering_unit.required' => "Please select ordering unit ",
            'currency.required' => "Please select currency",
        ];

        $validator = Validator::make($service_master, [
                    'service_name' => "required",
                    'service_category' => "required",
                    'service_group' => "required",
                    'unit_of_measure' => "required",
                    'ordering_unit' => "required",
                    'currency' => "required",
                    'service_description' => "required|max:240",
                    'short_text' => "required|max:24"
                        ], $validationmessages);


        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/service_master/create')->withErrors($validator)->withInput(Input::all());
        }

        servicemaster::create($service_master);

        session()->flash('flash_message', 'ServiceMaster created successfully...');
        return redirect('admin/service_master');
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
        //

        $service_data = DB::table('service_master')->where('service_id', '=', $id)->get();

        foreach ($service_data as $key => $value) {

            $service_master = $value;
        }

        //get unit of measure
        $unit_of_measure = unitofmeasure::all();

        foreach ($unit_of_measure as $unitofmeasure) {
            $unitmeasure[$unitofmeasure->unitofmeasure] = $unitofmeasure->unitofmeasure;
        }

        //get ordering unit
        $ordering_unit = orderingunit::all();
        foreach ($ordering_unit as $orderingunit) {
            $orderingut[$orderingunit->orderingunit] = $orderingunit->orderingunit;
        }

        //get currency
        $currency_data = currency::all();

        foreach ($currency_data as $currencydata) {
            $currency[$currencydata->short_code] = $currencydata->short_code;
        }

        //get vendorid and name
        $vendor_data = DB::table("vendor")
                ->select('vendor_id', 'name')
                ->get();

        $vid = '';
        foreach ($vendor_data as $key => $vendordata) {
            $vid[$vendordata->vendor_id] = $vendordata->name;
        }

        return view('admin.servicemaster.create', compact('service_master', 'unitmeasure', 'orderingut', 'currency', 'vid'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //
        $servicedata = DB::table('service_master')->where('service_id', '=', $id)->get();

        foreach ($servicedata as $key => $value) {

            $service_id = $value->service_id;
        }

        $serviceInputs = Input::except('_method', '_token');


        $validationmessages = [
            'service_name.required' => "Please enter service name",
            'service_category.required' => "Please select service category",
            'service_group.required' => "Please select service group ",
            'unit_of_measure.required' => "Please select unit of measure",
            'ordering_unit.required' => "Please select ordering unit ",
            'currency.required' => "Please select currency",
        ];

        $validator = Validator::make($serviceInputs, [
                    'service_name' => "required",
                    'service_category' => "required",
                    'service_group' => "required",
                    'unit_of_measure' => "required",
                    'ordering_unit' => "required",
                    'currency' => "required",
                    'service_description' => "required|max:240",
                    'short_text' => "required|max:24"
                        ], $validationmessages);


        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/service_master/' . $service_id . '/edit')->withErrors($validator)->withInput(Input::all());
        }

        DB::table('service_master')
                ->where('service_id', $service_id)
                ->update($serviceInputs);

        session()->flash('flash_message', 'ServiceMaster updated successfully...');
        return redirect('admin/service_master');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        DB::table('service_master')->where('service_id', '=', $id)->delete();
        session()->flash('flash_message', 'ServiceMaster deleted successfully...');
        return redirect('admin/service_master');
    }

}
