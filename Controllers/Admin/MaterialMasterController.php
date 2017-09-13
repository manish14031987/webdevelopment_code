<?php

namespace Illuminate\Database\Eloquent;

//

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\materialgroup;
use App\unitofmeasure;
use App\orderingunit;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use App\materialcategory;
use Illuminate\Support\Facades\DB;
use App\materialmaster;
use App\vendor;
use App\Currency;

class MaterialMasterController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {

//        $material_master = materialmaster::all();
        $material_master = DB::table('material_master')
                ->select('material_master.*', 'vendor.name')
                ->leftJoin('vendor', 'material_master.supplier_name', '=', 'vendor.vendor_id')
                ->get();
        return view('admin.materialmaster.index', compact('material_master'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {

        $material_master = materialmaster::all();

        //get material group
        $material_group = materialgroup::all();
        $materialgrp = array();
        foreach ($material_group as $group) {
            $materialgrp[$group->materialgroup] = $group->materialgroup;
        }

        //get unit of measure
        $unit_of_measure = unitofmeasure::all();
        $unitmeasure = array();
        foreach ($unit_of_measure as $unitofmeasure) {
            $unitmeasure[$unitofmeasure->unitofmeasure] = $unitofmeasure->unitofmeasure;
        }

        //get ordering unit
        $ordering_unit = orderingunit::all();
        $orderingut = array();
        foreach ($ordering_unit as $orderingunit) {
            $orderingut[$orderingunit->orderingunit] = $orderingunit->orderingunit;
        }

        //get material category
        $material_category = materialcategory::all();
        $materialcat = array();
        foreach ($material_category as $material) {
            $materialcat[$material->materialcategory] = $material->materialcategory;
        }


        $temp = Currency::all();
        $currency = array();
        foreach ($temp as $key => $value) {
            $currency[$value->id] = $value->short_code;
        }


        //get vendor name and id
        $vendor_data = DB::table("vendor")
                ->select('vendor_id', 'name')
                ->get();

        $vid = array();
        foreach ($vendor_data as $key => $vendordata) {
            $vid[$vendordata->vendor_id] = $vendordata->name;
        }

        return view('admin.materialmaster.create', compact('currency','material_master', 'materialcat', 'materialgrp', 'unitmeasure', 'orderingut', 'vid'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {


        $material_master = Input::all();

        $validationmessages = [
            'material_name.required' => "Please enter material name",
            'material_description.required' => "Please enter material description",
            'material_category.required' => "Please select material category",
            'material_group.required' => "Please select material group",
            'supplier_name.required' => "Please select supplier",
            'unit_of_measure.required' => "Please select Unit of measure",
            'ordering_unit.required' => "Please select Orderning unit",
        ];

        $validator = Validator::make($material_master, [
                    'material_name' => "required",
                    'material_description' => "required",
                    'material_category' => "required",
                    'material_group' => "required",
                    'supplier_name' => "required",
                    'unit_of_measure' => "required",
                    'ordering_unit' => "required",
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/material_master/create')->withErrors($validator)->withInput(Input::all());
        }

        materialmaster::create($material_master);


        session()->flash('flash_message', 'MaterialMaster created successfully...');
        return redirect('admin/material_master');
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
    //
    public function edit($id) {


        $material_data = DB::table('material_master')->where('material_number', '=', $id)->get();


        foreach ($material_data as $key => $value) {

            $material_master = $value;
        }

        //get material group
        $material_group = materialgroup::all();
        foreach ($material_group as $group) {
            $materialgrp[$group->materialgroup] = $group->materialgroup;
        }

        
        $temp = Currency::all();
        foreach ($temp as $key => $value) {
            $currency[$value->id] = $value->short_code;
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

        //get material category
        $material_category = materialcategory::all();

        foreach ($material_category as $material) {
            $materialcat[$material->materialcategory] = $material->materialcategory;
        }

        //get vendor name and id
        $vendor_data = DB::table("vendor")
                ->select('vendor_id', 'name')
                ->get();

        $vid = '';
        foreach ($vendor_data as $key => $vendordata) {
            $vid[$vendordata->vendor_id] = $vendordata->name;
        }

        return view('admin.materialmaster.create', compact('currency','material_master', 'materialcat', 'materialgrp', 'unitmeasure', 'orderingut', 'vid'));
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

        $masterdata = DB::table('material_master')->where('material_number', '=', $id)->get();

        foreach ($masterdata as $key => $value) {

            $material_id = $value->material_number;
        }

        $masterInputs = Input::except('_method', '_token');

        $validationmessages = [
            'material_name.required' => "Please enter material name",
            'material_description.required' => "Please enter material description",
            'material_category.required' => "Please select material category",
            'material_group.required' => "Please select material group",
            'supplier_name.required' => "Please select supplier",
            'unit_of_measure.required' => "Please select Unit of measure",
            'ordering_unit.required' => "Please select Orderning unit",
        ];

        $validator = Validator::make($masterInputs, [
                    'material_name' => "required",
                    'material_description' => "required",
                    'material_category' => "required",
                    'material_group' => "required",
                    'supplier_name' => "required",
                    'unit_of_measure' => "required",
                    'ordering_unit' => "required",
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/material_master/' . $material_id . '/edit')->withErrors($validator)->withInput(Input::all());
        }
        DB::table('material_master')
                ->where('material_number', $material_id)
                ->update($masterInputs);

        session()->flash('flash_message', 'MaterialMaster updated successfully...');
        return redirect('admin/material_master');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {

        DB::table('material_master')->where('material_number', '=', $id)->delete();
        session()->flash('flash_message', 'MaterialMaster deleted successfully...');
        return redirect('admin/material_master');
    }

}
