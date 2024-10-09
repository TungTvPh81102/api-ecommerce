<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attribute_value_variant', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Variant::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\AttributeValue::class)->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['variant_id', 'attribute_value_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_value_variant');
    }
};
