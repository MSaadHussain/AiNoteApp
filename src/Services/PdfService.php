<?php

namespace NoteNest\Services;

class PdfService
{
    /**
     * Extract plain text from a raw PDF binary content or file path
     */
    public static function extractText(string $filePathOrContent): string
    {
        $content = file_exists($filePathOrContent) 
            ? file_get_contents($filePathOrContent) 
            : $filePathOrContent;

        if (empty($content)) {
            return '';
        }

        // Method 1: Extract text enclosed in PDF string operators BT ... ET / (...) Tj
        $text = '';
        
        // Find text objects
        if (preg_match_all('/BT[\s\S]*?ET/m', $content, $btMatches)) {
            foreach ($btMatches[0] as $block) {
                // Extract strings in parentheses (text)
                if (preg_match_all('/\((.*?)\)\s*(?:Tj|TJ|'|\")/s', $block, $strMatches)) {
                    foreach ($strMatches[1] as $str) {
                        $cleaned = self::unescapePdfString($str);
                        if (!empty(trim($cleaned))) {
                            $text .= $cleaned . ' ';
                        }
                    }
                    $text .= "\n";
                }
            }
        }

        // Method 2: If regex didn't catch enough text, try general parenthesis pattern
        if (strlen(trim($text)) < 50) {
            if (preg_match_all('/\(([a-zA-Z0-9\s.,!?:;\-\'"]{3,})\)/', $content, $matches)) {
                $text = implode(' ', $matches[1]);
            }
        }

        // Clean up excessive whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text) ?: "[PDF content loaded. Text extraction was limited for this format; visual PDF reader is available.]";
    }

    /**
     * Approximate page count from PDF binary stream
     */
    public static function getPageCount(string $filePathOrContent): int
    {
        $content = file_exists($filePathOrContent) 
            ? file_get_contents($filePathOrContent) 
            : $filePathOrContent;

        if (preg_match_all('/\/Type\s*\/Page\b/', $content, $matches)) {
            return max(1, count($matches[0]));
        }

        if (preg_match('/\/Count\s+(\d+)/', $content, $matches)) {
            return max(1, (int)$matches[1]);
        }

        return 1;
    }

    private static function unescapePdfString(string $str): string
    {
        $str = str_replace(
            ['\\n', '\\r', '\\t', '\\(', '\\)', '\\\\'],
            ["\n", "\r", "\t", '(', ')', '\\'],
            $str
        );
        return preg_replace('/[^\x20-\x7E\n\r\t]/', '', $str);
    }
}
