<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ebooks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author')->nullable();
            $table->boolean('is_free')->default(false);
            $table->integer('price_cents')->default(0);
            $table->string('enc_path');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ebooks'); }
};
