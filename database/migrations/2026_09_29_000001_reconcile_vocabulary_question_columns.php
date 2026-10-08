<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = Schema::getColumnListing('vocabularies');

        Schema::table('vocabularies', function (Blueprint $table) use ($columns): void {
            foreach (['word', 'reading', 'meaning'] as $legacyColumn) {
                if (in_array($legacyColumn, $columns, true)) {
                    $table->string($legacyColumn)->nullable()->change();
                } else {
                    $table->string($legacyColumn)->nullable();
                }
            }

            if (!in_array('question', $columns, true)) {
                $table->text('question')->nullable();
            }
            if (!in_array('image_url', $columns, true)) {
                $table->text('image_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Existing installations may have had these columns before this migration.
    }
};