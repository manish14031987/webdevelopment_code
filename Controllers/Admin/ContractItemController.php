<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\contract_item;
class ContractItemController extends Controller
{

    public function deleteItem($id)
    {
        $contract_item_id = contract_item::find($id);
        $contract_item_id->delete($id);
        session()->flash('flash_message', 'Contract item deleted successfully...');
        return redirect('admin/contract');
    }

}
