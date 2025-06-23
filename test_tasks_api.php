<?php
// Simple test to check if getTasks method works
require_once __DIR__ . '/vendor/autoload.php';

// Mock the necessary dependencies
$mockRequest = new class() implements \OCP\IRequest {
    public function getHeader(string $name): string { return ''; }
    public function getParam(string $key, $default = null) { return $default; }
    public function getParams(): array { return []; }
    public function getMethod(): string { return 'GET'; }
    public function getUploadedFile(string $key) { return null; }
    public function getEnv(string $key) { return null; }
    public function getCookie(string $key) { return null; }
    public function passesCSRFCheck(): bool { return true; }
    public function isUserAgent(array $userAgent): bool { return false; }
    public function getRemoteAddress(): string { return '127.0.0.1'; }
    public function getServerProtocol(): string { return 'HTTP/1.1'; }
    public function getInsecureServerHost(): string { return 'localhost'; }
    public function getServerHost(): string { return 'localhost'; }
    public function getRequestUri(): string { return '/test'; }
    public function getRequestId(): string { return 'test'; }
    public function getRawPathInfo(): string { return '/test'; }
    public function getPathInfo(): string { return '/test'; }
    public function getScriptName(): string { return '/test'; }
    public function isSecure(): bool { return false; }
    public function getHttpProtocol(): string { return 'HTTP/1.1'; }
};

$mockConfig = new class() implements \OCP\IConfig {
    public function setSystemValue($key, $value) {}
    public function getSystemValue($key, $default = '') { return $default; }
    public function getSystemValueString($key, $default = '') { return $default; }
    public function getSystemValueInt($key, $default = 0) { return $default; }
    public function getSystemValueBool($key, $default = false) { return $default; }
    public function getSystemValues() { return []; }
    public function deleteSystemValue($key) {}
    public function getAppKeys($appName) { return []; }
    public function setAppValue($appName, $key, $value) {}
    public function getAppValue($appName, $key, $default = '') { return $default; }
    public function deleteAppValue($appName, $key) {}
    public function deleteAppValues($appName) {}
    public function setUserValue($userId, $appName, $key, $value) {}
    public function getUserValue($userId, $appName, $key, $default = '') { return $default; }
    public function getUserValueForUsers($appName, $key, $userIds) { return []; }
    public function getAllUserValues($userId) { return []; }
    public function getUserKeys($userId, $appName) { return []; }
    public function deleteUserValue($userId, $appName, $key) {}
    public function deleteAllUserValues($userId) {}
    public function deleteAppFromAllUsers($appName) {}
    public function getSystemValueForUsers($key, $userIds) { return []; }
    public function searchUsersByUserConfigValue(string $appName, string $key, string $value): array { return []; }
};

echo "Testing tasks API without full Nextcloud bootstrap...\n";
echo "This should fail gracefully if dependencies are missing.\n";
?>
