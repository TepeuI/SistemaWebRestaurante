document.addEventListener('DOMContentLoaded', function() {
    const submenuToggles = document.querySelectorAll('.submenu-toggle');

    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();

            const parentLi = this.parentElement;
            const parentUl = parentLi.parentElement; // ul that contains this li
            const submenu = parentLi.querySelector('.submenu');
            const isActive = parentLi.classList.contains('active');

            // Cerrar solo los elementos hermanos (misma profundidad)
            if (parentUl) {
                Array.from(parentUl.children).forEach(li => {
                    if (li !== parentLi) {
                        li.classList.remove('active');
                        const sibSub = li.querySelector('.submenu');
                        if (sibSub) {
                            sibSub.style.maxHeight = null;
                        }
                        const sibToggle = li.querySelector('.submenu-toggle');
                        if (sibToggle) sibToggle.classList.remove('active');
                    }
                });
            }

            if (submenu) {
                if (isActive) {
                    // cerrar
                    parentLi.classList.remove('active');
                    this.classList.remove('active');
                    submenu.style.maxHeight = null;
                } else {
                    // abrir
                    parentLi.classList.add('active');
                    this.classList.add('active');
                    // calcular altura real para animación
                    submenu.style.maxHeight = submenu.scrollHeight + 'px';
                }
            }
        });
    });

    // Al cargar la página, asegurar que submenús activos tengan su altura correcta
    function adjustActiveSubmenus() {
        document.querySelectorAll('.sidebar-empleados li.active > .submenu').forEach(s => {
            s.style.maxHeight = s.scrollHeight + 'px';
        });
    }

    adjustActiveSubmenus();

    // Ajustar alturas al redimensionar ventana (para contenidos dinámicos)
    window.addEventListener('resize', function() {
        adjustActiveSubmenus();
    });
});
