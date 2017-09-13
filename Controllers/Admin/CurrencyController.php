<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use App\Currency;
use Illuminate\Support\Facades\Validator;

class CurrencyController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $currency = Currency::all();
        return view('admin.currency.index', compact('currency'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {

        return view('admin.currency.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        
        $data = Input::all();
        $validationmessages = [
            'short_code' => 'Please enter currency',
            'fullname' => 'Please enter currency description',
        ];

        $validator = Validator::make($data, [
                    'short_code' => 'required',
                    'fullname' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/currencies/create')->withErrors($validator)->withInput(Input::all());
        }
        Currency::create([
            'short_code' => $request->input('short_code'),
            'fullname' => $request->input('fullname'),  
            'status' => $request->input('status'),
            'company_id' => Auth::user()->company_id
        ]);

        session()->flash('flash_message', 'Currency created successfully...');
        return redirect('admin/currencies');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $currency = Currency::find($id);
        return view('admin.currency.create', compact('currency'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $currency = Currency::find($id);

         $dataInputs = Input::all();
        $validationmessages = [
            'short_code' => 'Please enter currency',
            'fullname' => 'Please enter currency description',
        ];

        $validator = Validator::make($dataInputs, [
                    'short_code' => 'required',
                    'fullname' => 'required',
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/currencies/' . $id . '/edit')->withErrors($validator)->withInput(Input::all());
        }
        $currency->update($request->all());
        session()->flash('flash_message', 'Currency updated successfully...');
        return redirect('admin/currencies');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $currency = Currency::find($id);
        $currency->delete();
        session()->flash('flash_message', 'Currency deleted successfully...');
        return redirect('admin/currencies');
    }

}
