<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\DbDescription;
use App\Models\TableDescription;
use App\Models\TableStructure;
use App\Models\TableIndex;
use App\Models\TableRelation;
use App\Models\ViewDescription;
use App\Models\ViewInformation;
use App\Models\ViewColumn;
use App\Models\PsDescription;
use App\Models\PsInformation;
use App\Models\PsParameter;
use App\Models\FunctionDescription;
use App\Models\FuncInformation;
use App\Models\FuncParameter;
use App\Models\TriggerDescription;
use App\Models\TriggerInformation;

class DatabaseStructureService
{
    /**
     * Extrait et sauvegarde toute la structure de la base de données
     *
     * @param string $connectionName Nom de la connexion à utiliser
     * @param int $dbId ID de l'entrée dans db_description
     * @return bool
     */

    private function formatDataType($column)
    {
        if (!isset($column->data_type)) {
            return 'unknown';
        }

        $type = $column->data_type;

        if (in_array(strtolower($type), ['varchar', 'nvarchar', 'char', 'nchar', 'binary', 'varbinary'])) {
            if (isset($column->max_length)) {
                // Pour nvarchar/nchar, la longueur est en caractères, pas en octets
                $maxLength = in_array(strtolower($type), ['nvarchar', 'nchar'])
                    ? $column->max_length / 2
                    : $column->max_length;

                $type .= "(" . ($maxLength == -1 ? 'MAX' : $maxLength) . ")";
            }
        } else if (in_array(strtolower($type), ['decimal', 'numeric'])) {
            if (isset($column->precision) && isset($column->scale)) {
                $type .= "({$column->precision},{$column->scale})";
            }
        }

        return $type;
    }

    // Assurez-vous d'avoir une méthode similaire pour MySQL si nécessaire
    private function formatMySqlDataType($column)
    {
        // Implémentation spécifique à MySQL si différente de formatDataType
        // Pour l'exemple, je vais juste appeler la fonction générique
        return $this->formatDataType($column);
    }

    public function extractAndSaveAllStructures($connectionName, $dbId)
    {
        try {
            // Déterminer le type de base de données pour adapter les requêtes
            $databaseType = DB::connection($connectionName)->getDriverName();
            Log::info("Extraction de la structure pour: {$databaseType}");

            // 1. Extraire et sauvegarder les tables
            $this->extractAndSaveTables($connectionName, $dbId, $databaseType);

            // 2. Extraire et sauvegarder les vues
            $this->extractAndSaveViews($connectionName, $dbId, $databaseType);

            // 3. Extraire et sauvegarder les fonctions
            $this->extractAndSaveFunctions($connectionName, $dbId, $databaseType);

            // 4. Extraire et sauvegarder les procédures stockées
            $this->extractAndSaveProcedures($connectionName, $dbId, $databaseType);

            // 5. Extraire et sauvegarder les triggers
            $this->extractAndSaveTriggers($connectionName, $dbId, $databaseType);

            Log::info("Extraction de la structure terminée avec succès pour la base de données ID: {$dbId}");

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveAllStructures', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Extrait et sauvegarde les tables de la base de données
     */
    private function extractAndSaveTables($connectionName, $dbId, $databaseType)
    {
        try {
            Log::info('Début extraction des tables...');

            if ($databaseType === 'sqlsrv') {
                $this->extractAndSaveSqlServerTables($connectionName, $dbId);
            } elseif ($databaseType === 'mysql') {
                $this->extractAndSaveMySqlTables($connectionName, $dbId);
            } elseif ($databaseType === 'pgsql') {
                $this->extractAndSavePostgreSqlTables($connectionName, $dbId);
            } else {
                Log::warning('Type de base de données non supporté pour l\'extraction des tables', ['type' => $databaseType]);
            }

            Log::info('Fin extraction des tables');
        } catch (\Exception $e) {
            Log::error('Erreur dans extractAndSaveTables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Extrait et sauvegarde les tables de SQL Server
     */
    private function extractAndSaveSqlServerTables($connectionName, $dbId)
    {
        try {
            // Récupérer la liste des tables
            $tables = DB::connection($connectionName)->select("
                SELECT
                    t.name AS table_name,
                    s.name AS schema_name,
                    ISNULL(CONVERT(VARCHAR(8000), ep.value), '') AS description,
                    CONVERT(VARCHAR(50), SCHEMA_NAME(t.schema_id) + '.' + t.name) AS full_name,
                    t.create_date,
                    t.modify_date
                FROM
                    sys.tables t
                INNER JOIN
                    sys.schemas s ON t.schema_id = s.schema_id
                LEFT JOIN
                    sys.extended_properties ep ON ep.major_id = t.object_id
                    AND ep.minor_id = 0
                    AND ep.name = 'MS_Description'
                WHERE
                    t.is_ms_shipped = 0
                ORDER BY
                    s.name, t.name
            ");

            Log::info('Tables SQL Server trouvées: ' . count($tables));

            foreach ($tables as $table) {
                // ✅ Début de la transaction pour chaque table
                DB::transaction(function () use ($table, $dbId, $connectionName) {
                    try {
                        // Créer ou mettre à jour l'entrée dans table_description
                        $tableDescription = TableDescription::updateOrCreate(
                            [
                                'dbid' => $dbId,
                                'tablename' => $table->table_name
                            ],
                            [
                                'language' => 'en',
                                'description' => $table->description ?? null,
                                'updated_at' => now()
                            ]
                        );

                        // --- Colonnes ---
                        // Récupérer les colonnes de la table
                        $columns = DB::connection($connectionName)->select("
                            SELECT
                                c.name AS column_name,
                                t.name AS data_type,
                                c.max_length,
                                c.precision,
                                c.scale,
                                c.is_nullable,
                                CASE WHEN pk.column_id IS NOT NULL THEN 'PK' ELSE '' END AS key_type,
                                ISNULL(CONVERT(VARCHAR(8000), ep.value), '') AS description
                            FROM
                                sys.columns c
                            INNER JOIN
                                sys.types t ON c.user_type_id = t.user_type_id
                            INNER JOIN
                                sys.tables tbl ON c.object_id = tbl.object_id
                            LEFT JOIN
                                (SELECT
                                    ic.column_id, ic.object_id
                                   FROM
                                     sys.indexes i
                                   INNER JOIN
                                     sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
                                   WHERE
                                     i.is_primary_key = 1) pk
                                ON pk.column_id = c.column_id AND pk.object_id = c.object_id
                            LEFT JOIN
                                sys.extended_properties ep ON ep.major_id = c.object_id
                                AND ep.minor_id = c.column_id
                                AND ep.name = 'MS_Description'
                            WHERE
                                tbl.name = ?
                            ORDER BY
                                c.column_id
                        ", [$table->table_name]);

                        // Supprimer les anciennes colonnes pour cette table
                        TableStructure::where('id_table', $tableDescription->id)->delete();

                        // ✅ Préparer les données pour l'insertion en masse des colonnes
                        $columnsToInsert = [];
                        foreach ($columns as $column) {
                            $dataType = $this->formatDataType($column);
                            $columnsToInsert[] = [
                                'id_table' => $tableDescription->id,
                                'column' => $column->column_name,
                                'type' => $dataType,
                                'nullable' => $column->is_nullable ? 1 : 0,
                                'key' => $column->key_type,
                                'description' => $column->description ?? null,
                                'created_at' => now(), // Ajout des timestamps
                                'updated_at' => now(),
                            ];
                        }
                        // ✅ Insérer les colonnes en masse
                        if (!empty($columnsToInsert)) {
                            TableStructure::insert($columnsToInsert);
                        }

                        // --- Index ---
                        // Récupérer les index de la table
                        $indexes = DB::connection($connectionName)->select("
                            SELECT
                                i.name AS index_name,
                                i.type_desc AS index_type,
                                (
                                    SELECT STRING_AGG(c2.name, ', ')
                                    FROM sys.index_columns ic2
                                    INNER JOIN sys.columns c2 ON ic2.object_id = c2.object_id AND ic2.column_id = c2.column_id
                                    WHERE ic2.object_id = i.object_id AND ic2.index_id = i.index_id
                                ) AS column_names,
                                i.is_unique,
                                i.is_primary_key
                            FROM
                                sys.indexes i
                            INNER JOIN
                                sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
                            INNER JOIN
                                sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
                            INNER JOIN
                                sys.tables t ON i.object_id = t.object_id
                            WHERE
                                t.name = ?
                                AND i.name IS NOT NULL
                            GROUP BY
                                i.name, i.type_desc, i.is_unique, i.is_primary_key, i.object_id, i.index_id
                            ORDER BY
                                i.is_primary_key DESC, i.name
                        ", [$table->table_name]);

                        // Supprimer les anciens index pour cette table
                        TableIndex::where('id_table', $tableDescription->id)->delete();

                        // ✅ Préparer les données pour l'insertion en masse des index
                        $indexesToInsert = [];
                        foreach ($indexes as $index) {
                            $properties = [];
                            if ($index->is_primary_key) $properties[] = 'PRIMARY KEY';
                            if ($index->is_unique) $properties[] = 'UNIQUE';

                            $indexesToInsert[] = [
                                'id_table' => $tableDescription->id,
                                'name' => $index->index_name,
                                'type' => $index->index_type,
                                'column' => $index->column_names,
                                'properties' => implode(', ', $properties),
                                'created_at' => now(), // Ajout des timestamps
                                'updated_at' => now(),
                            ];
                        }
                        // ✅ Insérer les index en masse
                        if (!empty($indexesToInsert)) {
                            TableIndex::insert($indexesToInsert);
                        }

                        // --- Relations de clés étrangères ---
                        // Récupérer les relations de clés étrangères
                        $foreignKeys = DB::connection($connectionName)->select("
                            SELECT
                                fk.name AS constraint_name,
                                COL_NAME(fkc.parent_object_id, fkc.parent_column_id) AS column_name,
                                OBJECT_NAME(fkc.referenced_object_id) AS referenced_table,
                                COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) AS referenced_column,
                                CASE
                                    WHEN fk.delete_referential_action = 1 THEN 'CASCADE'
                                    WHEN fk.delete_referential_action = 2 THEN 'SET NULL'
                                    WHEN fk.delete_referential_action = 3 THEN 'SET DEFAULT'
                                    ELSE 'NO ACTION'
                                END AS delete_action
                            FROM
                                sys.foreign_keys fk
                            INNER JOIN
                                sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
                            INNER JOIN
                                sys.tables t ON fk.parent_object_id = t.object_id
                            WHERE
                                t.name = ?
                            ORDER BY
                                fk.name
                        ", [$table->table_name]);

                        // Supprimer les anciennes relations pour cette table
                        TableRelation::where('id_table', $tableDescription->id)->delete();

                        // ✅ Préparer les données pour l'insertion en masse des relations
                        $foreignKeysToInsert = [];
                        foreach ($foreignKeys as $fk) {
                            $foreignKeysToInsert[] = [
                                'id_table' => $tableDescription->id,
                                'constraints' => $fk->constraint_name,
                                'column' => $fk->column_name,
                                'referenced_table' => $fk->referenced_table,
                                'referenced_column' => $fk->referenced_column,
                                'action' => $fk->delete_action,
                                'created_at' => now(), // Ajout des timestamps
                                'updated_at' => now(),
                            ];
                        }
                        // ✅ Insérer les relations en masse
                        if (!empty($foreignKeysToInsert)) {
                            TableRelation::insert($foreignKeysToInsert);
                        }

                        // Log pour chaque table, mais à un niveau moins verbeux si la performance est critique
                        // Log::info('Table extraite et sauvegardée: ' . $table->table_name);

                    } catch (\Exception $e) {
                        Log::error('Erreur lors de l\'extraction et sauvegarde d\'une table SQL Server', [
                            'table' => $table->table_name,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Re-lancer l'exception pour que la transaction soit rollbackée
                        throw $e;
                    }
                }); // ✅ Fin de la transaction pour chaque table
            }
            Log::info('Toutes les tables SQL Server ont été traitées.'); // Log récapitulatif
        } catch (\Exception $e) {
            Log::error('Erreur globale dans extractAndSaveSqlServerTables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-lancer pour propager l'erreur
        }
    }

    // ... (Vos autres méthodes extractAndSaveMySqlTables, extractAndSavePostgreSqlTables, etc.
    // devront être mises à jour de la même manière avec transactions et insertions en masse)

    // Exemple pour extractAndSaveMySqlTables (structure similaire)
    private function extractAndSaveMySqlTables($connectionName, $dbId)
    {
        try {
            $database = DB::connection($connectionName)->getDatabaseName();

            $tables = DB::connection($connectionName)->select("
                SELECT
                    TABLE_NAME AS table_name,
                    TABLE_SCHEMA AS schema_name,
                    TABLE_COMMENT AS description,
                    CREATE_TIME AS create_date,
                    UPDATE_TIME AS modify_date
                FROM
                    INFORMATION_SCHEMA.TABLES
                WHERE
                    TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'
                ORDER BY
                    TABLE_NAME
            ", [$database]);

            Log::info('Tables MySQL trouvées: ' . count($tables));

            foreach ($tables as $table) {
                DB::transaction(function () use ($table, $dbId, $connectionName, $database) {
                    try {
                        $tableDescription = TableDescription::updateOrCreate(
                            [
                                'dbid' => $dbId,
                                'tablename' => $table->table_name
                            ],
                            [
                                'language' => 'en',
                                'description' => $table->description ?? null,
                                'updated_at' => now()
                            ]
                        );

                        // --- Colonnes ---
                        $columns = DB::connection($connectionName)->select("
                            SELECT
                                COLUMN_NAME AS column_name,
                                DATA_TYPE AS data_type,
                                CHARACTER_MAXIMUM_LENGTH AS max_length,
                                NUMERIC_PRECISION AS precision,
                                NUMERIC_SCALE AS scale,
                                IS_NULLABLE = 'YES' AS is_nullable,
                                COLUMN_KEY AS key_type,
                                COLUMN_COMMENT AS description
                            FROM
                                INFORMATION_SCHEMA.COLUMNS
                            WHERE
                                TABLE_SCHEMA = ? AND TABLE_NAME = ?
                            ORDER BY
                                ORDINAL_POSITION
                        ", [$database, $table->table_name]);

                        TableStructure::where('id_table', $tableDescription->id)->delete();
                        $columnsToInsert = [];
                        foreach ($columns as $column) {
                            $keyType = '';
                            if ($column->key_type === 'PRI') $keyType = 'PK';
                            else if ($column->key_type === 'MUL') $keyType = 'FK';
                            else if ($column->key_type === 'UNI') $keyType = 'UK';
                            
                            $dataType = $this->formatMySqlDataType($column);

                            $columnsToInsert[] = [
                                'id_table' => $tableDescription->id,
                                'column' => $column->column_name,
                                'type' => $dataType,
                                'nullable' => $column->is_nullable ? 1 : 0,
                                'key' => $keyType,
                                'description' => $column->description ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        if (!empty($columnsToInsert)) {
                            TableStructure::insert($columnsToInsert);
                        }

                        // --- Index ---
                        $indexes = DB::connection($connectionName)->select("
                            SELECT
                                INDEX_NAME AS index_name,
                                CASE
                                    WHEN INDEX_TYPE = 'FULLTEXT' THEN 'FULLTEXT'
                                    WHEN NON_UNIQUE = 0 AND INDEX_NAME = 'PRIMARY' THEN 'PRIMARY KEY'
                                    WHEN NON_UNIQUE = 0 THEN 'UNIQUE'
                                    ELSE 'INDEX'
                                END AS index_type,
                                GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS column_names,
                                INDEX_NAME = 'PRIMARY' AS is_primary_key,
                                NON_UNIQUE = 0 AS is_unique
                            FROM
                                INFORMATION_SCHEMA.STATISTICS
                            WHERE
                                TABLE_SCHEMA = ? AND TABLE_NAME = ?
                            GROUP BY
                                INDEX_NAME, INDEX_TYPE, NON_UNIQUE
                            ORDER BY
                                INDEX_NAME
                        ", [$database, $table->table_name]);

                        TableIndex::where('id_table', $tableDescription->id)->delete();
                        $indexesToInsert = [];
                        foreach ($indexes as $index) {
                            $properties = [];
                            if ($index->is_primary_key) $properties[] = 'PRIMARY KEY';
                            if ($index->is_unique) $properties[] = 'UNIQUE';
                            $indexesToInsert[] = [
                                'id_table' => $tableDescription->id,
                                'name' => $index->index_name,
                                'type' => $index->index_type,
                                'column' => $index->column_names,
                                'properties' => implode(', ', $properties),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        if (!empty($indexesToInsert)) {
                            TableIndex::insert($indexesToInsert);
                        }

                        // --- Relations de clés étrangères ---
                        $foreignKeys = DB::connection($connectionName)->select("
                            SELECT
                                CONSTRAINT_NAME AS constraint_name,
                                COLUMN_NAME AS column_name,
                                REFERENCED_TABLE_NAME AS referenced_table,
                                REFERENCED_COLUMN_NAME AS referenced_column,
                                'CASCADE' AS delete_action -- MySQL doesn't directly expose ON DELETE action in KEY_COLUMN_USAGE, you might need to query INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS for precise action
                            FROM
                                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                            WHERE
                                TABLE_SCHEMA = ? AND TABLE_NAME = ?
                                AND REFERENCED_TABLE_NAME IS NOT NULL
                            ORDER BY
                                CONSTRAINT_NAME
                        ", [$database, $table->table_name]);

                        TableRelation::where('id_table', $tableDescription->id)->delete();
                        $foreignKeysToInsert = [];
                        foreach ($foreignKeys as $fk) {
                            $foreignKeysToInsert[] = [
                                'id_table' => $tableDescription->id,
                                'constraints' => $fk->constraint_name,
                                'column' => $fk->column_name,
                                'referenced_table' => $fk->referenced_table,
                                'referenced_column' => $fk->referenced_column,
                                'action' => $fk->delete_action,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        if (!empty($foreignKeysToInsert)) {
                            TableRelation::insert($foreignKeysToInsert);
                        }
                    } catch (\Exception $e) {
                        Log::error('Erreur lors de l\'extraction et sauvegarde d\'une table MySQL', [
                            'table' => $table->table_name,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                });
            }
            Log::info('Toutes les tables MySQL ont été traitées.');
        } catch (\Exception $e) {
            Log::error('Erreur globale dans extractAndSaveMySqlTables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    // ... (Vos autres méthodes extractAndSavePostgreSqlTables, extractAndSaveViews, extractAndSaveFunctions, extractAndSaveProcedures, extractAndSaveTriggers
    // devront être mises à jour de la même manière avec transactions et insertions en masse)

    /**
     * Extrait et sauvegarde les tables de PostgreSQL
     */
    private function extractAndSavePostgreSqlTables($connectionName, $dbId)
    {
        try {
            // Récupérer la liste des tables
            $tables = DB::connection($connectionName)->select("
                SELECT
                    c.relname AS table_name,
                    n.nspname AS schema_name,
                    COALESCE(d.description, '') AS description
                FROM
                    pg_class c
                JOIN
                    pg_namespace n ON n.oid = c.relnamespace
                LEFT JOIN
                    pg_description d ON d.objoid = c.oid AND d.objsubid = 0
                WHERE
                    c.relkind = 'r'
                    AND n.nspname NOT IN ('pg_catalog', 'information_schema')
                ORDER BY
                    n.nspname, c.relname
            ");

            Log::info('Tables PostgreSQL trouvées: ' . count($tables));

            foreach ($tables as $table) {
                DB::transaction(function () use ($table, $dbId, $connectionName) {
                    try {
                        // Créer une entrée dans table_description
                        $tableDescription = TableDescription::updateOrCreate(
                            [
                                'dbid' => $dbId,
                                'tablename' => $table->table_name
                            ],
                            [
                                'language' => 'en',
                                'description' => $table->description ?? null,
                                'updated_at' => now()
                            ]
                        );

                        // --- Colonnes ---
                        $columns = DB::connection($connectionName)->select("
                            SELECT
                                a.attname AS column_name,
                                pg_catalog.format_type(a.atttypid, a.atttypmod) AS data_type,
                                CASE
                                    WHEN a.atttypmod > 0 THEN a.atttypmod - 4
                                    ELSE NULL
                                END AS max_length,
                                NULL AS precision,
                                NULL AS scale,
                                NOT a.attnotnull AS is_nullable,
                                CASE
                                    WHEN pk.contype = 'p' THEN 'PK'
                                    WHEN fk.conname IS NOT NULL THEN 'FK'
                                    ELSE ''
                                END AS key_type,
                                COALESCE(d.description, '') AS description
                            FROM
                                pg_catalog.pg_attribute a
                            JOIN
                                pg_catalog.pg_class c ON c.oid = a.attrelid
                            JOIN
                                pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                            LEFT JOIN
                                pg_catalog.pg_description d ON d.objoid = a.attrelid AND d.objsubid = a.attnum
                            LEFT JOIN (
                                SELECT
                                    contype, conrelid, conkey, conname
                                FROM
                                    pg_catalog.pg_constraint
                                WHERE
                                    contype = 'p'
                            ) pk ON pk.conrelid = c.oid AND a.attnum = ANY(pk.conkey)
                            LEFT JOIN (
                                SELECT
                                    conname, conrelid, conkey
                                FROM
                                    pg_catalog.pg_constraint
                                WHERE
                                    contype = 'f'
                            ) fk ON fk.conrelid = c.oid AND a.attnum = ANY(fk.conkey)
                            WHERE
                                c.relname = ?
                                AND n.nspname = ?
                                AND a.attnum > 0
                                AND NOT a.attisdropped
                            ORDER BY
                                a.attnum
                        ", [$table->table_name, $table->schema_name]);

                        TableStructure::where('id_table', $tableDescription->id)->delete();
                        $columnsToInsert = [];
                        foreach ($columns as $column) {
                            $columnsToInsert[] = [
                                'id_table' => $tableDescription->id,
                                'column' => $column->column_name,
                                'type' => $column->data_type, // PostgreSQL format_type already includes length/precision
                                'nullable' => $column->is_nullable ? 1 : 0,
                                'key' => $column->key_type,
                                'description' => $column->description ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        if (!empty($columnsToInsert)) {
                            TableStructure::insert($columnsToInsert);
                        }

                        // --- Index ---
                        $indexes = DB::connection($connectionName)->select("
                            SELECT
                                i.relname AS index_name,
                                am.amname AS index_type,
                                array_to_string(array_agg(a.attname ORDER BY indseq.ord), ', ') AS column_names,
                                ix.indisprimary AS is_primary_key,
                                ix.indisunique AS is_unique
                            FROM
                                pg_index ix
                            JOIN
                                pg_class i ON i.oid = ix.indexrelid
                            JOIN
                                pg_class t ON t.oid = ix.indrelid
                            JOIN
                                pg_am am ON am.oid = i.relam
                            JOIN
                                pg_namespace n ON n.oid = t.relnamespace
                            JOIN
                                pg_attribute a ON a.attrelid = t.oid
                            JOIN
                                LATERAL unnest(ix.indkey) WITH ORDINALITY AS indseq(key, ord)
                                ON a.attnum = indseq.key
                            WHERE
                                t.relname = ?
                                AND n.nspname = ?
                            GROUP BY
                                i.relname, am.amname, ix.indisprimary, ix.indisunique
                            ORDER BY
                                ix.indisprimary DESC, i.relname
                        ", [$table->table_name, $table->schema_name]);

                        TableIndex::where('id_table', $tableDescription->id)->delete();
                        $indexesToInsert = [];
                        foreach ($indexes as $index) {
                            $properties = [];
                            if ($index->is_primary_key) $properties[] = 'PRIMARY KEY';
                            if ($index->is_unique) $properties[] = 'UNIQUE';

                            $indexesToInsert[] = [
                                'id_table' => $tableDescription->id,
                                'name' => $index->index_name,
                                'type' => $index->index_type,
                                'column' => $index->column_names,
                                'properties' => implode(', ', $properties),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        if (!empty($indexesToInsert)) {
                            TableIndex::insert($indexesToInsert);
                        }

                        // --- Relations de clés étrangères ---
                        $foreignKeys = DB::connection($connectionName)->select("
                            SELECT
                                con.conname AS constraint_name,
                                att.attname AS column_name,
                                cl.relname AS referenced_table,
                                att2.attname AS referenced_column,
                                CASE con.confdeltype
                                    WHEN 'a' THEN 'NO ACTION'
                                    WHEN 'r' THEN 'RESTRICT'
                                    WHEN 'c' THEN 'CASCADE'
                                    WHEN 'n' THEN 'SET NULL'
                                    WHEN 'd' THEN 'SET DEFAULT'
                                END AS delete_action
                            FROM (
                                SELECT
                                    conname, conrelid, confrelid, conkey, confkey, confdeltype
                                FROM
                                    pg_constraint
                                WHERE
                                    contype = 'f'
                            ) con
                            JOIN
                                pg_class cl ON cl.oid = con.confrelid
                            JOIN
                                pg_class cl2 ON cl2.oid = con.conrelid
                            JOIN
                                pg_attribute att ON att.attrelid = con.conrelid AND att.attnum = ANY(con.conkey)
                            JOIN
                                pg_attribute att2 ON att2.attrelid = con.confrelid AND att2.attnum = ANY(con.confkey)
                            WHERE
                                cl2.relname = ?
                                AND con.conrelid = (SELECT oid FROM pg_class WHERE relname = ? AND relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = ?))
                            ORDER BY
                                con.conname
                        ", [$table->table_name, $table->table_name, $table->schema_name]);


                        TableRelation::where('id_table', $tableDescription->id)->delete();
                        $foreignKeysToInsert = [];
                        foreach ($foreignKeys as $fk) {
                            $foreignKeysToInsert[] = [
                                'id_table' => $tableDescription->id,
                                'constraints' => $fk->constraint_name,
                                'column' => $fk->column_name,
                                'referenced_table' => $fk->referenced_table,
                                'referenced_column' => $fk->referenced_column,
                                'action' => $fk->delete_action,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        if (!empty($foreignKeysToInsert)) {
                            TableRelation::insert($foreignKeysToInsert);
                        }
                    } catch (\Exception $e) {
                        Log::error('Erreur lors de l\'extraction et sauvegarde d\'une table PostgreSQL', [
                            'table' => $table->table_name,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                });
            }
            Log::info('Toutes les tables PostgreSQL ont été traitées.');
        } catch (\Exception $e) {
            Log::error('Erreur globale dans extractAndSavePostgreSqlTables', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function extractAndSaveViews($connectionName, $dbId, $databaseType)
    {
        Log::info('Début extraction des vues...');
        try {
            $views = []; // Récupérer les vues selon le databaseType
            if ($databaseType === 'sqlsrv') {
                // Requête SQL Server pour les vues
                $views = DB::connection($connectionName)->select("
                    SELECT
                        v.name AS view_name,
                        s.name AS schema_name,
                        OBJECT_DEFINITION(v.object_id) AS definition,
                        v.create_date,
                        v.modify_date,
                        ISNULL(CONVERT(VARCHAR(8000), ep.value), '') AS description
                    FROM
                        sys.views v
                    INNER JOIN
                        sys.schemas s ON v.schema_id = s.schema_id
                    LEFT JOIN
                        sys.extended_properties ep ON ep.major_id = v.object_id
                        AND ep.minor_id = 0
                        AND ep.name = 'MS_Description'
                    WHERE
                        v.is_ms_shipped = 0
                    ORDER BY
                        s.name, v.name
                ");
            } elseif ($databaseType === 'mysql') {
                $database = DB::connection($connectionName)->getDatabaseName();
                $views = DB::connection($connectionName)->select("
                    SELECT
                        TABLE_NAME AS view_name,
                        TABLE_SCHEMA AS schema_name,
                        VIEW_DEFINITION AS definition,
                        CREATE_TIME AS create_date,
                        UPDATE_TIME AS modify_date,
                        '' AS description -- MySQL views don't have direct comments like tables
                    FROM
                        INFORMATION_SCHEMA.VIEWS
                    WHERE
                        TABLE_SCHEMA = ?
                    ORDER BY
                        TABLE_NAME
                ", [$database]);
            } elseif ($databaseType === 'pgsql') {
                $views = DB::connection($connectionName)->select("
                    SELECT
                        c.relname AS view_name,
                        n.nspname AS schema_name,
                        pg_get_viewdef(c.oid, true) AS definition,
                        pg_catalog.pg_postmaster_start_time() AS create_date, -- Placeholder, PostgreSQL views don't have direct creation_date
                        pg_catalog.pg_postmaster_start_time() AS modify_date, -- Placeholder
                        COALESCE(d.description, '') AS description
                    FROM
                        pg_class c
                    JOIN
                        pg_namespace n ON n.oid = c.relnamespace
                    LEFT JOIN
                        pg_description d ON d.objoid = c.oid AND d.objsubid = 0
                    WHERE
                        c.relkind = 'v'
                        AND n.nspname NOT IN ('pg_catalog', 'information_schema')
                    ORDER BY
                        n.nspname, c.relname
                ");
            }

            foreach ($views as $view) {
                
                DB::transaction(function () use ($view, $dbId, $connectionName, $databaseType) {
                    try {
                        $viewDescription = ViewDescription::updateOrCreate(
                            [
                                'dbid' => $dbId,
                                'viewname' => $view->view_name
                            ],
                            [
                                'language' => ($databaseType === 'mysql' ? 'en' : 'fr'),
                                'description' => $view->description ?? null,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );

                        // Save ViewInformation
                        $viewInfo = ViewInformation::updateOrCreate(
                            ['id_view' => $viewDescription->id],
                            [
                                'schema_name' => $view->schema_name,
                                'definition' => $view->definition,
                                'creation_date' => $view->create_date,
                                'last_change_date' => $view->modify_date,
                                'updated_at' => now()
                            ]
                        );

                        // --- View Columns ---
                        $viewColumns = [];
                        if ($databaseType === 'sqlsrv') {
                            $viewColumns = DB::connection($connectionName)->select("
                                SELECT
                                    c.name AS column_name,
                                    t.name AS data_type,
                                    c.max_length,
                                    c.precision,
                                    c.scale,
                                    c.is_nullable,
                                    ISNULL(CONVERT(VARCHAR(8000), ep.value), '') AS description
                                FROM
                                    sys.columns c
                                INNER JOIN
                                    sys.types t ON c.user_type_id = t.user_type_id
                                INNER JOIN
                                    sys.views v ON c.object_id = v.object_id
                                LEFT JOIN
                                    sys.extended_properties ep ON ep.major_id = c.object_id
                                    AND ep.minor_id = c.column_id
                                    AND ep.name = 'MS_Description'
                                WHERE
                                    v.name = ?
                                ORDER BY
                                    c.column_id
                            ", [$view->view_name]);
                        } elseif ($databaseType === 'mysql') {
                            $database = DB::connection($connectionName)->getDatabaseName();
                            $viewColumns = DB::connection($connectionName)->select("
                                SELECT
                                    COLUMN_NAME AS column_name,
                                    DATA_TYPE AS data_type,
                                    CHARACTER_MAXIMUM_LENGTH AS max_length,
                                    NUMERIC_PRECISION AS precision,
                                    NUMERIC_SCALE AS scale,
                                    IS_NULLABLE = 'YES' AS is_nullable,
                                    '' AS description -- MySQL columns in views don't have direct comments
                                FROM
                                    INFORMATION_SCHEMA.COLUMNS
                                WHERE
                                    TABLE_SCHEMA = ? AND TABLE_NAME = ?
                                ORDER BY
                                    ORDINAL_POSITION
                            ", [$database, $view->view_name]);
                        } elseif ($databaseType === 'pgsql') {
                            $viewColumns = DB::connection($connectionName)->select("
                                SELECT
                                    a.attname AS column_name,
                                    pg_catalog.format_type(a.atttypid, a.atttypmod) AS data_type,
                                    CASE
                                        WHEN a.atttypmod > 0 THEN a.atttypmod - 4
                                        ELSE NULL
                                    END AS max_length,
                                    NULL AS precision,
                                    NULL AS scale,
                                    NOT a.attnotnull AS is_nullable,
                                    COALESCE(d.description, '') AS description
                                FROM
                                    pg_catalog.pg_attribute a
                                JOIN
                                    pg_catalog.pg_class c ON c.oid = a.attrelid
                                JOIN
                                    pg_catalog.pg_namespace n ON n.oid = c.relnamespace
                                LEFT JOIN
                                    pg_catalog.pg_description d ON d.objoid = a.attrelid AND d.objsubid = a.attnum
                                WHERE
                                    c.relname = ?
                                    AND n.nspname = ?
                                    AND a.attnum > 0
                                    AND NOT a.attisdropped
                                ORDER BY
                                    a.attnum
                            ", [$view->view_name, $view->schema_name]);
                        }

                        // Suppression des anciennes colonnes pour cette vue (bonne pratique pour la synchronisation)
                        ViewColumn::where('id_view', $viewDescription->id)->delete();

                        $columnsToInsert = [];
                        foreach ($viewColumns as $column) {
                            $dataType = $this->formatDataType($column); // Use formatDataType for consistency
                            $columnsToInsert[] = [
                                'id_view' => $viewDescription->id,
                                'name' => $column->column_name,
                                'type' => $dataType,
                                'max_length' => $column->max_length ?? null,
                                'precision' => $column->precision ?? null,
                                'scale' => $column->scale ?? null,
                                'is_nullable' => $column->is_nullable ? 1 : 0,
                                'description' => $column->description ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        $chunkSize = 150;

                        if (!empty($columnsToInsert)) {
                            foreach (array_chunk($columnsToInsert, $chunkSize) as $chunk) {
                                ViewColumn::insert($chunk);
                            }
                        }

                    } catch (\Exception $e) {
                        Log::error('Erreur lors de l\'extraction et sauvegarde d\'une vue', [
                            'view' => $view->view_name,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        throw $e;
                    }
                });
            }
            Log::info('Fin extraction des vues.');
        } catch (\Exception $e) {
            Log::error('Erreur globale dans extractAndSaveViews', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // Exemple de structure pour extractAndSaveFunctions (à compléter)
    private function extractAndSaveFunctions($connectionName, $dbId, $databaseType)
    {
        Log::info('Début extraction des fonctions...');
        try {
            $functions = []; // Récupérer les fonctions selon le databaseType

            if ($databaseType === 'sqlsrv') {
                $functions = DB::connection($connectionName)->select("
                    SELECT
                        o.name AS function_name,
                        s.name AS schema_name,
                        o.type_desc AS function_type,
                        OBJECT_DEFINITION(o.object_id) AS definition,
                        o.create_date,
                        o.modify_date,
                        ISNULL(CONVERT(VARCHAR(8000), ep.value), '') AS description
                    FROM
                        sys.objects o
                    INNER JOIN
                        sys.schemas s ON o.schema_id = s.schema_id
                    LEFT JOIN
                        sys.extended_properties ep ON ep.major_id = o.object_id
                        AND ep.minor_id = 0
                        AND ep.name = 'MS_Description'
                    WHERE
                        o.type IN ('FN', 'IF', 'TF') -- FN: Scalar function, IF: Inline table-valued function, TF: Table-valued function
                        AND o.is_ms_shipped = 0
                    ORDER BY
                        s.name, o.name
                ");
            } elseif ($databaseType === 'mysql') {
                $database = DB::connection($connectionName)->getDatabaseName();
                $functions = DB::connection($connectionName)->select("
                    SELECT
                        ROUTINE_NAME AS function_name,
                        ROUTINE_SCHEMA AS schema_name,
                        ROUTINE_TYPE AS function_type, -- 'FUNCTION'
                        ROUTINE_DEFINITION AS definition,
                        CREATED AS create_date,
                        LAST_ALTERED AS modify_date,
                        ROUTINE_COMMENT AS description
                    FROM
                        INFORMATION_SCHEMA.ROUTINES
                    WHERE
                        ROUTINE_SCHEMA = ? AND ROUTINE_TYPE = 'FUNCTION'
                    ORDER BY
                        ROUTINE_NAME
                ", [$database]);
            } elseif ($databaseType === 'pgsql') {
                $functions = DB::connection($connectionName)->select("
                    SELECT
                        p.proname AS function_name,
                        n.nspname AS schema_name,
                        CASE p.proretset WHEN TRUE THEN 'Table-valued function' ELSE 'Scalar function' END AS function_type,
                        pg_get_functiondef(p.oid) AS definition,
                        NULL AS create_date, -- PostgreSQL functions don't have direct creation_date
                        NULL AS modify_date, -- PostgreSQL functions don't have direct modify_date
                        COALESCE(d.description, '') AS description,
                        pg_catalog.format_type(p.prorettype, NULL) AS return_type
                    FROM
                        pg_proc p
                    JOIN
                        pg_namespace n ON n.oid = p.pronamespace
                    LEFT JOIN
                        pg_description d ON d.objoid = p.oid
                    WHERE
                        p.prokind = 'f' -- 'f' for function, 'p' for procedure
                        AND n.nspname NOT IN ('pg_catalog', 'information_schema')
                    ORDER BY
                        n.nspname, p.proname
                ");
            }

            foreach ($functions as $function) {
                DB::transaction(function () use ($function, $dbId, $connectionName, $databaseType) {
                    try {
                        $functionDescription = FunctionDescription::updateOrCreate(
                            [
                                'dbid' => $dbId,
                                'functionname' => $function->function_name
                            ],
                            [
                                'language' => ($databaseType === 'mysql' ? 'en' : 'fr'),
                                'description' => $function->description ?? null,
                                'updated_at' => now()
                            ]
                        );

                        // Save FuncInformation
                        $funcInfo = FuncInformation::updateOrCreate(
                            ['id_func' => $functionDescription->id],
                            [
                                'type' => $function->function_type,
                                'return_type' => $function->return_type ?? null, // Add return_type for PostgreSQL
                                'definition' => $function->definition,
                                'creation_date' => $function->create_date,
                                'last_change_date' => $function->modify_date,
                                'updated_at' => now()
                            ]
                        );

                        // --- Function Parameters ---
                        $parameters = [];
                        if ($databaseType === 'sqlsrv') {
                            $parameters = DB::connection($connectionName)->select("
                                SELECT
                                    p.name AS parameter_name,
                                    TYPE_NAME(p.user_type_id) AS data_type,
                                    CASE WHEN p.is_output = 1 THEN 'OUTPUT' ELSE 'INPUT' END AS output_type,
                                    ISNULL(CONVERT(VARCHAR(8000), ep.value), '') AS description
                                FROM
                                    sys.parameters p
                                INNER JOIN
                                    sys.objects o ON p.object_id = o.object_id
                                LEFT JOIN
                                    sys.extended_properties ep ON ep.major_id = p.object_id
                                    AND ep.minor_id = p.parameter_id
                                    AND ep.name = 'MS_Description'
                                WHERE
                                    o.name = ?
                                ORDER BY
                                    p.parameter_id
                            ", [$function->function_name]);
                        } elseif ($databaseType === 'mysql') {
                            $database = DB::connection($connectionName)->getDatabaseName();
                            $parameters = DB::connection($connectionName)->select("
                                SELECT
                                    PARAMETER_NAME AS parameter_name,
                                    DTD_IDENTIFIER AS data_type,
                                    PARAMETER_MODE AS output_type,
                                    '' AS description -- MySQL parameters don't have direct comments
                                FROM
                                    INFORMATION_SCHEMA.PARAMETERS
                                WHERE
                                    SPECIFIC_SCHEMA = ?
                                    AND SPECIFIC_NAME = ?
                                    AND ROUTINE_TYPE = 'FUNCTION'
                                ORDER BY
                                    ORDINAL_POSITION
                            ", [$database, $function->function_name]);
                        } elseif ($databaseType === 'pgsql') {
                            $parameters = DB::connection($connectionName)->select("
                                SELECT
                                    unnest(p.proargnames) AS parameter_name,
                                    pg_catalog.format_type(unnest(p.proargtypes), NULL) AS data_type,
                                    'INPUT' AS output_type, -- PostgreSQL doesn't have direct OUTPUT parameters for functions in this view
                                    COALESCE(d.description, '') AS description
                                FROM
                                    pg_proc p
                                JOIN
                                    pg_namespace n ON n.oid = p.pronamespace
                                LEFT JOIN
                                    pg_description d ON d.objoid = p.oid AND d.objsubid = (SELECT unnest(p.proargnames) WHERE unnest = unnest(p.proargnames))
                                WHERE
                                    p.proname = ?
                                    AND n.nspname = ?
                                ORDER BY
                                    array_position(p.proargnames, unnest(p.proargnames))
                            ", [$function->function_name, $function->schema_name]);
                        }

                        FuncParameter::where('id_func', $functionDescription->id)->delete();
                        $parametersToInsert = [];
                        foreach ($parameters as $param) {
                            $parametersToInsert[] = [
                                'id_func' => $functionDescription->id,
                                'name' => $param->parameter_name,
                                'type' => $param->data_type,
                                'output' => $param->output_type,
                                'definition' => $param->description ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        if (!empty($parametersToInsert)) {
                            FuncParameter::insert($parametersToInsert);
                        }

                    } catch (\Exception $e) {
                        Log::error('Erreur lors de l\'extraction et sauvegarde d\'une fonction', [
                            'function' => $function->function_name,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                });
            }
            Log::info('Fin extraction des fonctions.');
        } catch (\Exception $e) {
            Log::error('Erreur globale dans extractAndSaveFunctions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // Exemple de structure pour extractAndSaveProcedures (à compléter)
    private function extractAndSaveProcedures($connectionName, $dbId, $databaseType)
    {
        Log::info('Début extraction des procédures stockées...');
        try {
            $procedures = []; // Récupérer les procédures selon le databaseType

            if ($databaseType === 'sqlsrv') {
                $procedures = DB::connection($connectionName)->select("
                    SELECT
                        o.name AS procedure_name,
                        s.name AS schema_name,
                        OBJECT_DEFINITION(o.object_id) AS definition,
                        o.create_date,
                        o.modify_date,
                        ISNULL(CONVERT(VARCHAR(8000), ep.value), '') AS description
                    FROM
                        sys.objects o
                    INNER JOIN
                        sys.schemas s ON o.schema_id = s.schema_id
                    LEFT JOIN
                        sys.extended_properties ep ON ep.major_id = o.object_id
                        AND ep.minor_id = 0
                        AND ep.name = 'MS_Description'
                    WHERE
                        o.type = 'P' -- P: Stored procedure
                        AND o.is_ms_shipped = 0
                    ORDER BY
                        s.name, o.name
                ");
            } elseif ($databaseType === 'mysql') {
                $database = DB::connection($connectionName)->getDatabaseName();
                $procedures = DB::connection($connectionName)->select("
                    SELECT
                        ROUTINE_NAME AS procedure_name,
                        ROUTINE_SCHEMA AS schema_name,
                        ROUTINE_TYPE AS procedure_type, -- 'PROCEDURE'
                        ROUTINE_DEFINITION AS definition,
                        CREATED AS create_date,
                        LAST_ALTERED AS modify_date,
                        ROUTINE_COMMENT AS description
                    FROM
                        INFORMATION_SCHEMA.ROUTINES
                    WHERE
                        ROUTINE_SCHEMA = ? AND ROUTINE_TYPE = 'PROCEDURE'
                    ORDER BY
                        ROUTINE_NAME
                ", [$database]);
            } elseif ($databaseType === 'pgsql') {
                $procedures = DB::connection($connectionName)->select("
                    SELECT
                        p.proname AS procedure_name,
                        n.nspname AS schema_name,
                        pg_get_functiondef(p.oid) AS definition,
                        NULL AS create_date, -- Placeholder
                        NULL AS modify_date, -- Placeholder
                        COALESCE(d.description, '') AS description
                    FROM
                        pg_proc p
                    JOIN
                        pg_namespace n ON n.oid = p.pronamespace
                    LEFT JOIN
                        pg_description d ON d.objoid = p.oid
                    WHERE
                        p.prokind = 'p' -- 'p' for procedure, 'f' for function
                        AND n.nspname NOT IN ('pg_catalog', 'information_schema')
                    ORDER BY
                        n.nspname, p.proname
                ");
            }

            foreach ($procedures as $procedure) {
                DB::transaction(function () use ($procedure, $dbId, $connectionName, $databaseType) {
                    try {
                        $psDescription = PsDescription::updateOrCreate(
                            [
                                'dbid' => $dbId,
                                'psname' => $procedure->procedure_name
                            ],
                            [
                                'language' => ($databaseType === 'mysql' ? 'en' : 'fr'),
                                'description' => $procedure->description ?? null,
                                'updated_at' => now()
                            ]
                        );

                        // Save PsInformation
                        $psInfo = PsInformation::updateOrCreate(
                            ['id_ps' => $psDescription->id],
                            [
                                'schema' => $procedure->schema_name,
                                'definition' => $procedure->definition,
                                'creation_date' => $procedure->create_date,
                                'last_change_date' => $procedure->modify_date,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );

                        // --- Procedure Parameters ---
                        $parameters = [];
                        if ($databaseType === 'sqlsrv') {
                            $parameters = DB::connection($connectionName)->select("
                                SELECT
                                    p.name AS parameter_name,
                                    TYPE_NAME(p.user_type_id) AS data_type,
                                    CASE WHEN p.is_output = 1 THEN 'OUTPUT' ELSE 'INPUT' END AS output_type,
                                    ISNULL(CONVERT(VARCHAR(8000), ep.value), '') AS description
                                FROM
                                    sys.parameters p
                                INNER JOIN
                                    sys.objects o ON p.object_id = o.object_id
                                LEFT JOIN
                                    sys.extended_properties ep ON ep.major_id = p.object_id
                                    AND ep.minor_id = p.parameter_id
                                    AND ep.name = 'MS_Description'
                                WHERE
                                    o.name = ?
                                ORDER BY
                                    p.parameter_id
                            ", [$procedure->procedure_name]);
                        } elseif ($databaseType === 'mysql') {
                            $database = DB::connection($connectionName)->getDatabaseName();
                            $parameters = DB::connection($connectionName)->select("
                                SELECT
                                    PARAMETER_NAME AS parameter_name,
                                    DTD_IDENTIFIER AS data_type,
                                    PARAMETER_MODE AS output_type,
                                    '' AS description -- MySQL parameters don't have direct comments
                                FROM
                                    INFORMATION_SCHEMA.PARAMETERS
                                WHERE
                                    SPECIFIC_SCHEMA = ?
                                    AND SPECIFIC_NAME = ?
                                    AND ROUTINE_TYPE = 'PROCEDURE'
                                ORDER BY
                                    ORDINAL_POSITION
                            ", [$database, $procedure->procedure_name]);
                        } elseif ($databaseType === 'pgsql') {
                            $parameters = DB::connection($connectionName)->select("
                                SELECT
                                    unnest(p.proargnames) AS parameter_name,
                                    pg_catalog.format_type(unnest(p.proargtypes), NULL) AS data_type,
                                    'INPUT' AS output_type, -- PostgreSQL doesn't have direct OUTPUT parameters for procedures in this view
                                    COALESCE(d.description, '') AS description
                                FROM
                                    pg_proc p
                                JOIN
                                    pg_namespace n ON n.oid = p.pronamespace
                                LEFT JOIN
                                    pg_description d ON d.objoid = p.oid AND d.objsubid = (SELECT unnest(p.proargnames) WHERE unnest = unnest(p.proargnames)) -- This join is tricky, might need adjustment
                                WHERE
                                    p.proname = ?
                                    AND n.nspname = ?
                                ORDER BY
                                    array_position(p.proargnames, unnest(p.proargnames))
                            ", [$procedure->procedure_name, $procedure->schema_name]);
                        }

                        PsParameter::where('id_ps', $psDescription->id)->delete();
                        $parametersToInsert = [];
                        foreach ($parameters as $param) {
                            $parametersToInsert[] = [
                                'id_ps' => $psDescription->id,
                                'name' => $param->parameter_name,
                                'type' => $param->data_type,
                                'output' => $param->output_type,
                                'description' => $param->description ?? null,
                                'default_value' => "null",
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                        if (!empty($parametersToInsert)) {
                            PsParameter::insert($parametersToInsert);
                        }

                    } catch (\Exception $e) {
                        Log::error('Erreur lors de l\'extraction et sauvegarde d\'une procédure', [
                            'procedure' => $procedure->procedure_name,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                });
            }
            Log::info('Fin extraction des procédures stockées.');
        } catch (\Exception $e) {
            Log::error('Erreur globale dans extractAndSaveProcedures', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // Exemple de structure pour extractAndSaveTriggers (à compléter)
    private function extractAndSaveTriggers($connectionName, $dbId, $databaseType)
    {
        Log::info('Début extraction des triggers...');
        try {
            $triggers = []; // Récupérer les triggers selon le databaseType

            if ($databaseType === 'sqlsrv') {
                $triggers = DB::connection($connectionName)->select("
                    SELECT
                        t.name AS trigger_name,
                        OBJECT_NAME(t.parent_id) AS table_name,
                        SCHEMA_NAME(tbl.schema_id) AS schema_name,
                        OBJECT_DEFINITION(t.object_id) AS definition,
                        t.create_date,
                        t.modify_date,
                        CASE WHEN t.is_disabled = 1 THEN 1 ELSE 0 END AS is_disabled,
                        CASE
                            WHEN t.is_instead_of_trigger = 1 THEN 'INSTEAD OF'
                            ELSE 'AFTER'
                        END AS trigger_type,
                        (
                            SELECT STRING_AGG(tev.type_desc, ',')
                            FROM sys.trigger_events tev
                            WHERE tev.object_id = t.object_id
                        ) AS trigger_event,
                        ISNULL(CONVERT(VARCHAR(8000), ep.value), '') AS description
                    FROM
                        sys.triggers t
                    INNER JOIN
                        sys.tables tbl ON t.parent_id = tbl.object_id
                    INNER JOIN
                        sys.schemas s ON tbl.schema_id = s.schema_id
                    LEFT JOIN
                        sys.extended_properties ep ON ep.major_id = t.object_id
                        AND ep.minor_id = 0
                        AND ep.name = 'MS_Description'
                    WHERE
                        t.is_ms_shipped = 0
                    ORDER BY
                        t.name;
                ");
            } elseif ($databaseType === 'mysql') {
                $database = DB::connection($connectionName)->getDatabaseName();
                $triggers = DB::connection($connectionName)->select("
                    SELECT
                        TRIGGER_NAME AS trigger_name,
                        EVENT_OBJECT_TABLE AS table_name,
                        TRIGGER_SCHEMA AS schema_name,
                        ACTION_STATEMENT AS definition,
                        CREATED AS create_date,
                        NULL AS modify_date, -- MySQL doesn't have direct modify_date for triggers in INFORMATION_SCHEMA
                        '0' AS is_disabled, -- MySQL triggers are always enabled by default, unless explicitly disabled
                        ACTION_TIMING AS trigger_type, -- 'BEFORE' or 'AFTER'
                        EVENT_MANIPULATION AS trigger_event, -- 'INSERT', 'UPDATE', 'DELETE'
                        '' AS description -- MySQL triggers don't have direct comments
                    FROM
                        INFORMATION_SCHEMA.TRIGGERS
                    WHERE
                        TRIGGER_SCHEMA = ?
                    ORDER BY
                        TRIGGER_NAME
                ", [$database]);
            } elseif ($databaseType === 'pgsql') {
                $triggers = DB::connection($connectionName)->select("
                    SELECT
                        t.tgname AS trigger_name,
                        c.relname AS table_name,
                        n.nspname AS schema_name,
                        pg_get_triggerdef(t.oid) AS definition,
                        NULL AS create_date, -- Placeholder
                        NULL AS modify_date, -- Placeholder
                        CASE WHEN t.tgisenabled = 'D' THEN 1 ELSE 0 END AS is_disabled, -- 'D' for disabled
                        CASE t.tgtype & (1<<1) WHEN 0 THEN 'AFTER' ELSE 'BEFORE' END AS trigger_type, -- Bitmask for BEFORE/AFTER
                        CASE
                            WHEN t.tgtype & (1<<2) THEN 'INSERT'
                            WHEN t.tgtype & (1<<3) THEN 'DELETE'
                            WHEN t.tgtype & (1<<4) THEN 'UPDATE'
                            WHEN t.tgtype & (1<<5) THEN 'TRUNCATE' -- TRUNCATE is also an event
                            ELSE ''
                        END AS trigger_event,
                        COALESCE(d.description, '') AS description
                    FROM
                        pg_trigger t
                    JOIN
                        pg_class c ON c.oid = t.tgrelid
                    JOIN
                        pg_namespace n ON n.oid = c.relnamespace
                    LEFT JOIN
                        pg_description d ON d.objoid = t.oid
                    WHERE
                        NOT t.tgisinternal -- Exclude internal triggers
                        AND n.nspname NOT IN ('pg_catalog', 'information_schema')
                    ORDER BY
                        t.tgname
                ");
            }

            foreach ($triggers as $trigger) {
                DB::transaction(function () use ($trigger, $dbId, $connectionName, $databaseType) {
                    try {
                        $triggerDescription = TriggerDescription::updateOrCreate(
                            [
                                'dbid' => $dbId,
                                'triggername' => $trigger->trigger_name
                            ],
                            [
                                'language' => ($databaseType === 'mysql' ? 'en' : 'fr'),
                                'description' => $trigger->description ?? null,
                                'updated_at' => now()
                            ]
                        );

                        // Save TriggerInformation
                        $triggerInfo = TriggerInformation::updateOrCreate(
                            ['id_trigger' => $triggerDescription->id],
                            [
                                'table' => $trigger->table_name,
                                'schema' => $trigger->schema_name,
                                'type' => $trigger->trigger_type,
                                'event' => $trigger->trigger_event,
                                'is_disabled' => $trigger->is_disabled,
                                'definition' => $trigger->definition,
                                'creation_date' => $trigger->create_date,
                                'last_change_date' => $trigger->modify_date,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );

                    } catch (\Exception $e) {
                        Log::error('Erreur lors de l\'extraction et sauvegarde d\'un trigger', [
                            'trigger' => $trigger->trigger_name,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        throw $e;
                    }
                });
            }
            Log::info('Fin extraction des triggers.');
        } catch (\Exception $e) {
            Log::error('Erreur globale dans extractAndSaveTriggers', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}