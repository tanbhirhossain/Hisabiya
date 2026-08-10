<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\PersonalAccounting\Models\PersonalContact;

class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tenantId = (int) $user->tenant_id;

        return Inertia::render('PersonalAccounting::Contacts/Index', [
            'contacts' => PersonalContact::query()
                ->forTenant($tenantId)
                ->where('user_id', $user->id)
                ->withCount('loans')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['person', 'business'])],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        PersonalContact::create([
            ...$data,
            'tenant_id' => (int) $request->user()->tenant_id,
            'user_id' => (int) $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Contact added.');
    }

    public function destroy(Request $request, PersonalContact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->back()->with('success', 'Contact deleted.');
    }
}
