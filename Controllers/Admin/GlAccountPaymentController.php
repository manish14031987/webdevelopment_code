<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\glaccountpayment;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class GlAccountPaymentController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        
        $gl = glaccountpayment::all();
        return view('admin.glpayment.view_gl', compact('gl'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {         
         
        return view('admin.glpayment.add_gl', compact('gl'));
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
            'glaccount_payment.required' => 'Please enter gl account down payment',
            
        ];

        $validator = Validator::make($data, [
                    'glaccount_payment' => 'required | unique:glaccount_payment',
                    
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/GlAccountDownPayment/create')->withErrors($validator)->withInput(Input::all());
        }
        
       
        $data1 = glaccountpayment::create($data);



        session()->flash('flash_message', 'gl account Down payment created successfully...');
        return redirect('admin/GlAccountDownPayment');
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
        
        $gl = glaccountpayment::find($id);    
      
        
        
        return view('admin.glpayment.add_gl', compact('gl'));
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
        $data = glaccountpayment::find($id);
        
        $dataInputs = Input::all();
        
         $validationmessages = [
           'glaccount_payment.required' => 'Please enter gl account down payment',
           
        ];

        $validator = Validator::make($dataInputs, [
                   
                   'glaccount_payment' => 'required',
                    
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/GlAccountDownPayment/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $data->update($dataInputs);
        session()->flash('flash_message', 'gl account down payment updated successfully...');
        return redirect('admin/GlAccountDownPayment');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
        $gl_id = glaccountpayment::find($id);
        $gl_id->delete($id);
        session()->flash('flash_message', 'gl account down payment deleted successfully...');
        return redirect('admin/GlAccountDownPayment');
    }

}
