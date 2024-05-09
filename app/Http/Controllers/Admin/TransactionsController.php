<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Components\Services\TransactionService;


class TransactionsController extends Controller
{
    private $transactionService;
    public function __construct(TransactionService $transactionService)
    {
        $this->TransactionService = $transactionService;
    }
    public function index()
    {

        return view('admin.transactions.index',
            [
                'data' => $this->TransactionService->getAll(),
            ]);
    }

    public function delete($id)
    {
        $this->TransactionService->delete($id);
        return redirect()->route('admin.transactions.index')->with('flash_message_success','Query deleted successfully');
    }

}
