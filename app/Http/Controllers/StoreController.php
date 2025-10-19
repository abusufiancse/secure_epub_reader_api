<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Ebook;
use App\Models\Purchase;

class StoreController extends Controller {
    public function purchase(Request $r, $id) {
        $ebook = Ebook::findOrFail($id);
        if ($ebook->is_free) return response()->json(['message'=>'Book is free'], 400);
        Purchase::firstOrCreate(['user_id'=>$r->user()->id,'ebook_id'=>$ebook->id], ['status'=>'paid']);
        return response()->json(['message'=>'Purchased']);
    }
}
