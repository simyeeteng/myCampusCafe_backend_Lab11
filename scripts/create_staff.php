<?php
require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/security.php';
$username = 'admin';
$plainPassword = 'admin123';
$role = 'admin';
try {
	$db = (new DB())->connect();
	$sql = 'INSERT INTO staff (username, bcrypt_password, role)
	       VALUES (:username, :bcrypt_password, :role)';
	       $stmt = $db->prepare($sql);
	$stmt->execute([
	                   ':username' => $username,
	                   ':bcrypt_password' => bcryptHash($plainPassword),
	                   ':role' => $role
	               ]);
	echo "Staff account created successfully.\n";
	echo "Username: admin\n";
	echo "Password: admin123\n";
} catch (PDOException $e) {
	echo "Error: " . $e->getMessage() . "\n";
}
?>