<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\location;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;

class LocationController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $location = location::all();
        return view('admin.location.index', compact('location'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        return view('admin.location.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $create = $request->all();
        
        $validationmessages = [
            'subrub.required' => 'Please enter city',
            'state.required' => 'Please enter state',
            'postcode.required' => 'Please enter postcode',
            'latitude.required' => 'Please enter latitude',
            'longitude.required' => 'Please enter longitude',
        ];

        $validator = Validator::make($create, [
                    'subrub' => 'required',
                    'state' => 'required',
                    'postcode' => 'required',
                    'latitude' => 'required',
                    'longitude' => 'required'
                        ], $validationmessages);

        if ($validator->fails()) {
            $msgs = $validator->messages();
            return redirect('admin/location/create')->withErrors($validator)->withInput(Input::all());
        }


        $create['company_id'] = Auth::user()->company_id;
        location::create($create);
        session()->flash('flash_message', 'location created successfully...');
        return redirect('admin/location');
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
        $location = location::find($id);
        return view('admin.location.create', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        $location = location::find($id);
        $this->validate($request, [
            'subrub' => 'required',
                /* 'description' => 'required' */
        ]);
        $location->update($request->all());
        session()->flash('flash_message', 'location updated successfully...');
        return redirect('admin/location');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $location = location::find($id);
        $location->delete();
        session()->flash('flash_message', 'location deleted successfully...');
        return redirect('admin/location');
    }

}
