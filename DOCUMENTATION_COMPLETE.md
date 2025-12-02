# Documentación Completa del Sistema Requiem (NRE & PackR)

## 1. Descripción General
Requiem es un sistema de gestión de requerimientos diseñado para administrar **NREs (Non-Recurring Engineering)** y **PackR (Packing Requirements)**. Permite a los usuarios crear, rastrear y gestionar solicitudes de compra, con flujos de trabajo específicos para cada tipo de requerimiento.

## 2. Características Principales

### 2.1 Gestión de NREs
- **Creación Manual**: Formulario detallado para ingresar solicitudes de NRE.
- **Flujo de Aprobación**:
  - **Draft**: Estado inicial.
  - **Approved**: Aprobado por un administrador (envía notificación por correo).
  - **In Process**: En proceso de compra.
  - **Arrived**: Material recibido.
  - **Cancelled**: Solicitud cancelada.
- **Notificaciones**: Envío automático de correos electrónicos en cambios de estado clave.

### 2.2 Gestión de PackR (Nuevo)
- **Carga desde PDF**: Creación automática de requerimientos subiendo el PDF de Solicitud de Compra de SAP.
- **Extracción Inteligente**: El sistema lee el PDF y extrae:
  - Número de documento SAP.
  - Lista de materiales (Código, Descripción, Cantidad, Precio).
  - Departamento y Proyecto.
- **Flujo Simplificado**: Los PackR se crean directamente en estado **"In Process"**, sin necesidad de aprobación por correo.
- **Visualización**: Enlace directo al PDF original desde el dashboard.

### 2.3 Reportes y Análisis
- **Dashboard**: Vista general con estadísticas clave y lista de requerimientos.
- **Filtros Avanzados**: Filtrado por Tipo (NRE/PackR), Estado, Fecha y Solicitante.
- **Gráficas**: Visualización de datos por Estado, Operación, Tendencia Mensual y Top Solicitantes.
- **Exportación**: Descarga de reportes en formatos CSV y Excel.

### 2.4 Administración
- **Gestión de Usuarios**: Creación y edición de usuarios, asignación de roles (Admin/User).
- **Tipos de Cambio**: Configuración mensual del tipo de cambio USD/MXN para cálculos precisos.

## 3. Instrucciones de Uso

### 3.1 Acceso al Sistema
1. Ingrese a la URL del sistema.
2. Inicie sesión con sus credenciales.

### 3.2 Crear un NRE
1. En el Dashboard, haga clic en **"Nuevo"** -> **"Nuevo NRE"**.
2. Complete el formulario con los detalles del requerimiento.
3. Adjunte cotizaciones si es necesario.
4. Haga clic en **"Guardar"**. La solicitud quedará en estado "Draft".

### 3.3 Crear un PackR (Material de Empaque)
1. En el Dashboard, haga clic en **"Nuevo"** -> **"Nuevo PackR (SAP PDF)"**.
2. Seleccione el archivo PDF de la Solicitud de Compra generado por SAP.
3. Haga clic en **"Procesar PDF"**.
4. El sistema validará el archivo y creará los requerimientos automáticamente en estado "In Process".

### 3.4 Gestión de Requerimientos (Dashboard)
- **Filtrar**: Use los botones "Todos", "NREs", "PackR" en la parte superior para filtrar la lista.
- **Ver Detalles**: Haga clic en el número de NRE para ver más detalles (o editar si está en Draft).
- **Ver PDF (PackR)**: Haga clic en el icono de PDF en la columna de acciones para ver el documento SAP original.
- **Cambiar Estado**:
  - **Marcar como En Proceso**: (Solo Admin) Mueve de Approved a In Process.
  - **Marcar como Arrived**: Registra la llegada del material.
  - **Cancelar**: Cancela la solicitud.

### 3.5 Generar Reportes
1. Vaya a la sección **"Reportes"** en el menú superior.
2. Use los filtros disponibles (Tipo, Estado, Fechas, Solicitante).
3. Haga clic en **"Filtrar"** para ver los resultados en pantalla y actualizar las gráficas.
4. Use los botones **"Descargar CSV"** o **"Descargar Excel"** para exportar los datos.

## 4. Detalles Técnicos

### 4.1 Arquitectura
- **Backend**: PHP 8.x (Sin frameworks pesados, estructura MVC ligera).
- **Frontend**: HTML5, Bootstrap 5, JavaScript (Vanilla), Chart.js.
- **Base de Datos**: MySQL / MariaDB.
- **PDF Parsing**: `pdftotext` (poppler-utils) para extracción de texto.

### 4.2 Estructura de Base de Datos
La tabla principal `nres` almacena ambos tipos de requerimientos, diferenciados por la columna `requirement_type`:
- `requirement_type`: ENUM('NRE', 'PackR')
- `sap_document_number`: Para vincular con SAP (PackR).
- `department`, `project`: Campos adicionales extraídos de SAP.

### 4.3 Configuración
- Archivo `.env`: Contiene credenciales de base de datos y configuración SMTP.
- Directorio `uploads/`: Almacena cotizaciones y PDFs de SAP. Requiere permisos de escritura.

## 5. Solución de Problemas Comunes

- **Error al subir PDF**: Verifique que el archivo sea un PDF válido generado por SAP y que no exceda el tamaño máximo permitido.
- **Tipo de Cambio no configurado**: Si recibe un error sobre el tipo de cambio, contacte a un administrador para que registre la tasa del mes actual en "Tipos de Cambio".
- **Permisos de Archivos**: Asegúrese de que la carpeta `uploads/` y sus subcarpetas tengan permisos de escritura (755 o 777 según el entorno).

---
*Documentación generada el 02 de Diciembre de 2025.*
