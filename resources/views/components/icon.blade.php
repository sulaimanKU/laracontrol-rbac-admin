@props(['name' => 'grid'])
@php
$paths = [
 'grid' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
 'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M16 3.13a4 4 0 0 1 0 7.75',
 'shield' => 'M12 22s9-4 9-11V5l-9-3-9 3v6c0 7 9 11 9 11z M8 12l3 3 5-6',
 'activity' => 'M3 3v18h18 M7 14l4-5 4 3 5-8',
 'settings' => 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M9 3h6l1 3 3 1 2 5-2 5-3 1-1 3H9l-1-3-3-1-2-5 2-5 3-1z',
 'user' => 'M20 21v-2a7 7 0 0 0-14 0v2 M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8',
 'search' => 'M21 21l-4.5-4.5 M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15',
 'sun' => 'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M12 2v2 M12 20v2 M2 12h2 M20 12h2 M5 5l1.5 1.5 M17.5 17.5L19 19 M5 19l1.5-1.5 M17.5 6.5L19 5',
 'moon' => 'M21 13A9 9 0 0 1 11 3a9 9 0 1 0 10 10z',
 'bell' => 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9 M10 21h4',
 'chevron' => 'M6 9l6 6 6-6',
 'arrow' => 'M5 12h14 M13 6l6 6-6 6',
 'plus' => 'M12 5v14 M5 12h14',
 'check' => 'M5 12l4 4L19 6',
 'logout' => 'M9 21H3V3h6 M10 12h11 M17 8l4 4-4 4',
 'menu' => 'M3 6h18 M3 12h18 M3 18h18',
 'close' => 'M6 6l12 12 M6 18L18 6',
 'lock' => 'M5 10h14v11H5z M8 10V6a4 4 0 0 1 8 0v4',
 'clock' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18 M12 7v5l3 2',
 'calendar' => 'M3 5h18v16H3z M16 3v4 M8 3v4 M3 10h18',
 'more' => 'M5 12h.01 M12 12h.01 M19 12h.01',
 'edit' => 'M12 20H4V4h8 M15 3l6 6-10 10-6 1 1-6z M13 5l6 6',
 'eye' => 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z M12 9a3 3 0 1 0 0 6 3 3 0 0 0 0-6',
 'filter' => 'M3 4h18l-7 8v7l-4 2v-9z',
 'monitor' => 'M3 3h18v14H3z M8 21h8 M12 17v4',
 'mail' => 'M3 5h18v14H3z M3 5l9 7 9-7',
 'info' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18 M12 11v6 M12 7h.01',
];
@endphp
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['grid'] }}"/></svg>