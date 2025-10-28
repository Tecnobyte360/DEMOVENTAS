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

                // Índice único filtrado: 1 sola serie por tipo_documento marcada como default
                DB::statement("
                    CREATE UNIQUE INDEX IX_series_un_default_por_tipo
                    ON series (tipo_documento_id)
                    WHERE es_default = 1;
                ");
                break;

            // ==================== MYSQL / MARIADB ====================
            case 'mysql':
            default:
                // Limpia índices viejos si existieran (ignora errores)
                try { DB::statement("DROP INDEX IX_series_default_por_documento ON series"); } catch (\Throwable $e) {}
                try { DB::statement("DROP INDEX IX_series_un_default_por_tipo ON series"); } catch (\Throwable $e) {}

                // Asegura columna auxiliar (normal, no generada) con el MISMO tipo que la FK
                if (!Schema::hasColumn('series', 'default_key')) {
                    // BIGINT UNSIGNED para empatar con foreignId()
                    DB::statement("ALTER TABLE series ADD COLUMN default_key BIGINT UNSIGNED NULL AFTER tipo_documento_id");
                }

                // Backfill inicial
                DB::statement("
                    UPDATE series
                    SET default_key = CASE WHEN es_default = 1 THEN tipo_documento_id ELSE NULL END
                ");

                // Triggers para mantener default_key sincronizada
                // (Elimina si ya existen para evitar errores)
                try { DB::statement("DROP TRIGGER IF EXISTS trg_series_bi_default_key"); } catch (\Throwable $e) {}
                try { DB::statement("DROP TRIGGER IF EXISTS trg_series_bu_default_key"); } catch (\Throwable $e) {}

                DB::statement("
                    CREATE TRIGGER trg_series_bi_default_key
                    BEFORE INSERT ON series
                    FOR EACH ROW
                    BEGIN
                        SET NEW.default_key = CASE
                            WHEN NEW.es_default = 1 THEN NEW.tipo_documento_id
                            ELSE NULL
                        END;
                    END
                ");

                DB::statement("
                    CREATE TRIGGER trg_series_bu_default_key
                    BEFORE UPDATE ON series
                    FOR EACH ROW
                    BEGIN
                        SET NEW.default_key = CASE
                            WHEN NEW.es_default = 1 THEN NEW.tipo_documento_id
                            ELSE NULL
                        END;
                    END
                ");

                // Crear índice único si no existe
                $exists = DB::table('information_schema.statistics')
                    ->where('table_schema', DB::getDatabaseName())
                    ->where('table_name', 'series')
                    ->where('index_name', 'IX_series_un_default_por_tipo')
                    ->exists();

                if (!$exists) {
                    DB::statement("CREATE UNIQUE INDEX IX_series_un_default_por_tipo ON series (default_key)");
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
                // Borra índice único
                try { DB::statement("DROP INDEX IX_series_un_default_por_tipo ON series"); } catch (\Throwable $e) {}

                // Borra triggers
                try { DB::statement("DROP TRIGGER IF EXISTS trg_series_bi_default_key"); } catch (\Throwable $e) {}
                try { DB::statement("DROP TRIGGER IF EXISTS trg_series_bu_default_key"); } catch (\Throwable $e) {}

                // Borra columna auxiliar
                if (Schema::hasColumn('series', 'default_key')) {
                    DB::statement("ALTER TABLE series DROP COLUMN default_key");
                }
                break;
        }
    }
};
