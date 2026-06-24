<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Hiển thị danh sách người dùng với chức năng tìm kiếm cơ bản.
     */
    public function index(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');

        $users = User::with('roles')
            ->when($name, fn($query) => $query->where('name', 'like', "%{$name}%"))
            ->when($email, fn($query) => $query->where('email', 'like', "%{$email}%"))
            ->paginate(20)
            ->appends(['name' => $name, 'email' => $email]);

        $roles = Role::all();

        return view('users.index', compact('users', 'roles', 'name', 'email'));
    }

    /**
     * Hiển thị form tạo người dùng mới.
     */
    public function create()
    {
        $roles = Role::all();
        return view('users._form', compact('roles'));
    }

    /**
     * Lưu người dùng mới.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6',
            'roles' => 'required|array|min:1',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        $user->syncRoles($validated['roles']);

        return redirect()->route('users.index');
    }

    /**
     * Không sử dụng method show.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Hiển thị form chỉnh sửa người dùng.
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $user->load('roles');
        return view('users._form', compact('user', 'roles'));
    }

    /**
     * Cập nhật thông tin người dùng.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|confirmed|min:6',
            'roles' => 'required|array|min:1',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = bcrypt($validated['password']);
        }

        $user->update($data);
        $user->syncRoles($validated['roles']);

        return redirect()->route('users.index');
    }

    /**
     * Không sử dụng method destroy.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Admin đặt lại mật khẩu mặc định cho người dùng.
     */
    public function adminResetPassword(User $user)
    {
        $newPass = '12345678';
        $user->update([
            'password' => Hash::make($newPass),
            'reset_password_at' => now(),
            'reset_password_by' => auth()->user()?->email,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Đặt lại mật khẩu thành công.');
    }

    /**
     * Xuất danh sách người dùng ra file Excel.
     */
    public function exportExcel()
    {
        $users = User::with('roles')->get();
        $filename = 'users_' . now()->format('Ymd_His') . '.xlsx';

        $headings = ['Tên', 'Email', 'Vai trò'];
        $rows = $users->map(fn($user) => [
            $user->name,
            $user->email,
            $user->getRoleNames()->implode(', ')
        ])->toArray();

        $export = new class($rows, $headings) implements FromArray, WithHeadings {
            protected $rows;
            protected $headings;
            public function __construct(array $rows, array $headings)
            {
                $this->rows = $rows;
                $this->headings = $headings;
            }
            public function array(): array
            {
                return $this->rows;
            }
            public function headings(): array
            {
                return $this->headings;
            }
        };

        return Excel::download($export, $filename);
    }
}
