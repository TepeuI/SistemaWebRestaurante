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

    const resp = await fetch("../mesas_crud.php", { method: "POST", body: datos });
    const res = await resp.json();

    if (res.status === "ok") {
      Swal.fire({
        icon: "success",
        title: editando ? "Mesa modificada" : "Mesa agregada",
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
        text: res.msg ?? "Revisa los datos.",
      });
    }
  });

  // Validación básica
  function validarFormulario() {
    let valid = true;
    const regexSQL = /['";=<>*%]/;

    form.querySelectorAll("input, select").forEach(input => {
      if (!input.value.trim()) {
        input.classList.add("is-invalid");
        valid = false;
      } else if (regexSQL.test(input.value)) {
        Swal.fire({
          icon: "warning",
          title: "Carácteres inválidos",
          text: `El campo "${input.id}" contiene caracteres no permitidos.`,
        });
        input.classList.add("is-invalid");
        valid = false;
      } else {
        input.classList.remove("is-invalid");
      }
    });

    if (!valid) {
      Swal.fire({
        icon: "warning",
        title: "Formulario incompleto",
        text: "Por favor, corrige los campos marcados en rojo.",
      });
    }
    return valid;
  }

  // Cargar Tabla
  function cargarTabla() {
    fetch("../mesas_crud.php", {
      method: "POST",
      body: new URLSearchParams({ accion: "listar" }),
    })
      .then(r => r.json())
      .then(res => {
        const tbody = document.querySelector("#tabla-mesas tbody");
        tbody.innerHTML = "";
        if (res.status === "ok") {
          res.data.forEach(row => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
              <td>${row.id_mesa}</td>
              <td>${row.descripcion}</td>
              <td>${row.capacidad_personas}</td>
              <td>${row.estado}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-primary btn-editar">✏️</button>
                <button class="btn btn-sm btn-danger btn-eliminar">🗑️</button>
              </td>
            `;
            tr.querySelector(".btn-editar").addEventListener("click", () => editarFila(row));
            tr.querySelector(".btn-eliminar").addEventListener("click", () => eliminarFila(row.id_mesa));
            tbody.appendChild(tr);
          });
        }
      });
  }

  // Editar Mesa
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
      showConfirmButton: false,
      timer: 1200,
    });
  }

  // Eliminar Mesa
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
    }).then(result => {
      if (result.isConfirmed) {
        const datos = new URLSearchParams();
        datos.append("accion", "eliminar");
        datos.append("id_mesa", id);

        fetch("../mesas_crud.php", { method: "POST", body: datos })
          .then(r => r.json())
          .then(res => {
            if (res.status === "ok") {
              Swal.fire({
                icon: "success",
                title: "Mesa eliminada",
                showConfirmButton: false,
                timer: 1300,
              });
              cargarTabla();
              cargarSiguienteId();
            }
          });
      }
    });
  }

  //boton de refrescar
  btnActualizar.addEventListener("click", () => {
    cargarTabla();
    form.reset();
    editando = false;
    idEditando = null;
    btnInsertar.textContent = "Insertar";
    cargarSiguienteId();

    Swal.fire({
      icon: "info",
      title: "Formulario reiniciado",
      text: "La tabla ha sido actualizada y el formulario se limpió.",
      timer: 1500,
      showConfirmButton: false
    });
  });

});

// obtener siguiente ID
function cargarSiguienteId() {
  fetch("../mesas_crud.php", {
    method: "POST",
    body: new URLSearchParams({ accion: "siguiente_id" }),
  })
    .then(r => r.json())
    .then(res => {
      if (res.status === "ok") {
        document.getElementById("mesa-id").value = res.siguiente;
      }
    });
}
