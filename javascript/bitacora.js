document.addEventListener("DOMContentLoaded", () => {
  const inputUsuario = document.getElementById("filtro-usuario");
  const inputInicio = document.getElementById("filtro-fecha-inicio");
  const inputFin = document.getElementById("filtro-fecha-fin");
  const btnFiltrar = document.getElementById("btn-filtrar");
  const btnLimpiar = document.getElementById("btn-limpiar");

  cargarBitacora(); // carga inicial sin filtros

  btnFiltrar.addEventListener("click", () => {
    cargarBitacora(inputUsuario.value.trim(), inputInicio.value, inputFin.value);
  });

  btnLimpiar.addEventListener("click", () => {
    inputUsuario.value = "";
    inputInicio.value = "";
    inputFin.value = "";
    cargarBitacora();
  });

  function cargarBitacora(usuario = "", fechaInicio = "", fechaFin = "") {
    const datos = new URLSearchParams();
    datos.append("accion", "listar");
    datos.append("usuario", usuario);
    datos.append("fecha_inicio", fechaInicio);
    datos.append("fecha_fin", fechaFin);

    fetch("../bitacora_crud.php", {
  method: "POST",
  body: datos,
})
  .then((r) => r.text())
  .then((text) => {
    console.log("Respuesta del servidor:", text);
    const res = JSON.parse(text);
    const tbody = document.querySelector("#tabla-bitacora tbody");
    tbody.innerHTML = "";

    if (res.status === "ok" && res.data.length > 0) {
      res.data.forEach((row) => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${row.id_bitacora}</td>
          <td>${row.usuario}</td>
          <td>${row.operacion_realizada}</td>
          <td>${row.ip}</td>
          <td>${row.pc}</td>
          <td>${row.fecha_hora_accion}</td>
        `;
        tbody.appendChild(tr);
      });
    } else {
      tbody.innerHTML =
        '<tr><td colspan="6" class="text-muted py-3">No hay registros de bitácora</td></tr>';
    }
  })
  .catch((err) => console.error("Error al cargar bitácora:", err));

  }
});


