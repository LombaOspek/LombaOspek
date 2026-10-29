<?php

namespace App\Support;

/**
 * Ikon garis 24×24 yang digambar khusus untuk OspekQuest.
 *
 * Kenapa tidak emoji dan tidak pakai ikon generik:
 * - Emoji dirender berbeda tiap OS (Windows memakai Segoe UI Emoji berwarna,
 *   jadi tampilan desain tidak konsisten dengan mockup).
 * - Ikon "AI-generated" biasanya berupa lingkaran gradien + sparkle; di sini
 *   semua ikon memakai grid 24px yang sama, ketebalan garis 1.75, ujung
 *   membulat, dan hanya `currentColor` sehingga otomatis mengikuti tema.
 *
 * Elemen dekoratif (lencana, piala) sengaja dibuat dengan bentuk geometris
 * sederhana — bukan gradien atau efek glow — agar terasa digambar tangan.
 */
class Icon
{
    /**
     * Path untuk tiap nama ikon. `null` menandakan ikon tidak dikenal.
     *
     * @var array<string, string>
     */
    private const PATHS = [
        'home' => '<path d="M4 10.5 12 4l8 6.5V19a1.5 1.5 0 0 1-1.5 1.5h-4v-6h-5v6h-4A1.5 1.5 0 0 1 4 19v-8.5Z"/>',
        'map' => '<path d="M9 4 3.5 6.2v13.3L9 17.3l6 2.2 5.5-2.2V4L15 6.2 9 4Z"/><path d="M9 4v13.3M15 6.2v13.3"/>',
        'compass' => '<circle cx="12" cy="12" r="8.5"/><path d="m14.8 9.2-1.6 4-4 1.6 1.6-4 4-1.6Z"/>',
        'trophy' => '<path d="M7 4h10v4.5a5 5 0 0 1-10 0V4Z"/><path d="M7 5.5H4.5V7A3 3 0 0 0 7 10M17 5.5h2.5V7a3 3 0 0 1-2.5 3"/><path d="M12 13.5V17M8.5 20h7M9.5 20l.5-3h4l.5 3"/>',
        'medal' => '<circle cx="12" cy="14.5" r="5"/><path d="m8.2 9.8-2-5.3h3.4L12 9l2.4-4.5h3.4l-2 5.3"/><path d="m12 12.4.9 1.8 2 .3-1.4 1.4.3 2-1.8-1-1.8 1 .3-2L9 14.5l2-.3.9-1.8Z"/>',
        'badge' => '<path d="M12 3 4.8 5.6v5.6c0 4.3 3 7.4 7.2 9 4.2-1.6 7.2-4.7 7.2-9V5.6L12 3Z"/><path d="m9 11.8 2.2 2.2 4-4.3"/>',
        'sparkle' => '<path d="M12 4.5c.7 3.6 1.9 4.8 5.5 5.5-3.6.7-4.8 1.9-5.5 5.5-.7-3.6-1.9-4.8-5.5-5.5 3.6-.7 4.8-1.9 5.5-5.5Z"/><path d="M17.5 15.5c.35 1.7.95 2.3 2.6 2.65-1.65.35-2.25.95-2.6 2.65-.35-1.7-.95-2.3-2.6-2.65 1.65-.35 2.25-.95 2.6-2.65Z"/>',
        'quiz' => '<path d="M4.5 5.5h15v10h-8.5L7 19.5v-4H4.5v-10Z"/><path d="M9.4 9.2a2 2 0 1 1 2.9 1.8c-.5.3-.8.7-.8 1.3v.4"/><path d="M11.5 14.6h.01"/>',
        'book' => '<path d="M4.5 5.5A2 2 0 0 1 6.5 4H19v14H6.5a2 2 0 0 0-2 2V5.5Z"/><path d="M4.5 20a2 2 0 0 1 2-2H19v2H6.5"/><path d="M8.5 8.5h6M8.5 11.5h4"/>',
        'flame' => '<path d="M12 3.5c3.2 3 4.8 5.2 4.8 7.6a4.8 4.8 0 1 1-9.6 0c0-1.3.5-2.3 1.4-3.4.5.9 1 1.4 1.7 1.7-.2-2 .3-3.9 1.7-5.9Z"/>',
        'bolt' => '<path d="M13.5 3 5.8 13.4h5L10 21l7.9-10.6h-5.2L13.5 3Z"/>',
        'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><path d="M12 8v.01"/>',
        'users' => '<circle cx="9.5" cy="8.5" r="3.2"/><path d="M3.8 19.5a5.7 5.7 0 0 1 11.4 0"/><path d="M15.6 5.6a3.2 3.2 0 0 1 0 5.9M17 14.4a5 5 0 0 1 3.4 5.1"/>',
        'user' => '<circle cx="12" cy="8.5" r="3.6"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 3.2v2.2M12 18.6v2.2M4.7 7.9l1.9 1.1M17.4 15l1.9 1.1M4.7 16.1l1.9-1.1M17.4 9l1.9-1.1"/>',
        'help' => '<circle cx="12" cy="12" r="8.5"/><path d="M9.8 9.5a2.3 2.3 0 1 1 3.3 2.1c-.6.3-1 .9-1 1.6v.3"/><path d="M12 16.6h.01"/>',
        'search' => '<circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.4 15.4 4.1 4.1"/>',
        'bell' => '<path d="M6.5 10a5.5 5.5 0 0 1 11 0c0 3.2.6 4.6 1.5 5.6H5c.9-1 1.5-2.4 1.5-5.6Z"/><path d="M10 18.5a2 2 0 0 0 4 0"/>',
        'lock' => '<rect x="5" y="10.5" width="14" height="9.5" rx="2"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"/>',
        'unlock' => '<rect x="5" y="10.5" width="14" height="9.5" rx="2"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 6.6-1.6"/>',
        'check' => '<path d="m4.8 12.6 4.6 4.6L19.2 7"/>',
        'check-circle' => '<circle cx="12" cy="12" r="8.5"/><path d="m8.2 12.2 2.6 2.6 5-5.4"/>',
        'x' => '<path d="m6.5 6.5 11 11M17.5 6.5l-11 11"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'arrow-right' => '<path d="M4.5 12h14M13 6.5l5.5 5.5L13 17.5"/>',
        'arrow-left' => '<path d="M19.5 12h-14M11 6.5 5.5 12 11 17.5"/>',
        'chevron-right' => '<path d="m9.5 6 6 6-6 6"/>',
        'chevron-down' => '<path d="m6 9.5 6 6 6-6"/>',
        'play' => '<path d="M8 5.5 18 12 8 18.5v-13Z"/>',
        'logout' => '<path d="M14.5 8V5.5a1.5 1.5 0 0 0-1.5-1.5H6a1.5 1.5 0 0 0-1.5 1.5v13A1.5 1.5 0 0 0 6 20h7a1.5 1.5 0 0 0 1.5-1.5V16"/><path d="M10 12h9.5M16.5 8.5 20 12l-3.5 3.5"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>',
        'calendar' => '<rect x="4" y="5.5" width="16" height="14.5" rx="2"/><path d="M4 10h16M8.5 3.5v4M15.5 3.5v4"/>',
        'pin' => '<path d="M12 21c4-4.4 6-7.3 6-9.8a6 6 0 1 0-12 0C6 13.7 8 16.6 12 21Z"/><circle cx="12" cy="11" r="2.3"/>',
        'send' => '<path d="M20.5 3.5 3.5 10.2l6.6 2.7 2.7 6.6 7.7-16Z"/><path d="m10.1 12.9 4.4-4.4"/>',
        'ticket' => '<path d="M3.5 9.5V6.5A1.5 1.5 0 0 1 5 5h14a1.5 1.5 0 0 1 1.5 1.5v3a2.5 2.5 0 0 0 0 5v3A1.5 1.5 0 0 1 19 19H5a1.5 1.5 0 0 1-1.5-1.5v-3a2.5 2.5 0 0 0 0-5Z"/><path d="M12 8.5v7" stroke-dasharray="1.6 1.8"/>',
        'certificate' => '<rect x="3.5" y="5" width="17" height="11.5" rx="1.8"/><path d="M7 9h6M7 12h4"/><circle cx="16.5" cy="16.5" r="2.6"/><path d="m15 18.7-.5 2.3 2-1 2 1-.5-2.3"/>',
        'key' => '<circle cx="8" cy="12" r="3.5"/><path d="M11.5 12H20M17 12v3M14 12v2.2"/>',
        'shield' => '<path d="M12 3.2 5 5.8v5.4c0 4.2 2.9 7.2 7 8.8 4.1-1.6 7-4.6 7-8.8V5.8L12 3.2Z"/>',
        'star' => '<path d="m12 4 2.3 4.9 5.2.7-3.8 3.7.9 5.2-4.6-2.5-4.6 2.5.9-5.2L4.5 9.6l5.2-.7L12 4Z"/>',
        'leaf' => '<path d="M20 4c-8.5 0-14 4-14 9.5A5.5 5.5 0 0 0 11.5 19C17 19 20 12.5 20 4Z"/><path d="M4 20c1.5-4.5 4.5-7.5 9-9.5"/>',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'eye' => '<path d="M2.8 12S6 6.5 12 6.5 21.2 12 21.2 12 18 17.5 12 17.5 2.8 12 2.8 12Z"/><circle cx="12" cy="12" r="2.6"/>',
        'eye-off' => '<path d="M4 4l16 16"/><path d="M9.6 6.9A9.4 9.4 0 0 1 12 6.5c6 0 9.2 5.5 9.2 5.5a17 17 0 0 1-3 3.6M6.4 8.9A17 17 0 0 0 2.8 12S6 17.5 12 17.5a9.6 9.6 0 0 0 3.4-.6"/><path d="M10.3 10.4a2.6 2.6 0 0 0 3.5 3.6"/>',
        'camera' => '<path d="M3.5 8.5h3l1.4-2h8.2l1.4 2h3v10h-17v-10Z"/><circle cx="12" cy="13.4" r="3.2"/>',
        'info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 8h.01"/>',
        'warning' => '<path d="M12 4 2.8 20h18.4L12 4Z"/><path d="M12 10v4M12 16.6h.01"/>',
        'trending' => '<path d="m4 15.5 4.5-4.5 3 3L20 5.5"/><path d="M15 5.5h5v5"/>',
        'layers' => '<path d="m12 3.5 8 4.3-8 4.3-8-4.3 8-4.3Z"/><path d="m4 12.3 8 4.2 8-4.2M4 16.6l8 4.2 8-4.2"/>',
        'refresh' => '<path d="M20 12a8 8 0 1 1-2.6-5.9"/><path d="M20 4.5V10h-5.3"/>',
        'mobile' => '<rect x="7" y="3" width="10" height="18" rx="2.2"/><path d="M10.8 18h2.4"/>',
        'mail' => '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4.5 7 7.5 5.5L19.5 7"/>',
        'podium' => '<path d="M4 20V13h4v7M10 20V9h4v11M16 20V6h4v14"/><path d="M3 20h18"/>',
        'gift' => '<rect x="3.5" y="9.5" width="17" height="4" rx="1"/><path d="M5 13.5V20h14v-6.5M12 9.5V20"/><path d="M12 9.5C10.5 5.5 6 5 6 7.5S10 9.5 12 9.5Zm0 0C13.5 5.5 18 5 18 7.5S14 9.5 12 9.5Z"/>',
        'flag' => '<path d="M5.5 21V4M5.5 4.5h11l-1.8 3.6 1.8 3.6H5.5"/>',
    ];

    public static function exists(string $name): bool
    {
        return isset(self::PATHS[$name]);
    }

    /**
     * Ambil hanya isi path (tanpa <svg>) agar ikon dapat disematkan ke dalam
     * SVG lain — dipakai App\Support\Badge untuk lencana perisai.
     */
    public static function path(string $name): string
    {
        return self::PATHS[$name] ?? self::PATHS['info'];
    }

    /**
     * Render SVG inline. Ukuran dan kelas diserahkan ke pemanggil agar ikon
     * bisa dipakai di dalam tombol, badge, dan chip dengan konsisten.
     */
    public static function svg(string $name, string $class = 'size-5', float $stroke = 1.75): string
    {
        $path = self::PATHS[$name] ?? self::PATHS['info'];

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            .'stroke-width="%s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="%s">%s</svg>',
            rtrim(rtrim(number_format($stroke, 2, '.', ''), '0'), '.'),
            e($class),
            $path,
        );
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_keys(self::PATHS);
    }
}
