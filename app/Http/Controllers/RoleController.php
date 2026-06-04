<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = Role::orderBy('name')->get();

        return view(
            'roles.index',
            compact('roles')
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get();

        return view('roles._form', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        $role = new Role();
        $role->name = $request->name;
        $role->guard_name = 'web';
        $role->save();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Tạo vai trò thành công');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get();

        $role->load('permissions');

        return view('roles._form', compact('role', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
        Role $role
    ) {
        $request->validate([
            'name' => 'required'
        ]);

        $role->forceFill([
            'name' => $request->name,
        ])->save();

        $role->syncPermissions(
            $request->permissions ?? []
        );

        return redirect()
            ->route('roles.index')
            ->with(
                'success',
                'Cập nhật thành công'
            );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
