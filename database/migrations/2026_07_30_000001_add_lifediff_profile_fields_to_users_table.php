<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('handle', 30)->nullable()->unique();
            $table->string('display_name', 80)->nullable();
            $table->text('bio')->nullable();
            $table->string('status', 40)->default('stable');
            $table->string('timezone', 64)->default('UTC');
        });

        DB::table('users')
            ->whereNull('display_name')
            ->update(['display_name' => DB::raw('name')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->dropColumn(['handle', 'display_name', 'bio', 'status', 'timezone']);
        });
    }
};
