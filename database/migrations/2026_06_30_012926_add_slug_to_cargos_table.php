<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('nombre');
            // Como puede haber datos repetidos en la BD actual que no están limpios, 
            // no forzaremos unique('nombre') a nivel DB si falla, o podemos ponerlo. 
            // Si hay repetidos, fallará. Para mayor seguridad, solo agregamos el slug.
            // O podemos agregar el unique si la DB está limpia.
            // Para la seguridad de que se pueda correr:
            // $table->unique('nombre'); // Lo comento por si la BD actual tiene repetidos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
