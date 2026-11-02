<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\CRUD\Helpers\ModelHelpers\TranslatableColumnDefinition;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TranslatableColumnDefinitionTest extends TestCase
{
    public function test_mysql_string_length_charset_and_collation_are_preserved(): void
    {
        $this->assertSame(
            "\$table->string('en_title', 83)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();",
            TranslatableColumnDefinition::make([
                'name' => 'title', 'type_name' => 'varchar', 'type' => 'varchar(83)',
                'collation' => 'utf8mb4_unicode_ci', 'nullable' => false, 'default' => 'Original',
            ], 'en_title', 'mysql')
        );
    }

    public function test_text_sizes_and_fixed_length_character_columns_are_preserved(): void
    {
        foreach (['tinytext' => 'tinyText', 'text' => 'text', 'mediumtext' => 'mediumText', 'longtext' => 'longText'] as $type => $method) {
            $this->assertSame("\$table->{$method}('fr_body')->nullable();", TranslatableColumnDefinition::make([
                'name' => 'body', 'type_name' => $type, 'type' => $type,
            ], 'fr_body', 'mysql'));
        }

        $this->assertSame("\$table->char('en_code', 12)->nullable();", TranslatableColumnDefinition::make([
            'name' => 'code', 'type_name' => 'bpchar', 'type' => 'character(12)',
        ], 'en_code', 'pgsql'));
    }

    public function test_unsupported_types_fail_instead_of_silently_using_an_incorrect_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported base column type [json]');
        TranslatableColumnDefinition::make(['name' => 'content', 'type_name' => 'json', 'type' => 'json'], 'en_content', 'mysql');
    }
}
