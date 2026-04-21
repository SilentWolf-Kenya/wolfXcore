<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Users\UserCreationService;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function __construct(private UserCreationService $creationService)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'username'         => 'required|string|min:1|max:191|unique:users,username|regex:/^[a-zA-Z0-9_\-\.]+$/',
            'email'            => 'required|email|max:191|unique:users,email',
            'name_first'       => 'required|string|min:1|max:191',
            'name_last'        => 'required|string|min:1|max:191',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $this->creationService->handle([
            'username'   => $request->input('username'),
            'email'      => $request->input('email'),
            'name_first' => $request->input('name_first'),
            'name_last'  => $request->input('name_last'),
            'password'   => $request->input('password'),
            'root_admin' => false,
        ]);

        return new JsonResponse(['success' => true, 'message' => 'Account created. You may now log in.']);
    }
}
