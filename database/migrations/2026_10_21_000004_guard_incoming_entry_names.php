<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql';

    // Snapshot the identities protected by this migration, including their deletion fields.
    private const RULES = [
        'list_roles' => [
            ['area' => 'roles', 'column' => 'name', 'scope' => []],
            ['area' => 'role_slugs', 'column' => 'slug', 'scope' => []],
        ],
        'list_courses' => [['area' => 'courses', 'column' => 'name', 'scope' => []]],
        'list_programs' => [['area' => 'programs', 'column' => 'name', 'scope' => ['program_id' => 'id', 'type_id' => 'id']]],
        'list_references' => [['area' => 'references', 'column' => 'name', 'scope' => ['classification' => 'text', 'type' => 'text']]],
        'list_statuses' => [['area' => 'statuses', 'column' => 'name', 'scope' => ['type' => 'text']]],
        'document_categories' => [['area' => 'document_categories', 'column' => 'name', 'deleted_column' => 'deleted_at', 'scope' => []]],
        'list_routes' => [
            ['area' => 'navigation', 'column' => 'label', 'scope' => ['main_id' => 'parent']],
            ['area' => 'navigation_slugs', 'column' => 'slug', 'scope' => []],
        ],
        'schools' => [['area' => 'schools', 'column' => 'name', 'scope' => []]],
        'school_campus_course_curriculum_subjects' => [['area' => 'subjects', 'column' => 'subject_code', 'scope' => ['curriculum_id' => 'id', 'semester_id' => 'id', 'year' => 'text']]],
    ];

    public function up(): void
    {
        $db = DB::connection($this->getConnection());
        $db->statement('LOCK TABLE '.implode(', ', array_keys(self::RULES)).' IN ACCESS EXCLUSIVE MODE');
        $db->statement(<<<'SQL'
            CREATE TABLE entry_identity_guards (
                area VARCHAR(60) NOT NULL,
                identity_key CHAR(32) NOT NULL,
                active_count BIGINT NOT NULL CHECK (active_count >= 0),
                PRIMARY KEY (area, identity_key)
            )
        SQL);

        $db->unprepared(<<<'SQL'
            CREATE FUNCTION sims_entry_identity_keys(record_data JSONB, rules JSONB)
            RETURNS TABLE (area TEXT, identity_key TEXT, field TEXT)
            LANGUAGE plpgsql IMMUTABLE AS $$
            DECLARE
                rule JSONB;
                scope_column TEXT;
                scope_type TEXT;
                parts JSONB;
                value TEXT;
            BEGIN
                IF record_data IS NULL THEN RETURN; END IF;
                FOR rule IN SELECT * FROM jsonb_array_elements(rules) LOOP
                    IF rule->>'deleted_column' = 'deleted_at' THEN
                        IF record_data->>'deleted_at' IS NOT NULL THEN CONTINUE; END IF;
                    ELSIF (record_data->>'is_delete')::boolean IS DISTINCT FROM false THEN
                        CONTINUE;
                    END IF;
                    value := record_data->>(rule->>'column');
                    IF value IS NULL THEN CONTINUE; END IF;
                    parts := jsonb_build_array(lower(btrim(value)));
                    FOR scope_column, scope_type IN SELECT * FROM jsonb_each_text(rule->'scope') LOOP
                        IF scope_type = 'parent' THEN
                            value := CASE WHEN (record_data->>'is_submenu')::boolean THEN coalesce(record_data->>scope_column, '-1') ELSE '-1' END;
                        ELSIF scope_type = 'text' THEN
                            value := lower(btrim(coalesce(record_data->>scope_column, '')));
                        ELSE
                            value := coalesce(record_data->>scope_column, '-1');
                        END IF;
                        parts := parts || jsonb_build_array(value);
                    END LOOP;
                    RETURN QUERY SELECT rule->>'area', md5(parts::text), rule->>'column';
                END LOOP;
            END;
            $$
        SQL);

        $db->unprepared(<<<'SQL'
            CREATE FUNCTION sims_guard_entry_identities() RETURNS TRIGGER
            LANGUAGE plpgsql AS $$
            DECLARE
                old_data JSONB;
                new_data JSONB;
                changed RECORD;
                resulting_count BIGINT;
            BEGIN
                IF TG_OP <> 'INSERT' THEN old_data := to_jsonb(OLD); END IF;
                IF TG_OP <> 'DELETE' THEN new_data := to_jsonb(NEW); END IF;
                FOR changed IN
                    SELECT keys.area, keys.identity_key, keys.field, sum(keys.delta) AS delta
                    FROM (
                        SELECT k.*, -1 AS delta FROM sims_entry_identity_keys(old_data, TG_ARGV[0]::jsonb) k
                        UNION ALL
                        SELECT k.*, 1 AS delta FROM sims_entry_identity_keys(new_data, TG_ARGV[0]::jsonb) k
                    ) keys
                    GROUP BY keys.area, keys.identity_key, keys.field
                    HAVING sum(keys.delta) <> 0
                    ORDER BY keys.area, keys.identity_key
                LOOP
                    INSERT INTO entry_identity_guards (area, identity_key, active_count)
                    VALUES (changed.area, changed.identity_key, 0)
                    ON CONFLICT DO NOTHING;

                    -- This atomic update serializes competing claims without changing legacy duplicates.
                    UPDATE entry_identity_guards
                    SET active_count = active_count + changed.delta
                    WHERE area = changed.area AND identity_key = changed.identity_key
                        AND (changed.delta < 0 OR active_count = 0)
                    RETURNING active_count INTO resulting_count;
                    IF NOT FOUND THEN
                        RAISE EXCEPTION 'duplicate key value violates unique constraint "entry_%_ci_unique"', changed.area
                            USING ERRCODE = '23505', CONSTRAINT = 'entry_' || changed.area || '_ci_unique';
                    END IF;
                END LOOP;
                RETURN NULL;
            END;
            $$
        SQL);

        foreach (self::RULES as $table => $rules) {
            foreach ($rules as &$rule) {
                $rule['scope'] = (object) $rule['scope'];
            }
            unset($rule);
            $json = json_encode($rules, JSON_THROW_ON_ERROR);
            // Count existing identities, including duplicate groups; leave every source row intact.
            $db->statement("INSERT INTO entry_identity_guards (area, identity_key, active_count)
                SELECT k.area, k.identity_key, COUNT(*) FROM {$table} source
                CROSS JOIN LATERAL sims_entry_identity_keys(to_jsonb(source), ?::jsonb) k
                GROUP BY k.area, k.identity_key", [$json]);
            $quoted = $db->getPdo()->quote($json);
            $db->statement("CREATE TRIGGER sims_entry_identity_guard
                AFTER INSERT OR UPDATE OR DELETE ON {$table}
                FOR EACH ROW EXECUTE FUNCTION sims_guard_entry_identities({$quoted})");
        }

        // These older global rules wrongly reserve names belonging to soft-deleted entries.
        foreach (['list_roles' => ['list_roles_name_unique', 'list_roles_slug_unique'], 'schools' => ['schools_name_unique'], 'list_courses' => ['list_courses_name_unique']] as $table => $constraints) {
            foreach ($constraints as $constraint) {
                $db->statement("ALTER TABLE {$table} DROP CONSTRAINT IF EXISTS {$constraint}");
            }
        }
        foreach (['entry_roles_ci_unique', 'entry_role_slugs_ci_unique', 'entry_document_categories_ci_unique'] as $index) {
            $db->statement("DROP INDEX IF EXISTS {$index}");
        }
    }

    public function down(): void
    {
        $db = DB::connection($this->getConnection());
        // Reinstating old restrictions can fail if deleted names have since been reused.
        // Let PostgreSQL stop the rollback rather than alter or discard those records.
        $db->statement('ALTER TABLE list_roles ADD CONSTRAINT list_roles_name_unique UNIQUE (name)');
        $db->statement('ALTER TABLE list_roles ADD CONSTRAINT list_roles_slug_unique UNIQUE (slug)');
        $db->statement('ALTER TABLE schools ADD CONSTRAINT schools_name_unique UNIQUE (name)');
        $db->statement('CREATE UNIQUE INDEX entry_roles_ci_unique ON list_roles (LOWER(TRIM(name))) WHERE name IS NOT NULL');
        $db->statement('CREATE UNIQUE INDEX entry_role_slugs_ci_unique ON list_roles (LOWER(TRIM(slug))) WHERE slug IS NOT NULL');
        $db->statement('CREATE UNIQUE INDEX entry_document_categories_ci_unique ON document_categories (LOWER(TRIM(name))) WHERE name IS NOT NULL');
        foreach (array_keys(self::RULES) as $table) {
            $db->statement("DROP TRIGGER sims_entry_identity_guard ON {$table}");
        }
        $db->statement('DROP FUNCTION sims_guard_entry_identities()');
        $db->statement('DROP FUNCTION sims_entry_identity_keys(JSONB, JSONB)');
        $db->statement('DROP TABLE entry_identity_guards');
    }
};
