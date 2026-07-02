<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->paginate(20);
        return view('permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('permissions._form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:permissions,name'
        ]);

        DB::table('permissions')->insert([
            'name' => $validated['name'],
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Tạo thành công');
    }

    public function edit(Permission $permission)
    {
        return view('permissions._form', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name' => 'required'
        ]);

        DB::table('permissions')
            ->where('id', $permission->id)
            ->update([
                'name' => $validated['name'],
                'updated_at' => now(),
            ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('permissions.index');
    }

    public function destroy(Permission $permission)
    {
        DB::table('permissions')
            ->where('id', $permission->id)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        return back();
    }
}