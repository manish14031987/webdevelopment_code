<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\salesregion;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class SalesRegionController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $salesregion = salesregion::all();
        return view('admin.salesregion.view_sales_region', compact('salesregion'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
        return view('admin.salesregion.add_sales_region');
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
            'sales_region.required' => 'Please enter sales region',
          
        ];

        $validator = Validator::make($data, [
                    'sales_region' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/salesregion/create')->withErrors($validator)->withInput(Input::all());
        }
        
       
        $data1 = salesregion::create($data);



        session()->flash('flash_message', 'sales region created successfully...');
        return redirect('admin/salesregion');
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
            
        $salesregion = salesregion::find($id);    
        
        return view('admin.salesregion.add_sales_region', compact('salesregion'));
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
        $data = salesregion::find($id);
        
        $dataInputs = Input::all();
        
         $validationmessages = [
            'sales_region.required' => 'Please enter sales region',
          
        ];

        $validator = Validator::make($dataInputs, [
                    'sales_region' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/salesregion/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $data->update($dataInputs);
        session()->flash('flash_message', 'sales region updated successfully...');
        return redirect('admin/salesregion');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
        $salesregion_id = salesregion::find($id);
        $salesregion_id->delete($id);
        session()->flash('flash_message', 'sales region deleted successfully...');
        return redirect('admin/salesregion');
    }

}
