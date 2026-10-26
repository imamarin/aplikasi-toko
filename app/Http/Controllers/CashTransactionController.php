<?php

namespace App\Http\Controllers;

use App\Services\CashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashTransactionController extends Controller
{
    public function __construct(
        protected CashService $cashService
    ) {}

    public function store(Request $request)
    {
        $rawAmount = $request->input('amount');
        if (is_string($rawAmount)) {
            $rawAmount = str_replace(['Rp', 'rp', ' ', '.'], '', $rawAmount);
            $rawAmount = str_replace(',', '.', $rawAmount);
            $request->merge(['amount' => $rawAmount]);
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:in,out'],
            'category' => ['required', 'string', 'in:expense,capital,other'],
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
        ], [
            'amount.required' => 'Nominal uang kas wajib diisi.',
            'description.required' => 'Keterangan pengeluaran/pemasukan kas wajib diisi.',
        ]);

        $user = Auth::user();
        $currentShift = $user->currentOpenShift;

        $this->cashService->recordTransaction(
            cashShiftId: $currentShift?->id,
            userId: $user->id,
            type: $validated['type'],
            category: $validated['category'],
            amount: (float) $validated['amount'],
            description: $validated['description']
        );

        return back()->with('success', 'Transaksi kas operasional berhasil dicatat.');
    }
}
