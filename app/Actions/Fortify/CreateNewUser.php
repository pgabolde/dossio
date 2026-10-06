<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $username = $input['name'];
            $user = User::create([
                'name' => $username,
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $organization = Organization::create([
                'name' => "Organisation de $username",
            ]);
            $organization->members()->attach($user, ['role' => 'owner']);
            $user->currentOrganization()->associate($organization);
            $user->save();

            return $user;
        });
    }
}
