<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\inquiry_type;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class InquiryTypeController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $inquirytype = inquiry_type::all();
        return view('admin.inquiry_type.view_inquiry_type', compact('inquirytype'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
        return view('admin.inquiry_type.add_inquiry_type');
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
            'inquiry_type.required' => 'Please enter inquiry type',
          
        ];

        $validator = Validator::make($data, [
                    'inquiry_type' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/inquiry_type/create')->withErrors($validator)->withInput(Input::all());
        }
        
       
        $data1 = inquiry_type::create($data);



        session()->flash('flash_message', 'inquiry type created successfully...');
        return redirect('admin/inquiry_type');
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
            
        $inquirytype = inquiry_type::find($id);    
        
        return view('admin.inquiry_type.add_inquiry_type', compact('inquirytype'));
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
        $data = inquiry_type::find($id);
        
        $dataInputs = Input::all();
        
         $validationmessages = [
            'inquiry_type.required' => 'Please enter ineuiry type',
          
        ];

        $validator = Validator::make($dataInputs, [
                    'inquiry_type' => 'required',
                   
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/inquiry_type/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $data->update($dataInputs);
        session()->flash('flash_message', 'inquiry type updated successfully...');
        return redirect('admin/inquiry_type');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
        $inquiry_id = inquiry_type::find($id);
        $inquiry_id->delete($id);
        session()->flash('flash_message', 'inquiry type deleted successfully...');
        return redirect('admin/inquiry_type');
    }

}
