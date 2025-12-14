<?php
require_once __DIR__ . '/db_connect.php';

// Helper functions for data management using MySQL (Procedural Style)

function getUserByEmail($email) {
    global $conn;
    $email = mysqli_real_escape_string($conn, $email);  
    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function getOrganizationById($id) {
    global $conn;
    $id = (int)$id;
    $query = "SELECT * FROM organizations WHERE id = $id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function getComplaintsByOrganization($organizationId) {
    global $conn;
    $organizationId = (int)$organizationId;
    $query = "
        SELECT c.*, u.name as unit_number 
        FROM complaints c
        JOIN units u ON c.unit_id = u.id
        WHERE c.organization_id = $organizationId 
        ORDER BY c.submitted_at DESC
    ";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getComplaintsByUser($userId) {
    global $conn;
    $userId = (int)$userId;
    $query = "
        SELECT c.*, u.name as unit_number 
        FROM complaints c
        JOIN units u ON c.unit_id = u.id
        WHERE c.user_id = $userId 
        ORDER BY c.submitted_at DESC
    ";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getPriorityBadgeClass($priority) {
    switch ($priority) {
        case 'high':
            return 'priority-high';
        case 'medium':
            return 'priority-medium';
        case 'low':
            return 'priority-low';
        default:
            return 'priority-medium';
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'status-pending';
        case 'in_progress':
            return 'status-progress';
        case 'resolved':
            return 'status-resolved';
        default:
            return 'status-pending';
    }
}

function formatDateTime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

function getUnitsByOrganization($organizationId) {
    global $conn;
    $organizationId = (int)$organizationId;
    $query = "SELECT * FROM units WHERE organization_id = $organizationId";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getUserUnits($userId, $organizationId = null) {
    global $conn;
    $userId = (int)$userId;
    
    if ($organizationId) {
        $organizationId = (int)$organizationId;
        $query = "
            SELECT uu.*, u.name as unit_name, u.description as unit_description 
            FROM user_units uu
            JOIN units u ON uu.unit_id = u.id
            WHERE uu.user_id = $userId AND uu.organization_id = $organizationId AND uu.status = 1
        ";
    } else {
        $query = "
            SELECT uu.*, u.name as unit_name, u.description as unit_description 
            FROM user_units uu
            JOIN units u ON uu.unit_id = u.id
            WHERE uu.user_id = $userId AND uu.status = 1
        ";
    }
    
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getUserOrganizations($userId) {
    global $conn;
    $userId = (int)$userId;
    $query = "
        SELECT DISTINCT o.* 
        FROM organizations o
        JOIN user_units uu ON o.id = uu.organization_id
        WHERE uu.user_id = $userId
    ";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getUnitById($unitId) {
    global $conn;
    $unitId = (int)$unitId;
    $query = "SELECT * FROM units WHERE id = $unitId";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

function getComplaintsByUnit($unitId, $userId = null) {
    global $conn;
    $unitId = (int)$unitId;
    
    if ($userId) {
        $userId = (int)$userId;
        $query = "SELECT * FROM complaints WHERE unit_id = $unitId AND user_id = $userId ORDER BY submitted_at DESC";
    } else {
        $query = "SELECT * FROM complaints WHERE unit_id = $unitId ORDER BY submitted_at DESC";
    }
    
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function isUserInUnit($userId, $unitId) {
    global $conn;
    $userId = (int)$userId;
    $unitId = (int)$unitId;
    
    $query = "SELECT id FROM user_units WHERE user_id = $userId AND unit_id = $unitId";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

function getLandlordOrganizations($adminId) {
    global $conn;
    $adminId = (int)$adminId;
    $query = "SELECT * FROM organizations WHERE admin_id = $adminId";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getAllLandlordComplaints($adminId) {
    global $conn;
    $adminId = (int)$adminId;
    $query = "
        SELECT c.*, u.name as unit_number, o.name as org_name, o.address as org_address
        FROM complaints c
        JOIN units u ON c.unit_id = u.id
        JOIN organizations o ON c.organization_id = o.id
        WHERE o.admin_id = $adminId
        ORDER BY c.submitted_at DESC
    ";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function getLandlordTenantRequests($adminId) {
    global $conn;
    $adminId = (int)$adminId;
    $query = "
        SELECT uu.id as link_id, uu.status as status,
               u.name as tenant_name, u.email as tenant_email,
               un.name as unit_number,
               o.name as org_name
        FROM user_units uu
        JOIN users u ON uu.user_id = u.id
        JOIN units un ON uu.unit_id = un.id
        JOIN organizations o ON uu.organization_id = o.id
        WHERE o.admin_id = $adminId
        ORDER BY uu.status ASC, uu.id DESC
    ";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

