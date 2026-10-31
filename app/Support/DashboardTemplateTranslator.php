<?php

namespace App\Support;

class DashboardTemplateTranslator
{
    private const DASHBOARD_LAYOUTS = [
        'admin.layouts.app',
        'staff.main',
        'superadmin.layout',
    ];

    private static ?array $englishTranslations = null;

    public static function compile(string $source): string
    {
        if (! self::isDashboardTemplate($source)) {
            return $source;
        }

        $excludedBlocks = [];
        $source = preg_replace_callback(
            '/<(script|style)\b[^>]*>.*?<\/\1>/is',
            static function (array $matches) use (&$excludedBlocks): string {
                $placeholder = '<!--DASHBOARD_BLOCK_'.count($excludedBlocks).'-->';
                $excludedBlocks[$placeholder] = strtolower($matches[1]) === 'script' ? self::translateScript($matches[0]) : $matches[0];

                return $placeholder;
            },
            $source
        ) ?? $source;
        $source = self::translateSections($source);
        $source = self::translateDirectiveStrings($source);
        $source = self::translateBladeExpressions($source);
        $source = self::translateAttributes($source);

        $bladeExpressions = [];
        $source = preg_replace_callback(
            '/\{\{.*?\}\}/s',
            static function (array $matches) use (&$bladeExpressions): string {
                $placeholder = '<!--DASHBOARD_EXPRESSION_'.count($bladeExpressions).'-->';
                $bladeExpressions[$placeholder] = $matches[0];

                return $placeholder;
            },
            $source
        ) ?? $source;

        $source = preg_replace_callback(
            '/>([^<>]+)</u',
            static fn (array $matches): string => '>'.self::translateText($matches[1]).'<',
            $source
        ) ?? $source;

        return strtr($source, $excludedBlocks + $bladeExpressions);
    }

    public static function translate(string $text): string
    {
        if (app()->getLocale() !== 'en') {
            return $text;
        }

        $translation = __($text);
        if ($translation !== $text) {
            return $translation;
        }

        $translations = self::englishTranslations();
        $keys = array_filter(
            array_keys($translations),
            static fn (string $key): bool => str_contains($text, $key) && preg_match('/\p{Arabic}/u', $key) === 1
        );
        usort($keys, static fn (string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left));

        foreach ($keys as $key) {
            $text = preg_replace_callback(
                '/(?<![\p{Arabic}])'.preg_quote($key, '/').'(?![\p{Arabic}])/u',
                static fn (array $matches): string => $translations[$key],
                $text
            ) ?? $text;
        }

        return preg_replace_callback('/[\p{Arabic}]+/u', static function (array $matches): string {
            $wordTranslation = __($matches[0]);

            return $wordTranslation === $matches[0] ? $matches[0] : $wordTranslation;
        }, $text) ?? $text;
    }

    public static function clientTranslations(): array
    {
        return array_filter(
            self::englishTranslations(),
            static fn (string $value, string $key): bool => preg_match('/\p{Arabic}/u', $key) === 1,
            ARRAY_FILTER_USE_BOTH
        );
    }

    private static function englishTranslations(): array
    {
        if (self::$englishTranslations === null) {
            $content = file_get_contents(lang_path('en.json'));
            if ($content === false) {
                throw new \RuntimeException('Unable to read the English dashboard translations.');
            }

            $translations = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($translations)) {
                throw new \UnexpectedValueException('The English dashboard translations must be a JSON object.');
            }

            self::$englishTranslations = $translations;
        }

        return self::$englishTranslations;
    }

    private static function isDashboardTemplate(string $source): bool
    {
        foreach (self::DASHBOARD_LAYOUTS as $layout) {
            if (preg_match('/@extends\(\s*[\'"]'.preg_quote($layout, '/').'[\'"]\s*\)/', $source)) {
                return true;
            }
        }

        return false;
    }

    private static function translateSections(string $source): string
    {
        return preg_replace_callback(
            '/(@section\(\s*[\'"](title|page_title|page-title)[\'"]\s*\))(.*?)(@endsection)/s',
            static fn (array $matches): string => $matches[1].self::translateText($matches[3]).$matches[4],
            $source
        );
    }

    private static function translateBladeExpressions(string $source): string
    {
        return preg_replace_callback('/\{\{.*?\}\}/s', static function (array $matches): string {
            if (str_contains($matches[0], 'DashboardTemplateTranslator::translate')
                || preg_match('/(?<![A-Za-z0-9_])__\s*\(/', $matches[0])) {
                return $matches[0];
            }

            $expression = trim(substr($matches[0], 2, -2));
            if (preg_match('/^(?:session\(\s*[\'"](?:status|success|error|warning)[\'"]\s*\)|\$message|\$error|\$errors->first\([^)]*\))$/s', $expression)) {
                return '{{ \\'.self::class.'::translate((string) ('.$expression.')) }}';
            }

            return preg_replace_callback(
                '/([\'"])([^\'"\r\n]*\p{Arabic}[^\'"\r\n]*)\1/u',
                static fn (array $literal): string => self::expression($literal[2]),
                $matches[0]
            );
        }, $source) ?? $source;
    }

    private static function translateDirectiveStrings(string $source): string
    {
        return preg_replace_callback('/@(foreach|section)\(([^)]*)\)/s', static function (array $matches): string {
            if (str_contains($matches[2], 'DashboardTemplateTranslator::translate')
                || preg_match('/(?<![A-Za-z0-9_])__\s*\(/', $matches[2])) {
                return $matches[0];
            }

            $arguments = preg_replace_callback(
                '/([\'"])([^\'"\r\n]*\p{Arabic}[^\'"\r\n]*)\1/u',
                static fn (array $literal): string => self::phpExpression($literal[2]),
                $matches[2]
            );

            return '@'.$matches[1].'('.$arguments.')';
        }, $source) ?? $source;
    }

    private static function translateAttributes(string $source): string
    {
        $source = preg_replace_callback(
            '/\b(placeholder|title|aria-label|aria-description|alt|data-confirm)\s*=\s*(["\'])([^"\']*[\p{Arabic}][^"\']*)\2/u',
            static function (array $matches): string {
                if (str_contains($matches[3], '{{') || str_contains($matches[3], '@')) {
                    return $matches[0];
                }

                return $matches[1].'='.$matches[2].self::expression($matches[3]).$matches[2];
            },
            $source
        ) ?? $source;

        return preg_replace_callback(
            '/\b(on[a-z]+\s*=\s*)(["\'])(.*?)\2/s',
            static function (array $matches): string {
                if (str_contains($matches[3], '{{')) {
                    return $matches[0];
                }

                $value = preg_replace_callback(
                    '/([\'"])([^\'"]*\p{Arabic}[^\'"]*)\1/u',
                    static fn (array $literal): string => $literal[1].'{{ '.self::phpExpression($literal[2]).' }}'.$literal[1],
                    $matches[3]
                );

                return $matches[1].$matches[2].$value.$matches[2];
            },
            $source
        ) ?? $source;
    }

    private static function translateText(string $text): string
    {
        if (! preg_match('/\p{Arabic}/u', $text)
            || str_contains($text, '@')
            || preg_match('/(?<![A-Za-z0-9_])__\s*\(/', $text)) {
            return $text;
        }

        $parts = preg_split('/(\{\{.*?\}\})/s', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $text;
        }

        foreach ($parts as $index => $part) {
            if ($index % 2 === 0 && preg_match('/\p{Arabic}/u', $part)) {
                $parts[$index] = self::translateStaticText($part);
            }
        }

        return implode('', $parts);
    }

    private static function translateStaticText(string $text): string
    {
        if (preg_match('/^(\s*)(.*?)(\s*)$/us', $text, $matches) !== 1 || trim($matches[2]) === '') {
            return $text;
        }

        return $matches[1].self::expression($matches[2]).$matches[3];
    }

    private static function translateScript(string $script): string
    {
        $index = 0;

        return self::translateJavaScriptCode($script, $index);
    }

    private static function translateJavaScriptCode(string $script, int &$index, bool $stopAtClosingBrace = false): string
    {
        $length = strlen($script);
        $translated = '';
        $braceDepth = 0;

        while ($index < $length) {
            if ($script[$index] === "'" || $script[$index] === '"') {
                $end = self::quotedStringEnd($script, $index);
                $literal = substr($script, $index, $end - $index);
                $contents = substr($literal, 1, -1);
                $before = substr($script, max(0, $index - 8), min(8, $index));
                if (preg_match('/\p{Arabic}/u', $contents)
                    && ! str_contains($contents, '{{')
                    && ! str_contains($contents, '$')
                    && ! str_contains($before, '__(')) {
                    $translated .= self::jsonExpression($contents);
                } else {
                    $translated .= $literal;
                }
                $index = $end;
                continue;
            }

            if (substr($script, $index, 2) === '//') {
                $end = strpos($script, "\n", $index + 2);
                $end = $end === false ? $length : $end;
                $translated .= substr($script, $index, $end - $index);
                $index = $end;
                continue;
            }

            if (substr($script, $index, 2) === '/*') {
                $end = strpos($script, '*/', $index + 2);
                $end = $end === false ? $length : $end + 2;
                $translated .= substr($script, $index, $end - $index);
                $index = $end;
                continue;
            }

            if ($script[$index] === '`') {
                $translated .= self::translateTemplateLiteral($script, $index);
                continue;
            }

            if ($stopAtClosingBrace && $script[$index] === '}') {
                if ($braceDepth === 0) {
                    $translated .= '}';
                    $index++;

                    return $translated;
                }

                $braceDepth--;
            } elseif ($stopAtClosingBrace && $script[$index] === '{') {
                $braceDepth++;
            }

            $translated .= $script[$index];
            $index++;
        }

        return $translated;
    }

    private static function translateTemplateLiteral(string $script, int &$index): string
    {
        $length = strlen($script);
        $translated = '`';
        $text = '';
        $index++;

        while ($index < $length) {
            if ($script[$index] === '\\' && $index + 1 < $length) {
                $text .= substr($script, $index, 2);
                $index += 2;
                continue;
            }

            if ($script[$index] === '`') {
                $translated .= self::translateTemplateText($text).'`';
                $index++;

                return $translated;
            }

            if (substr($script, $index, 2) === '${') {
                $translated .= self::translateTemplateText($text).'${';
                $text = '';
                $index += 2;
                $translated .= self::translateJavaScriptCode($script, $index, true);
                continue;
            }

            $text .= $script[$index];
            $index++;
        }

        return $translated.$text;
    }

    private static function quotedStringEnd(string $script, int $index): int
    {
        $quote = $script[$index];
        $length = strlen($script);
        $index++;

        while ($index < $length) {
            if ($script[$index] === '\\') {
                $index += 2;
                continue;
            }

            if ($script[$index] === $quote) {
                return $index + 1;
            }

            $index++;
        }

        return $length;
    }

    private static function translateTemplateText(string $text): string
    {
        if (! preg_match('/\p{Arabic}/u', $text)) {
            return $text;
        }

        return '${'.self::jsonExpression($text).'}';
    }

    private static function jsonExpression(string $text): string
    {
        return '<?php echo json_encode('.self::phpExpression($text).', 15, 512); ?>';
    }

    private static function expression(string $text): string
    {
        return '{{ '.self::phpExpression($text).' }}';
    }

    private static function phpExpression(string $text): string
    {
        return '\\'.self::class.'::translate('.var_export($text, true).')';
    }
}
