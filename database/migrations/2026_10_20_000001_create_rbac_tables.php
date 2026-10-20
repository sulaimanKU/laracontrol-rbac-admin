<?php

use App\Enums\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->json('permissions');
            $t->timestamps();
        });
        foreach (['Admin' => array_column(Permission::cases(), 'value'), 'Manager' => ['access dashboard', 'view users', 'manage users', 'view roles'], 'Member' => []] as $name => $permissions) {
            DB::table('roles')->insert(['name' => $name, 'permissions' => json_encode($permissions), 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('role_id')->nullable()->constrained()->restrictOnDelete();
            $t->boolean('is_active')->default(true);
        });
        DB::table('users')->update(['role_id' => DB::table('roles')->where('name', 'Member')->value('id')]);
        Schema::create('audit_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action')->index();
            $t->json('metadata');
            $t->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('role_id');
            $t->dropColumn('is_active');
        });
        Schema::dropIfExists('roles');
    }
};
