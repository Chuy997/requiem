# Sistema de Gestión de NREs (Número de Requerimiento de Compra)

![Logo](https://placeholder.com/150x60?text=NRE+System)  
*Versión 1.0 - Noviembre 2025*

## 📌 Tabla de Contenidos
- [1. Visión General](#1-visión-general)
- [2. Contexto de Negocio](#2-contexto-de-negocio)
- [3. Requisitos Técnicos](#3-requisitos-técnicos)
- [4. Estructura del Proyecto](#4-estructura-del-proyecto)
- [5. Instalación y Configuración](#5-instalación-y-configuración)
- [6. Flujo de Trabajo](#6-flujo-de-trabajo)
- [7. Decisiones de Diseño Clave](#7-decisiones-de-diseño-clave)
- [8. Seguridad y Cumplimiento](#8-seguridad-y-cumplimiento)
- [9. Pruebas Realizadas](#9-pruebas-realizadas)
- [10. Mantenimiento y Evolución](#10-mantenimiento-y-evolución)
- [11. Contacto y Soporte](#11-contacto-y-soporte)

---

## 1. Visión General

Sistema web para digitalizar el flujo de gestión de NREs (Número de Requerimiento de Compra) que reemplaza el proceso manual basado en Excel y PDFs. Permite crear solicitudes de compra, gestionar aprobaciones, registrar recepción de materiales y mantener un historial completo con trazabilidad.

**Objetivo principal:**  
Reducir el tiempo de procesamiento de NREs de 3-5 días a menos de 24 horas y eliminar errores por entrada manual de datos.

---

## 2. Contexto de Negocio

### Problema Actual
- Flujo manual basado en formularios Excel y PDFs impresos
- Sin trazabilidad en tiempo real del estado de las solicitudes
- Errores frecuentes en cálculos de conversión USD/MXN
- Dificultad para auditar historial de compras

### Proceso a Digitalizar
1. Ingeniero identifica necesidad de compra
2. Busca cotizaciones de proveedores (PDFs/imágenes)
3. Llena formulario Excel con datos completos
4. Envía por correo a aprobadores (Kevin, Pedro, César)
5. Tras aprobación, genera PDF de SAP, imprime, firma, escanea y sube
6. Monitorea llegada de materiales (fecha firma + 14 días)

### Documentos de Referencia
- `Purchase Request_20251104_84021AM.pdf`: Formato actual de solicitud
- `Cost File.xlsx`: Base de datos histórica de NREs

---

## 3. Requisitos Técnicos

### Stack Tecnológico (Estricto)
| Componente | Versión | Requisito |
|------------|---------|-----------|
| Sistema Operativo | Ubuntu 24.04 LTS | Producción |
| Servidor Web | Apache 2.4.x | Módulos: mod_rewrite, mod_ssl |
| Base de Datos | MariaDB 10.11 | Motor InnoDB |
| Backend | PHP 8.3 | Sin frameworks |
| Librerías | PHPMailer 6.9.1 | Instalado offline |
| Frontend | Bootstrap 5.3.2 | JavaScript vanilla |

### Requisitos de Hardware
- Mínimo: 2GB RAM, 20GB disco
- Recomendado: 4GB RAM, 50GB SSD

### Variables de Entorno
```env
# Base de Datos
DB_HOST=localhost
DB_NAME=requiem
DB_USER=jmuro
DB_PASS=Monday.03

# Correo Electrónico
SMTP_HOST=smtphz.qiye.163.com
SMTP_USERNAME=alertservice@xinya-la.com
SMTP_PASSWORD=M4ru4t4.2025!
SMTP_PORT=465
SMTP_ENCRYPTION=ssl

# Configuración General
APP_ENV=dev
APP_SECRET=change-me-for-nretracker


/var/www/html/requiem/
├── database/
│   ├── schema.sql        # Esquema de base de datos
│   └── seed_rates.sql    # Datos iniciales de tipos de cambio
├── logs/
│   └── app.log           # Logging de aplicación
├── public/               # Document root de Apache
│   ├── index.php         # Punto de entrada principal
│   └── assets/           # CSS, JS, imágenes públicas
├── src/
│   ├── config/
│   │   ├── db.php        # Conexión segura a MariaDB (patrón Singleton)
│   │   └── mail.php      # Configuración de PHPMailer
│   ├── controllers/      # Lógica de negocio
│   │   ├── NreController.php
│   │   └── NreListController.php
│   ├── models/           # Capa de acceso a datos
│   │   ├── Nre.php
│   │   ├── User.php
│   │   └── ExchangeRate.php
│   └── services/         # Servicios externos
│       └── EmailService.php
├── templates/            # Vistas (MVC)
│   └── nre/
│       ├── create.php    # Formulario de creación
│       ├── preview.php   # Vista previa antes de enviar
│       └── list.php      # Lista de NREs del usuario
├── uploads/
│   ├── quotations/       # Cotizaciones subidas (PDFs/imágenes)
│   └── pdfs/             # PDFs generados (futuro)
└── vendor/
    └── phpmailer/        # PHPMailer instalado offline


    Tablas de Base de Datos Clave

Tabla nres:

    nre_number: Formato XY2025112601 (XY + AAAAMMDD + secuencial)
    status: Enum('Draft','Approved','In Process','Arrived','Cancelled')
    arrival_date: Fecha de recepción de materiales
    unit_price_usd/unit_price_mxn: Precios con conversión automática
    Relación con users (requester_id, approved_by)

Tabla exchange_rates:

    period: Formato YYYYMM (ej. 202510)
    rate_mxn_per_usd: Tipo de cambio desde SAFE

5. Instalación y Configuración
Requisitos Previos
sudo apt update
sudo apt install apache2 mariadb-server php8.3 php8.3-mysql php8.3-mbstring php8.3-zip

Pasos de Instalación

    Clonar repositorio:

sudo git clone https://github.com/tu-usuario/requiem.git /var/www/html/requiem
sudo chown -R www-www-data /var/www/html/requiem

    Configurar base de datos:
    sudo mysql -u root -p < /var/www/html/requiem/database/schema.sql
    sudo mysql -u root -p < /var/www/html/requiem/database/seed_rates.sql

    Configurar PHPMailer:
    cd /var/www/html/requiem/vendor
    sudo wget https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v6.9.1.zip
    sudo unzip v6.9.1.zip
    sudo mv PHPMailer-6.9.1 phpmailer
    sudo rm v6.9.1.zip

    Configurar permisos:
    sudo chmod -R 755 /var/www/html/requiem
    sudo chmod -R 775 /var/www/html/requiem/uploads
    sudo chown -R www-www-data /var/www/html/requiem/uploads

    Configurar Apache:
    <VirtualHost *:80>
    DocumentRoot /var/www/html/requiem/public
    <Directory /var/www/html/requiem/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/requiem_error.log
    CustomLog ${APACHE_LOG_DIR}/requiem_access.log combined
    </VirtualHost>
    sudo a2ensite requiem.conf
    sudo systemctl restart apache2

    stateDiagram-v2
    [*] --> Draft
    Draft --> In Process: Marcar como "En SAP"
    Draft --> Cancelled: Cancelar NRE
    In Process --> Arrived: Finalizar (con fecha recepción)
    In Process --> Cancelled: Cancelar NRE
    Arrived --> [*]
    Cancelled --> [*]

Secuencia de Creación de NRE

    Usuario accede a /requiem/public/
    Selecciona "+ Nuevo NRE"
    Completa formulario con múltiples ítems
    Adjunta cotizaciones (PDFs/imágenes)
    Click en "Vista Previa"
    Revisa resumen con:
        Conversión automática USD→MXN usando tipo de cambio del mes anterior
        Cálculo de IVA (16%)
        Números de NRE generados (XY2025112601, XY2025112602...)
    Confirma envío
    Sistema:
        Guarda NREs en base de datos (estado: Draft)
        Mueve cotizaciones a /uploads/quotations/
        Envía correo a aprobadores con resumen en formato Excel

Gestión Post-Creación

    Usuario monitorea estado en /requiem/public/
    Al confirmar aprobación en SAP: marca como "En SAP"
    Al recibir materiales: finaliza con fecha de recepción
    Opción de cancelar en cualquier momento antes de finalizar

7. Decisiones de Diseño Clave
Generación de Números de NRE

    Formato: XY + AAAAMMDD + secuencial (ej. XY2025112601)
    Lógica: Conteo diario basado en registros existentes en la BD
    Ventaja: Evita gaps y garantiza unicidad

Conversión de Monedas

    Fuente: Tipos de cambio mensuales desde SAFE
    Cálculo: MXN = USD * tipo_cambio o USD = MXN / tipo_cambio
    IVA: Siempre calculado sobre el total en MXN (16%)

Seguridad de Archivos

    Directorio uploads/: Permisos 775, propiedad www-www-data
    Nombres de archivo: Prefijo único + sanitización (uniqid() + preg_replace)
    Tipos permitidos: .pdf, .jpg, .jpeg, .png

Manejo de Errores

    Logs: Todos los errores críticos en /logs/app.log
    Usuario: Mensajes genéricos sin detalles técnicos
    BD: Transacciones implícitas en operaciones críticas

8. Seguridad y Cumplimiento
Medidas Implementadas

    Validación de entradas: Sanitización de todos los campos de formulario
    Prevención XSS: htmlspecialchars() en todas las salidas
    Prevención SQL Injection: Sentencias preparadas en todas las consultas
    Protección de archivos: Directorios fuera de document root
    HTTPS: Configuración obligatoria en producción (no implementada en MVP)

Cumplimiento ISO

    Auditoría: Logging de todas las acciones críticas
    Integridad de datos: Restricciones de BD (NOT NULL, FOREIGN KEY)
    Disponibilidad: Respaldos diarios recomendados

9. Pruebas Realizadas
Caso de Prueba
	
Resultado
	
Observaciones
Creación de NRE con 1 ítem
	
✅ Éxito
	
Conversión USD→MXN correcta
Creación con 5 ítems
	
✅ Éxito
	
Números consecutivos correctos
Adjuntar 3 cotizaciones
	
✅ Éxito
	
Archivos movidos a uploads/quotations/
Cancelar NRE en Draft
	
✅ Éxito
	
Estado actualizado correctamente
Marcar como "En SAP"
	
✅ Éxito
	
Estado cambiado a In Process
Finalizar con fecha personalizada
	
✅ Éxito
	
arrival_date guardado correctamente
Crear NRE con cotización en MXN
	
✅ Éxito
	
Conversión a USD correcta
Vista previa con IVA
	
✅ Éxito
	
Cálculos correctos para 16%
Pruebas Pendientes

    Carga de 50+ ítems simultáneos
    Adjuntar archivos >10MB
    Simulación de fallo de conexión SMTP
    Pruebas de penetración básicas

10. Mantenimiento y Evolución
Tareas de Mantenimiento Recomendadas

    Diario: Verificar logs de errores (/logs/app.log)
    Semanal: Limpiar cotizaciones de NREs cancelados
    Mensual: Actualizar tipos de cambio en exchange_rates
    Trimestral: Respaldar base de datos completa

Roadmap de Evolución
Versión
	
Características
	
Prioridad
1.1 (Q1 2026)
	
Generación de PDF para SAP
	
Alta
1.2 (Q2 2026)
	
Panel de aprobadores (Kevin/Pedro/César)
	
Media
2.0 (Q3 2026)
	
API REST para integración con SAP
	
Crítica
2.1 (Q4 2026)
	
Dashboard analítico con reportes
	
Media
Posibles Mejoras Futuras

    Autenticación robusta con roles de usuario
    Notificaciones automáticas por correo para NREs estancados
    Integración con proveedores para cotizaciones en tiempo real
    Módulo de inventario básico para materiales recibidos

11. Contacto y Soporte
Equipo de Desarrollo

    Jesús Muro (Owner)
        Email: jesus.muro@xinya-la.com       
        Horario de soporte: Lunes-Viernes 8:00-17:00 CST

Documentación Adicional

    Esquema de base de datos completo: /database/schema.sql
    Ejemplos de formularios: /examples/
    Guía de estilos de correo: /docs/email_templates.md

Reporte de Incidencias

    Capturar pantalla del error
    Guardar logs relevantes
    Enviar a jesus.muro@xinya-la.com con asunto "[NRE-SYSTEM] Incidente reportado"
    Incluir pasos para reproducir

    Nota: Este sistema fue desarrollado específicamente para Xinya Latinamerica - Planta Tlajomulco de Zúñiga. Cualquier modificación o distribución requiere autorización escrita de la gerencia.

© 2025 Xinya Latinamerica. Todos los derechos reservados.