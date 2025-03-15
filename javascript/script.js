document.getElementById("accion").addEventListener("change", function() {
    const accion = this.value;
    const procedimientoSelect = document.getElementById("procedimiento");
    const opcionesDiv = document.getElementById("opciones");
    
    if (accion) {
        opcionesDiv.style.display = "block";
        procedimientoSelect.innerHTML = "<option value=''>Seleccione...</option>";
        
        const procedimientos = {
            insertar: ["InsertarAnimal", "InsertarEspecie", "InsertarPlanta"],
            actualizar: ["ActualizarAnimal", "ActualizarEspecie", "ActualizarPlanta"],
            eliminar: ["EliminarAnimal", "EliminarEspecie", "EliminarPlanta"]
        };
        
        procedimientos[accion].forEach(proc => {
            const option = document.createElement("option");
            option.value = proc;
            option.textContent = proc;
            procedimientoSelect.appendChild(option);
        });
    } else {
        opcionesDiv.style.display = "none";
    }
});

document.getElementById("procedimiento").addEventListener("change", function() {
    const formulario = document.getElementById("formulario");
    const camposDiv = document.getElementById("campos");
    camposDiv.innerHTML = "";
    
    if (this.value) {
        formulario.style.display = "block";
        
        const campos = {
            InsertarAnimal: ["nombre", "edad", "especie_id"],
            ActualizarAnimal: ["id", "nombre", "edad"],
            EliminarAnimal: ["id"]
        };
        
        (campos[this.value] || []).forEach(campo => {
            const input = document.createElement("input");
            input.type = "text";
            input.name = campo;
            input.placeholder = campo;
            camposDiv.appendChild(input);
        });
    } else {
        formulario.style.display = "none";
    }
});

document.getElementById("formulario").addEventListener("submit", function(e) {
    e.preventDefault();
    
    const accion = document.getElementById("accion").value;
    const procedimiento = document.getElementById("procedimiento").value;
    const params = {};
    
    document.querySelectorAll("#campos input").forEach(input => {
        params[input.name] = input.value;
    });
    
    fetch("procesos.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `accion=${accion}&procedimiento=${procedimiento}&params=${JSON.stringify(params)}`
    })
    .then(response => response.json())
    .then(data => {
        Swal.fire(data.status === "success" ? "Éxito" : "Error", data.message, data.status);
    })
    .catch(() => Swal.fire("Error", "Hubo un problema con la solicitud", "error"));
});