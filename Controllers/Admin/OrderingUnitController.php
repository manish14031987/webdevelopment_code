<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\orderingunit;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class OrderingUnitController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $orderingunit = orderingunit::all();
        return view('admin.ordering_unit.view_ordering_unit', compact('orderingunit'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
        return view('admin.ordering_unit.add_ordering_unit');
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
            'orderingunit.required' => 'Please enter OrderingUnit',
          
        ];

        $validator = Validator::make($data, [
                    'orderingunit' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addOrderingUnit/create')->withErrors($validator)->withInput(Input::all());
        }
        

        $data1 = orderingunit::create($data);



        session()->flash('flash_message', 'Ordering Unit  created successfully...');
        return redirect('admin/addOrderingUnit');
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

        $orderingunit = orderingunit::find($id);
        return view('admin.ordering_unit.add_ordering_unit', compact('orderingunit'));
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
        $data = orderingunit::find($id);

        $dataInputs = Input::all();
        
         $validationmessages = [
            'orderingunit.required' => 'Please enter OrderingUnit',
          
        ];

        $validator = Validator::make($dataInputs, [
                    'orderingunit' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addOrderingUnit/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $data->update($dataInputs);
        session()->flash('flash_message', 'Ordering Unit updated successfully...');
        return redirect('admin/addOrderingUnit');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
        $orderingunit_id = orderingunit::find($id);
        $orderingunit_id->delete($id);
        session()->flash('flash_message', 'Ordering Unit deleted successfully...');
        return redirect('admin/addOrderingUnit');
    }

}
