<?php

namespace App\Http\Responses;

use App\Support\RecoveryCodes;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->user();

        if ($user) {
            $codes = RecoveryCodes::regenerateForUser($user);

            $request->session()->put('recovery_codes_handoff_required', true);
            $request->session()->put('recovery_codes_handoff_codes', $codes);
        }

        return redirect()->route('recovery-codes.handoff');
    }
}
