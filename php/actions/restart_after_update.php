<?php
session_start();

// Simula una richiesta POST con l'azione corretta
$_POST['action'] = 'docker-restart';

// Includi lo script principale che gestisce le azioni
require 'printserver.php';
exit;
