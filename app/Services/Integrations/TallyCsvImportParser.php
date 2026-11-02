<?php

namespace App\Services\Integrations;

use InvalidArgumentException;
use RuntimeException;

final class TallyCsvImportParser
{
    /**
     * Parse a mapped CSV without splitting quoted commas or quoted newlines.
     * Values are preserved as entered because accounting semantics belong to the mapping/API contract.
     *
     * @return array<int, array<string, string>>
     */
    public function parse(string $contents): array
    {
        if (trim($contents) === '') {
            throw new InvalidArgumentException('The CSV file is empty.');
        }

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new RuntimeException('Unable to create a CSV parsing stream.');
        }

        try {
            fwrite($stream, $contents);
            rewind($stream);

            $headers = fgetcsv($stream, null, ',', '"', '');
            if ($headers === false || $headers === [null]) {
                throw new InvalidArgumentException('The CSV file must contain a header row.');
            }

            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]) ?? (string) $headers[0];
            $headers = array_map(static fn ($header): string => trim((string) $header), $headers);

            if (in_array('', $headers, true)) {
                throw new InvalidArgumentException('CSV headers cannot be empty.');
            }

            if (count(array_unique($headers)) !== count($headers)) {
                throw new InvalidArgumentException('CSV headers must be unique.');
            }

            $rows = [];
            $lineNumber = 1;

            while (($values = fgetcsv($stream, null, ',', '"', '')) !== false) {
                $lineNumber++;

                if ($values === [null]) {
                    continue;
                }

                if (count($values) !== count($headers)) {
                    throw new InvalidArgumentException("CSV row {$lineNumber} has a different number of columns than the header.");
                }

                $rows[] = array_combine($headers, array_map(static fn ($value): string => (string) $value, $values));
            }

            if ($rows === []) {
                throw new InvalidArgumentException('The CSV file contains no data rows.');
            }

            return $rows;
        } finally {
            fclose($stream);
        }
    }
}
