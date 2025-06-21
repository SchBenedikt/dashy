<?php
// Quick test script for Notes API
require_once 'vendor/autoload.php';

// Test database connection
try {
    echo "Testing Notes API...\n";
    
    // Simulate the basic controller logic
    $config = [
        'user_id' => 'testuser',
        'folder' => ''
    ];
    
    echo "Config: " . json_encode($config) . "\n";
    echo "API endpoint should return notes array\n";
    
    // Check if we can even create the basic response structure
    $response = ['notes' => []];
    echo "Basic response: " . json_encode($response) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
