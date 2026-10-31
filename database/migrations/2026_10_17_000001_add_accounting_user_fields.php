<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->string('password_hash')->nullable();
            $table->string('security_question', 500)->nullable();
            $table->string('security_answer_hash')->nullable();
            $table->enum('role', ['customer', 'staff', 'supervisor', 'admin', 'accountant', 'viewer'])->default('staff')->change();
        });

        foreach (DB::table('users')->orderBy('id')->get(['id', 'email', 'password']) as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'username' => $user->email,
                'password_hash' => $user->password,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->whereIn('role', ['accountant', 'viewer', 'customer'])->update(['role' => 'staff']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['supervisor', 'staff', 'admin'])->default('staff')->change();
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'password_hash', 'security_question', 'security_answer_hash']);
        });
    }
};
