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
    public function export()
    {
        $transactions = $this->TransactionService->getAll();
        // Set response headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="transactions.csv"');

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add column headers to CSV
        fputcsv($output, ['Name', 'Amount','Created At']);

        // Add subscriber data to CSV
        foreach ($transactions as $transaction) {
             $name = !empty($transaction->user_row) ? $transaction->user_row->name." < ". $transaction->user_row->email." > " : 'N/A';
             $amount=format_amount($transaction->amount);
             $formatted_date = date('m/d/y', strtotime($transaction->created_at));
            fputcsv($output, [$name, $amount, $formatted_date]);
        }

        // Close the output stream
        fclose($output);

        // Return an empty response to prevent any additional output
        return response('', 200);
    }
}
