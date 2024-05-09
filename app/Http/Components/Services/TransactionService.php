<?php

namespace App\Http\Components\Services;

use App\Models\Payments_model;

class TransactionService
{
    public function getAll()
    {
        $rows=Payments_model::latest()->get();
        foreach($rows as $row){
            $row->user_row=$row->user_row;
        }
        return $rows;
    }
    public function getById($id)
    {
        return Payments_model::find($id);
    }
    public function getOne($slug)
    {
        return Payments_model::where('slug', $slug)->first();
    }
    public function create($request)
    {
       
    
    }
    public function update($request, $id)
    {
       
    }
    public function delete($id)
    {
        $page = Payments_model::find($id);
        $page->delete();
    }
}