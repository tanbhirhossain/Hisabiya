<?php

namespace Modules\CORE\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\CORE\Requests\UserRequest;
use Modules\CORE\Services\UserService;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function index(Request $request): Response
    {
        return Inertia::render('CORE::Users/Index', [
            'users' => $this->userService->paginate($request),
            'options' => $this->userService->options(),
            'filters' => $request->only(['search', 'status', 'role', 'tenant_id', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CORE::Users/Create', [
            'options' => $this->userService->options(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->userService->create(
            $request->safe()->except('roles'),
            $request->validated('roles', []),
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('CORE::Users/Edit', [
            'user' => $user->load(['tenant:id,name', 'roles:id,name']),
            'options' => $this->userService->options(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update(
            $user,
            $request->safe()->except('roles'),
            $request->validated('roles', []),
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->userService->delete($user);

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
