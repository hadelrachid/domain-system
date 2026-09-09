<?php if (!defined('DOMAIN_SYSTEM_ROOT')) exit; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="description" content="Agende sua consulta online na Daher Clínica. Escolha o médico, data e horário de forma rápida e segura.">
    <meta name="theme-color" content="#1A365D">
    <title>Agendamento Online - Daher Clínica</title>
    
    <!-- Preconnect para performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Theme CSS (inline para evitar problema de URL pública) -->
    <style>
    <?php
    $cssFile = __DIR__ . '/assets/css/booking.css';
    if (file_exists($cssFile)) {
        echo file_get_contents($cssFile);
    }
    ?>
    </style>
</head>
<body>
    <!-- Header -->
    <header class="booking-header-bar">
        <a href="https://daherclinica.com" target="_blank" rel="noopener" class="logo-link">
            <i class="fas fa-heartbeat" style="color: var(--secondary); font-size: 1.4rem;"></i>
            <span class="logo-text">Daher <span class="accent">Clínica</span></span>
        </a>
    </header>
