<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user {email} {username?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Execute the console command.
     */
     public function handle(): int
     {
         $email = $this->argument('email');
         $username = $this->argument('username') ?? Str::before($email, '@');

         if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
             $this->output->error('Invalid email address.');

             return self::FAILURE;
         }

         if (User::where('email', $email)->exists()) {
             $this->output->error('A user with this email already exists.');

             return self::FAILURE;
         }

         if (User::where('username', $username)->exists()) {
             $this->output->error('A user with this username already exists.');

             return self::FAILURE;
         }

         $password = Str::password(16, symbols: false);

         User::create([
             'email' => $email,
             'username' => $username,
             'password' => $password,
         ]);

         $this->output->info("User \"{$username}\" created successfully.");
         $this->output->info("Password: {$password}");

         return self::SUCCESS;
     }
}
