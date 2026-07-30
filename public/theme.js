/**
 * theme.js
 * Gestión centralizada de modo claro/oscuro para todo el sitio.
 * Se debe cargar en el <head>, ANTES del <link> a main.css,
 * para evitar el parpadeo (FOUC) al aplicar el tema guardado.
 */
(function () {
    const STORAGE_KEY = 'tablero_theme'; // 'dark' | 'light'

    function getPreferredTheme() {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved === 'dark' || saved === 'light') return saved;
        // Sin elección previa del usuario → siempre arranca en claro.
        return 'light';
    }

    function updateToggleLabels(theme) {
        document.querySelectorAll('[data-theme-toggle]').forEach(btn => {
            btn.textContent = theme === 'dark' ? '☀️ Modo claro' : '🌙 Modo oscuro';
        });
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);
        updateToggleLabels(theme);
    }

    // Aplicar de inmediato (antes de pintar) para evitar parpadeo
    applyTheme(getPreferredTheme());

    // Única función global de alternancia — los botones la llaman vía onclick="toggleTheme()"
    window.toggleTheme = function () {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    };

    // IMPORTANTE: no se agregan listeners de clic aquí.
    // Los botones ya usan onclick="toggleTheme()" en el HTML.
    // Agregar un addEventListener adicional duplicaría el toggle
    // (oscuro→claro→oscuro en el mismo clic) y el botón parecería no hacer nada.
    document.addEventListener('DOMContentLoaded', function () {
        updateToggleLabels(document.documentElement.getAttribute('data-theme') || 'light');
    });
})();