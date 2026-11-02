<?php

namespace Tests\Feature;

use App\Services\Integrations\TallyCsvImportParser;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TallyCsvImportParserTest extends TestCase
{
    public function test_it_preserves_quoted_commas_and_embedded_newlines(): void
    {
        $csv = "date,description,amount,reference\r\n2026-10-01,\"Office, supplies\",25.75,INV-7\r\n2026-10-02,\"Two lines\ncontinued\",10.00,INV-8\r\n";

        $rows = app(TallyCsvImportParser::class)->parse($csv);

        $this->assertSame([
            [
                'date' => '2026-10-01',
                'description' => 'Office, supplies',
                'amount' => '25.75',
                'reference' => 'INV-7',
            ],
            [
                'date' => '2026-10-02',
                'description' => "Two lines\ncontinued",
                'amount' => '10.00',
                'reference' => 'INV-8',
            ],
        ], $rows);
    }

    public function test_it_removes_utf8_bom_and_ignores_empty_rows(): void
    {
        $rows = app(TallyCsvImportParser::class)->parse("\xEF\xBB\xBFdate,amount\r\n2026-10-01,4.20\r\n\r\n");

        $this->assertSame([['date' => '2026-10-01', 'amount' => '4.20']], $rows);
    }

    #[DataProvider('invalidCsvProvider')]
    public function test_it_rejects_invalid_csv_shapes(string $csv, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        app(TallyCsvImportParser::class)->parse($csv);
    }

    public static function invalidCsvProvider(): array
    {
        return [
            'empty file' => ['', 'empty'],
            'empty header' => ["date,,amount\n2026-10-01,x,4.00", 'headers cannot be empty'],
            'duplicate header' => ["date,amount,amount\n2026-10-01,4,5", 'headers must be unique'],
            'wrong column count' => ["date,description,amount\n2026-10-01,only two", 'different number of columns'],
            'header only' => ["date,amount\n", 'no data rows'],
        ];
    }
}
