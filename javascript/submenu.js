document.addEventListener('DOMContentLoaded', function() {
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            const parentLi = this.parentElement;
            const isActive = parentLi.classList.contains('active');
            
            // Cerrar todos los submenús
            document.querySelectorAll('.sidebar-empleados li').forEach(li => {
                li.classList.remove('active');
            });
            
            document.querySelectorAll('.submenu-toggle').forEach(t => {
                t.classList.remove('active');
            });
            
            // Si no estaba activo, abrirlo
            if (!isActive) {
                parentLi.classList.add('active');
                this.classList.add('active');
            }
        });
    });
});
