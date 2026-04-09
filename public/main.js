    function formatearFecha() {
        const hoy = new Date();
        
        const dia = String(hoy.getDate()).padStart(2, '0');
        const mes = String(hoy.getMonth() + 1).padStart(2, '0'); // Los meses en JS empiezan en 0
        const anio = hoy.getFullYear();

        const fechaCompleta = `${dia}/${mes}/${anio}`;
        
        document.getElementById('fecha-actual').textContent = fechaCompleta;
    }

    // Ejecutar la función al cargar la página
    formatearFecha();