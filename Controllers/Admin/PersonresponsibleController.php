<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Personresponsible;


class PersonresponsibleController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $personresponsible = Personresponsible::all();
        return view('admin.personresponsible.index', compact('personresponsible'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        return view('admin.personresponsible.create');
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

        $create['company_id'] = Auth::user()->company_id;
        Personresponsible::create($create);
        
        session()->flash('flash_message', 'Person Responsible created successfully...');
        return redirect('admin/personresponsible');
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
        $personresponsible = Personresponsible::find($id);
        return view('admin.personresponsible.create', compact('personresponsible'));
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
        $personresponsible = Personresponsible::find($id);
        $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => 'required',
        ]); 
        $personresponsible->update($request->all());
        session()->flash('flash_message', 'Person Responsible updated successfully...');
        return redirect('admin/personresponsible');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $personresponsible = Personresponsible::find($id);
        $personresponsible->delete();
        session()->flash('flash_message', 'Person Responsible deleted successfully...');
        return redirect('admin/personresponsible');
    }
}
