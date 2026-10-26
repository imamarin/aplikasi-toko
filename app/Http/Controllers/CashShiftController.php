<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\CashTransaction;
use App\Services\CashService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashShiftController extends Controller
{
    public function __construct(
        protected CashService $cashService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = CashShift::with('user')->latest('opened_at');

        // Non-superadmin / non-owner can only see their own shifts
        if (!$user->hasRole(['super-admin', 'admin', 'owner'])) {
            $query->where('user_id', $user->id);
        }

        $shifts = $query->paginate(15);
        $currentShift = $user->currentOpenShift;

        return view('cash-shifts.index', compact('shifts', 'currentShift'));
    }

    public function current(Request $request)
    {
        $user = Auth::user();
        $shiftId = $request->query('shift_id');

        if ($shiftId) {
            $currentShift = CashShift::find($shiftId);
        } else {
            $currentShift = $user->currentOpenShift;
            // If admin or owner is viewing, allow seeing the latest active shift in the system
            if (!$currentShift && $user->hasRole(['super-admin', 'admin', 'owner'])) {
                $currentShift = CashShift::where('status', 'open')->latest('opened_at')->first();
            }
        }

        if (!$currentShift || $currentShift->status !== 'open') {
            return redirect()->route('cash-shifts.index')->with('info', 'Tidak ada sesi shift kasir aktif yang sedang berjalan.');
        }

        $summary = $this->cashService->calculateShiftSummary($currentShift);
        $transactions = CashTransaction::where('cash_shift_id', $currentShift->id)->latest()->get();

        return view('cash-shifts.current', compact('currentShift', 'summary', 'transactions'));
    }

    public function openShift(Request $request)
    {
        $rawCash = $request->input('opening_cash');
        if (is_string($rawCash)) {
            $rawCash = str_replace(['Rp', 'rp', ' ', '.'], '', $rawCash);
            $rawCash = str_replace(',', '.', $rawCash);
            $request->merge(['opening_cash' => $rawCash]);
        }

        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ], [
            'opening_cash.required' => 'Nominal modal awal kas wajib diisi.',
        ]);

        try {
            $shift = $this->cashService->openShift(Auth::id(), (float) $validated['opening_cash'], $validated['notes'] ?? null);

            return redirect()->route('pos.index')->with('success', 'Sesi shift kasir berhasil dibuka dengan modal awal Rp ' . number_format($shift->opening_cash, 0, ',', '.') . '. Selamat bertugas!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function closeShift(Request $request)
    {
        // Sanitize closing_cash_actual if submitted with dots or currency formatting (e.g. "368.500" or "Rp 368.500")
        $rawCash = $request->input('closing_cash_actual');
        if (is_string($rawCash)) {
            $rawCash = str_replace(['Rp', 'rp', ' ', '.'], '', $rawCash);
            $rawCash = str_replace(',', '.', $rawCash);
            $request->merge(['closing_cash_actual' => $rawCash]);
        }

        $validated = $request->validate([
            'cash_shift_id' => ['nullable', 'exists:cash_shifts,id'],
            'closing_cash_actual' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ], [
            'closing_cash_actual.required' => 'Penghitungan fisik uang tunai di laci wajib diisi.',
            'closing_cash_actual.numeric' => 'Nominal uang fisik harus berupa angka yang valid.',
        ]);

        $user = Auth::user();
        $shiftId = $request->input('cash_shift_id');

        if ($shiftId) {
            $currentShift = CashShift::where('id', $shiftId)->where('status', 'open')->first();
            // Non-admin can only close their own shift
            if ($currentShift && !$user->hasRole(['super-admin', 'admin', 'owner']) && $currentShift->user_id !== $user->id) {
                return redirect()->route('cash-shifts.index')->with('error', 'Anda tidak memiliki hak untuk menutup shift kasir ini.');
            }
        } else {
            $currentShift = $user->currentOpenShift;
            if (!$currentShift && $user->hasRole(['super-admin', 'admin', 'owner'])) {
                $currentShift = CashShift::where('status', 'open')->latest('opened_at')->first();
            }
        }

        if (!$currentShift) {
            return redirect()->route('cash-shifts.index')->with('error', 'Tidak ada sesi shift aktif yang dapat ditutup.');
        }

        try {
            $shift = $this->cashService->closeShift($currentShift, (float) $validated['closing_cash_actual'], $validated['notes'] ?? null);

            return redirect()->route('cash-shifts.show', $shift->id)
                ->with('success', 'Sesi shift kasir berhasil ditutup (Closing). Riwayat rekonsiliasi kas telah tersimpan rapi.');
        } catch (Exception $e) {
            return back()->with('error', 'Gagal menutup shift: ' . $e->getMessage());
        }
    }

    public function show(CashShift $cashShift)
    {
        $cashShift->load(['user', 'sales.customer', 'transactions']);
        $summary = $this->cashService->calculateShiftSummary($cashShift);

        return view('cash-shifts.show', compact('cashShift', 'summary'));
    }
}
