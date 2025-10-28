<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            // ==================== SQL SERVER ====================
            case 'sqlsrv':
                // Limpia índices viejos si existieran
                DB::statement("
                    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_series_default_por_documento' AND object_id = OBJECT_ID('series'))
                        DROP INDEX IX_series_default_por_documento ON series;
                ");
                DB::statement("
                    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_series_un_default_por_tipo' AND object_id = OBJECT_ID('series'))
                        DROP INDEX IX_series_un_default_por_tipo ON series;
                ");

                // Índice único filtrado: solo una default por tipo_documento
                DB::statement("
                    CREATE UNIQUE INDEX IX_series_un_default_por_tipo
                    ON series (tipo_documento_id)
                    WHERE es_default = 1;
                ");
                break;

            // ==================== MYSQL / MARIADB ====================
            case 'mysql':
            default:
                // Borra índices viejos si existen (ignora error si no están)
                try { DB::statement("DROP INDEX IX_series_default_por_documento ON series"); } catch (\Throwable $e) {}
                try { DB::statement("DROP INDEX IX_series_un_default_por_tipo ON series"); } catch (\Throwable $e) {}

                // 1) Asegurar que es_default sea NULLABLE (sin doctrine/dbal)
                //    Si ya es nullable, esto no afectará.
                DB::statement("
                    ALTER TABLE series
                    MODIFY es_default TINYINT(1) NULL
                ");

                // 2) Normalizar datos: es_default = NULL para los no-default
                DB::statement("
                    UPDATE series SET es_default = NULL WHERE es_default = 0
                ");

                // 3) Crear índice único compuesto; MySQL permite múltiples NULLs
                $exists = DB::table('information_schema.statistics')
                    ->where('table_schema', DB::getDatabaseName())
                    ->where('table_name', 'series')
                    ->where('index_name', 'IX_series_un_default_por_tipo')
                    ->exists();

                if (!$exists) {
                    DB::statement("
                        CREATE UNIQUE INDEX IX_series_un_default_por_tipo
                        ON series (tipo_documento_id, es_default)
                    ");
                }
                break;
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'sqlsrv':
                DB::statement("
                    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_series_un_default_por_tipo' AND object_id = OBJECT_ID('series'))
                        DROP INDEX IX_series_un_default_por_tipo ON series;
                ");
                break;

            case 'mysql':
            default:
                // Quitar índice único compuesto
                try { DB::statement("DROP INDEX IX_series_un_default_por_tipo ON series"); } catch (\Throwable $e) {}

                // (Opcional) volver es_default a NOT NULL DEFAULT 0
                // Si prefieres dejarlo nullable, elimina este bloque.
                try {
                    DB::statement("
                        UPDATE series SET es_default = 0 WHERE es_default IS NULL
                    ");
                    DB::statement("
                        ALTER TABLE series
                        MODIFY es_default TINYINT(1) NOT NULL DEFAULT 0
                    ");
                } catch (\Throwable $e) {
                    // Ignorar si no quieres revertir la nulabilidad
                }
                break;
        }
    }
};
