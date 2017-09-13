<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Viewtype;


class ViewtypeController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $viewtype = Viewtype::all();
        return view('admin.viewtype.index', compact('viewtype'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        return view('admin.viewtype.create');
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
        viewtype::create($request->all());
        
        session()->flash('flash_message', 'View type created successfully...');
        return redirect('admin/viewtype');
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
        $viewtype = viewtype::find($id);
        return view('admin.viewtype.create', compact('viewtype'));
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
        $viewtype = viewtype::find($id);
        $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => 'required',
        ]); 
        $viewtype->update($request->all());
        session()->flash('flash_message', 'View type updated successfully...');
        return redirect('admin/viewtype');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $viewtype = viewtype::find($id);
        $viewtype->delete();
        session()->flash('flash_message', 'View type deleted successfully...');
        return redirect('admin/viewtype');
    }
}
