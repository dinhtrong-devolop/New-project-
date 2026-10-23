<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 50)->unique()->default('general');
            $table->string('site_name');
            $table->text('site_keywords')->nullable();
            $table->text('site_description')->nullable();
            $table->string('contact_email');
            $table->string('chat_label')->default('Hỗ trợ trực tuyến');
            $table->string('chat_url')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('footer_logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
