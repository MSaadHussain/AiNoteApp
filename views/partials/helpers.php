<?php
/**
 * View helpers.
 *
 * Subject accent colours live in the database as legacy Tailwind class strings
 * ("bg-sky-200 text-sky-800 border-sky-300"). A purged Tailwind build only
 * emits classes it can see in the source, so those runtime strings would
 * compile to nothing. This maps each stored hue onto a literal, curated class
 * set written out below - which the content scanner *can* see.
 */

if (!function_exists('subject_accent')) {
    /**
     * @return array{dot:string, chip:string, bar:string, ring:string}
     */
    function subject_accent(?string $stored): array
    {
        static $palette = [
            'rose' => [
                'dot'  => 'bg-rose-500',
                'chip' => 'bg-rose-50 text-rose-700 ring-rose-200',
                'bar'  => 'from-rose-500 to-rose-400',
                'ring' => 'ring-rose-200',
            ],
            'sky' => [
                'dot'  => 'bg-sky-500',
                'chip' => 'bg-sky-50 text-sky-700 ring-sky-200',
                'bar'  => 'from-sky-500 to-sky-400',
                'ring' => 'ring-sky-200',
            ],
            'emerald' => [
                'dot'  => 'bg-emerald-500',
                'chip' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'bar'  => 'from-emerald-500 to-emerald-400',
                'ring' => 'ring-emerald-200',
            ],
            'amber' => [
                'dot'  => 'bg-amber-500',
                'chip' => 'bg-amber-50 text-amber-700 ring-amber-200',
                'bar'  => 'from-amber-500 to-amber-400',
                'ring' => 'ring-amber-200',
            ],
            'violet' => [
                'dot'  => 'bg-violet-500',
                'chip' => 'bg-violet-50 text-violet-700 ring-violet-200',
                'bar'  => 'from-violet-500 to-violet-400',
                'ring' => 'ring-violet-200',
            ],
            'orange' => [
                'dot'  => 'bg-orange-500',
                'chip' => 'bg-orange-50 text-orange-700 ring-orange-200',
                'bar'  => 'from-orange-500 to-orange-400',
                'ring' => 'ring-orange-200',
            ],
        ];

        foreach (array_keys($palette) as $hue) {
            if ($stored && str_contains($stored, '-' . $hue . '-')) {
                return $palette[$hue];
            }
        }

        // Unknown or missing hue falls back to the brand accent.
        return [
            'dot'  => 'bg-brand-500',
            'chip' => 'bg-brand-50 text-brand-700 ring-brand-200',
            'bar'  => 'from-brand-500 to-brand-400',
            'ring' => 'ring-brand-200',
        ];
    }
}

if (!function_exists('note_type_meta')) {
    /**
     * Icon + label + badge class for a note's source type. The icon and text
     * label mean the badge never relies on colour alone to convey type.
     *
     * @return array{icon:string, label:string, badge:string}
     */
    function note_type_meta(?string $type): array
    {
        return match ($type) {
            'audio' => ['icon' => 'mic',       'label' => 'Lecture',  'badge' => 'badge-info'],
            'pdf'   => ['icon' => 'file-text', 'label' => 'Document', 'badge' => 'badge-warning'],
            default => ['icon' => 'pen-line',  'label' => 'Written',  'badge' => 'badge-success'],
        };
    }
}

if (!function_exists('note_href')) {
    /** Route a note to the reader that matches its type. */
    function note_href(object $note): string
    {
        return match ($note->type) {
            'pdf'  => '/pdf/' . $note->id,
            'text' => '/notepad?id=' . $note->id,
            default => '/note/' . $note->id,
        };
    }
}
