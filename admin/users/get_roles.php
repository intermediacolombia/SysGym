<?php
require_once __DIR__ . '/../../inc/config.php';
   

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    if ($action == "add") {
        // Agregar un nuevo rol
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $permissions = $_POST['permissions'] ?? [];

        // Verificar si el rol ya existe
        $stmtCheck = db()->prepare("SELECT id, borrado FROM roles WHERE name = :name LIMIT 1");
        $stmtCheck->execute([':name' => $name]);
        $existingRole = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existingRole) {
            if ($existingRole['borrado'] == 0) {
                // El rol ya existe y no está borrado
                echo json_encode(['status' => 'error', 'message' => 'El rol ya existe']);
                exit;
            } else {
                // Reactivar el rol si está marcado como borrado
                $roleId = $existingRole['id'];
                $stmtReactivate = db()->prepare("UPDATE roles SET description = :description, borrado = 0 WHERE id = :id");
                if ($stmtReactivate->execute([':description' => $description, ':id' => $roleId])) {
                    // Eliminar permisos antiguos
                    $stmtDeletePermissions = db()->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
                    $stmtDeletePermissions->execute([':role_id' => $roleId]);

                    // Insertar nuevos permisos
                    if (!empty($permissions)) {
                        $insertQuery = "INSERT INTO role_permissions (role_id, permission_id) VALUES ";
                        $values = [];
                        foreach ($permissions as $permissionId) {
                            $values[] = "(:role_id, :permission_id_$permissionId)";
                        }
                        $insertQuery .= implode(", ", $values);

                        $stmtInsertPermissions = db()->prepare($insertQuery);
                        $params = ['role_id' => $roleId];
                        foreach ($permissions as $permissionId) {
                            $params["permission_id_$permissionId"] = $permissionId;
                        }
                        $stmtInsertPermissions->execute($params);
                    }
					
					// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
    'rol_id'      => $roleId,
    'accion'      => 'Rol reactivado',
    'nombre'      => $name,
    'descripcion' => $description,
    'permisos'    => $permissions
], JSON_UNESCAPED_UNICODE);

log_action('Reactivar rol', $desc, 'Roles');
// END LOGS

					
                    echo json_encode(['status' => 'success', 'message' => 'Rol reactivado correctamente']);
                    exit;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error al reactivar el rol']);
                    exit;
                }
            }
        }

        // Insertar el nuevo rol
        $stmt = db()->prepare("INSERT INTO roles (name, description, borrado) VALUES (:name, :description, 0)");
        if ($stmt->execute([':name' => $name, ':description' => $description])) {
            $roleId = db()->lastInsertId();

            // Asignar permisos
            if (!empty($permissions)) {
                $insertQuery = "INSERT INTO role_permissions (role_id, permission_id) VALUES ";
                $values = [];
                foreach ($permissions as $permissionId) {
                    $values[] = "(:role_id, :permission_id_$permissionId)";
                }
                $insertQuery .= implode(", ", $values);

                $stmt = db()->prepare($insertQuery);
                $params = ['role_id' => $roleId];
                foreach ($permissions as $permissionId) {
                    $params["permission_id_$permissionId"] = $permissionId;
                }
                $stmt->execute($params);
            }

			
			// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
    'rol_id'      => $roleId,
    'accion'      => 'Rol creado',
    'nombre'      => $name,
    'descripcion' => $description,
    'permisos'    => $permissions
], JSON_UNESCAPED_UNICODE);

log_action('Crear rol', $desc, 'Roles');
// END LOGS

			
            echo json_encode(['status' => 'success', 'message' => 'Rol agregado correctamente']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el rol']);
            exit;
        }

    } elseif ($action == "edit") {
        // Editar un rol existente
        $roleId = trim($_POST['id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $permissions = $_POST['permissions'] ?? [];

        // Actualizar el rol
        $stmt = db()->prepare("UPDATE roles SET name = :name, description = :description WHERE id = :id");
        $stmt->execute([':name' => $name, ':description' => $description, ':id' => $roleId]);

        // Eliminar permisos antiguos
        $stmt = db()->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
        $stmt->execute([':role_id' => $roleId]);

        // Insertar nuevos permisos
        if (!empty($permissions)) {
            $insertQuery = "INSERT INTO role_permissions (role_id, permission_id) VALUES ";
            $values = [];
            foreach ($permissions as $permissionId) {
                $values[] = "(:role_id, :permission_id_$permissionId)";
            }
            $insertQuery .= implode(", ", $values);

            $stmt = db()->prepare($insertQuery);
            $params = ['role_id' => $roleId];
            foreach ($permissions as $permissionId) {
                $params["permission_id_$permissionId"] = $permissionId;
            }
            $stmt->execute($params);
        }

		
		// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
    'rol_id'      => $roleId,
    'accion'      => 'Rol actualizado',
    'nombre'      => $name,
    'descripcion' => $description,
    'permisos'    => $permissions
], JSON_UNESCAPED_UNICODE);

log_action('Editar rol', $desc, 'Roles');
// END LOGS

		
        echo json_encode(['status' => 'success', 'message' => 'Rol actualizado correctamente']);
        exit;

    } elseif ($action == "delete") {
        // Marcar un rol como borrado (borrado lógico)
        $roleId = trim($_POST['id']);

        // Actualizar el estado de borrado
        $stmt = db()->prepare("UPDATE roles SET borrado = 1 WHERE id = :id");
        if ($stmt->execute([':id' => $roleId])) {
			
			// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
    'rol_id'  => $roleId,
    'accion'  => 'Rol marcado como borrado'
], JSON_UNESCAPED_UNICODE);

log_action('Eliminar rol', $desc, 'Roles');
// END LOGS

			
            echo json_encode(['status' => 'success', 'message' => 'Rol borrado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al borrar el rol']);
        }
        exit;

    } elseif ($action == "get") {
        // Obtener detalles de un rol específico
        $roleId = trim($_POST['id']);

        // Obtener el rol
        $stmt = db()->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->execute([':id' => $roleId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);

        // Obtener los permisos del rol
        $stmt = db()->prepare("SELECT permission_id FROM role_permissions WHERE role_id = :role_id");
        $stmt->execute([':role_id' => $roleId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => $role['id'],
                'name' => $role['name'],
                'description' => $role['description'],
                'permissions' => $permissions
            ]
        ]);
        exit;
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] == "fetch") {
    // Listar todos los roles activos (borrado = 0)
    $stmt = db()->query("SELECT id, name, description FROM roles WHERE borrado = 0");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $roles]);
    exit;
}
?>