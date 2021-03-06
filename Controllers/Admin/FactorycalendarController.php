<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Factorycalendar;


class FactorycalendarController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $factorycalendar = factorycalendar::all();
        return view('admin.factorycalendar.index', compact('factorycalendar'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        return view('admin.factorycalendar.create');
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
            'name' => 'required',
            'description' => 'required',
            'status' => 'required',
        ]);

        $create = $request->all();
        $create['company_id'] = Auth::user()->company_id;
        Factorycalendar::create($create);
        
        session()->flash('flash_message', 'Factory calendar created successfully...');
        return redirect('admin/factorycalendar');
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
        $factorycalendar = Factorycalendar::find($id);
        return view('admin.factorycalendar.create', compact('factorycalendar'));
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
        $factorycalendar = Factorycalendar::find($id);
        $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => 'required',
        ]); 
        $factorycalendar->update($request->all());
        session()->flash('flash_message', 'Factory calendar updated successfully...');
        return redirect('admin/factorycalendar');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $factorycalendar = Factorycalendar::find($id);
        $factorycalendar->delete();
        session()->flash('flash_message', 'Factory calendar deleted successfully...');
        return redirect('admin/factorycalendar');
    }
}
