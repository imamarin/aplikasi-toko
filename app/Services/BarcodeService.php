<?php

namespace App\Services;

class BarcodeService
{
    /**
     * Standard ISO/IEC 15417 Code 128 module width patterns (0 - 106).
     */
    private const CODE128_PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    /**
     * Generate an inline SVG string for a Code 128B barcode.
     */
    public static function generateSvg(string $code, int $height = 42, float $barWidth = 1.4): string
    {
        $code = trim($code);
        if ($code === '') {
            return '';
        }

        // Start Code B = 104
        $codes = [104];
        $checksum = 104;
        $length = strlen($code);

        $weight = 1;
        for ($i = 0; $i < $length; $i++) {
            $ascii = ord($code[$i]);
            if ($ascii < 32 || $ascii > 126) {
                continue;
            }
            $val = $ascii - 32;
            $codes[] = $val;
            $checksum += $val * $weight;
            $weight++;
        }

        $codes[] = $checksum % 103;
        $codes[] = 106; // Stop pattern

        $quietZone = 6; // modules on each side
        $totalModules = ($quietZone * 2) + ((count($codes) - 1) * 11) + 13;
        $totalWidth = round($totalModules * $barWidth, 2);

        $rects = [];
        $x = $quietZone * $barWidth;

        foreach ($codes as $codeIndex) {
            $pattern = self::CODE128_PATTERNS[$codeIndex];
            $patternLen = strlen($pattern);

            for ($j = 0; $j < $patternLen; $j++) {
                $modules = (int) $pattern[$j];
                $w = round($modules * $barWidth, 2);
                if ($j % 2 === 0) {
                    $xPos = round($x, 2);
                    $rects[] = "<rect x=\"{$xPos}\" y=\"0\" width=\"{$w}\" height=\"{$height}\" fill=\"#000000\" />";
                }
                $x += $modules * $barWidth;
            }
        }

        $rectsHtml = implode('', $rects);

        return "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 {$totalWidth} {$height}\" width=\"100%\" height=\"{$height}\" preserveAspectRatio=\"none\" style=\"max-width: {$totalWidth}px; display: block; margin: 0 auto;\">{$rectsHtml}</svg>";
    }
}

