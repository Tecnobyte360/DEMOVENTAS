<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Si la columna no existe, la crea; si existe, ajusta el tipo.
        DB::statement("
IF OBJECT_ID(N'[movimientos]', N'U') IS NULL
    THROW 50000, 'La tabla [movimientos] no existe en el esquema por defecto.', 1;

-- detalle -> NVARCHAR(255)
IF COL_LENGTH('movimientos','detalle') IS NULL
    ALTER TABLE [movimientos] ADD [detalle] NVARCHAR(255) NULL;
ELSE
    ALTER TABLE [movimientos] ALTER COLUMN [detalle] NVARCHAR(255) NULL;

-- debe -> DECIMAL(18,2) NOT NULL
IF COL_LENGTH('movimientos','debe') IS NULL
    ALTER TABLE [movimientos] ADD [debe] DECIMAL(18,2) NOT NULL CONSTRAINT DF_mov_debe DEFAULT(0);
ELSE
    ALTER TABLE [movimientos] ALTER COLUMN [debe] DECIMAL(18,2) NOT NULL;

-- haber -> DECIMAL(18,2) NOT NULL
IF COL_LENGTH('movimientos','haber') IS NULL
    ALTER TABLE [movimientos] ADD [haber] DECIMAL(18,2) NOT NULL CONSTRAINT DF_mov_haber DEFAULT(0);
ELSE
    ALTER TABLE [movimientos] ALTER COLUMN [haber] DECIMAL(18,2) NOT NULL;
        ");
    }

    public function down(): void
    {
        // Opcional: revertir solo si lo necesitas (y si conoces el tipo anterior)
        // DB::statement("ALTER TABLE [movimientos] ALTER COLUMN [detalle] BIGINT NULL;");
        // DB::statement("ALTER TABLE [movimientos] ALTER COLUMN [debe]   DECIMAL(18,2) NULL;");
        // DB::statement("ALTER TABLE [movimientos] ALTER COLUMN [haber]  DECIMAL(18,2) NULL;");
    }
};
