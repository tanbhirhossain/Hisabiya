<?php

namespace Modules\PersonalAccounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Models\Membership;
use Modules\CORE\Services\ModuleMembershipService;

/**
 * Module-scoped user panel for the Personal Accounting module. The Owner can
 * add members directly (creating their credentials) and manage module roles.
 * Gated by the personal-accounting.acl permission.
 */
class ModuleUserController extends Controller
{
    public const MODULE = 'personal_accounting';

    public function __construct(private readonly ModuleMembershipService $memberships)
    {
    }

    public function index(Request $request): Response
    {
        $tenantId = (int) $request->user()->tenant_id;

        return Inertia::render('PersonalAccounting::Settings/Users', [
            'members' => $this->memberships->members($tenantId, self::MODULE),
            'roles' => Membership::ROLES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(Membership::ROLES)],
        ]);

        $this->memberships->addMember(
            $request->user()->tenant,
            self::MODULE,
            $data,
        );

        return redirect()->back()->with('success', 'Member added.');
    }

    public function update(Request $request, Membership $membership): RedirectResponse
    {
        abort_unless($membership->module === self::MODULE, 403);

        $data = $request->validate([
            'role' => ['sometimes', Rule::in(Membership::ROLES)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $this->memberships->updateMember($membership, $data);

        return redirect()->back()->with('success', 'Member updated.');
    }

    public function destroy(Request $request, Membership $membership): RedirectResponse
    {
        abort_unless($membership->module === self::MODULE, 403);

        $this->memberships->removeMember($membership);

        return redirect()->back()->with('success', 'Member removed.');
    }
}
