<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $name = $request->input('name');
        $email = $request->input('email');

        $query = User::with('roles');

        if ($name) {
            $query->where('name', 'like', '%' . $name . '%');
        }

        if ($email) {
            $query->where('email', 'like', '%' . $email . '%');
        }

        $users = $query->paginate(20)->appends([
            'name' => $name,
            'email' => $email,
        ]);
        $roles = Role::all();

        return view('users.index', compact('users', 'roles', 'name', 'email'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::all();
        return view('users._form', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
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
            'password' => $validated['password'],
        ]);

        $user->syncRoles($validated['roles']);

        return redirect()->route('users.index');
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
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();

        $user->load('roles');

        return view('users._form', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
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
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        $user->syncRoles($validated['roles']);

        return redirect()->route('users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function adminResetPassword(
        // ResetUserPasswordRequest $request,
        User $user
    ) {
        $newPass = '12345678';
        $data = [
            'password' => Hash::make(
                $newPass
            ),
            'reset_password_at' => now(),
            'reset_password_by' => auth()->user()->email,
        ];
        // dd($data);

        $user->update($data);
    
        return redirect()
            ->back()
            ->with(
                'success',
                'Đặt lại mật khẩu thành công.'
            );
    }

    public function exportExcel()
    {
        $users = User::with('roles')->get();

        $filename = 'users_' . now()->format('Ymd_His') . '.xlsx';

        $headings = ['Tên', 'Email', 'Vai trò'];

        $rows = $users->map(function ($user) {
            return [
                $user->name,
                $user->email,
                $user->getRoleNames()->implode(', ')
            ];
        })->toArray();

        // Sử dụng Export class ẩn danh cho gọn
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

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }
}
