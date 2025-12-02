# Implementación de Pack Requirements (PackR)

## Resumen
Se ha implementado un nuevo módulo para gestionar requerimientos de material de empaque (PackR) basado en la carga de documentos PDF de SAP.

## Características Principales

### 1. Carga Automática desde PDF
- **Parser Inteligente**: Extrae automáticamente toda la información del PDF de SAP:
  - Número de solicitud
  - Fecha
  - Lista de materiales (Código, Descripción, Cantidad, Precio)
  - Departamento y Proyecto
- **Validación**: Verifica que el archivo sea un PDF válido de SAP antes de procesarlo.

### 2. Flujo Simplificado
- **Estado Directo**: Los PackR se crean directamente en estado **"In Process"**.
- **Sin Aprobación**: No requieren flujo de aprobación ni envío de correos.
- **Gestión**: Se pueden marcar como "Arrived" o cancelar igual que los NREs.

### 3. Integración en Dashboard
- **Filtros**: Nuevos botones para filtrar por "Todos", "NREs" o "PackR".
- **Visualización Adaptativa**: La tabla muestra columnas específicas para PackR (Documento SAP, Departamento, Proyecto) y oculta las irrelevantes (Proveedor, Operación).
- **Acceso al PDF**: Enlace directo para ver el PDF original subido.

## Archivos Clave

- `public/packr.php`: Interfaz de carga.
- `src/services/PdfParser.php`: Lógica de extracción de datos.
- `src/models/PackRequirement.php`: Modelo de datos.
- `templates/nre/list.php`: Dashboard actualizado.

## Instrucciones de Uso

1. Ir al Dashboard.
2. Clic en **"Nuevo"** -> **"Nuevo PackR (SAP PDF)"**.
3. Subir el archivo PDF de la Solicitud de Compra de SAP.
4. El sistema procesará el archivo y creará los requerimientos automáticamente.
5. Ver los requerimientos en el Dashboard (filtrar por "PackR" si se desea).

## Notas Técnicas
- Se requiere `pdftotext` instalado en el servidor.
- Los PDFs se guardan en `uploads/packr/`.
- La base de datos se ha actualizado para soportar el nuevo tipo de requerimiento.
