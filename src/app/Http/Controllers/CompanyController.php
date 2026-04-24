<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('companies', 'name')],
            'details' => 'nullable|string',
        ]);

        Company::create([
            'name' => $validated['name'],
            'details' => $validated['details'] ?? null,
        ]);

        return back()->with('success', 'Компания-отправитель создана.');
    }

    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('companies', 'name')->ignore($company->id)],
            'details' => 'nullable|string',
        ]);

        $company->update([
            'name' => $validated['name'],
            'details' => $validated['details'] ?? null,
        ]);

        return back()->with('success', 'Компания-отправитель обновлена.');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return back()->with('success', 'Компания-отправитель удалена.');
    }
}
