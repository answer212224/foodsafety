<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quality_task_has_quality_clear_defects', function (Blueprint $table) {
            $table->boolean('is_repeat')->default(false)->after('is_suggestion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quality_task_has_quality_clear_defects', function (Blueprint $table) {
            $table->dropColumn('is_repeat');
        });
    }
};
