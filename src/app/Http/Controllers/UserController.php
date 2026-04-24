<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['department', 'groups'])
            ->orderBy('name')
            ->get();

        $departments = Department::orderBy('name')->get();
        $groups = Group::orderBy('name')->get();

        return view('users.index', compact('users', 'departments', 'groups'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateUser($request);
        $groupIds = $validated['group_ids'] ?? [];
        unset($validated['group_ids']);
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        $user->groups()->sync($groupIds);
        Cache::forget('all_users_simple');

        return back()->with('success', 'Пользователь создан.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validateUser($request, $user);
        $groupIds = $validated['group_ids'] ?? [];
        unset($validated['group_ids']);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->groups()->sync($groupIds);
        Cache::forget('all_users_simple');

        return back()->with('success', 'Пользователь обновлен.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Нельзя удалить текущего пользователя.');
        }

        $user->delete();
        Cache::forget('all_users_simple');

        return back()->with('success', 'Пользователь удален.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Нельзя деактивировать собственную учетную запись.');
        }

        $user->update(['is_active' => !$user->is_active]);
        Cache::forget('all_users_simple');

        return back()->with('success', $user->is_active ? 'Пользователь активирован.' : 'Пользователь деактивирован.');
    }

    public function updateBackground(Request $request)
    {
        $validated = $request->validate([
            'background' => 'required|file|mimetypes:image/jpeg,image/jpg|max:4096',
        ]);

        $file = $validated['background'];
        $mimeType = $file->getMimeType() ?: 'image/jpeg';
        $backgroundImage = 'data:' . $mimeType . ';base64,' . base64_encode($file->get());

        $request->user()->update([
            'background_image' => $backgroundImage,
        ]);

        return response()->json([
            'ok' => true,
            'background_image' => $backgroundImage,
        ]);
    }

    public function destroyBackground(Request $request)
    {
        $request->user()->update([
            'background_image' => null,
        ]);

        return response()->json([
            'ok' => true,
            'background_image' => null,
        ]);
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $passwordRule = $user
            ? 'nullable|string|min:6'
            : 'required|string|min:6';

        return $request->validate([
            'name' => 'required|string|max:255',
            'login' => ['required', 'string', 'max:255', Rule::unique('users', 'login')->ignore($user?->id)],
            'password' => $passwordRule,
            'role' => 'required|in:admin,manager,clerk,employee',
            'department_id' => 'required|exists:departments,id',
            'position' => 'nullable|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:groups,id',
        ]) + [
            'is_active' => $request->has('is_active'),
        ];
    }
}
