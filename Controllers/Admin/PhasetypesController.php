<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\phasetype;

class PhasetypesController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $phasetype = Phasetype::all();
        return view('admin.phasetype.phasetype', compact('phasetype'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.phasetype.addphasetype');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:phase_type',
        ]);
        
        Phasetype::create($request->all());
        session()->flash('flash_message', 'Phase type created successfully...');
        return redirect('admin/phasetype');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $phasetype = Phasetype::find($id);
        return view('admin.phasetype.addphasetype', compact('phasetype'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request            
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $phasetype = Phasetype::find($id);
        $this->validate($request, [
            'name' => 'required',
            /* 'description' => 'required' */
        ]);
        $phasetype->update($request->all());
        session()->flash('flash_message', 'Phase type updated successfully...');
        return redirect('admin/phasetype');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $phasetype = Phasetype::find($id);
        $phasetype->delete();
        session()->flash('flash_message', 'Phase type deleted successfully...');
        return redirect('admin/phasetype');
    }
}
