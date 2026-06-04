<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->get();

        return view(
            'permissions.index',
            compact('permissions')
        );
    }

    public function create()
    {
        return view('permissions._form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name'
        ]);

        Permission::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        return redirect()
            ->route('permissions.index')
            ->with('success', 'Tạo thành công');
    }

    public function edit(Permission $permission)
    {
        return view('permissions._form', compact('permission'));
    }

    public function update(
        Request $request,
        Permission $permission
    ) {
        $request->validate([
            'name' => 'required'
        ]);

        $permission->forceFill([
            'name' => $request->name,
        ])->save();

        return redirect()
            ->route('permissions.index');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();

        return back();
    }
}