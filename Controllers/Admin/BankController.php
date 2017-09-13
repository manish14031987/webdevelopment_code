<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\bank;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class BankController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         $bankname = bank::all();
         return view('admin.bank.view_bank',compact('bankname'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
         return view('admin.bank.add_bank');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $bank_data = Input::all();
        
         $validationmessages = [
            'bank_name.required' => 'Please enter Bank name',
           
            
        ];

        $validator = Validator::make($bank_data, [
                    'bank_name' => 'required',
                   ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addBank/create')->withErrors($validator)->withInput(Input::all());
        }
        
        bank::create($bank_data);


        session()->flash('flash_message', 'BankName created successfully...');
        return redirect('admin/addBank');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
         $bankname = bank::find($id);
         return view('admin.bank.add_bank',compact('bankname'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
         $bank = bank::find($id);
        $bankInputs = Input::all();
        
         $validationmessages = [
            'bank_name.required' => 'Please enter Bank name',
           
            
        ];

        $validator = Validator::make($bankInputs, [
                    'bank_name' => 'required',
                   ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/addBank/'.$id.'/edit')->withErrors($validator)->withInput(Input::all());
        }
        
        $bank->update($bankInputs);
        session()->flash('flash_message', 'BankName updated successfully...');
        return redirect('admin/addBank');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $bank_id = bank::find($id);
        $bank_id->delete($id);
        session()->flash('flash_message', 'BankName deleted successfully...');
        return redirect('admin/addBank');
    }
    
}
