<?php
$entity = basename($_SERVER['PHP_SELF'], '.php');
// e.g. expenses.php  →  "expenses"

function logAudit($conn, $entity, $id, $type, $payload) {
    $entity = basename($_SERVER['PHP_SELF'], '.php');
// e.g. expenses.php  →  "expenses"

  $stmt = $conn->prepare(
    "INSERT INTO tbl_audit_log 
      (entity,entity_id,changed_by,change_type,change_data)
     VALUES (?,?,?,?,?)"
  );
  $json = json_encode($payload);
  $stmt->bind_param("siiss",
    $entity,
    $id,
    $_SESSION['user_id'],
    $type,
    $json
  );
  $stmt->execute();
}
?>
