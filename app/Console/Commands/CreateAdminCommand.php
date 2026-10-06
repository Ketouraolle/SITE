<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateAdminCommand extends Command
{
    protected $signature = 'shop:create-admin
                            {--name= : Nom affiche}
                            {--email= : Adresse e-mail de connexion}
                            {--password= : Mot de passe (demande si absent)}';

    protected $description = 'Cree un compte administrateur (il n\'existe pas d\'inscription publique)';

    public function handle(): int
    {
        $name = $this->option('name') ?: text('Nom de l\'administrateur', required: true);
        $email = $this->option('email') ?: text('Adresse e-mail', required: true);
        $secret = $this->option('password') ?: password('Mot de passe', required: true);

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $secret],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($secret),
        ]);

        $this->components->info("Administrateur cree : {$user->email}");
        $this->line('   Connexion : '.route('login'));

        return self::SUCCESS;
    }
}
