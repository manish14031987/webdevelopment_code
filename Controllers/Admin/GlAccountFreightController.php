<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\glaccountfreight;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class GlAccountFreightController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        
        $gl = glaccountfreight::all();
        return view('admin.glfreight.view_gl', compact('gl'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {         
         
        return view('admin.glfreight.add_gl', compact('gl'));
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
            'glaccount_freight.required' => 'Please enter gl account freight',
            'glaccount_freight.unique' => 'Please enter unique gl account freight',
            
        ];

        $validator = Validator::make($data, [
                    'glaccount_freight' => 'required | unique:glaccount_freight',
                    
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/GlAccountFreight/create')->withErrors($validator)->withInput(Input::all());
        }
        
       
        $data1 = glaccountfreight::create($data);



        session()->flash('flash_message', 'gl account freight created successfully...');
        return redirect('admin/GlAccountFreight');
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
        
        $gl = glaccountfreight::find($id);    
      
        
        
        return view('admin.glfreight.add_gl', compact('gl'));
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
        $data = glaccountfreight::find($id);
        
        $dataInputs = Input::all();
        
         $validationmessages = [
           'glaccount_freight.required' => 'Please enter gl account freight',
           
        ];

        $validator = Validator::make($dataInputs, [
                   
                   'glaccount_freight' => 'required',
                    
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/GlAccountFreight/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $data->update($dataInputs);
        session()->flash('flash_message', 'gl account freight updated successfully...');
        return redirect('admin/GlAccountFreight');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
        $gl_id = glaccountfreight::find($id);
        $gl_id->delete($id);
        session()->flash('flash_message', 'gl account freight deleted successfully...');
        return redirect('admin/GlAccountFreight');
    }

}
