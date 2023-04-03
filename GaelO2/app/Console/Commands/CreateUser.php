<?php

namespace App\Console\Commands;

use App\GaelO\Constants\Enums\JobEnum;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gaelo:create-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create user in GaelO';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(
        User $user
    ) {

        $email = $this->ask('email');
        $lastname = $this->ask('lastname');
        $firstname = $this->ask('firstname');
        $phone = $this->ask('phone');
        $mainCenter = $this->anticipate('Main Center Code', [0]);
        $job = $this->choice('Job', array_column(JobEnum::cases(), 'value'));
        $password = $this->secret('Password');
        $administrator = false;
        if ($this->confirm('Set Administrator role ?')) {
            $administrator = true;
        }

        $user = new User();
        $user->lastname = $lastname;
        $user->firstname = $firstname;
        $user->phone = $phone;
        $user->email = $email;
        $user->creation_date = now();
        $user->password = Hash::make($password);
        $user->center_code = $mainCenter;
        $user->job = $job;
        $user->administrator = $administrator;
        $user->email_verified_at = now();
        $user->save();

        return 0;
    }
}
