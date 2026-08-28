/* =====================================================================
   SGRSI — PixelMind · JavaScript del lado del cliente
   ---------------------------------------------------------------------
   Responsabilidades (exigidas por la letra, primera entrega y mantenidas):
     1. Manipulación del DOM
     2. Manejo de eventos
     3. Validaciones personalizadas
     4. Control de errores
   Las validaciones aquí son una CAPA DE COMODIDAD: el servidor repite
   todas las reglas en Validador.php (el cliente es evadible).
   ===================================================================== */
'use strict';

/* ------------------------------------------------------------------
 * 1) Utilidad: marcar un campo como inválido/ válido + mensaje
 * ------------------------------------------------------------------ */
function marcarCampo(campo, valido, mensajePersonalizado) {
    if (!campo) return;
    campo.classList.toggle('is-invalid', !valido);
    campo.classList.toggle('is-valid', valido && campo.value.trim() !== '');

    if (!valido && mensajePersonalizado) {
        const feedback = campo.parentElement.querySelector('.invalid-feedback')
            || campo.closest('.col-12, .col-md-6, .col-sm-6, .col')?.querySelector('.invalid-feedback');
        if (feedback) feedback.textContent = mensajePersonalizado;
    }
}

/* ------------------------------------------------------------------
 * 2) Validadores personalizados
 * ------------------------------------------------------------------ */
const validaciones = {
    requerido: (valor) => valor !== null && valor.trim() !== '',

    email: (valor) => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(valor.trim()),

    largoMin: (valor, min) => valor.trim().length >= parseInt(min, 10),

    // Contraseña: mínimo 8, al menos una mayúscula y un carácter especial
    passwordSegura: (valor) => /^(?=.*[A-Z])(?=.*[^\sA-Za-z0-9])\S{8,}$/.test(valor),

    // Texto con sentido: evita letras al azar del teclado (ej. "ajksbhhdjdbahjdbh")
    textoSensible: (valor) => {
        const limpio = valor.trim().toLowerCase().replace(/[^a-záéíóúüñ]/g, '');
        if (limpio.length < 6) return true; // siglas/códigos se permiten
        const vocales = (limpio.match(/[aeiouáéíóúü]/g) || []).length;
        if (vocales / limpio.length < 0.20) return false;
        const filas = ['qwertyuiop', 'asdfghjkl', 'zxcvbnm'];
        const mapa = {};
        filas.forEach((fila, f) => [...fila].forEach((chr, c) => { mapa[chr] = [f, c]; }));
        const chars = [...limpio];
        let total = 0, ady = 0;
        for (let i = 0; i < chars.length - 1; i++) {
            const a = mapa[chars[i]], b = mapa[chars[i + 1]];
            if (!a || !b) continue;
            total++;
            if (a[0] === b[0] && Math.abs(a[1] - b[1]) <= 1) ady++;
        }
        return total < 5 || ady / total < 0.7;
    },

    coinciden: (a, b) => a === b,

    fechaNoPasada: (valor) => {
        if (!valor) return false;
        const hoy = new Date(); hoy.setHours(0, 0, 0, 0);
        return new Date(valor + 'T00:00:00') >= hoy;
    },
};

/* ------------------------------------------------------------------
 * 3) Validación en vivo por formulario (manejo de eventos input/change)
 * ------------------------------------------------------------------ */
function validarCampoEnVivo(campo) {
    try {
        let ok = true;
        const valor = campo.value ?? '';

        if (campo.hasAttribute('required')) {
            ok = validaciones.requerido(valor);
        }
        if (ok && campo.type === 'email' && valor !== '') {
            ok = validaciones.email(valor);
        }
        if (ok && campo.hasAttribute('data-minlength')) {
            ok = validaciones.largoMin(valor, campo.dataset.minlength);
        }
        if (ok && campo.hasAttribute('data-password-segura')) {
            ok = validaciones.passwordSegura(valor);
        }
        if (ok && campo.hasAttribute('data-texto-sensible')) {
            ok = validaciones.textoSensible(valor);
        }
        if (ok && campo.hasAttribute('data-match')) {
            const par = document.querySelector(campo.dataset.match);
            ok = par ? validaciones.coinciden(par.value, valor) : false;
            // Si el original cambia, revalidar la confirmación
            if (par && !par.dataset.revalida) {
                par.dataset.revalida = '1';
                par.addEventListener('input', () => validarCampoEnVivo(campo));
            }
        }
        if (ok && campo.type === 'date' && campo.hasAttribute('min') && valor !== '') {
            ok = validaciones.fechaNoPasada(valor);
        }
        marcarCampo(campo, ok);
        return ok;
    } catch (error) {
        console.error('[SGRSI] Error validando campo:', error); // control de errores JS
        return true; // ante fallo interno no bloqueamos al usuario
    }
}

document.querySelectorAll('form[id]').forEach((form) => {
    form.setAttribute('novalidate', 'novalidate');

    form.querySelectorAll('input:not([type=hidden]), select, textarea').forEach((campo) => {
        campo.addEventListener('blur', () => validarCampoEnVivo(campo));
        campo.addEventListener('input', () => {
            if (campo.classList.contains('is-invalid')) validarCampoEnVivo(campo);
        });
    });

    /* Última barrera antes de enviar: valida todos los campos */
    form.addEventListener('submit', (evento) => {
        let formularioValido = true;

        form.querySelectorAll('input:not([type=hidden]), select, textarea').forEach((campo) => {
            const okCampo = validarCampoEnVivo(campo);
            if (!okCampo && formularioValido) campo.focus();
            formularioValido = formularioValido && okCampo;
        });

        if (!formularioValido) {
            evento.preventDefault();
            evento.stopPropagation();
            const primero = form.querySelector('.is-invalid');
            if (primero) primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
});

/* ------------------------------------------------------------------
 * 4) Medidor de fuerza de contraseña (data-strength)
 * ------------------------------------------------------------------ */
document.querySelectorAll('[data-strength]').forEach((campo) => {
    const barra = campo.parentElement.querySelector('.progress .progress-bar')
        || campo.closest('div')?.parentElement?.querySelector('.progress-bar');
    if (!barra) return;
    const contenedor = barra.closest('.progress');
    contenedor.classList.remove('d-none');

    campo.addEventListener('input', () => {
        const v = campo.value;
        let puntaje = Math.min(4, Math.floor(v.length / 3));
        if (/[A-Z]/.test(v) && /[a-z]/.test(v)) puntaje++;
        if (/\d/.test(v)) puntaje++;
        if (/[^A-Za-z0-9]/.test(v)) puntaje++;

        const niveles = [
            ['20%', 'bg-danger'], ['40%', 'bg-warning'],
            ['60%', 'bg-info'],   ['80%', 'bg-primary'], ['100%', 'bg-success'],
        ];
        const [ancho, color] = niveles[Math.min(puntaje, 4)];
        barra.style.width = ancho;
        barra.className = 'progress-bar ' + color;
    });
});

/* ------------------------------------------------------------------
 * 5) Mostrar/ocultar contraseña (data-toggle-password)
 * ------------------------------------------------------------------ */
document.querySelectorAll('[data-toggle-password]').forEach((boton) => {
    boton.addEventListener('click', () => {
        const objetivo = document.querySelector(boton.dataset.togglePassword);
        if (!objetivo) return;
        objetivo.type = objetivo.type === 'password' ? 'text' : 'password';
        boton.classList.toggle('active');
    });
});

/* ------------------------------------------------------------------
 * 6) Contador de caracteres en textareas (data-contador-caracteres)
 * ------------------------------------------------------------------ */
document.querySelectorAll('[data-contador-caracteres]').forEach((area) => {
    const salida = area.parentElement.querySelector('[data-salida-contador]');
    if (!salida) return;
    const actualizar = () => { salida.textContent = String(area.value.length); };
    area.addEventListener('input', actualizar);
    actualizar();
});

/* ------------------------------------------------------------------
 * 7) Confirmaciones antes de acciones sensibles (data-confirmar)
 * ------------------------------------------------------------------ */
document.querySelectorAll('form[data-confirmar]').forEach((form) => {
    form.addEventListener('submit', (evento) => {
        if (!window.confirm(form.dataset.confirmar)) {
            evento.preventDefault();
        }
    });
});

/* ------------------------------------------------------------------
 * 8) Filtros que se aplican solos al cambiar un select (data-filtro-auto)
 * ------------------------------------------------------------------ */
document.querySelectorAll('form[data-filtro-auto] select').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
});

/* ------------------------------------------------------------------
 * 9) Aviso "nota obligatoria" cuando se resuelve un ticket
 *    (#avisoResolucion + data-estado-actual en detalle ticket/solicitud)
 * ------------------------------------------------------------------ */
const selectorEstado = document.querySelector('#estado[data-estado-actual], #estado');
const avisoResolucion = document.getElementById('avisoResolucion');
if (selectorEstado && avisoResolucion) {
    selectorEstado.addEventListener('change', () => {
        const opcion = selectorEstado.options[selectorEstado.selectedIndex];
        const texto = opcion ? opcion.textContent.toLowerCase() : '';
        avisoResolucion.classList.toggle('d-none', !texto.includes('resuelto'));
    });
}

/* ------------------------------------------------------------------
 * 10) Advertencia al poner un equipo en estado «baja»
 * ------------------------------------------------------------------ */
const selectorBaja = document.querySelector('[data-advertencia-baja]');
if (selectorBaja) {
    selectorBaja.addEventListener('change', () => {
        const opcion = selectorBaja.options[selectorBaja.selectedIndex];
        const texto = opcion ? opcion.textContent.toLowerCase() : '';
        if (texto.includes('baja')) {
            alert('Atención: dar de baja cierra la asignación activa del equipo.');
        }
    });
}

/* ------------------------------------------------------------------
 * 11) Contadores animados del dashboard ([data-contador])
 * ------------------------------------------------------------------ */
document.querySelectorAll('[data-contador]').forEach((elemento) => {
    const destino = parseInt(elemento.dataset.contador, 10) || 0;
    if (destino <= 0 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const duracionMs = 700;
    const inicio = performance.now();
    const paso = (ahora) => {
        const progreso = Math.min(1, (ahora - inicio) / duracionMs);
        elemento.textContent = String(Math.round(destino * progreso));
        if (progreso < 1) requestAnimationFrame(paso);
    };
    elemento.textContent = '0';
    requestAnimationFrame(paso);
});
