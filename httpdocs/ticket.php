<?php
// Get the ticket ID from the URL path
$requestUri = $_SERVER['REQUEST_URI'];
$pathParts = explode('/', trim($requestUri, '/'));

// Extract ticket ID (e.g., /ticket/TICKET-ABC123 or /ticket.php?id=TICKET-ABC123)
$ticketId = '';
if (isset($_GET['id'])) {
    $ticketId = $_GET['id'];
} elseif (count($pathParts) >= 2 && $pathParts[0] === 'ticket') {
    $ticketId = $pathParts[1];
}

// Remove any query strings
$ticketId = explode('?', $ticketId)[0];

// Validate ticket ID format
if (empty($ticketId) || !preg_match('/^TICKET-[A-Za-z0-9]+$/', $ticketId)) {
    header("HTTP/1.0 404 Not Found");
    die("Invalid ticket ID");
}

// Include view_ticket.php to display the ticket with clean URL
$_GET['id'] = $ticketId;
include __DIR__ . '/view_ticket.php';
exit;
