<?php

namespace App\Support;

class StudentNumber
{
    public static function normalize(string $studentNumber): string
    {
        $studentNumber = trim(strip_tags($studentNumber));

        if ($studentNumber === '') {
            return '';
        }

        if (preg_match('/^S(?=\d)/i', $studentNumber) === 1) {
            $studentNumber = substr($studentNumber, 1);
        }

        if (strncasecmp($studentNumber, 'USR-', 4) === 0) {
            $suffix = substr($studentNumber, 4);
        } else {
            $suffix = $studentNumber;
        }

        if (preg_match('/^\d+$/', $suffix) === 1) {
            $suffix = ltrim($suffix, '0') ?: '0';
        }

        return 'USR-'.$suffix;
    }
}
