<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Portfoliotype;

class PortfolioTypesController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $portfoliotype = Portfoliotype::all();
        return view('admin.portftype.portfoliotype', compact('portfoliotype'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.portftype.addportfoliotype');
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
            'name' => 'required|unique:portfolio_type',
        ]);
         Portfoliotype::create([
            'name' => $request->input('name'),             
            'status' => $request->input('status'),
            'company_id' => Auth::user()->company_id
        ]);
        session()->flash('flash_message', 'Portfolio type created successfully...');
        return redirect('admin/portfoliotypes');
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
        $portfoliotype = Portfoliotype::find($id);
        return view('admin.portftype.addportfoliotype', compact('portfoliotype'));
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
        $portfoliotype = Portfoliotype::find($id);
        $this->validate($request, [
            'name' => 'required',
            /* 'description' => 'required' */
        ]);
        $portfoliotype->update($request->all());
        session()->flash('flash_message', 'Portfolio type updated successfully...');
        return redirect('admin/portfoliotypes');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id            
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $portfoliotype = Portfoliotype::find($id);
        $portfoliotype->delete();
        session()->flash('flash_message', 'Portfolio type deleted successfully...');
        return redirect('admin/portfoliotypes');
    }
}
