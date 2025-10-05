<?php declare(strict_types=1);

return [

    /**
     * Add an additional second for every 100th word of the toast messages.
     */
    'accessibility' => true,

    /**
     * The vertical alignment of the toast container.
     *
     * Supported: "bottom", "middle" or "top"
     */
    'alignment' => 'bottom',

    /**
     * Allow users to close toast messages prematurely.
     */
    'closeable' => true,

    /**
     * The on-screen duration of each toast (in ms).
     */
    'duration' => 4000,

    /**
     * The horizontal position of each toast.
     *
     * Supported: "center", "left" or "right"
     */
    'position' => 'right',

    /**
     * Replace similar toasts instead of stacking them.
     */
    'replace' => false,

    /**
     * Prevent duplicate toasts.
     */
    'suppress' => false,

    /**
     * Translate messages automatically.
     */
    'translate' => true,

    /**
     * 🎨 Custom visual styles
     */
    'style' => [
        'success' => 'bg-green-600 text-white font-semibold shadow-lg rounded-xl px-4 py-3',
        'error'   => 'bg-red-600 text-white font-semibold shadow-lg rounded-xl px-4 py-3',
        'info'    => 'bg-blue-600 text-white font-semibold shadow-lg rounded-xl px-4 py-3',
        'warning' => 'bg-yellow-500 text-black font-semibold shadow-lg rounded-xl px-4 py-3',
    ],
];
