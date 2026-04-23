<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('login')->nullable()->after('name');
            $table->string('password')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->rememberToken();
            $table->softDeletes();
        });

        $users = DB::table('users')->select('id')->orderBy('id')->get();

        foreach ($users as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'login' => 'user' . $user->id,
                    'password' => Hash::make('password123'),
                    'is_active' => true,
                ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('login');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login']);
            $table->dropSoftDeletes();
            $table->dropRememberToken();
            $table->dropColumn(['login', 'password', 'is_active']);
        });
    }
};
