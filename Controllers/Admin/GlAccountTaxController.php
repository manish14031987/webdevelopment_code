<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\glaccounttax;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class GlAccountTaxController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        
        $gl = glaccounttax::all();
        return view('admin.gltax.view_gl', compact('gl'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {         
         
        return view('admin.gltax.add_gl', compact('gl'));
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
            'glaccount_tax.required' => 'Please enter gl account tax',
            
        ];

        $validator = Validator::make($data, [
                    'glaccount_tax' => 'required | unique:glaccount_tax',
                    
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/GlAccountTax/create')->withErrors($validator)->withInput(Input::all());
        }
        
       
        $data1 = glaccounttax::create($data);



        session()->flash('flash_message', 'gl account tax created successfully...');
        return redirect('admin/GlAccountTax');
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
        
        $gl = glaccounttax::find($id);    
      
        
        
        return view('admin.gltax.add_gl', compact('gl'));
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
        $data = glaccounttax::find($id);
        
        $dataInputs = Input::all();
        
         $validationmessages = [
           'glaccount_tax.required' => 'Please enter gl account tax',
           
        ];

        $validator = Validator::make($dataInputs, [
                   
                   'glaccount_tax' => 'required',
                    
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/GlAccountTax/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $data->update($dataInputs);
        session()->flash('flash_message', 'gl account tax updated successfully...');
        return redirect('admin/GlAccountTax');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
        $gl_id = glaccounttax::find($id);
        $gl_id->delete($id);
        session()->flash('flash_message', 'gl account tax deleted successfully...');
        return redirect('admin/GlAccountTax');
    }

}
