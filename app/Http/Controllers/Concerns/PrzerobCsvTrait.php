<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\File;

trait PrzerobCsvTrait
{
    protected function przerobCsvHeaders(string $path): array
    {
        $lines = $this->readCsvLines($path);
        if (empty($lines)) {
            return [[], []];
        }

        $headerRow = str_getcsv(array_shift($lines), ';');
        $uniqueHeaders = $this->makeUniqueHeaders($headerRow);
        $outputLines = [implode(';', $uniqueHeaders)];

        foreach ($lines as $line) {
            $cols = str_getcsv($line, ';');
            $cols = array_map(fn ($c) => $this->usunSpacjeWTysiacach($c), $cols);
            $outputLines[] = implode(';', $cols);
        }

        return [$outputLines, $uniqueHeaders];
    }

    protected function usunSpacjeWTysiacach(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            return $value;
        }
        if (preg_match('/^[\d\s,.\-]+$/', $v)) {
            return str_replace(' ', '', $v);
        }
        return $value;
    }

    protected function makeUniqueHeaders(array $headers): array
    {
        $counts = [];
        $result = [];
        $kolumnaNum = 0;
        foreach ($headers as $h) {
            $base = trim($h);
            if ($base === '') {
                $kolumnaNum++;
                $result[] = 'kolumna' . $kolumnaNum;
                continue;
            }
            $key = mb_strtolower($base);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
            $num = $counts[$key];
            $result[] = $num > 1 ? $base . $num : $base;
        }
        return $result;
    }

    protected function readCsvLines(string $path): array
    {
        $rawContent = file_get_contents($path);
        $lines = preg_split('/\r\n|\r|\n/', $rawContent);
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        if (empty($lines)) {
            return [];
        }

        $content = implode("\n", $lines);
        if (mb_check_encoding($content, 'UTF-8')) {
            return $lines;
        }

        foreach (['Windows-1250', 'CP1250', 'ISO-8859-2'] as $enc) {
            $converted = [];
            $ok = true;
            foreach ($lines as $line) {
                $conv = @iconv($enc, 'UTF-8', $line);
                if ($conv === false) {
                    $ok = false;
                    break;
                }
                $converted[] = $conv;
            }
            if ($ok && ! empty($converted) && mb_check_encoding(implode('', $converted), 'UTF-8')) {
                return $converted;
            }
        }

        return $lines;
    }

    protected function parseAmount(string $value): float
    {
        $value = trim(str_replace(',', '.', $value));
        return $value === '' ? 0.0 : (float) $value;
    }
}
