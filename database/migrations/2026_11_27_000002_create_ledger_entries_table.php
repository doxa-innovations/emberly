<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignUuid('generation_id')->nullable()->constrained();
            $table->string('type');
            $table->bigInteger('amount');
            $table->bigInteger('balance_after');
            $table->string('reference')->nullable()->unique();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['generation_id', 'type']);
            $table->index(['user_id', 'id']);
        });

        // The ledger is the source of truth; users.balance is its running total, kept in the same
        // statement as every insert so a balance read never has to sum the ledger.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION apply_ledger_entry() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                UPDATE users SET balance = balance + NEW.amount WHERE id = NEW.user_id
                    RETURNING balance INTO NEW.balance_after;
                RETURN NEW;
            END $$;

            CREATE TRIGGER ledger_entries_apply BEFORE INSERT ON ledger_entries
                FOR EACH ROW EXECUTE FUNCTION apply_ledger_entry();

            CREATE OR REPLACE FUNCTION reject_ledger_change() RETURNS trigger LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'ledger_entries is append-only';
            END $$;

            CREATE TRIGGER ledger_entries_append_only BEFORE UPDATE OR DELETE ON ledger_entries
                FOR EACH ROW EXECUTE FUNCTION reject_ledger_change();
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        DB::unprepared('DROP FUNCTION IF EXISTS apply_ledger_entry, reject_ledger_change');
    }
};
