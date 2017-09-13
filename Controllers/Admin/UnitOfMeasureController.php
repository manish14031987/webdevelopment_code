<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\unitofmeasure;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class UnitOfMeasureController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $unitofmeasure = unitofmeasure::all();
        return view('admin.unit_of_measure.view_unit_of_measure', compact('unitofmeasure'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
        return view('admin.unit_of_measure.add_unit_of_measure');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //
        $data = Input::all();
        
        $validationmessages = [
            'unitofmeasure.required' => 'Please enter UnitOfMeasure',
          
        ];

        $validator = Validator::make($data, [
                    'unitofmeasure' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addUnitOfMeasure/create')->withErrors($validator)->withInput(Input::all());
        }
        

        $data1 = unitofmeasure::create($data);



        session()->flash('flash_message', 'UnitOfMeasure  created successfully...');
        return redirect('admin/addUnitOfMeasure');
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

        $unitofmeasure = unitofmeasure::find($id);
        return view('admin.unit_of_measure.add_unit_of_measure', compact('unitofmeasure'));
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
        $data = unitofmeasure::find($id);

        $dataInputs = Input::all();
        
           $validationmessages = [
            'unitofmeasure.required' => 'Please enter UnitOfMeasure',
          
        ];

        $validator = Validator::make($dataInputs, [
                    'unitofmeasure' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addUnitOfMeasure/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $data->update($dataInputs);
        session()->flash('flash_message', 'UnitOfMeasure updated successfully...');
        return redirect('admin/addUnitOfMeasure');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
        $material_id = unitofmeasure::find($id);
        $material_id->delete($id);
        session()->flash('flash_message', 'UnitOfMeasure deleted successfully...');
        return redirect('admin/addUnitOfMeasure');
    }

}
