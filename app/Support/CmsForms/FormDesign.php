<?php

namespace App\Support\CmsForms;

/**
 * Theme + layout rules for the CMS Forms designer. Everything the builder
 * sends passes through sanitize()/sanitizeField(): unknown keys are dropped,
 * colours must be hex, numbers are clamped, enums are whitelisted - so the
 * public page can print these values into CSS safely.
 *
 * resources/views/cms_forms/builder.blade.php mirrors PRESETS and the
 * renderer; keep them in step.
 */
class FormDesign
{
    const COLOR_KEYS = [
        'primary', 'header_bg', 'header_text', 'page_bg', 'card_bg',
        'field_bg', 'field_border', 'text', 'label', 'section_bg', 'section_text',
    ];

    /** Font name => Google Fonts family spec. */
    const FONTS = [
        'Nunito Sans' => 'Nunito+Sans:wght@400;600;700;800',
        'Inter' => 'Inter:wght@400;500;600;700;800',
        'Lato' => 'Lato:wght@400;700;900',
        'Poppins' => 'Poppins:wght@400;500;600;700',
        'Roboto' => 'Roboto:wght@400;500;700;900',
        'Montserrat' => 'Montserrat:wght@400;600;700;800',
        'Merriweather' => 'Merriweather:wght@400;700;900',
        'Baloo 2' => 'Baloo+2:wght@400;500;600;700;800',
    ];

    const ENUMS = [
        'header_style' => ['split', 'banner', 'centered', 'minimal'],
        'title_align' => ['left', 'center', 'right'],
        // 'auto' keeps each header style's own placement: centred when
        // stacked, following the title in a banner, left in logo + title.
        'logo_align' => ['auto', 'left', 'center', 'right'],
        'field_style' => ['outline', 'filled', 'underline'],
        'label_position' => ['top', 'left'],
        'button_style' => ['full', 'auto'],
        'button_align' => ['left', 'center', 'right'],
    ];

    /** key => [min, max] */
    const NUMBERS = [
        'radius' => [0, 24],
        'title_size' => [18, 64],
        'max_width' => [480, 1100],
        'spacing' => [6, 40],
        'logo_size' => [24, 160],
        'subtitle_size' => [11, 36],
        'header_padding' => [8, 80],
    ];

    const BOOLS = ['show_logo', 'frame', 'title_uppercase', 'logo_plate'];

    /** key => max length */
    const STRINGS = [
        'theme' => 30,
        'header_kicker' => 80,
        'header_subtitle' => 300,
        'submit_label' => 40,
        'footer_left' => 160,
        'footer_right' => 160,
    ];

    const DEFAULTS = [
        'theme' => 'engage',
        'colors' => self::PRESETS['engage']['colors'],
        'font' => 'Nunito Sans',
        // Titles and section headings - Baloo 2 matches the CRM's own headings.
        // '' = same font as the text.
        'heading_font' => 'Baloo 2',
        'header_style' => 'centered',
        'title_align' => 'left',
        'title_size' => 28,
        'title_uppercase' => false,
        'show_logo' => true,
        'logo_size' => 46,
        'logo_align' => 'auto',
        'logo_plate' => true,
        'header_padding' => 26,
        'header_kicker' => '',
        'header_subtitle' => '',
        'subtitle_size' => 16,
        'frame' => false,
        'field_style' => 'outline',
        'label_position' => 'top',
        'radius' => 10,
        'spacing' => 18,
        'max_width' => 680,
        'submit_label' => 'Submit',
        'button_style' => 'full',
        'button_align' => 'center',
        'footer_left' => '',
        'footer_right' => '',
    ];

    /**
     * One-click looks. A preset sets colours, font and the style knobs below;
     * the form's own texts (button label, footer) are left alone.
     */
    const PRESETS = [
        'engage' => [
            'name' => 'Engage',
            'font' => 'Nunito Sans', 'heading_font' => 'Baloo 2', 'header_style' => 'centered', 'field_style' => 'outline', 'label_position' => 'top', 'radius' => 10, 'frame' => false, 'title_uppercase' => false,
            'colors' => ['primary' => '#C8355F', 'header_bg' => '#16436E', 'header_text' => '#FFFFFF', 'page_bg' => '#F6F3EE', 'card_bg' => '#FFFDFA', 'field_bg' => '#FFFFFF', 'field_border' => '#DDD4C8', 'text' => '#2B3A4C', 'label' => '#2B3A4C', 'section_bg' => '#16436E', 'section_text' => '#FFFFFF'],
        ],
        'medical' => [
            'name' => 'Medical Navy',
            'font' => 'Lato', 'heading_font' => '', 'header_style' => 'split', 'field_style' => 'filled', 'label_position' => 'left', 'radius' => 2, 'frame' => true, 'title_uppercase' => true,
            'colors' => ['primary' => '#1C2E4A', 'header_bg' => '#1C2E4A', 'header_text' => '#FFFFFF', 'page_bg' => '#E4F4FB', 'card_bg' => '#FFFFFF', 'field_bg' => '#E8F8FD', 'field_border' => '#1C2E4A', 'text' => '#1C2E4A', 'label' => '#111827', 'section_bg' => '#1C2E4A', 'section_text' => '#FFFFFF'],
        ],
        'slate' => [
            'name' => 'Slate',
            'font' => 'Inter', 'heading_font' => '', 'header_style' => 'minimal', 'field_style' => 'outline', 'label_position' => 'top', 'radius' => 8, 'frame' => false, 'title_uppercase' => false,
            'colors' => ['primary' => '#18181B', 'header_bg' => '#18181B', 'header_text' => '#FAFAFA', 'page_bg' => '#F4F4F5', 'card_bg' => '#FFFFFF', 'field_bg' => '#FFFFFF', 'field_border' => '#E4E4E7', 'text' => '#09090B', 'label' => '#09090B', 'section_bg' => '#F4F4F5', 'section_text' => '#09090B'],
        ],
        'emerald' => [
            'name' => 'Emerald',
            'font' => 'Poppins', 'heading_font' => '', 'header_style' => 'banner', 'field_style' => 'outline', 'label_position' => 'top', 'radius' => 12, 'frame' => false, 'title_uppercase' => false,
            'colors' => ['primary' => '#059669', 'header_bg' => '#064E3B', 'header_text' => '#FFFFFF', 'page_bg' => '#ECFDF5', 'card_bg' => '#FFFFFF', 'field_bg' => '#FFFFFF', 'field_border' => '#A7F3D0', 'text' => '#064E3B', 'label' => '#064E3B', 'section_bg' => '#D1FAE5', 'section_text' => '#065F46'],
        ],
        'violet' => [
            'name' => 'Violet',
            'font' => 'Montserrat', 'heading_font' => '', 'header_style' => 'banner', 'field_style' => 'filled', 'label_position' => 'top', 'radius' => 14, 'frame' => false, 'title_uppercase' => false,
            'colors' => ['primary' => '#7C3AED', 'header_bg' => '#4C1D95', 'header_text' => '#FFFFFF', 'page_bg' => '#F5F3FF', 'card_bg' => '#FFFFFF', 'field_bg' => '#F5F3FF', 'field_border' => '#DDD6FE', 'text' => '#2E1065', 'label' => '#2E1065', 'section_bg' => '#EDE9FE', 'section_text' => '#5B21B6'],
        ],
        'sunset' => [
            'name' => 'Sunset',
            'font' => 'Poppins', 'heading_font' => '', 'header_style' => 'split', 'field_style' => 'underline', 'label_position' => 'top', 'radius' => 0, 'frame' => true, 'title_uppercase' => true,
            'colors' => ['primary' => '#EA580C', 'header_bg' => '#7C2D12', 'header_text' => '#FFFFFF', 'page_bg' => '#FFF7ED', 'card_bg' => '#FFFFFF', 'field_bg' => '#FFFFFF', 'field_border' => '#FDBA74', 'text' => '#431407', 'label' => '#431407', 'section_bg' => '#EA580C', 'section_text' => '#FFFFFF'],
        ],
    ];

    // ── Per-field layout ────────────────────────────────────────────────

    const FIELD_DEFAULTS = [
        'width' => 12,           // grid columns out of 12
        'label_position' => '',  // '' = follow the form's default
        'label_bold' => true,
        'label_size' => 'md',
        'rows' => 4,             // long text / text block height
        'option_columns' => 1,   // radio + checkbox
        'section_style' => 'bar',
        'align' => 'left',       // section heading + text block
        'content' => '',         // text block body
    ];

    const FIELD_ENUMS = [
        'label_position' => ['', 'top', 'left'],
        'label_size' => ['sm', 'md', 'lg'],
        'section_style' => ['bar', 'underline', 'plain'],
        'align' => ['left', 'center', 'right'],
    ];

    public static function resolve(?array $design): array
    {
        return self::sanitize($design ?? []);
    }

    public static function sanitize(array $in): array
    {
        $out = self::DEFAULTS;

        $out['colors'] = self::DEFAULTS['colors'];
        foreach (self::COLOR_KEYS as $key) {
            $color = $in['colors'][$key] ?? null;
            if (is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $out['colors'][$key] = strtoupper($color);
            }
        }

        if (isset($in['font']) && array_key_exists($in['font'], self::FONTS)) {
            $out['font'] = $in['font'];
        }
        if (isset($in['heading_font']) && ($in['heading_font'] === '' || array_key_exists($in['heading_font'], self::FONTS))) {
            $out['heading_font'] = $in['heading_font'];
        }
        foreach (self::ENUMS as $key => $allowed) {
            if (isset($in[$key]) && in_array($in[$key], $allowed, true)) {
                $out[$key] = $in[$key];
            }
        }
        foreach (self::NUMBERS as $key => [$min, $max]) {
            if (isset($in[$key]) && is_numeric($in[$key])) {
                $out[$key] = max($min, min($max, (int) $in[$key]));
            }
        }
        foreach (self::BOOLS as $key) {
            if (array_key_exists($key, $in)) {
                $out[$key] = filter_var($in[$key], FILTER_VALIDATE_BOOL);
            }
        }
        foreach (self::STRINGS as $key => $max) {
            if (isset($in[$key]) && is_string($in[$key])) {
                $out[$key] = mb_substr(trim($in[$key]), 0, $max);
            }
        }
        if ($out['submit_label'] === '') {
            $out['submit_label'] = 'Submit';
        }

        return $out;
    }

    public static function sanitizeField(?array $in): array
    {
        $in ??= [];
        $out = self::FIELD_DEFAULTS;

        if (isset($in['width']) && is_numeric($in['width'])) {
            $out['width'] = max(1, min(12, (int) $in['width']));
        }
        if (isset($in['rows']) && is_numeric($in['rows'])) {
            $out['rows'] = max(1, min(20, (int) $in['rows']));
        }
        if (isset($in['option_columns']) && is_numeric($in['option_columns'])) {
            $out['option_columns'] = max(1, min(4, (int) $in['option_columns']));
        }
        if (array_key_exists('label_bold', $in)) {
            $out['label_bold'] = filter_var($in['label_bold'], FILTER_VALIDATE_BOOL);
        }
        foreach (self::FIELD_ENUMS as $key => $allowed) {
            if (isset($in[$key]) && in_array($in[$key], $allowed, true)) {
                $out[$key] = $in[$key];
            }
        }
        if (isset($in['content']) && is_string($in['content'])) {
            $out['content'] = mb_substr($in['content'], 0, 3000);
        }

        return $out;
    }

    /**
     * CSS custom properties for the public page - values are already
     * whitelisted by sanitize(), so they're safe to print into a style attribute.
     */
    public static function cssVars(array $d): string
    {
        $vars = [];
        foreach ($d['colors'] as $key => $color) {
            $vars[] = '--pf-'.str_replace('_', '-', $key).':'.$color;
        }
        $vars[] = "--pf-font:'{$d['font']}', system-ui, sans-serif";
        $heading = $d['heading_font'] ?: $d['font'];
        $vars[] = "--pf-heading-font:'{$heading}', '{$d['font']}', system-ui, sans-serif";
        // Baloo 2 is already heavy - its 600 matches the other fonts' 800.
        $vars[] = '--pf-heading-weight:'.($heading === 'Baloo 2' ? 600 : 800);
        $vars[] = '--pf-radius:'.$d['radius'].'px';
        $vars[] = '--pf-title-size:'.$d['title_size'].'px';
        $vars[] = '--pf-max:'.$d['max_width'].'px';
        $vars[] = '--pf-gap:'.$d['spacing'].'px';
        $vars[] = '--pf-logo-size:'.$d['logo_size'].'px';
        $vars[] = '--pf-header-pad:'.$d['header_padding'].'px';
        $vars[] = '--pf-subtitle-size:'.$d['subtitle_size'].'px';

        return implode(';', $vars);
    }

    public static function fontUrl(array $d): string
    {
        $families = array_unique(array_filter([$d['font'], $d['heading_font']]));

        return 'https://fonts.googleapis.com/css2?'.implode('&', array_map(fn ($f) => 'family='.self::FONTS[$f], $families)).'&display=swap';
    }
}
