function mostrarSubopciones() {
    const opcionPrincipal = document.getElementById('opcionPrincipal').value;
    const subopcionesDiv = document.getElementById('subopcionesContainer');
    subopcionesDiv.innerHTML = '';

    if (opcionPrincipal === '') return;

    // Crear el select de subopciones
    const select = document.createElement('select');
    select.id = 'subopcion';
    select.name = 'subopcion';
    select.className = 'form-select mb-3';
    select.required = true;
    select.onchange = cargarParametros;

    // Opción por defecto
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = '-- Selecciona un procedimiento --';
    defaultOption.disabled = true;
    defaultOption.selected = true;
    select.appendChild(defaultOption);

    // Llenar con opciones según la categoría
    if (opcionPrincipal === 'insertar') {
        agregarOpcion(select, 'InsertarAlimentacion', 'Insertar Alimentación');
        agregarOpcion(select, 'InsertarAnimal', 'Insertar Animal');
        agregarOpcion(select, 'InsertarCosto', 'Insertar Costo');
        agregarOpcion(select, 'InsertarEmpleado', 'Insertar Empleado');
        agregarOpcion(select, 'InsertarEspecie', 'Insertar Especie');
        agregarOpcion(select, 'InsertarHistorialSalud', 'Insertar Nuevo historial de salud');
        agregarOpcion(select, 'InsertarInventario', 'Insertar nuevo producto al inventario');
        agregarOpcion(select, 'InsertarPlanta', 'Insertar Nueva Planta');
        agregarOpcion(select, 'InsertarProduccion', 'Insertar Produccion');
        agregarOpcion(select, 'InsertarProveedor', 'Insertar Nuevo Proveedor');
        agregarOpcion(select, 'InsertarReporte', 'Insertar Nuevo Reporte');
        agregarOpcion(select, 'InsertarVacuna', 'Insertar Nuevo tipo de Vacuna');
        agregarOpcion(select, 'InsertarVacunacion', 'Insertar nueva vacunacion');
        agregarOpcion(select, 'InsertarVenta', 'Insertar Venta');
    } 
    else if (opcionPrincipal === 'actualizar') {
        agregarOpcion(select, 'ActualizarVacuna', 'Actualizar Vacuna');
        agregarOpcion(select, 'actualizar_alimentacion', 'Actualizar Alimentacion');
        agregarOpcion(select, 'actualizar_animal', 'Actualizar Animal');
        agregarOpcion(select, 'actualizar_empleado', 'Actualizar Empleado');
        agregarOpcion(select, 'actualizar_animal', 'Actualizar Animal');
        agregarOpcion(select, 'actualizar_especie', 'Actualizar Especie');
        agregarOpcion(select, 'actualizar_estado_salud', 'Actualizar Estado de salud');
        agregarOpcion(select, 'actualizar_inventario', 'Actualizar Inventario');
        agregarOpcion(select, 'actualizar_planta', 'Actualizar Planta');
        agregarOpcion(select, 'actualizar_produccion', 'Actualizar Produccion');
        agregarOpcion(select, 'actualizar_proveedor', 'Actualizar Proveedor');
    }
    else if (opcionPrincipal === 'eliminar') {
        agregarOpcion(select, 'EliminarAnimal', 'Eliminar Animal');
        agregarOpcion(select, 'EliminarEmpleado', 'Eliminar Empleado');
        agregarOpcion(select, 'EliminarEspecie', 'Eliminar Especie');
        agregarOpcion(select, 'EliminarPlanta', 'Eliminar Planta');
        agregarOpcion(select, 'EliminarProveedor', 'Eliminar Proveedor');
        agregarOpcion(select, 'EliminarReporte', 'Eliminar Reporte');
        agregarOpcion(select, 'EliminarVacuna', 'Eliminar Vacuna');
    }

    subopcionesDiv.appendChild(select);
}

function agregarOpcion(selectElement, value, text) {
    const option = document.createElement('option');
    option.value = value;
    option.textContent = text;
    selectElement.appendChild(option);
}

function cargarParametros() {
    const subopcion = document.getElementById('subopcion').value;
    const parametrosDiv = document.getElementById('parametrosContainer');
    parametrosDiv.innerHTML = '';

    if (!subopcion) return;

    // Definir parámetros para cada procedimiento
    const parametros = {
        // Procedimientos de inserción
        'InsertarAlimentacion': ['ID Especie', 'Tipo Alimento', 'Comidas al dia', 'Cantidad (g)', 'Ultima alimentacion'],
        'InsertarAnimal': ['Nombre científico', 'Nombre común', 'ID Especie', 'Edad', 'Ubicación', 'Estado', 'Descripción'],
        'InsertarCosto': ['Tipo de costo', 'Descripción', 'Monto', 'Fecha', 'ID Empleado', 'ID Animal', 'ID Especie'],
        'InsertarEmpleado': ['Nombre', 'Rol', 'Teléfono', 'Fecha contratación', 'Salario'],
        'InsertarEspecie': ['Nombre', 'Descripción'],
        'InsertarHistorialSalud': ['ID Animal', 'ID Planta', 'Estado anterior', 'Estado nuevo'],
        'InsertarInventario': ['Nombre producto', 'Cantidad', 'Unidad medida', 'Fecha ingreso', 'ID Proveedor'],
        'InsertarPlanta': ['Nombre científico', 'Nombre común', 'Ubicación', 'Estado', 'Descripción'],
        'InsertarProduccion': ['ID Animal', 'Tipo producción', 'Cantidad', 'Fecha recolección'],
        'InsertarProveedor': ['Nombre', 'Teléfono', 'Dirección', 'Tipo producto'],
        'InsertarReporte': ['ID Usuario', 'ID Planta', 'ID Animal', 'Tipo', 'Diagnóstico'],
        'InsertarTratamiento': ['ID Reporte', 'Descripción', 'Fecha inicio', 'Fecha fin', 'Resultado'],
        'InsertarVacuna': ['Nombre', 'Descripción', 'Fabricante', 'Temperatura almacenamiento', 'Vida útil', 'Cantidad'],
        'InsertarVacunacion': ['ID Animal', 'ID Vacuna', 'Fecha aplicación', 'Próxima dosis', 'Dosis', 'ID Empleado', 'Observaciones'],
        'InsertarVenta': ['ID Producción', 'ID Animal', 'Cantidad', 'Precio total', 'Fecha venta', 'Comprador'],
        
        // Procedimientos de actualización
        'ActualizarVacuna': ['ID Vacuna', 'Nuevo Nombre', 'Nueva Descripción', 'Fabricante', 'Temperatura Almacenamiento', 'Vida Útil', 'Cantidad'],
        'actualizar_alimentacion': ['ID Alimentación', 'ID Especie', 'Tipo Alimento', 'Comidas por día', 'Cantidad (g)', 'Fecha Última Alimentación'],
        'actualizar_animal': ['ID Animal', 'Nombre Científico', 'Nombre Común', 'ID Especie', 'Edad', 'Ubicación', 'Estado', 'Descripción'],
        'actualizar_empleado': ['ID Empleado', 'Nombre', 'Apellido', 'Cargo', 'Salario'],
        'actualizar_especie': ['ID Especie', 'Nombre Especie', 'Descripción'],
        'actualizar_estado_salud': ['ID Historial', 'Estado Anterior', 'Estado Nuevo'],
        'actualizar_inventario': ['ID Inventario', 'Ítem', 'Cantidad', 'Fecha Actualización'],
        'actualizar_planta': ['ID Planta', 'Nombre Científico', 'Nombre Común', 'Ubicación', 'Estado', 'Descripción'],
        'actualizar_produccion': ['ID Producción', 'ID Animal', 'Tipo Producción', 'Cantidad', 'Fecha Recolección'],
        'actualizar_proveedor': ['ID Proveedor', 'Nombre', 'Teléfono', 'Dirección'],
        
        // Procedimientos de eliminación
        'EliminarAnimal': ['ID Animal'],
        'EliminarEmpleado': ['ID Empleado'],
        'EliminarEspecie': ['ID Especie'],
        'EliminarPlanta': ['ID Planta'],
        'EliminarProveedor': ['ID Proveedor'],
        'EliminarReporte': ['ID Reporte'],
        'EliminarVacuna': ['ID Vacuna']

    };

    // Crear campos para los parámetros
    if (parametros[subopcion]) {
        parametros[subopcion].forEach((param, index) => {
            const div = document.createElement('div');
            div.className = 'mb-3';
            
            const label = document.createElement('label');
            label.htmlFor = `param${index + 1}`;
            label.className = 'form-label';
            label.textContent = param;
            
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            input.id = `param${index + 1}`;
            input.name = `param${index + 1}`;
            input.required = true;
            
            div.appendChild(label);
            div.appendChild(input);
            parametrosDiv.appendChild(div);
        });
    }
}

function enviarFormulario() {
    const opcionPrincipal = document.getElementById('opcionPrincipal').value;
    const form = document.getElementById('miFormulario');
    
    if (opcionPrincipal === 'insertar') {
        form.action = '../conexion/procedimientos_insertar.php';
    } 
    else if (opcionPrincipal === 'actualizar') {
        form.action = '../conexion/procedimientos_actualizar.php';
    }
    else if (opcionPrincipal === 'eliminar') {
        form.action = '../conexion/procedimientos_eliminar.php';
    }
    
    return true; 
}