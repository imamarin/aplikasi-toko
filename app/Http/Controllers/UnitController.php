<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::withCount('products')->latest()->paginate(10);
        return view('units.index', compact('units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:units,name'],
            'short_name' => ['nullable', 'string', 'max:50'],
        ], [
            'name.required' => 'Nama satuan wajib diisi.',
            'name.unique' => 'Nama satuan sudah ada.',
        ]);

        Unit::create($validated);

        return redirect()->route('units.index')->with('success', 'Satuan produk berhasil ditambahkan.');
    }

    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:units,name,' . $unit->id],
            'short_name' => ['nullable', 'string', 'max:50'],
        ]);

        $unit->update($validated);

        return redirect()->route('units.index')->with('success', 'Satuan produk berhasil diperbarui.');
    }

    public function destroy(Unit $unit)
    {
        if ($unit->products()->count() > 0) {
            return back()->with('error', 'Satuan tidak dapat dihapus karena sedang digunakan oleh produk.');
        }

        $unit->delete();
        return redirect()->route('units.index')->with('success', 'Satuan produk berhasil dihapus.');
    }
}
