/**
 * Componente compartido de selección de fecha para pedidos.
 * Extraído de create.blade.php y edit.blade.php para eliminar duplicación.
 *
 * Uso en Blade:
 *   <div x-data="datePicker({ initialDate: '...' })">
 *
 * Props:
 *   initialDate (string|null) — Fecha ISO existente (modo edición). Si es null, usa "hoy".
 */
export default function datePicker({ initialDate = null } = {}) {
    return {
        fechaHora: '',
        activeShortcut: '',
        minDate: '',

        init() {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            this.minDate = now.toISOString().slice(0, 16);

            if (initialDate) {
                this.fechaHora = initialDate;
                this.activeShortcut = '';
            } else {
                this.setDate('hoy');
            }
        },

        setDate(tipo) {
            const now = new Date();
            const target = new Date(now);

            const offsets = { hoy: 0, manana: 1, '3dias': 3, semana: 7 };
            const offset = offsets[tipo] ?? 0;
            if (offset > 0) target.setDate(now.getDate() + offset);

            target.setHours(12, 0, 0, 0);
            target.setMinutes(target.getMinutes() - target.getTimezoneOffset());
            this.fechaHora = target.toISOString().slice(0, 16);
            this.activeShortcut = tipo;
        },

        formatLabel(daysFromNow) {
            const d = new Date();
            d.setDate(d.getDate() + daysFromNow);
            return d.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
        },

        get todayLabel()    { return this.formatLabel(0); },
        get mananaLabel()   { return this.formatLabel(1); },
        get tresDiasLabel() { return this.formatLabel(3); },
        get semanaLabel()   { return this.formatLabel(7); },

        get fechaLegible() {
            if (!this.fechaHora) return '';
            const d = new Date(this.fechaHora);
            if (isNaN(d.getTime())) return '';
            return d.toLocaleDateString('es-ES', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });
        },

        get horaLegible() {
            if (!this.fechaHora) return '';
            const d = new Date(this.fechaHora);
            if (isNaN(d.getTime())) return '';
            return d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        },
    };
}
