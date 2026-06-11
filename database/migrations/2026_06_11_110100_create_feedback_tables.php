<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel untuk konten tipe 'feedback' (form survei ala Google Form, tanpa
     * penilaian). Disimpan relasional agar jawaban mudah diagregasi untuk
     * ringkasan: pertanyaan + submission + jawaban.
     */
    public function up(): void
    {
        // Definisi pertanyaan form feedback.
        Schema::create('feedback_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->onDelete('cascade');
            // rating | single_choice | multi_choice | text
            $table->string('type', 30);
            $table->text('question');
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            // rating: {"max":5,"min_label":"...","max_label":"..."}
            // single/multi_choice: {"options":[{"id":"o1","label":"..."}]}
            $table->json('config')->nullable();
            $table->timestamps();
        });

        // Satu pengiriman form per peserta per konten.
        Schema::create('feedback_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('content_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('submitted'); // draft | submitted
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'content_id']);
        });

        // Jawaban per pertanyaan. Kolom bertipe agar mudah diagregasi (mis.
        // AVG(rating_value)); pilihan ganda disimpan sebagai array di choice_value.
        Schema::create('feedback_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('feedback_submissions')->onDelete('cascade');
            $table->foreignId('question_id')->nullable()->constrained('feedback_questions')->onDelete('cascade');
            $table->unsignedSmallInteger('rating_value')->nullable();
            $table->longText('text_value')->nullable();
            $table->json('choice_value')->nullable(); // ["o1","o3"] (id opsi terpilih)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_answers');
        Schema::dropIfExists('feedback_submissions');
        Schema::dropIfExists('feedback_questions');
    }
};
