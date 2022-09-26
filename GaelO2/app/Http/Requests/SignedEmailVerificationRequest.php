<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Auth\EmailVerificationRequest;

/**
 * Class SignedEmailVerificationRequest
 *
 * A request that authorizes the user based on the route signature.
 * 
 * Overload Laravel regular class to remove needs of authentication to validate email
 *
 * @package App\Http\Requests
 */
class SignedEmailVerificationRequest extends EmailVerificationRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (!$this->hasValidSignature()) {
            return false;
        }
        auth()->loginUsingId($this->route('id'));
        return parent::authorize();
    }
}
