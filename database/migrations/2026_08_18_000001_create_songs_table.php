<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('key')->nullable();
            $table->string('tempo')->nullable();
            // Lyrics split by syllable with chords/melody per syllable:
            // { sections: [{ id, name, lines: [{ id, syllables: [{ id, text, chords[], notes[] }] }] }], tabs: [{ id, title, tablature }] }
            $table->json('content')->nullable();
            $table->foreignId('org_id')->constrained('organizations');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};