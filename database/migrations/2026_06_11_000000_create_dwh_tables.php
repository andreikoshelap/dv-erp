<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lightweight tenant stub (~1000 accounting clients in prod).
        Schema::create('tenants', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('reg_code')->nullable(); // Äriregistri kood
            $t->timestamps();
        });

        Schema::create('source_systems', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->string('type'); // smartaccounts | saf | erply | directo
            $t->string('label')->nullable();
            $t->text('config')->nullable(); // per-source API creds; encrypted:array cast → ciphertext, so TEXT not jsonb
            $t->timestamp('last_synced_at')->nullable();
            $t->timestamps();
        });

        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('source_system_id')->constrained()->cascadeOnDelete();
            $t->string('source_code');               // account code in the source ERP
            $t->string('canonical_code')->nullable(); // filled by mapping module (later)
            $t->string('name');
            $t->string('name_en')->nullable();
            $t->string('type'); // asset | liability | income | expense
            $t->timestamps();
            $t->unique(['source_system_id', 'source_code']);
        });

        Schema::create('journal_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('source_system_id')->constrained()->cascadeOnDelete();
            $t->string('source_ref');          // entry id in the source → idempotency key
            $t->date('entry_date');
            $t->string('period');              // 'YYYY-MM' for fast grouping
            $t->string('document_type')->nullable();
            $t->string('document_number')->nullable();
            $t->char('currency', 3)->default('EUR');
            $t->timestamps();
            $t->unique(['source_system_id', 'source_ref']);
            $t->index(['tenant_id', 'period']);
        });

        Schema::create('journal_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $t->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $t->decimal('debit', 15, 2)->default(0);
            $t->decimal('credit', 15, 2)->default(0);
            $t->char('currency', 3)->default('EUR');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('source_systems');
        Schema::dropIfExists('tenants');
    }
};
