<?php
use Firebase\JWT\JWT;
const JWT_SECRET = 'mycampus_cafe_secret_key_change_this';
const JWT_ALG = 'HS256';
const JWT_EXPIRY_SECONDS = 3600;
function bcryptHash(string $plainPassword): string {
	$cost = 12;
	$salt = substr(strtr(base64_encode(random_bytes(16)), '+', '.'), 0, 22);
	return crypt($plainPassword, sprintf('$2y$%02d$%s$', $cost, $salt));
}
function bcryptVerify(string $plainPassword, string $storedHash): bool {
	return hash_equals($storedHash, crypt($plainPassword, $storedHash));
}
function createJwtToken(array $staff): string {
	$issuedAt = time();
	$payload = [
	 'iat' => $issuedAt,
	 'exp' => $issuedAt + JWT_EXPIRY_SECONDS,
	 'staff_id' => $staff['staff_id'],
	 'username' => $staff['username'],
	 'role' => $staff['role']
	];
	return JWT::encode($payload, JWT_SECRET, JWT_ALG);
}
?>