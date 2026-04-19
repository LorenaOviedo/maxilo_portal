/** Login */

document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    const usuarioInput = document.getElementById('usuario');
    const passwordInput = document.getElementById('password');
    const btnSubmit = loginForm.querySelector('.btn-submit');
 
    // Validación en tiempo real
    usuarioInput.addEventListener('blur', function () {
        validarUsuario();
    });
 
    passwordInput.addEventListener('blur', function () {
        validarPassword();
    });
 
    // Limpiar errores al modificar los camposS
    usuarioInput.addEventListener('input', function () {
        limpiarError('usuario');
    });
 
    passwordInput.addEventListener('input', function () {
        limpiarError('password');
    });
 
    // Validación al enviar el formulario
    loginForm.addEventListener('submit', function (e) {
        e.preventDefault();
 
        const usuarioValido = validarUsuario();
        const passwordValido = validarPassword();
 
        if (usuarioValido && passwordValido) {
            // Deshabilitar botón para evitar más envios
            btnSubmit.disabled = true;
            btnSubmit.textContent = 'Ingresando...';
 
            // Enviar el formulario
            this.submit();
        }
    });
 
    // Funciones de validación
    function validarUsuario() {
        const valor = usuarioInput.value.trim();
        const errorSpan = document.getElementById('usuarioError');
 
        if (valor === '') {
            mostrarError('usuario', 'El usuario o correo es requerido');
            return false;
        }
 
        if (valor.length < 3) {
            mostrarError('usuario', 'El usuario debe tener al menos 3 caracteres');
            return false;
        }
 
        // Validar si contiene @ el formato de email
        if (valor.includes('@')) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(valor)) {
                mostrarError('usuario', 'El formato del correo no es válido');
                return false;
            }
        }
 
        limpiarError('usuario');
        return true;
    }
 
    function validarPassword() {
        const valor = passwordInput.value;
 
        if (valor === '') {
            mostrarError('password', 'La contraseña es requerida');
            return false;
        }
 
        if (valor.length < 6) {
            mostrarError('password', 'La contraseña debe tener al menos 6 caracteres');
            return false;
        }
 
        limpiarError('password');
        return true;
    }
 
    function mostrarError(campo, mensaje) {
        const input = document.getElementById(campo);
        const errorSpan = document.getElementById(campo + 'Error');
 
        input.classList.add('error');
        errorSpan.textContent = mensaje;
    }
 
    function limpiarError(campo) {
        const input = document.getElementById(campo);
        const errorSpan = document.getElementById(campo + 'Error');
 
        input.classList.remove('error');
        errorSpan.textContent = '';
    }
 
    // Ocultar mensaje de error general después de 5 seg.
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) {
        setTimeout(function () {
            errorMessage.style.animation = 'slideUp 0.3s ease';
            setTimeout(function () {
                errorMessage.style.display = 'none';
            }, 300);
        }, 5000);
    }
 
    // ── Visor de contraseña ───────────────────────────────────────────
    agregarVisorContrasena(passwordInput);
});
 
/**
 * Agrega el ícono de ojo a un input de contraseña.
 * - Click sostenido → muestra la contraseña
 * - Suelta → vuelve a ocultar
 * @param {HTMLInputElement} input
 */
function agregarVisorContrasena(input) {
    if (!input) return;
 
    // Wrapper relativo para posicionar el ícono
    const wrapper = document.createElement('div');
    wrapper.style.cssText = 'position:relative; display:block;';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);
 
    // Botón con ícono de ojo
    const btn = document.createElement('button');
    btn.type      = 'button';
    btn.tabIndex  = -1; // no interrumpe el tab del formulario
    btn.setAttribute('aria-label', 'Mostrar contraseña');
    btn.innerHTML = '<i class="ri-eye-line"></i>';
    btn.style.cssText = `
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        color: #999;
        font-size: 18px;
        line-height: 1;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color .2s;
        user-select: none;
        -webkit-user-select: none;
    `;
 
    wrapper.appendChild(btn);
 
    // Agregar padding al input para que el texto no quede bajo el ícono
    input.style.paddingRight = '42px';
 
    // Mostrar mientras se mantiene presionado
    btn.addEventListener('mousedown', function (e) {
        e.preventDefault(); // evitar que el input pierda foco
        input.type = 'text';
        btn.style.color = '#008B8B';
        btn.innerHTML   = '<i class="ri-eye-off-line"></i>';
    });
 
    // Ocultar al soltar
    const ocultarContrasena = function () {
        input.type    = 'password';
        btn.style.color = '#999';
        btn.innerHTML   = '<i class="ri-eye-line"></i>';
    };
 
    btn.addEventListener('mouseup',    ocultarContrasena);
    btn.addEventListener('mouseleave', ocultarContrasena);
 
    // Touch para móviles
    btn.addEventListener('touchstart', function (e) {
        e.preventDefault();
        input.type    = 'text';
        btn.style.color = '#008B8B';
        btn.innerHTML   = '<i class="ri-eye-off-line"></i>';
    });
    btn.addEventListener('touchend', ocultarContrasena);
}
 
// Animación donde se ocult el mensaje
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }
`;
document.head.appendChild(style);