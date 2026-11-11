export default function selectorCliente(seleccion, urlBuscar, urlAlta, csrf) {
    return {
        seleccion,
        texto: '',
        resultados: [],
        estado: '',
        buscando: false,
        alta: false,
        guardando: false,
        nombre: '',
        telefono: '',
        errorAlta: '',
        espera: null,
        consulta: null,

        enfocar(campo) {
            const destino = this.$root.querySelector(`[data-foco-cliente="${campo}"]`);
            this.$nextTick(() => requestAnimationFrame(() => requestAnimationFrame(() => destino?.focus())));
        },

        abrirBusqueda() {
            this.buscando = true;
            this.enfocar('busqueda');
        },

        cerrarBusqueda() {
            clearTimeout(this.espera);
            this.consulta?.abort();
            this.buscando = false;
            this.alta = false;
            this.texto = '';
            this.resultados = [];
            this.estado = '';
            this.enfocar(this.seleccion ? 'cambiar' : 'busqueda');
        },

        quitar() {
            this.seleccion = null;
            this.cerrarBusqueda();
        },

        elegir(opcion) {
            this.seleccion = opcion;
            this.nombre = '';
            this.telefono = '';
            this.errorAlta = '';
            this.cerrarBusqueda();
        },

        programarBusqueda() {
            clearTimeout(this.espera);
            this.consulta?.abort();
            this.resultados = [];
            this.estado = '';
            this.espera = setTimeout(() => this.buscar(), 250);
        },

        async buscar() {
            clearTimeout(this.espera);
            this.consulta?.abort();

            const texto = this.texto.trim();
            if (texto.length < 2) {
                this.resultados = [];
                this.estado = texto ? 'Escribe al menos dos caracteres.' : '';
                return;
            }

            const consulta = new AbortController();
            this.consulta = consulta;
            this.estado = 'Buscando…';
            this.resultados = [];

            try {
                const url = new URL(urlBuscar);
                url.searchParams.set('q', texto);
                const respuesta = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: consulta.signal,
                    cache: 'no-store',
                });

                if (!respuesta.ok) throw new Error();
                const resultados = await respuesta.json();
                if (consulta.signal.aborted) return;

                this.resultados = resultados;
                this.estado = resultados.length
                    ? (resultados.length === 10 ? 'Mostrando hasta 10 coincidencias. Puedes precisar la búsqueda.' : `${resultados.length} coincidencias.`)
                    : 'No encontramos clientes activos.';
            } catch {
                if (!consulta.signal.aborted) this.estado = 'No se pudo buscar. Presiona Enter para reintentar.';
            }
        },

        async registrar() {
            if (this.guardando) return;

            this.guardando = true;
            this.errorAlta = '';
            try {
                const respuesta = await fetch(urlAlta, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ nombre: this.nombre, telefono: this.telefono }),
                });

                if (respuesta.status === 422) {
                    const { errors } = await respuesta.json();
                    this.errorAlta = Object.values(errors).flat().join(' ');
                    return;
                }

                if (!respuesta.ok) throw new Error();
                this.elegir(await respuesta.json());
            } catch {
                this.errorAlta = 'No se pudo registrar el cliente. Intenta de nuevo.';
            } finally {
                this.guardando = false;
            }
        },
    };
}
