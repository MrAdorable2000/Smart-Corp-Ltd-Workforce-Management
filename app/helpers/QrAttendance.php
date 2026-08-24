<?php
/**
 * QR Code Attendance Helper
 * Generates encrypted QR tokens and validates scans
 * Uses pure-PHP QR rendering via Google Charts API fallback + local generation
 */
class QrAttendance
{
    /**
     * Generate or retrieve QR token for an employee
     */
    public static function getOrCreate(int $employeeId): array
    {
        $db = Database::getInstance();

        // Check existing active QR
        $existing = $db->fetch(
            "SELECT * FROM employee_qr_codes
             WHERE employee_id = :eid AND is_active = 1
             AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY id DESC LIMIT 1",
            ['eid' => $employeeId]
        );

        if ($existing) return $existing;

        // Generate new
        return self::generate($employeeId);
    }

    /**
     * Create a new QR token for an employee
     */
    public static function generate(int $employeeId): array
    {
        $db = Database::getInstance();

        // Deactivate old
        $db->query(
            "UPDATE employee_qr_codes SET is_active = 0 WHERE employee_id = :eid",
            ['eid' => $employeeId]
        );

        $secret   = bin2hex(random_bytes(16));
        $payload  = json_encode([
            'eid'  => $employeeId,
            'sec'  => $secret,
            'ver'  => 2,
            'ts'   => time(),
        ]);
        $token    = self::encryptToken($payload);
        $expiresAt = QR_ROTATION_DAYS > 0
            ? date('Y-m-d H:i:s', strtotime('+' . QR_ROTATION_DAYS . ' days'))
            : null;

        $id = $db->insert('employee_qr_codes', [
            'employee_id' => $employeeId,
            'qr_token'    => $token,
            'qr_secret'   => $secret,
            'is_active'   => 1,
            'expires_at'  => $expiresAt,
        ]);

        // Mark employee as QR enrolled
        $db->update('employees', ['qr_enrolled' => 1], 'id = :id', ['id' => $employeeId]);

        return $db->fetch("SELECT * FROM employee_qr_codes WHERE id = :id", ['id' => $id]);
    }

    /**
     * Validate a QR token from a scan
     * Returns ['valid'=>bool, 'employee_id'=>int, 'employee'=>array, 'message'=>string]
     */
    public static function validate(string $token): array
    {
        $db = Database::getInstance();

        $qr = $db->fetch(
            "SELECT q.*, e.first_name, e.last_name, e.employee_code, e.photo, e.is_active AS emp_active
             FROM employee_qr_codes q
             INNER JOIN employees e ON e.id = q.employee_id
             WHERE q.qr_token = :t AND q.is_active = 1",
            ['t' => $token]
        );

        if (!$qr) {
            return ['valid' => false, 'message' => 'QR code not recognized or deactivated'];
        }
        if (!$qr['emp_active']) {
            return ['valid' => false, 'message' => 'Employee account is inactive'];
        }
        if ($qr['expires_at'] && strtotime($qr['expires_at']) < time()) {
            return ['valid' => false, 'message' => 'QR code has expired. Please request a new one.'];
        }

        // Decrypt and verify payload
        $payload = self::decryptToken($token);
        if (!$payload) {
            return ['valid' => false, 'message' => 'Invalid QR token signature'];
        }
        $data = json_decode($payload, true);
        if (!$data || ($data['eid'] ?? 0) != $qr['employee_id']) {
            return ['valid' => false, 'message' => 'QR token mismatch'];
        }

        // Update scan count
        $db->query(
            "UPDATE employee_qr_codes SET scan_count = scan_count + 1, last_scanned = NOW() WHERE id = :id",
            ['id' => $qr['id']]
        );

        return [
            'valid'       => true,
            'employee_id' => (int)$qr['employee_id'],
            'qr_id'       => $qr['id'],
            'employee'    => [
                'id'            => $qr['employee_id'],
                'employee_code' => $qr['employee_code'],
                'full_name'     => $qr['first_name'] . ' ' . $qr['last_name'],
                'photo'         => $qr['photo'],
            ],
            'message'     => 'QR validated',
        ];
    }

    /**
     * Generate QR code image URL (Google Charts API as CDN-free fallback)
     */
    public static function imageUrl(string $token, int $size = 250): string
    {
        $data = urlencode($token);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$data}&format=png&ecc=M";
    }

    /**
     * Get QR display URL for employee profile page
     */
    public static function displayUrl(string $token): string
    {
        return BASE_URL . '/attendance/qr-scan?token=' . urlencode($token);
    }

    // ─── Crypto helpers ──────────────────────────────────────────────

    private static function encryptToken(string $payload): string
    {
        $key  = hash('sha256', QR_SECRET_KEY, true);
        $iv   = random_bytes(16);
        $enc  = openssl_encrypt($payload, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $mac  = hash_hmac('sha256', $iv . $enc, $key, true);
        return rtrim(strtr(base64_encode($mac . $iv . $enc), '+/', '-_'), '=');
    }

    private static function decryptToken(string $token): ?string
    {
        $raw  = base64_decode(strtr($token . str_repeat('=', 4 - strlen($token) % 4), '-_', '+/'));
        if (strlen($raw) < 64) return null;
        $key  = hash('sha256', QR_SECRET_KEY, true);
        $mac  = substr($raw, 0, 32);
        $iv   = substr($raw, 32, 16);
        $enc  = substr($raw, 48);
        $expected = hash_hmac('sha256', $iv . $enc, $key, true);
        if (!hash_equals($expected, $mac)) return null;
        $dec = openssl_decrypt($enc, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $dec ?: null;
    }
}
