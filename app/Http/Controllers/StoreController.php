<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\StoreUpdateRequest;
use App\Models\Store;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(
            auth()->user()->can('view-stores'),
            403
        );
        $query = Store::query();

        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);

            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('region', 'like', "%{$keyword}%")
                    ->orWhere('am_name', 'like', "%{$keyword}%")
                    ->orWhere('am_email', 'like', "%{$keyword}%")
                    ->orWhere('om_name', 'like', "%{$keyword}%")
                    ->orWhere('om_email', 'like', "%{$keyword}%")
                    ->orWhere('technician_name', 'like', "%{$keyword}%")
                    ->orWhere('muasam_email', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        $stores = $query
            ->orderBy('area')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('stores.index', compact('stores'));
    }

    public function create()
    {
        return view('stores.create');
    }

    public function store(StoreStoreRequest $request)
    {
        Store::create($request->validated());

        return redirect()
            ->route('stores.index')
            ->with('success', 'Thêm cửa hàng thành công.');
    }

    public function show(Store $store)
    {
        return view('stores.show', compact('store'));
    }

    public function edit(Store $store)
    {
        return view('stores.edit', compact('store'));
    }

    public function update(StoreUpdateRequest $request, Store $store)
    {
        $store->update($request->validated());

        return redirect()
            ->route('stores.index')
            ->with('success', 'Cập nhật cửa hàng thành công.');
    }

    public function destroy(Store $store)
    {
        $store->delete();

        return redirect()
            ->route('stores.index')
            ->with('success', 'Xóa cửa hàng thành công.');
    }

    public function importLocation()
    {
        $file = storage_path('app/import/GPS.xlsx');

        if (!file_exists($file)) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy file: '.$file,
            ], 404);
        }

        $rows = Excel::toArray([], $file)[0];

        $total = 0;
        $updated = 0;
        $notFound = [];
        $errors = [];

        foreach ($rows as $index => $row) {

            // Bỏ dòng tiêu đề
            if ($index === 0) {
                continue;
            }

            $total++;

            $storeCode = trim($row[0] ?? '');

            if (empty($storeCode)) {
                continue;
            }

            $store = Store::where('code', $storeCode)->first();

            if (!$store) {
                $notFound[] = $storeCode;
                continue;
            }

            try {

                $store->update([
                    'longitude' => (float) str_replace(',', '.', trim($row[3] ?? '')),
                    'latitude' => (float) str_replace(',', '.', trim($row[4] ?? '')),
                ]);

                $updated++;

            } catch (\Throwable $e) {

                $errors[] = [
                    'code' => $storeCode,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'total_excel' => $total,
            'updated' => $updated,
            'not_found' => $notFound,
            'errors' => $errors,
        ]);
    }
}