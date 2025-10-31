document.addEventListener('DOMContentLoaded', function() {
    // Configurar valores por defecto
    const precioMinInput = document.getElementById('precio_min');
    const precioMaxInput = document.getElementById('precio_max');
    
    if (precioMinInput && !precioMinInput.value) {
        precioMinInput.value = '';
    }
    
    if (precioMaxInput && !precioMaxInput.value) {
        precioMaxInput.value = '';
    }
    
    // Validar que valor mínimo no sea mayor que máximo
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const precioMin = parseFloat(precioMinInput?.value) || 0;
            const precioMax = parseFloat(precioMaxInput?.value) || 0;
            
            if (precioMin > 0 && precioMax > 0 && precioMin > precioMax) {
                e.preventDefault();
                const tipoConsulta = document.getElementById('tipo_consulta')?.value;
                const campo = tipoConsulta === 'ingredientes' ? 'stock' : 'precio';
                alert(`El ${campo} mínimo no puede ser mayor que el ${campo} máximo`);
                precioMinInput?.focus();
            }
        });
    }
    
    // Auto-submit al cambiar tipo de consulta
    const tipoConsultaSelect = document.getElementById('tipo_consulta');
    if (tipoConsultaSelect) {
        tipoConsultaSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
});

function verDetalle(id, tipo) {
    let mensaje = '';
    switch(tipo) {
        case 'platos':
            mensaje = `Ver detalle del plato ID: ${id}`;
            break;
        case 'bebidas':
            mensaje = `Ver detalle de la bebida ID: ${id}`;
            break;
        case 'ingredientes':
            mensaje = `Ver detalle del ingrediente ID: ${id}`;
            break;
        case 'recetas':
            mensaje = `Ver detalle de la receta ID: ${id}`;
            break;
    }
    alert(mensaje);
    // window.location.href = `detalle_${tipo}.php?id=${id}`;
}

function exportarPDF() {
    const tipoConsulta = document.getElementById('tipo_consulta')?.value || 'platos';
    alert(`Exportando consulta de ${tipoConsulta} a PDF`);
    // window.location.href = `exportar_pdf.php?${window.location.search}`;
}

function exportarExcel() {
    const tipoConsulta = document.getElementById('tipo_consulta')?.value || 'platos';
    alert(`Exportando consulta de ${tipoConsulta} a Excel`);
    // window.location.href = `exportar_excel.php?${window.location.search}`;
}

// Función para aplicar filtros rápidos
function aplicarFiltroRapido(tipo, filtro) {
    const tipoConsultaSelect = document.getElementById('tipo_consulta');
    const precioMinInput = document.getElementById('precio_min');
    const precioMaxInput = document.getElementById('precio_max');
    
    if (tipoConsultaSelect) {
        tipoConsultaSelect.value = tipo;
        
        if (precioMinInput && precioMaxInput) {
            switch(filtro) {
                case 'economico':
                    precioMinInput.value = '0';
                    precioMaxInput.value = tipo === 'ingredientes' ? '10' : '10';
                    break;
                case 'medio':
                    precioMinInput.value = tipo === 'ingredientes' ? '10' : '10';
                    precioMaxInput.value = tipo === 'ingredientes' ? '50' : '25';
                    break;
                case 'premium':
                    precioMinInput.value = tipo === 'ingredientes' ? '50' : '25';
                    precioMaxInput.value = '1000';
                    break;
            }
        }
        
        tipoConsultaSelect.form.submit();
    }
}