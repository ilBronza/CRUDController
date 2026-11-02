<?php

namespace IlBronza\CRUD\Helpers\ModelHelpers;

use InvalidArgumentException;

/** Convert inspected text columns into explicit, standalone migration code. */
final class TranslatableColumnDefinition
{
    public static function make(array $column, string $name, string $driver): string
    {
        $type = strtolower($column['type_name']);
        $fullType = strtolower($column['type']);
        $arguments = [var_export($name, true)];

        if (in_array($type, ['varchar', 'character varying', 'bpchar', 'char', 'character'], true)) {
            $method = in_array($type, ['char', 'character', 'bpchar'], true) ? 'char' : 'string';

            if (preg_match('/\((\d+)\)/', $fullType, $length)) {
                $arguments[] = $length[1];
            } elseif ($driver !== 'sqlite') {
                throw new InvalidArgumentException("Cannot determine the length of base column [{$column['name']}].");
            }
        } else {
            $method = [
                'tinytext' => 'tinyText',
                'text' => 'text',
                'mediumtext' => 'mediumText',
                'longtext' => 'longText',
            ][$type] ?? null;

            if ($method === null) {
                throw new InvalidArgumentException("Unsupported base column type [{$column['type']}] for [{$column['name']}]. Create this translation migration manually.");
            }
        }

        $definition = '$table->'.$method.'('.implode(', ', $arguments).')';

        if ($collation = $column['collation'] ?? null) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $definition .= '->charset('.var_export(explode('_', $collation, 2)[0], true).')';
            }

            $definition .= '->collation('.var_export($collation, true).')';
        }

        return $definition.'->nullable();';
    }
}
