# 📘 Manual de Usuario: Sistema Requiem (NRE & PackR)

Bienvenido al **Sistema Requiem**, tu herramienta centralizada para la gestión de requerimientos de ingeniería (NRE) y materiales de empaque (PackR). Este manual te guiará de forma sencilla para que puedas aprovechar al máximo el sistema.

---

## 1. 🚀 Acceso al Sistema

1. Ingresa a la URL proporcionada por el administrador.
2. Inicia sesión con tu **correo electrónico** y **contraseña**.
3. Al entrar, verás tu **Dashboard**, donde podrás ver el resumen de tus solicitudes.

---

## 2. 📝 Cómo Crear un Nuevo NRE (Ingeniería)

Usa esta opción cuando necesites solicitar materiales o servicios de ingeniería de forma manual.

1. En el Dashboard, haz clic en el botón azul **"Nuevo"** y selecciona **"Nuevo NRE"**.
2. **Completa el formulario:**
   - **Descripción:** Sé claro y específico.
   - **Descripción del artículo**: Nombre claro del producto o servicio.
   - **Código, Cantidad, Precio y Moneda**: Detalles esenciales.
   - **Operación, Proveedor, Marca, Modelo**: Información técnica para compras.
   - **Nuevo/Reemplazo**: Indica si es una primera compra o sustitución.
   - **Razón**: Justificación breve del gasto.

   ![Formulario NRE](public/img/nre_form.png)
   
   > **Nota**: Los campos marcados con asterisco (*) son obligatorios.

   - **Cotizaciones:** Adjunta archivos PDF o imágenes (JPG, PNG) como soporte.
   
   > **Tip:** Usa el botón **"+ Agregar otro ítem"** para incluir múltiples productos en una sola solicitud.

3. Haz clic en **"Vista Previa y Enviar"**.
4. Revisa los datos y confirma.

### 📧 Proceso de Aprobación
Una vez enviado,   - **Notificaciones**: Al enviar, se notifica y solicita aprobación a:
     - **Engineering Team**: Jesus Muro, Cesar Gutierrez.
     - **Dirección General (CN)**: Wuzhijun, Weiguoli, XYSW/Board.
     - **Departamento de Compras**: Pedro Dabdoub, Rocio Cortes, Erik Navarro, Zaira Villegas, Laura Lopez.
     - **Departamento de Finanzas**: Dyane Gutierrez.
Tu solicitud quedará en **Draft** hasta recibir el visto bueno.

---

## 3. 📦 Cómo Crear un PackR (Material de Empaque vía SAP)

Esta opción es exclusiva para materiales de empaque que ya tienes documentados en SAP.

1. En el Dashboard, clic en **"Nuevo"** -> **"Nuevo PackR (SAP PDF)"**.
2. Haz clic en **"Seleccionar archivo"** y sube el PDF de **Solicitud de Compra** generado por SAP Business One.
3. Clic en **"Cargar y Procesar"**.
4. ¡Listo! El sistema leerá automáticamente los materiales, cantidades y precios, creando los registros en estado **"In Process"**.

---

## 4. 💲 Actualización de Tipo de Cambio (Administradores)

Para mantener los cálculos precisos, el tipo de cambio debe actualizarse mensualmente.

1. Ve a la sección de **Configuración** o **Tipos de Cambio**.
2. Consulta el tipo de cambio oficial en: [SAFE (State Administration of Foreign Exchange)](https://www.safe.gov.cn/safe/gzhbdmyzslb/index.html)
3. Ingresa el valor del mes actual y guarda.

---


---

---

## 4. 🔄 Gestionar Estados de tus Solicitudes

Como usuario, tienes el control para avanzar tus solicitudes según el proceso real de compra.

### De "Draft" a "In Process"
Cuando creas un NRE, inicia en estado **Draft** (Borrador).
1. Una vez que hayas ingresado la solicitud en SAP (si aplica) o confirmado la orden, debes actualizar el estado.
2. Busca tu NRE en la lista.
3. Haz clic en el botón de **Flecha Azul (Confirmar SAP)**.
4. Confirma la acción. Tu solicitud pasará a **In Process** (En Proceso), indicando que la compra está en curso.

### Cancelar una Solicitud
Si cometiste un error o el requerimiento ya no es necesario:
1. Mientras la solicitud esté en **Draft**, busca el botón **Rojo (X)**.
2. Confirma la cancelación. El estado cambiará a **Cancelled**.

---

---

## 5. 📊 Conociendo tu Dashboard (Pantalla Principal)

El Dashboard es tu centro de control. Aquí puedes ver el estado de todas tus solicitudes en tiempo real.

![Vista del Dashboard](public/img/dashboard_view.png)

### Elementos Clave:
1.  **Filtros Rápidos**: Usa las pestañas superiores para alternar entre "Mis NREs", "PackR" o "Todo".
2.  **Buscador**: Encuentra solicitudes por número de folio o descripción.
3.  **Indicadores de Estado (Colores)**:
    - ⚪ **Draft**: Solicitud creada, pendiente de procesar.

    - 🟡 **In Process**: La orden ya está colocada en SAP.
    - 🟢 **Arrived**: Material recibido en planta.
    - 🔴 **Cancelled**: Solicitud anulada.

### Acciones Disponibles por Fila:
- 👁️ **Ver Detalles**: Clic en el ID para ver toda la info.
- ✏️ **Editar**: Solo disponible si el estado es **Draft**.
- 📥 **PDF**: Descarga el formato oficial o la cotización adjunta.

---

## 6. 📥 Recepción de Materiales

Cuando el material llegue físicamente, debes registrarlo para mantener el sistema actualizado:

1. Ubica tu requerimiento en estado **"In Process"**.
2. Haz clic en el botón **Verde (✓) "Registrar Recepción"**.
3. En la ventana emergente:
   - **Fecha de Recepción**: Cuándo llegó el material.
   - **Cantidad Recibida**: Ajusta si llegó un parcial.
   - **Ubicación (Solo PackR)**: Selecciona dónde se guardará en el inventario.
4. Haz clic en **"Confirmar Recepción"**.
   - Si recibiste el total, el estado cambiará a **Arrived**.
   - Si fue parcial, se mantendrá en **In Process** hasta completar la cantidad.

---

## 💡 Solución de Problemas y Validaciones

- **Imágenes rotas o errores de carga**: Si ves errores en el manual web, asegúrate de tener conexión a internet o contacta a soporte.
- **Límite de Presupuesto**: El sistema valida automáticamente que no excedas **$4,000 USD** por mes en NREs.
- **Archivos PDF**:
  - Solo se permiten archivos `.pdf`.
  - Tamaño máximo: **10MB**.
  - Debe ser un formato válido de SAP Business One.

---
*Requiem v1.1 - Manual Actualizado*
