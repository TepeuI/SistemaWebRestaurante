document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-mesas");
  const btnInsertar = form.querySelector("button[type='submit']");
  const btnActualizar = form.querySelector(".btn-danger");

  let editando = false;
  let idEditando = null;

  // Inicialización
  cargarTabla();
  cargarSiguienteId();

  // Insertar o Modificar
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (!validarFormulario()) return;

    const datos = new FormData(form);
    datos.append("accion", editando ? "modificar" : "insertar");
    if (editando) datos.append("id_mesa", idEditando);

    try {
      const resp = await fetch("../mesas_crud.php", { method: "POST", body: datos });
      const res = await resp.json();

      if (res.status === "ok") {
        Swal.fire({
          icon: "success",
          title: editando ? "Mesa actualizada correctamente" : "Mesa agregada correctamente",
          showConfirmButton: false,
          timer: 1500,
        });

        form.reset();
        cargarTabla();
        cargarSiguienteId();
        editando = false;
        idEditando = null;
        btnInsertar.textContent = "Insertar";
      } else {
        Swal.fire({
          icon: "error",
          title: "Error en la operación",
          text: res.msg || "Ocurrió un error al procesar la mesa.",
        });
      }
    } catch (error) {
      console.error("Error:", error);
      Swal.fire({
        icon: "error",
        title: "Error de conexión",
        text: "No se pudo conectar con el servidor.",
      });
    }
  });

  // 🔹 Validaciones
  function validarFormulario() {
    const descripcion = document.getElementById("mesa-descripcion").value.trim();
    const capacidad = document.getElementById("mesa-capacidad").value.trim();
    const estado = document.getElementById("mesa-estado").value.trim();
    const regexDesc = /^[a-zA-Z0-9\s#-]+$/u;

    if (!descripcion || !capacidad || !estado) {
      Swal.fire({
        icon: "warning",
        title: "Campos incompletos",
        text: "Por favor, completa todos los campos antes de continuar.",
      });
      return false;
    }

    if (!regexDesc.test(descripcion)) {
      Swal.fire({
        icon: "warning",
        title: "Descripción inválida",
        text: "Solo se permiten letras, números, espacios, # y -.",
      });
      return false;
    }

    if (isNaN(capacidad) || capacidad <= 0) {
      Swal.fire({
        icon: "warning",
        title: "Capacidad inválida",
        text: "La capacidad debe ser un número mayor que cero.",
      });
      return false;
    }

    return true;
  }

  // 🔹 Cargar tabla
  function cargarTabla() {
    fetch("../mesas_crud.php", {
      method: "POST",
      body: new URLSearchParams({ accion: "listar" }),
    })
      .then((r) => r.json())
      .then((res) => {
        const tbody = document.querySelector("#tabla-mesas tbody");
        tbody.innerHTML = "";

        if (res.status === "ok" && res.data.length > 0) {
          res.data.forEach((row) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
              <td>${row.id_mesa}</td>
              <td>${row.descripcion}</td>
              <td>${row.capacidad_personas}</td>
              <td>
                <span class="badge ${row.estado === "DISPONIBLE" ? "bg-success" : "bg-secondary"}">
                  ${row.estado}
                </span>
              </td>
              <td class="text-center">
                <button class="btn btn-sm btn-primary btn-editar">✏️</button>
                <button class="btn btn-sm btn-danger btn-eliminar">🗑️</button>
              </td>
            `;

            tr.querySelector(".btn-editar").addEventListener("click", () => editarFila(row));
            tr.querySelector(".btn-eliminar").addEventListener("click", () => eliminarFila(row.id_mesa));
            tbody.appendChild(tr);
          });
        } else {
          tbody.innerHTML = `
            <tr>
              <td colspan="5" class="text-center text-muted py-3">No hay mesas registradas</td>
            </tr>`;
        }
      })
      .catch((err) => {
        console.error("Error al cargar tabla:", err);
        Swal.fire({
          icon: "error",
          title: "Error al cargar mesas",
          text: "No se pudo obtener la lista de mesas desde el servidor.",
        });
      });
  }

  // 🔹 Editar mesa
  function editarFila(row) {
    document.getElementById("mesa-id").value = row.id_mesa;
    document.getElementById("mesa-descripcion").value = row.descripcion;
    document.getElementById("mesa-capacidad").value = row.capacidad_personas;
    document.getElementById("mesa-estado").value = row.estado;

    idEditando = row.id_mesa;
    editando = true;
    btnInsertar.textContent = "Guardar Cambios";

    Swal.fire({
      icon: "info",
      title: `Editando mesa #${row.id_mesa}`,
      timer: 1200,
      showConfirmButton: false,
    });
  }

  // 🔹 Eliminar mesa
  function eliminarFila(id) {
    Swal.fire({
      title: `¿Eliminar mesa #${id}?`,
      text: "Esta acción no se puede deshacer.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then(async (result) => {
      if (result.isConfirmed) {
        try {
          const datos = new URLSearchParams({ accion: "eliminar", id_mesa: id });
          const resp = await fetch("../mesas_crud.php", { method: "POST", body: datos });
          const res = await resp.json();

          if (res.status === "ok") {
            Swal.fire({
              icon: "success",
              title: "Mesa eliminada correctamente",
              timer: 1300,
              showConfirmButton: false,
            });
            cargarTabla();
            cargarSiguienteId();
          } else {
            Swal.fire({
              icon: "error",
              title: "Error al eliminar",
              text: res.msg || "No se pudo eliminar la mesa.",
            });
          }
        } catch (err) {
          console.error("Error al eliminar:", err);
          Swal.fire({
            icon: "error",
            title: "Error de conexión",
            text: "No se pudo comunicar con el servidor.",
          });
        }
      }
    });
  }

  // 🔹 Botón Refrescar
  btnActualizar.addEventListener("click", () => {
    form.reset();
    editando = false;
    idEditando = null;
    btnInsertar.textContent = "Insertar";
    cargarTabla();
    cargarSiguienteId();

    Swal.fire({
      icon: "info",
      title: "Formulario reiniciado",
      text: "La tabla se actualizó y el formulario fue limpiado.",
      timer: 1500,
      showConfirmButton: false,
    });
  });

  // 🔹 Obtener siguiente ID
  function cargarSiguienteId() {
    fetch("../mesas_crud.php", {
      method: "POST",
      body: new URLSearchParams({ accion: "siguiente_id" }),
    })
      .then((r) => r.json())
      .then((res) => {
        if (res.status === "ok") {
          document.getElementById("mesa-id").value = res.siguiente;
        }
      })
      .catch((err) => console.error("Error al obtener ID:", err));
  }
});
