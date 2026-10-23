<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->text('footer_description')->nullable()->after('cover_path');
            $table->string('footer_trust_text')->nullable()->after('footer_description');
            $table->json('footer_columns')->nullable()->after('footer_trust_text');
            $table->string('footer_copyright')->nullable()->after('footer_columns');
            $table->string('footer_bottom_text')->nullable()->after('footer_copyright');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'footer_description',
                'footer_trust_text',
                'footer_columns',
                'footer_copyright',
                'footer_bottom_text',
            ]);
        });
    }
};
