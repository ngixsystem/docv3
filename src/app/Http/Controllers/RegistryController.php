<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Document;
use App\Models\RegistryAccess;
use App\Models\RegistryEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $departments = $this->accessibleDepartments($user);

        $activeDeptId = $request->integer('dept') ?: null;
        if ($activeDeptId && !$departments->contains('id', $activeDeptId)) {
            $activeDeptId = null;
        }

        $query = RegistryEntry::query()
            ->whereIn('department_id', $departments->pluck('id'))
            ->with([
                'document:id,number,type,subject,status,doc_date',
                'department:id,name',
                'addedBy:id,name',
            ]);

        if ($activeDeptId) {
            $query->where('department_id', $activeDeptId);
        }

        $entries = $query
            ->orderByDesc('pinned')
            ->orderByDesc('created_at')
            ->get();

        $deptCounts = RegistryEntry::query()
            ->whereIn('department_id', $departments->pluck('id'))
            ->selectRaw('department_id, count(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        return view('registry.index', compact('entries', 'departments', 'activeDeptId', 'deptCounts'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->hasAnyRole(['admin', 'clerk', 'manager']), 403);

        $validated = $request->validate([
            'document_id'   => 'required|exists:documents,id',
            'department_id' => 'required|exists:departments,id',
            'note'          => 'nullable|string|max:500',
        ]);

        $document = Document::findOrFail($validated['document_id']);
        abort_unless($user->canViewDocument($document), 403);

        $accessible = $this->accessibleDepartments($user)->pluck('id');
        abort_unless($accessible->contains((int) $validated['department_id']), 403);

        RegistryEntry::updateOrCreate(
            ['document_id' => $validated['document_id'], 'department_id' => $validated['department_id']],
            ['added_by' => $user->id, 'note' => $validated['note'] ?? null]
        );

        return back()->with('success', 'Документ добавлен в реестр отдела.');
    }

    public function togglePin(RegistryEntry $entry)
    {
        $user = Auth::user();
        $accessible = $this->accessibleDepartments($user)->pluck('id');
        abort_unless($accessible->contains($entry->department_id), 403);

        $entry->update(['pinned' => !$entry->pinned]);

        return response()->json(['pinned' => $entry->pinned]);
    }

    public function destroy(RegistryEntry $entry)
    {
        $user = Auth::user();
        $accessible = $this->accessibleDepartments($user)->pluck('id');
        abort_unless($accessible->contains($entry->department_id), 403);

        $entry->delete();

        return back()->with('success', 'Документ удалён из реестра.');
    }

    // ── Access management (admin only) ──────────────────────────────────────

    public function accessIndex()
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $departments = Department::with([
            'registryAccesses.user:id,name,role,department_id',
            'registryAccesses.user.department:id,name',
        ])->orderBy('name')->get();

        $allUsers = User::where('is_active', true)
            ->select(['id', 'name', 'role', 'department_id'])
            ->with('department:id,name')
            ->orderBy('name')
            ->get();

        return view('registry.access', compact('departments', 'allUsers'));
    }

    public function grantAccess(Request $request)
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $validated = $request->validate([
            'user_id'       => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
        ]);

        RegistryAccess::firstOrCreate($validated);

        return back()->with('success', 'Доступ предоставлен.');
    }

    public function revokeAccess(RegistryAccess $access)
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $access->delete();

        return back()->with('success', 'Доступ отозван.');
    }

    // ── Shared helper ────────────────────────────────────────────────────────

    public static function accessibleDepartments($user)
    {
        if ($user->hasAnyRole(['admin', 'clerk'])) {
            return Department::orderBy('name')->get(['id', 'name', 'code']);
        }

        return Department::whereHas('registryAccesses', fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }
}
