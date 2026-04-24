<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['head:id,name', 'users:id,name,department_id'])
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $groups = Group::with('users:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $companies = Company::query()
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);

        return view('admin.organization.index', compact('departments', 'groups', 'companies', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'head_id' => 'nullable|exists:users,id',
            'code' => ['nullable', 'string', 'max:20', Rule::unique('departments', 'code')],
        ]);

        Department::create(array_merge($validated, [
            'code' => $validated['code'] ?: Department::generateCode($validated['name']),
        ]));

        return back()->with('success', 'Отдел создан.');
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'head_id' => 'nullable|exists:users,id',
            'code' => ['nullable', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($department->id)],
        ]);

        $department->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'head_id' => $validated['head_id'] ?? null,
            'code' => $validated['code'] ?: $department->code ?: Department::generateCode($validated['name']),
        ]);

        return back()->with('success', 'Отдел обновлен.');
    }

    public function destroy(Department $department)
    {
        if ($department->users()->exists()) {
            return back()->with('error', 'Нельзя удалить отдел, пока в нем есть пользователи.');
        }

        $department->delete();

        return back()->with('success', 'Отдел удален.');
    }
}
