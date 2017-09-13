<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\gl;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class GlController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $gl = gl::all();
        return view('admin.gl.view_gl', compact('gl'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
          $rand_number = substr(md5(microtime()), rand(0, 26), 6);
         
        return view('admin.gl.add_gl', compact('rand_number'));
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
            'gl_account_description.required' => 'Please enter gl account description',
            'cost_element_type.required' => 'Please enter cost element type',
            'balance.required' => 'Please enter balance',
            'year.required' => 'Please enter year',
          
        ];

        $validator = Validator::make($data, [
                    'gl_account_description' => 'required',
                    'cost_element_type' => 'required',
                    'balance' => 'required',
                    'year' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/GlAccount/create')->withErrors($validator)->withInput(Input::all());
        }
        
       
        $data1 = gl::create($data);



        session()->flash('flash_message', 'Gl Account created successfully...');
        return redirect('admin/GlAccount');
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
        
        $gl = gl::find($id);    
      
        
        return view('admin.gl.add_gl', compact('gl'));
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
        $data = gl::find($id);
        
        $dataInputs = Input::all();
        
         $validationmessages = [
           'gl_account_description.required' => 'Please enter gl account description',
            'cost_element_type.required' => 'Please enter cost element type',
            'balance.required' => 'Please enter balance',
            'year.required' => 'Please enter year',
          
        ];

        $validator = Validator::make($dataInputs, [
                   
                   'gl_account_description' => 'required',
                    'cost_element_type' => 'required',
                    'balance' => 'required',
                    'year' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/GlAccount/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $data->update($dataInputs);
        session()->flash('flash_message', 'Gl Account updated successfully...');
        return redirect('admin/GlAccount');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
        $gl_id = gl::find($id);
        $gl_id->delete($id);
        session()->flash('flash_message', 'Gl Account deleted successfully...');
        return redirect('admin/GlAccount');
    }

}
