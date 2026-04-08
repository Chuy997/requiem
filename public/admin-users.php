<?php
// public/admin-users.php
// Panel de administración de usuarios (solo para admin)

require_once __DIR__ . '/../src/middleware/AuthMiddleware.php';
require_once __DIR__ . '/../src/models/User.php';

requireAuth();

$currentUser = new User($_SESSION['user_id']);

// Verificar que sea admin
if (!$currentUser->isAdmin()) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta página';
    header('Location: index.php');
    exit;
}

$isSuperAdmin = $currentUser->isSuperAdmin();
$areaRoles = User::getAreaRoles();

// Procesar acciones
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($action === 'create') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $fullName = $_POST['full_name'] ?? '';
            $role = $_POST['role'] ?? 'engineer';
            $areaRoleId = !empty($_POST['area_role_id']) ? (int)$_POST['area_role_id'] : null;
            
            if (!$isSuperAdmin && in_array($role, ['admin', 'super_admin'])) {
                throw new Exception('Solo un Súper Administrador puede asignar roles de administrador.');
            }
            
            $isAdmin = ($role === 'admin') ? 1 : 0;
            $isCompras = ($role === 'compras') ? 1 : 0;
            $isSuperAdmin = ($role === 'super_admin') ? 1 : 0;
            
            if (empty($email) || empty($password) || empty($fullName)) {
                throw new Exception('Todos los campos son requeridos');
            }
            
            if (strlen($password) < 8) {
                throw new Exception('La contraseña debe tener al menos 8 caracteres');
            }
            
            $userId = User::createUser($email, $password, $fullName, $isAdmin, $isCompras, $isSuperAdmin, $areaRoleId);
            $_SESSION['success'] = "Usuario creado exitosamente (ID: $userId)";
            header('Location: admin-users.php');
            exit;
            
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $targetUser = User::getUserById($id);
            if (!$targetUser) throw new Exception("Usuario no encontrado.");
            
            if (!$isSuperAdmin && ($targetUser['is_admin'] || $targetUser['is_super_admin'])) {
                throw new Exception("No tienes permisos para editar a otro administrador.");
            }
            
            $email = $_POST['email'] ?? '';
            $fullName = $_POST['full_name'] ?? '';
            $areaRoleId = !empty($_POST['area_role_id']) ? (int)$_POST['area_role_id'] : null;
            $newPassword = !empty($_POST['new_password']) ? $_POST['new_password'] : null;
            
            if (!$isSuperAdmin && !isset($_POST['role'])) {
                $isAdmin = $targetUser['is_admin'];
                $isSuperAdmin = $targetUser['is_super_admin'];
                $isCompras = $targetUser['is_compras'];
            } else {
                $role = $_POST['role'] ?? 'engineer';
                if (!$isSuperAdmin && in_array($role, ['admin', 'super_admin'])) {
                    throw new Exception('Solo un Súper Administrador puede asignar roles de administrador.');
                }
                $isAdmin = ($role === 'admin') ? 1 : 0;
                $isCompras = ($role === 'compras') ? 1 : 0;
                $isSuperAdmin = ($role === 'super_admin') ? 1 : 0;
            }
            
            if (empty($email) || empty($fullName)) {
                throw new Exception('Email y nombre completo son requeridos');
            }
            
            if ($newPassword && strlen($newPassword) < 8) {
                throw new Exception('La contraseña debe tener al menos 8 caracteres');
            }
            
            User::updateUser($id, $email, $fullName, $isAdmin, $newPassword, $isCompras, $isSuperAdmin, $areaRoleId);
            $_SESSION['success'] = 'Usuario actualizado exitosamente';
            header('Location: admin-users.php');
            exit;
            
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $targetUser = User::getUserById($id);
            if ($targetUser && !$isSuperAdmin && ($targetUser['is_admin'] || $targetUser['is_super_admin'])) {
                throw new Exception("No tienes permisos para eliminar a otro administrador.");
            }
            User::deleteUser($id);
            $_SESSION['success'] = 'Usuario eliminado exitosamente';
            header('Location: admin-users.php');
            exit;
            
        } elseif ($action === 'create_area_role' && $isSuperAdmin) {
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                throw new Exception('El nombre del área es requerido');
            }
            User::createAreaRole($name);
            $_SESSION['success'] = 'Rol de área creado exitosamente';
            header('Location: admin-users.php');
            exit;

        } elseif ($action === 'delete_area_role' && $isSuperAdmin) {
            $id = (int)$_POST['id'];
            User::deleteAreaRole($id);
            $_SESSION['success'] = 'Rol de área eliminado exitosamente';
            header('Location: admin-users.php');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener todos los usuarios
$users = User::getAllUsers();

// Obtener usuario para editar
$editUser = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editUser = User::getUserById((int)$_GET['id']);
}

$pageTitle = 'Administración de Usuarios';
include __DIR__ . '/../templates/components/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people-fill"></i> Administración de Usuarios</h2>
            <div>
                <?php if ($isSuperAdmin): ?>
                <button class="btn btn-outline-dark me-2" data-bs-toggle="modal" data-bs-target="#manageAreaRolesModal">
                    <i class="bi bi-tags-fill"></i> Gestionar Roles/Áreas
                </button>
                <?php endif; ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-person-plus"></i> Nuevo Usuario
                </button>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre Completo</th>
                                <th>Email</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                                </td>
                                <td>
                                    <i class="bi bi-envelope"></i>
                                    <?= htmlspecialchars($user['email']) ?>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($user['username']) ?></code>
                                </td>
                                <td>
                                    <?php if (!empty($user['is_super_admin'])): ?>
                                        <span class="badge bg-dark">
                                            <i class="bi bi-star-fill"></i> SUPER ADMIN
                                        </span>
                                    <?php elseif ($user['is_admin']): ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-shield-fill-check"></i> ADMIN
                                        </span>
                                    <?php elseif (!empty($user['is_compras'])): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-cart"></i> COMPRAS
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">
                                            <i class="bi bi-person"></i> ENGINEER
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($user['area_role_name'])): ?>
                                        <span class="badge bg-secondary ms-1">
                                            <i class="bi bi-tag-fill"></i> <?= htmlspecialchars($user['area_role_name']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php 
                                        $isTargetAdmin = $user['is_admin'] || $user['is_super_admin'];
                                        $canEditTarget = $isSuperAdmin || (!$isTargetAdmin || $user['id'] == $_SESSION['user_id']);
                                        if ($canEditTarget): 
                                        ?>
                                        <a href="admin-users.php?action=edit&id=<?= $user['id'] ?>" 
                                           class="btn btn-outline-primary"
                                           data-bs-toggle="modal" 
                                           data-bs-target="#editUserModal<?= $user['id'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($canEditTarget && $user['id'] != 1 && $user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteUserModal<?= $user['id'] ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            
                            <!-- Modal Editar Usuario -->
                            <div class="modal fade" id="editUserModal<?= $user['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="admin-users.php?action=update">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="bi bi-pencil"></i> Editar Usuario
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Nombre Completo *</label>
                                                    <input type="text" name="full_name" class="form-control" 
                                                           value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Email *</label>
                                                    <input type="email" name="email" class="form-control" 
                                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Nueva Contraseña (opcional)</label>
                                                    <input type="password" name="new_password" class="form-control" 
                                                           placeholder="Dejar en blanco para no cambiar">
                                                    <small class="text-muted">Mínimo 8 caracteres</small>
                                                </div>
                                                
                                                <?php if (true): // El Súper Admin ahora puede editar el rol de quien sea, incluido el creador original ?>
                                                    <?php if ($isSuperAdmin): ?>
                                                    <div class="mb-3">
                                                        <label class="form-label">Rol del Sistema</label>
                                                        <select name="role" class="form-select">
                                                            <option value="engineer" <?= !$user['is_admin'] && empty($user['is_super_admin']) && empty($user['is_compras']) ? 'selected' : '' ?>>Ingeniero</option>
                                                            <option value="compras" <?= !empty($user['is_compras']) ? 'selected' : '' ?>>Compras (Solo Lectura)</option>
                                                            <option value="admin" <?= !empty($user['is_admin']) && empty($user['is_super_admin']) ? 'selected' : '' ?>>Administrador</option>
                                                            <option value="super_admin" <?= !empty($user['is_super_admin']) ? 'selected' : '' ?>>Super Administrador</option>
                                                        </select>
                                                    </div>
                                                    <?php elseif (!$isTargetAdmin): // Regular admin editing an engineer/compras ?>
                                                    <div class="mb-3">
                                                        <label class="form-label">Rol del Sistema</label>
                                                        <select name="role" class="form-select">
                                                            <option value="engineer" <?= !$user['is_admin'] && empty($user['is_super_admin']) && empty($user['is_compras']) ? 'selected' : '' ?>>Ingeniero</option>
                                                            <option value="compras" <?= !empty($user['is_compras']) ? 'selected' : '' ?>>Compras (Solo Lectura)</option>
                                                        </select>
                                                    </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <div class="mb-3">
                                                    <label class="form-label">Rol de Área (Opcional)</label>
                                                    <select name="area_role_id" class="form-select">
                                                        <option value="">-- Ninguno --</option>
                                                        <?php foreach ($areaRoles as $ar): ?>
                                                            <option value="<?= $ar['id'] ?>" <?= ($user['area_role_id'] == $ar['id']) ? 'selected' : '' ?>><?= htmlspecialchars($ar['name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Cancelar
                                                </button>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-save"></i> Guardar Cambios
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modal Eliminar Usuario -->
                            <?php if ($user['id'] != 1 && $user['id'] != $_SESSION['user_id']): ?>
                            <div class="modal fade" id="deleteUserModal<?= $user['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="admin-users.php?action=delete">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">
                                                    <i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                <p>¿Estás seguro de eliminar al usuario?</p>
                                                <div class="alert alert-warning">
                                                    <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
                                                    <small><?= htmlspecialchars($user['email']) ?></small>
                                                </div>
                                                <p class="text-danger">
                                                    <i class="bi bi-exclamation-triangle"></i>
                                                    Esta acción no se puede deshacer.
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Cancelar
                                                </button>
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="bi bi-trash"></i> Eliminar Usuario
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="admin-users.php?action=create">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus"></i> Nuevo Usuario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required>
                        <small class="text-muted">Se usará para iniciar sesión</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contraseña *</label>
                        <input type="password" name="password" class="form-control" required minlength="8">
                        <small class="text-muted">Mínimo 8 caracteres</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rol del Sistema *</label>
                        <select name="role" class="form-select" required>
                            <option value="engineer" selected>Ingeniero</option>
                            <option value="compras">Compras (Solo Lectura)</option>
                            <?php if ($isSuperAdmin): ?>
                            <option value="admin">Administrador</option>
                            <option value="super_admin">Super Administrador</option>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">Los administradores gestionan NREs, los súper administradores pueden eliminarlos y reasignarlos.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Rol de Área (Opcional)</label>
                        <select name="area_role_id" class="form-select">
                            <option value="">-- Ninguno --</option>
                            <?php foreach ($areaRoles as $ar): ?>
                                <option value="<?= $ar['id'] ?>"><?= htmlspecialchars($ar['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($isSuperAdmin): ?>
<!-- Modal Gestionar Roles de Área -->
<div class="modal fade" id="manageAreaRolesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="bi bi-tags-fill"></i> Gestionar Roles/Áreas
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="admin-users.php?action=create_area_role" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="name" class="form-control" placeholder="Ej. Calidad, Finanzas..." required>
                        <button type="submit" class="btn btn-primary">Añadir Rol</button>
                    </div>
                </form>
                
                <ul class="list-group">
                    <?php foreach ($areaRoles as $ar): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-tag"></i> <?= htmlspecialchars($ar['name']) ?>
                        </div>
                        <form method="POST" action="admin-users.php?action=delete_area_role" onsubmit="return confirm('¿Seguro que deseas eliminar este rol de área? Se removerá la etiqueta de los usuarios que la tengan.');">
                            <input type="hidden" name="id" value="<?= $ar['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($areaRoles)): ?>
                    <li class="list-group-item text-muted text-center">No hay roles creados.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/components/footer.php'; ?>
