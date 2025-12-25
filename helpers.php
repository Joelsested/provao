<?php

if (!function_exists('digitsOnly')) {
    function digitsOnly(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }
}

if (!function_exists('parseDateParts')) {
    function parseDateParts(string $value)
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches) && checkdate((int)$matches[2], (int)$matches[3], (int)$matches[1])) {
            return [ (int)$matches[1], (int)$matches[2], (int)$matches[3] ];
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $matches) && checkdate((int)$matches[2], (int)$matches[1], (int)$matches[3])) {
            return [ (int)$matches[3], (int)$matches[2], (int)$matches[1] ];
        }

        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $value, $matches) && checkdate((int)$matches[2], (int)$matches[1], (int)$matches[3])) {
            return [ (int)$matches[3], (int)$matches[2], (int)$matches[1] ];
        }

        $digits = digitsOnly($value);
        if (strlen($digits) === 8) {
            $day = (int)substr($digits, 0, 2);
            $month = (int)substr($digits, 2, 2);
            $year = (int)substr($digits, 4, 4);
            if (checkdate($month, $day, $year)) {
                return [ $year, $month, $day ];
            }

            $year = (int)substr($digits, 0, 4);
            $month = (int)substr($digits, 4, 2);
            $day = (int)substr($digits, 6, 2);
            if (checkdate($month, $day, $year)) {
                return [ $year, $month, $day ];
            }
        }

        return false;
    }
}

if (!function_exists('normalizeDate')) {
    function normalizeDate(string $value): string
    {
        $parts = parseDateParts($value);
        if ($parts === false) {
            return '';
        }

        [$year, $month, $day] = $parts;
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}

if (!function_exists('birthDigits')) {
    function birthDigits(string $value): string
    {
        $parts = parseDateParts($value);
        if ($parts === false) {
            return '';
        }

        [$year, $month, $day] = $parts;
        return sprintf('%02d%02d%04d', $day, $month, $year);
    }
}

if (!function_exists('cleanCpfColumn')) {
    function cleanCpfColumn(string $column = 'cpf'): string
    {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($column, '.', ''), '-', ''), ' ', ''), '/', ''), '(', ''), ')', '')";
    }
}
