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
        // TODO: store file sizes in bytes as an INT attribute (this is fine since >20Mb files are not allowed)
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('name');
            $table->string('path');

            // If the value is null, the file is considered to be placed inside the root folder
            // TODO: create a special "root" folder to store the size of all the data stored in the cloud
            $table->foreignId('folder_id')->nullable()->constrained('folders');

            $table->foreignId('owner_id')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
};
