<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class NamedEntryUniqueness
{
    public static function constraintField(string $message): ?string
    {
        foreach (self::DEFINITIONS as $area => $definition) {
            if (str_contains($message, '"entry_'.$area.'_ci_unique"')) {
                return $definition['column'];
            }
        }
        foreach (['list_roles_name_unique' => 'name', 'list_roles_slug_unique' => 'slug', 'list_courses_name_unique' => 'name'] as $constraint => $field) {
            if (str_contains($message, '"'.$constraint.'"')) {
                return $field;
            }
        }

        return null;
    }

    public const DEFINITIONS = [
        'roles' => ['table' => 'list_roles', 'column' => 'name', 'active' => true, 'scope' => []],
        'role_slugs' => ['table' => 'list_roles', 'column' => 'slug', 'active' => true, 'scope' => []],
        'courses' => ['table' => 'list_courses', 'column' => 'name', 'active' => true, 'scope' => []],
        'programs' => ['table' => 'list_programs', 'column' => 'name', 'active' => true, 'scope' => ['program_id' => 'id', 'type_id' => 'id']],
        'references' => ['table' => 'list_references', 'column' => 'name', 'active' => true, 'scope' => ['classification' => 'text', 'type' => 'text']],
        'statuses' => ['table' => 'list_statuses', 'column' => 'name', 'active' => true, 'scope' => ['type' => 'text']],
        'document_categories' => ['table' => 'document_categories', 'column' => 'name', 'active' => true, 'deleted_column' => 'deleted_at', 'scope' => []],
        'navigation' => ['table' => 'list_routes', 'column' => 'label', 'active' => true, 'scope' => ['main_id' => 'parent']],
        'navigation_slugs' => ['table' => 'list_routes', 'column' => 'slug', 'active' => true, 'scope' => []],
        'schools' => ['table' => 'schools', 'column' => 'name', 'active' => true, 'scope' => []],
        'subjects' => ['table' => 'school_campus_course_curriculum_subjects', 'column' => 'subject_code', 'active' => true, 'scope' => ['curriculum_id' => 'id', 'semester_id' => 'id', 'year' => 'text']],
    ];

    public static function scopeExpression(string $column, string $type): string
    {
        return match ($type) {
            'text' => "LOWER(TRIM(COALESCE({$column}, '')))",
            'parent' => 'COALESCE(CASE WHEN is_submenu THEN main_id ELSE NULL END, -1)',
            default => "COALESCE({$column}, -1)",
        };
    }

    public static function query(string $area, string $value, array $context = [], int|string|null $ignoreId = null): Builder
    {
        $definition = self::DEFINITIONS[$area];
        $query = DB::connection('pgsql')->table($definition['table'])
            ->whereRaw("LOWER(TRIM({$definition['column']})) = LOWER(TRIM(?))", [$value]);
        if ($definition['active']) {
            if (($definition['deleted_column'] ?? null) === 'deleted_at') {
                $query->whereNull('deleted_at');
            } else {
                $query->where('is_delete', false);
            }
        }
        foreach ($definition['scope'] as $column => $type) {
            $expression = self::scopeExpression($column, $type);
            $query->whereRaw($expression.($type === 'text' ? ' = LOWER(TRIM(?))' : ' = ?'), [
                $context[$column] ?? ($type === 'text' ? '' : -1),
            ]);
        }
        if ($ignoreId !== null) {
            $query->where('id', '<>', $ignoreId);
        }

        return $query;
    }
}
