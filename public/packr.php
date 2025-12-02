<?php
// public/packr.php
// Página para crear Pack Requirements desde PDF de SAP

require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/controllers/PackRequirementController.php';

requireAuth();

$currentUser = new User($_SESSION['user_id']);
$message = $_SESSION['packr_message'] ?? null;
$error = $_SESSION['packr_error'] ?? null;

unset($_SESSION['packr_message'], $_SESSION['packr_error']);

// Procesar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sap_pdf'])) {
    $controller = new PackRequirementController();
    $result = $controller->createFromPdfUpload($_FILES['sap_pdf'], $_SESSION['user_id']);
    
    if ($result['success']) {
        $_SESSION['packr_message'] = $result['message'];
    } else {
        $_SESSION['packr_error'] = $result['message'];
    }
    
    header('Location: packr.php');
    exit;
}

$pageTitle = 'Nuevo Pack Requirement';
include __DIR__ . '/../templates/components/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-box-seam"></i> Nuevo Requerimiento de Empaque</h2>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Formulario de Upload -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-cloud-upload"></i> Cargar PDF de SAP
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <div class="mb-3">
                                <label for="sap_pdf" class="form-label">
                                    Documento PDF de SAP <span class="text-danger">*</span>
                                </label>
                                <input type="file" 
                                       class="form-control" 
                                       id="sap_pdf" 
                                       name="sap_pdf" 
                                       accept=".pdf" 
                                       required>
                                <div class="form-text">
                                    Solo archivos PDF de Solicitud de Compra de SAP (máx. 10MB)
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Nota:</strong> El PDF debe ser una Solicitud de Compra generada por SAP.
                                El sistema extraerá automáticamente toda la información y creará los requerimientos
                                directamente en estado <strong>"In Process"</strong>.
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-upload"></i> Cargar y Procesar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Instrucciones -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark">
                            <i class="bi bi-question-circle"></i> Instrucciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6 class="fw-bold">¿Cómo funciona?</h6>
                        <ol>
                            <li class="mb-2">
                                <strong>Genera el PDF</strong> de la Solicitud de Compra desde SAP
                            </li>
                            <li class="mb-2">
                                <strong>Sube el PDF</strong> usando el formulario de la izquierda
                            </li>                            
                        </ol>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Importante:</strong> Asegúrate de que el PDF tenga el formato estándar
                            de SAP Business One para Solicitudes de Compra.
                        </div>
                        
                        <h6 class="fw-bold mt-4">Formato esperado del PDF</h6>
                        <ul>
                            <li>Título: "SOLICITUD DE COMPRA NO."</li>
                            <li>Campos: Fecha, Solicitante, Moneda</li>
                            <li>Tabla con: Código, Descripción, Cantidad, Precio, etc.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vista Previa de PDF Ejemplo -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark">
                            <i class="bi bi-file-earmark-pdf"></i> Ejemplo de Formato Esperado
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th colspan="8" class="text-center bg-primary text-white">
                                            SOLICITUD DE COMPRA NO. XXXX
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>CÓDIGO</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>FECHA NECESARIA</th>
                                        <th>CANTIDAD</th>
                                        <th>PRECIO</th>
                                        <th>DEPARTAMENTO</th>
                                        <th>PROYECTO</th>
                                        <th>TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>EXP00075</code></td>
                                        <td>Wooden Pallet, 800*600</td>
                                        <td>09/12/2025</td>
                                        <td>200</td>
                                        <td>$247.50</td>
                                        <td>PRODUCTION</td>
                                        <td>00114</td>
                                        <td>$49,500.00</td>
                                    </tr>
                                    <tr>
                                        <td colspan="8" class="text-muted">
                                            <small><i class="bi bi-info-circle"></i> El sistema procesará cada línea como un PackR individual</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('sap_pdf');
    
    if (!fileInput.files.length) {
        e.preventDefault();
        alert('Por favor selecciona un archivo PDF');
        return false;
    }
    
    const file = fileInput.files[0];
    
    // Validar extensión
    if (!file.name.toLowerCase().endsWith('.pdf')) {
        e.preventDefault();
        alert('El archivo debe ser un PDF');
        return false;
    }
    
    // Validar tamaño (10MB)
    if (file.size > 10 * 1024 * 1024) {
        e.preventDefault();
        alert('El archivo es demasiado grande (máximo 10MB)');
        return false;
    }
    
    // Mostrar loading
    const btn = this.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
});
</script>

<?php include __DIR__ . '/../templates/components/footer.php'; ?>
