<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->string('severity')->nullable()->after('story_points');
            $table->text('steps_to_reproduce')->nullable()->after('severity');
            $table->text('expected_result')->nullable()->after('steps_to_reproduce');
            $table->text('actual_result')->nullable()->after('expected_result');
            $table->string('environment')->nullable()->after('actual_result');
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn([
                'severity',
                'steps_to_reproduce',
                'expected_result',
                'actual_result',
                'environment',
            ]);
        });
    }
};
