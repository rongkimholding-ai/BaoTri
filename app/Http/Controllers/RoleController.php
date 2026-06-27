<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Hiển thị danh sách vai trò.
     */
    public function index()
    {
        $roles = Role::orderBy('name')->paginate(20);
        return view('roles.index', compact('roles'));
    }

    /**
     * Hiển thị form tạo vai trò mới.
     */
    public function create()
    {
        $permissions = Permission::orderBy('name')->get();
        return view('roles._form', compact('permissions'));
    }

    /**
     * Lưu vai trò mới.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array'
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web'
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return redirect()->route('roles.index')->with('success', 'Tạo vai trò thành công');
    }

    /**
     * Hiển thị chi tiết vai trò (tạm thời chưa sử dụng).
     */
    public function show(Role $role)
    {
        // Tuỳ ý bổ sung nếu cần show chi tiết vai trò
    }

    /**
     * Hiển thị form chỉnh sửa vai trò.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('name')->get();
        $role->load('permissions');
        return view('roles._form', compact('role', 'permissions'));
    }

    /**
     * Cập nhật vai trò.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);

        // Sửa lỗi liên quan đến "generation_expression" và các cột không hợp lệ
        // Tránh sử dụng update() bulk nếu model có thuộc tính "guard_name" hoặc các cột đặc biệt trên bảng roles gây lỗi trên MySQL cũ.
        $role->name = $validated['name'];
        // Nếu có trường guard_name, không cập nhật lại nữa (tránh lỗi unknown column)
        $role->save();

        $role->syncPermissions($validated['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'Cập nhật thành công');
    }

    /**
     * Xoá vai trò.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Đã xoá vai trò');
    }
}
