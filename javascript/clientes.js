document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("form-clientes");
  const btnGuardar = form.querySelector("button[type='submit']");
  const btnRefrescar = document.querySelector(".btn-refrescar");
  let editando = false;
  let idEditando = null;

  // Inicialización
  cargarClientes();
  cargarSiguienteId();

  //insertar y modificar
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (!validarFormulario()) return;

    const datos = new FormData(form);
    datos.append("accion", editando ? "modificar" : "insertar");
    if (editando) datos.append("id_cliente", idEditando);

    try {
      const resp = await fetch("../clientes_crud.php", {
        method: "POST",
        body: datos,
      });
      const res = await resp.json();

      if (res.status === "ok") {
        Swal.fire({
          icon: "success",
          title: editando
            ? "Cliente actualizado correctamente"
            : "Cliente agregado correctamente",
          showConfirmButton: false,
          timer: 1500,
        });

        form.reset();
        editando = false;
        idEditando = null;
        btnGuardar.textContent = "Insertar";

        cargarClientes();
        cargarSiguienteId();
      } else {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: res.msg || "No se pudo completar la operación.",
        });
      }
    } catch (error) {
      console.error("Error al insertar/modificar:", error);
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "No se pudo conectar con el servidor.",
      });
    }
  });


  //validaciones basicas
  function validarFormulario() {
    const regexSQL = /['";=<>*%]/;
    let valido = true;
    let camposInvalidos = [];

    form.querySelectorAll("input").forEach((input) => {
      const valor = input.value.trim();

      // No validar ID
      if (input.id === "cliente-id") return;

      if (!valor) {
        valido = false;
        camposInvalidos.push(input.id);
        input.classList.add("is-invalid");
      } else if (regexSQL.test(valor)) {
        valido = false;
        camposInvalidos.push(input.id);
        input.classList.add("is-invalid");
      } else {
        input.classList.remove("is-invalid");
      }
    });

    if (!valido) {
      Swal.fire({
        icon: "warning",
        title: "Campos inválidos",
        text: "Por favor, completa todos los campos y evita caracteres especiales.",
      });
    }

    return valido;
  }

  //cargar clientes
  function cargarClientes() {
    fetch("../clientes_crud.php", {
      method: "POST",
      body: new URLSearchParams({ accion: "listar" }),
    })
      .then((r) => r.json())
      .then((res) => {
        const tbody = document.querySelector("#tabla-clientes tbody");
        tbody.innerHTML = "";

        if (res.status === "ok" && res.data.length > 0) {
          res.data.forEach((row) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
              <td>${row.id_cliente}</td>
              <td>${row.nombre}</td>
              <td>${row.apellido}</td>
              <td>${row.nit}</td>
              <td>${row.telefono}</td>
              <td>${row.correo}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-primary btn-editar">✏️</button>
                <button class="btn btn-sm btn-danger btn-eliminar">🗑️</button>
              </td>
            `;
            tr.querySelector(".btn-editar").addEventListener("click", () =>
              editarFila(row)
            );
            tr.querySelector(".btn-eliminar").addEventListener("click", () =>
              eliminarFila(row.id_cliente)
            );
            tbody.appendChild(tr);
          });
        } else {
          tbody.innerHTML = `
            <tr>
              <td colspan="7" class="text-center text-muted py-3">
                No hay clientes registrados
              </td>
            </tr>`;
        }
      })
      .catch((err) => console.error("Error al cargar clientes:", err));
  }

  //modificar cliente
  function editarFila(row) {
    document.getElementById("cliente-id").value = row.id_cliente;
    document.getElementById("cliente-nombre").value = row.nombre;
    document.getElementById("cliente-apellido").value = row.apellido;
    document.getElementById("cliente-nit").value = row.nit;
    document.getElementById("cliente-telefono").value = row.telefono;
    document.getElementById("cliente-correo").value = row.correo;

    editando = true;
    idEditando = row.id_cliente;
    btnGuardar.textContent = "Guardar cambios";

    Swal.fire({
      icon: "info",
      title: `Editando cliente #${row.id_cliente}`,
      timer: 1200,
      showConfirmButton: false,
    });
  }

  //eliminar cliente
  function eliminarFila(id) {
    Swal.fire({
      title: "¿Eliminar cliente?",
      text: `¿Estás seguro de eliminar el cliente #${id}? Esta acción no se puede deshacer.`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then(async (r) => {
      if (r.isConfirmed) {
        const datos = new URLSearchParams({
          accion: "eliminar",
          id_cliente: id,
        });

        try {
          const resp = await fetch("../clientes_crud.php", {
            method: "POST",
            body: datos,
          });
          const res = await resp.json();

          if (res.status === "ok") {
            Swal.fire({
              icon: "success",
              title: "Cliente eliminado correctamente",
              timer: 1200,
              showConfirmButton: false,
            });
            cargarClientes();
            cargarSiguienteId();
          } else {
            Swal.fire({
              icon: "error",
              title: "Error al eliminar",
              text: res.msg || "No se pudo eliminar el cliente.",
            });
          }
        } catch (err) {
          console.error("Error al eliminar:", err);
          Swal.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo conectar con el servidor.",
          });
        }
      }
    });
  }

  // refrescar y actualizar
  btnRefrescar.addEventListener("click", () => {
    form.reset();
    editando = false;
    idEditando = null;
    btnGuardar.textContent = "Insertar";
    cargarClientes();
    cargarSiguienteId();

    Swal.fire({
      icon: "info",
      title: "Formulario reiniciado",
      text: "Puedes ingresar un nuevo cliente.",
      timer: 1300,
      showConfirmButton: false,
    });
  });

    //obtener el ultimo id 
  function cargarSiguienteId() {
    fetch("../clientes_crud.php", {
      method: "POST",
      body: new URLSearchParams({ accion: "siguiente_id" }),
    })
      .then((r) => r.json())
      .then((res) => {
        if (res.status === "ok")
          document.getElementById("cliente-id").value = res.siguiente;
      })
      .catch((err) => console.error("Error al obtener siguiente ID:", err));
  }
});
