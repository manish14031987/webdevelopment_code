<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Collectiontype;


class CollectiontypeController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $collectiontype = Collectiontype::all();
        return view('admin.collectiontype.index', compact('collectiontype'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        return view('admin.collectiontype.create');
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
        Collectiontype::create($request->all());
        
        session()->flash('flash_message', 'Collection type created successfully...');
        return redirect('admin/collectiontype');
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
        $collectiontype = Collectiontype::find($id);
        return view('admin.collectiontype.create', compact('collectiontype'));
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
        $collectiontype = Collectiontype::find($id);
        $this->validate($request, [
            'name' => 'required',
            'description' => 'required',
            'status' => 'required',
        ]); 
        $collectiontype->update($request->all());
        session()->flash('flash_message', 'Collection type updated successfully...');
        return redirect('admin/collectiontype');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $collectiontype = Collectiontype::find($id);
        $collectiontype->delete();
        session()->flash('flash_message', 'Collection type deleted successfully...');
        return redirect('admin/collectiontype');
    }
}
