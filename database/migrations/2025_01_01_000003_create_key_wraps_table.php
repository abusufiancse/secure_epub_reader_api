<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('key_wraps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ebook_id')->constrained()->cascadeOnDelete();
            $table->text('wrapped_cek_b64');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('key_wraps'); }
};
