<?php
// perfil.php - Versión compatible con tu sistema actual (SIN JWT)
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de base de datos
$host = 'localhost';
$dbname = 'usuarios_urban_coop';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// VALIDACIÓN DE SESIÓN SIMPLIFICADA
function validateUserAccess() {
    // Aquí puedes agregar tu lógica de validación actual
    // Por ahora retorna true para testing
    return true;
}

// Variables por defecto
$user_name = 'Usuario';
$user_status = 2;
$user_id = 1; // ID de prueba - cambiar por tu lógica
$can_access = true;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urban Coop - Dashboard</title>
    <link rel="stylesheet" href="CSS/perfilStyles.css">
    <style>
        /* ===== URBAN COOP - PERFIL STYLES ===== */
/* Paleta de colores: Rojo (#DC143C), Blanco (#FFFFFF), Negro (#1a1a1a), Grises */

/* === VARIABLES CSS === */
:root {
  /* Colores principales */
  --color-primary: #DC143C;
  --color-primary-dark: #B71C1C;
  --color-primary-light: #FF5252;
  --color-white: #FFFFFF;
  --color-black: #1a1a1a;
  --color-text: #2c2c2c;
  --color-text-light: #6b6b6b;
  
  /* Colores de fondo */
  --color-background: #fafafa;
  --color-surface: #ffffff;
  --color-surface-hover: #f5f5f5;
  
  /* Colores de borde */
  --color-border: #e0e0e0;
  --color-border-light: #f0f0f0;
  --color-border-dark: #d0d0d0;
  
  /* Estados */
  --color-success: #2e7d32;
  --color-warning: #f57c00;
  --color-error: var(--color-primary);
  --color-info: #1976d2;
  
  /* Espaciado */
  --spacing-xs: 4px;
  --spacing-sm: 8px;
  --spacing-md: 16px;
  --spacing-lg: 24px;
  --spacing-xl: 32px;
  --spacing-2xl: 48px;
  
  /* Tipografía */
  --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
  --font-size-xs: 12px;
  --font-size-sm: 14px;
  --font-size-base: 16px;
  --font-size-lg: 18px;
  --font-size-xl: 24px;
  --font-size-2xl: 32px;
  
  /* Sombras */
  --shadow-sm: 0 2px 4px rgba(28, 28, 28, 0.1);
  --shadow-md: 0 4px 12px rgba(28, 28, 28, 0.15);
  --shadow-lg: 0 8px 24px rgba(28, 28, 28, 0.2);
  
  /* Bordes redondeados */
  --radius-sm: 6px;
  --radius-md: 12px;
  --radius-lg: 16px;
  --radius-full: 50%;
  
  /* Transiciones */
  --transition-fast: 0.15s ease;
  --transition-normal: 0.3s ease;
  --transition-slow: 0.5s ease;
}

/* === RESET Y BASE === */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  font-size: 16px;
  scroll-behavior: smooth;
}

body {
  font-family: var(--font-family);
  font-size: var(--font-size-base);
  line-height: 1.6;
  color: var(--color-text);
  background-color: var(--color-background);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

/* === LOADING SCREEN === */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--color-white);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-content {
  text-align: center;
}

.loading-spinner {
  width: 48px;
  height: 48px;
  border: 4px solid var(--color-border);
  border-top: 4px solid var(--color-primary);
  border-radius: var(--radius-full);
  animation: spin 1s linear infinite;
  margin: 0 auto var(--spacing-md);
}

.loading-text {
  font-size: var(--font-size-lg);
  font-weight: 500;
  color: var(--color-text-light);
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* === LAYOUT PRINCIPAL === */
.main-app {
  display: none;
}

.app-container {
  display: flex;
  height: 100vh;
  background: var(--color-background);
}

/* === SIDEBAR === */
.sidebar {
  width: 280px;
  background: var(--color-black);
  color: var(--color-white);
  display: flex;
  flex-direction: column;
  position: relative;
  box-shadow: var(--shadow-md);
}

.sidebar-header {
  padding: var(--spacing-xl) var(--spacing-lg);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo-container {
  margin-bottom: var(--spacing-lg);
}

.logo {
  width: 100%;
  max-width: 160px;
  height: auto;
  object-fit: contain;
}

.search-container {
  position: relative;
}

.search-input {
  width: 100%;
  padding: var(--spacing-md) var(--spacing-lg);
  padding-right: 40px;
  border: none;
  border-radius: var(--radius-md);
  background: rgba(255, 255, 255, 0.1);
  color: var(--color-white);
  font-size: var(--font-size-sm);
  transition: var(--transition-normal);
}

.search-input::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.search-input:focus {
  outline: none;
  background: rgba(255, 255, 255, 0.15);
  box-shadow: 0 0 0 2px rgba(220, 20, 60, 0.3);
}

.search-icon {
  position: absolute;
  right: var(--spacing-md);
  top: 50%;
  transform: translateY(-50%);
  color: rgba(255, 255, 255, 0.6);
}

.sidebar-nav {
  padding: var(--spacing-lg);
}

.nav-item {
  display: flex;
  align-items: center;
  padding: var(--spacing-md);
  margin-bottom: var(--spacing-sm);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: var(--transition-normal);
  position: relative;
}

.nav-item:hover {
  background: rgba(220, 20, 60, 0.15);
}

.nav-item-icon {
  margin-right: var(--spacing-md);
  display: flex;
  align-items: center;
  justify-content: center;
}

.nav-item-text {
  flex: 1;
  font-weight: 500;
}

.nav-item-badge {
  background: var(--color-primary);
  color: var(--color-white);
  border-radius: var(--radius-full);
  padding: 2px var(--spacing-sm);
  font-size: var(--font-size-xs);
  font-weight: 600;
  min-width: 20px;
  text-align: center;
}

/* === MAIN CONTENT === */
.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* === HEADER === */
.app-header {
  background: var(--color-white);
  border-bottom: 1px solid var(--color-border);
  padding: 0 var(--spacing-xl);
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 70px;
  box-shadow: var(--shadow-sm);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-nav {
  display: flex;
  gap: var(--spacing-sm);
}

.nav-button {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-md) var(--spacing-lg);
  border: none;
  background: transparent;
  color: var(--color-text-light);
  cursor: pointer;
  border-radius: var(--radius-sm);
  font-size: var(--font-size-sm);
  font-weight: 500;
  transition: var(--transition-normal);
  position: relative;
}

.nav-button:hover {
  background: var(--color-surface-hover);
  color: var(--color-primary);
}

.nav-button.active {
  background: var(--color-primary);
  color: var(--color-white);
}

.nav-button.active::after {
  content: '';
  position: absolute;
  bottom: -1px;
  left: 0;
  right: 0;
  height: 2px;
  background: var(--color-primary);
}

/* === PROFILE SECTION === */
.profile-section {
  position: relative;
}

.profile-dropdown {
  position: relative;
}

.profile-button {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: var(--radius-sm);
  transition: var(--transition-normal);
  color: var(--color-text);
}

.profile-button:hover {
  background: var(--color-surface-hover);
}

.dropdown-arrow {
  transition: var(--transition-fast);
}

.profile-dropdown.active .dropdown-arrow {
  transform: rotate(180deg);
}

.profile-menu {
  position: absolute;
  top: calc(100% + var(--spacing-sm));
  right: 0;
  background: var(--color-white);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  min-width: 180px;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-10px);
  transition: var(--transition-normal);
  z-index: 1000;
}

.profile-menu.show {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.profile-menu-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  width: 100%;
  padding: var(--spacing-md);
  border: none;
  background: transparent;
  color: var(--color-text);
  cursor: pointer;
  transition: var(--transition-normal);
  text-align: left;
  font-size: var(--font-size-sm);
}

.profile-menu-item:hover {
  background: var(--color-surface-hover);
  color: var(--color-primary);
}

/* === CONTENT WRAPPER === */
.content-wrapper {
  flex: 1;
  padding: var(--spacing-xl);
  overflow-y: auto;
}

.content-section {
  display: none;
}

.content-section.active {
  display: block;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
}

.section-title {
  font-size: var(--font-size-2xl);
  font-weight: 700;
  color: var(--color-text);
  margin: 0;
}

/* === BUTTONS === */
.action-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-md) var(--spacing-lg);
  border: 2px solid transparent;
  border-radius: var(--radius-sm);
  font-size: var(--font-size-sm);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  cursor: pointer;
  transition: var(--transition-normal);
  text-decoration: none;
  position: relative;
  overflow: hidden;
}

.action-button.primary {
  background: var(--color-primary);
  color: var(--color-white);
  border-color: var(--color-primary);
}

.action-button.primary:hover {
  background: var(--color-primary-dark);
  border-color: var(--color-primary-dark);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.action-button.secondary {
  background: transparent;
  color: var(--color-text);
  border-color: var(--color-border);
}

.action-button.secondary:hover {
  background: var(--color-surface-hover);
  border-color: var(--color-border-dark);
}

.action-button.danger {
  background: transparent;
  color: var(--color-primary);
  border-color: var(--color-primary);
}

.action-button.danger:hover {
  background: var(--color-primary);
  color: var(--color-white);
}

.action-button.small {
  padding: var(--spacing-sm) var(--spacing-md);
  font-size: var(--font-size-xs);
}

/* === SUMMARY GRID === */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-xl);
}

.summary-card {
  background: var(--color-white);
  border-radius: var(--radius-md);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-sm);
  border-left: 4px solid var(--color-primary);
  transition: var(--transition-normal);
  position: relative;
  overflow: hidden;
}

.summary-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, var(--color-primary) 0%, var(--color-primary-light) 100%);
}

.summary-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-lg);
}

.card-icon {
  padding: var(--spacing-sm);
  border-radius: var(--radius-sm);
  background: rgba(220, 20, 60, 0.1);
  color: var(--color-primary);
}

.card-title {
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: var(--color-text-light);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0;
}

.card-amount {
  font-size: var(--font-size-2xl);
  font-weight: 800;
  color: var(--color-text);
  margin-bottom: var(--spacing-sm);
  line-height: 1.2;
}

.summary-card.balance .card-amount {
  color: var(--color-success);
}

.summary-card.fee .card-amount {
  color: var(--color-primary);
}

.card-status,
.card-subtitle {
  font-size: var(--font-size-sm);
  color: var(--color-text-light);
  margin: 0;
}

/* === PROGRESS BAR === */
.progress-container {
  margin-bottom: var(--spacing-md);
}

.progress-bar {
  width: 100%;
  height: 12px;
  background: var(--color-border-light);
  border-radius: var(--radius-sm);
  overflow: hidden;
  margin-bottom: var(--spacing-sm);
  position: relative;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--color-success) 0%, var(--color-primary) 100%);
  border-radius: var(--radius-sm);
  transition: width var(--transition-slow);
  position: relative;
  overflow: hidden;
}

.progress-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(100%); }
}

.progress-text {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--color-text);
  text-align: center;
}

/* === MESSAGES === */
.messages-container {
  margin-bottom: var(--spacing-lg);
}

.alert {
  padding: var(--spacing-md) var(--spacing-lg);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-sm);
  font-weight: 500;
  border-left: 4px solid;
  margin-bottom: var(--spacing-md);
}

.alert-success {
  background-color: rgba(46, 125, 50, 0.1);
  color: var(--color-success);
  border-color: var(--color-success);
}

.alert-error {
  background-color: rgba(220, 20, 60, 0.1);
  color: var(--color-primary);
  border-color: var(--color-primary);
}

.alert-warning {
  background-color: rgba(245, 124, 0, 0.1);
  color: var(--color-warning);
  border-color: var(--color-warning);
}

/* === FORMS === */
.form-modal {
  display: none;
  margin-bottom: var(--spacing-xl);
}

.form-container {
  background: var(--color-white);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  overflow: hidden;
  border: 1px solid var(--color-border);
}

.form-header {
  padding: var(--spacing-lg);
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  color: var(--color-white);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-header h2 {
  font-size: var(--font-size-lg);
  font-weight: 600;
  margin: 0;
}

.close-btn {
  background: transparent;
  border: none;
  color: var(--color-white);
  cursor: pointer;
  padding: var(--spacing-sm);
  border-radius: var(--radius-sm);
  transition: var(--transition-normal);
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.2);
}

.form-container form {
  padding: var(--spacing-lg);
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: var(--spacing-lg);
  margin-bottom: var(--spacing-lg);
}

.form-group {
  margin-bottom: var(--spacing-lg);
}

.form-label {
  display: block;
  font-size: var(--font-size-sm);
  font-weight: 600;
  color: var(--color-text);
  margin-bottom: var(--spacing-sm);
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: var(--spacing-md);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-sm);
  font-family: var(--font-family);
  color: var(--color-text);
  background: var(--color-white);
  transition: var(--transition-normal);
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(220, 20, 60, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.form-hint {
  display: block;
  font-size: var(--font-size-xs);
  color: var(--color-text-light);
  margin-top: var(--spacing-xs);
}

.amount-input {
  position: relative;
  display: flex;
  align-items: center;
}

.amount-symbol {
  position: absolute;
  left: var(--spacing-md);
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--color-text-light);
  z-index: 1;
}

.form-input.amount {
  padding-left: 36px;
  font-size: var(--font-size-base);
  font-weight: 600;
}

.form-actions {
  display: flex;
  gap: var(--spacing-md);
  justify-content: flex-end;
  margin-top: var(--spacing-xl);
  padding-top: var(--spacing-lg);
  border-top: 1px solid var(--color-border-light);
}

/* === UPLOAD ZONE === */
.upload-zone {
  border: 2px dashed var(--color-border);
  border-radius: var(--radius-md);
  padding: var(--spacing-2xl);
  text-align: center;
  cursor: pointer;
  transition: var(--transition-normal);
  margin-bottom: var(--spacing-lg);
  position: relative;
  overflow: hidden;
  background: var(--color-surface);
}

.upload-zone:hover {
  border-color: var(--color-primary);
  background: rgba(220, 20, 60, 0.05);
}

.upload-zone.dragover {
  border-color: var(--color-primary);
  background: rgba(220, 20, 60, 0.1);
  transform: scale(1.02);
}

.upload-icon {
  color: var(--color-primary);
  margin-bottom: var(--spacing-md);
}

.upload-text {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-xs) 0;
}

.upload-subtext {
  font-size: var(--font-size-sm);
  color: var(--color-text-light);
  margin: 0 0 var(--spacing-md) 0;
}

.upload-info {
  font-size: var(--font-size-xs);
  color: var(--color-text-light);
  margin: 0;
}

.upload-zone input[type="file"] {
  position: absolute;
  opacity: 0;
  width: 100%;
  height: 100%;
  cursor: pointer;
  top: 0;
  left: 0;
}

/* === TASKS === */
.task-container {
  background: var(--color-white);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  border: 1px solid var(--color-border);
}

.task-item {
  display: flex;
  align-items: center;
  padding: var(--spacing-lg);
  border-bottom: 1px solid var(--color-border-light);
  transition: var(--transition-normal);
  position: relative;
}

.task-item:last-child {
  border-bottom: none;
}

.task-item:hover {
  background: var(--color-surface-hover);
}

.task-item.completed {
  opacity: 0.6;
}

.task-item.completed .task-text {
  text-decoration: line-through;
  color: var(--color-text-light);
}

.task-checkbox-container {
  position: relative;
  margin-right: var(--spacing-md);
}

.task-checkbox {
  appearance: none;
  width: 20px;
  height: 20px;
  border: 2px solid var(--color-border-dark);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: var(--transition-normal);
}

.task-checkbox:checked {
  background: var(--color-primary);
  border-color: var(--color-primary);
}

.checkbox-label {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.task-checkbox:checked + .checkbox-label::after {
  content: '✓';
  color: var(--color-white);
  font-weight: bold;
  font-size: var(--font-size-xs);
}

.task-text {
  flex: 1;
  font-size: var(--font-size-base);
  font-weight: 500;
  color: var(--color-text);
}

.task-actions {
  display: flex;
  gap: var(--spacing-sm);
  opacity: 0;
  transition: var(--transition-normal);
}

.task-item:hover .task-actions {
  opacity: 1;
}

.task-action-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: var(--spacing-sm);
  border-radius: var(--radius-sm);
  transition: var(--transition-normal);
  color: var(--color-text-light);
}

.task-action-btn:hover {
  background: var(--color-surface-hover);
  color: var(--color-primary);
}

.star-btn.favorite {
  color: var(--color-warning);
}

.star-btn.favorite svg {
  fill: currentColor;
}

/* === PAYMENT CARDS === */
.payments-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.payment-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--color-white);
  padding: var(--spacing-lg);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  transition: var(--transition-normal);
}

.payment-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.payment-info {
  flex: 1;
}

.payment-title {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-sm) 0;
}

.payment-details {
  display: flex;
  gap: var(--spacing-md);
  font-size: var(--font-size-sm);
  color: var(--color-text-light);
}

.payment-amount {
  font-weight: 600;
  color: var(--color-success);
}

.payment-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.payment-status {
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-xs);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.payment-status.pending {
  background: rgba(245, 124, 0, 0.1);
  color: var(--color-warning);
}

.payment-status.approved {
  background: rgba(46, 125, 50, 0.1);
  color: var(--color-success);
}

.payment-status.rejected {
  background: rgba(220, 20, 60, 0.1);
  color: var(--color-primary);
}

/* === HOURS === */
.hours-summary {
  background: var(--color-white);
  border-radius: var(--radius-md);
  padding: var(--spacing-lg);
  box-shadow: var(--shadow-sm);
  margin-bottom: var(--spacing-xl);
  border-left: 4px solid var(--color-info);
}

.summary-title {
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-md) 0;
}

.summary-stats {
  display: flex;
  gap: var(--spacing-xl);
}

.stat {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}

.stat-label {
  font-size: var(--font-size-sm);
  color: var(--color-text-light);
}

.stat-value {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--color-text);
}

.hours-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.hours-card {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: var(--color-white);
  padding: var(--spacing-lg);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--color-border);
  transition: var(--transition-normal);
}

.hours-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.hours-info {
  flex: 1;
}

.hours-title {
  font-size: var(--font-size-base);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-sm) 0;
}

.hours-type {
  display: inline-block;
  padding: var(--spacing-xs) var(--spacing-sm);
  background: var(--color-primary);
  color: var(--color-white);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-xs);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: var(--spacing-sm);
}

.hours-description {
  font-size: var(--font-size-sm);
  color: var(--color-text);
  line-height: 1.6;
  margin: 0 0 var(--spacing-sm) 0;
}

.hours-date {
  font-size: var(--font-size-xs);
  color: var(--color-text-light);
}

.hours-actions {
  margin-left: var(--spacing-lg);
}

/* === RESPONSIVE DESIGN === */
@media (max-width: 1200px) {
  .summary-grid {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  }
  
  .content-wrapper {
    padding: var(--spacing-lg);
  }
}

@media (max-width: 768px) {
  .app-container {
    flex-direction: column;
  }
  
  .sidebar {
    width: 100%;
    height: auto;
    position: relative;
  }
  
  .sidebar-header {
    padding: var(--spacing-lg);
  }
  
  .sidebar-nav {
    display: flex;
    overflow-x: auto;
    gap: var(--spacing-sm);
    padding: var(--spacing-md) var(--spacing-lg);
  }
  
  .nav-item {
    flex-shrink: 0;
    margin-bottom: 0;
  }
  
  .app-header {
    padding: var(--spacing-md) var(--spacing-lg);
    height: 60px;
  }
  
  .header-nav {
    overflow-x: auto;
    gap: var(--spacing-xs);
  }
  
  .nav-button {
    flex-shrink: 0;
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: var(--font-size-xs);
  }
  
  .content-wrapper {
    padding: var(--spacing-lg) var(--spacing-md);
  }
  
  .section-header {
    flex-direction: column;
    align-items: stretch;
    gap: var(--spacing-md);
  }
  
  .section-title {
    font-size: var(--font-size-xl);
  }
  
  .summary-grid {
    grid-template-columns: 1fr;
    gap: var(--spacing-md);
  }
  
  .form-grid {
    grid-template-columns: 1fr;
    gap: var(--spacing-md);
  }
  
  .form-actions {
    flex-direction: column;
    gap: var(--spacing-sm);
  }
  
  .action-button {
    width: 100%;
    justify-content: center;
  }
  
  .payment-card,
  .hours-card {
    flex-direction: column;
    gap: var(--spacing-md);
    align-items: stretch;
  }
  
  .payment-actions,
  .hours-actions {
    align-self: flex-end;
  }
  
  .summary-stats {
    flex-direction: column;
    gap: var(--spacing-md);
  }
  
  .profile-button span {
    display: none;
  }
}

@media (max-width: 480px) {
  .content-wrapper {
    padding: var(--spacing-md);
  }
  
  .form-container {
    margin: 0 -var(--spacing-md);
    border-radius: 0;
  }
  
  .summary-card,
  .task-container,
  .payment-card,
  .hours-card,
  .hours-summary {
    margin: 0 -var(--spacing-md);
    border-radius: 0;
    border-left: none;
    border-right: none;
  }
  
  .upload-zone {
    padding: var(--spacing-lg);
  }
  
  .section-title {
    font-size: var(--font-size-lg);
  }
  
  .card-amount {
    font-size: var(--font-size-xl);
  }
}

/* === UTILITIES === */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.text-center {
  text-align: center;
}

.text-left {
  text-align: left;
}

.text-right {
  text-align: right;
}

.d-none {
  display: none !important;
}

.d-block {
  display: block !important;
}

.d-flex {
  display: flex !important;
}

.justify-center {
  justify-content: center;
}

.align-center {
  align-items: center;
}

.gap-sm {
  gap: var(--spacing-sm);
}

.gap-md {
  gap: var(--spacing-md);
}

.gap-lg {
  gap: var(--spacing-lg);
}

.mb-0 {
  margin-bottom: 0 !important;
}

.mb-sm {
  margin-bottom: var(--spacing-sm) !important;
}

.mb-md {
  margin-bottom: var(--spacing-md) !important;
}

.mb-lg {
  margin-bottom: var(--spacing-lg) !important;
}

.mt-0 {
  margin-top: 0 !important;
}

.mt-sm {
  margin-top: var(--spacing-sm) !important;
}

.mt-md {
  margin-top: var(--spacing-md) !important;
}

.mt-lg {
  margin-top: var(--spacing-lg) !important;
}

/* === ANIMATIONS === */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes pulse {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
  100% {
    transform: scale(1);
  }
}

.animate-fadeIn {
  animation: fadeIn var(--transition-normal) ease-out;
}

.animate-slideIn {
  animation: slideIn var(--transition-normal) ease-out;
}

/* === FOCUS STYLES === */
*:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

/* === SCROLLBAR STYLES === */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: var(--color-background);
}

::-webkit-scrollbar-thumb {
  background: var(--color-border-dark);
  border-radius: var(--radius-sm);
}

::-webkit-scrollbar-thumb:hover {
  background: var(--color-text-light);
}

/* === PRINT STYLES === */
@media print {
  .sidebar,
  .app-header,
  .form-modal,
  .action-button,
  .task-actions,
  .payment-actions,
  .hours-actions {
    display: none !important;
  }
  
  .main-content {
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  
  .content-wrapper {
    padding: 0 !important;
  }
  
  .summary-card,
  .task-container,
  .payment-card,
  .hours-card {
    box-shadow: none !important;
    border: 1px solid var(--color-border) !important;
    break-inside: avoid;
  }
}
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div id="loadingScreen" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p class="loading-text">Cargando perfil...</p>
        </div>
    </div>

    <!-- Main Application -->
    <div id="mainApp" class="main-app">
        <div class="app-container">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-header">
                    <div class="logo-container">
                        <img src="IMG/UrbanCoop White.jpeg" alt="Urban Coop" class="logo">
                    </div>
                    <div class="search-container">
                        <input type="text" class="search-input" placeholder="Buscar...">
                        <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </div>
                </div>
                
                <nav class="sidebar-nav">
                    <div class="nav-item">
                        <div class="nav-item-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                            </svg>
                        </div>
                        <span class="nav-item-text">Importantes</span>
                        <span class="nav-item-badge">0</span>
                    </div>
                    
                    <div class="nav-item">
                        <div class="nav-item-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h4m6-6h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-4m-6-6V9a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </div>
                        <span class="nav-item-text">Tareas</span>
                        <span class="nav-item-badge">0</span>
                    </div>
                    
                    <div class="nav-item">
                        <div class="nav-item-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9,22 9,12 15,12 15,22"></polyline>
                            </svg>
                        </div>
                        <span class="nav-item-text">Casa</span>
                        <span class="nav-item-badge">3</span>
                    </div>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Header -->
                <header class="app-header">
                    <nav class="header-nav">
                        <button class="nav-button active" data-section="tasks">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h4m6-6h4a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-4m-6-6V9a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                            Tareas
                        </button>
                        <button class="nav-button" data-section="payments">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14,2 14,8 20,8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10,9 9,9 8,9"></polyline>
                            </svg>
                            Comprobantes
                        </button>
                        <button class="nav-button" data-section="hours">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12,6 12,12 16,14"></polyline>
                            </svg>
                            Horas Trabajadas
                        </button>
                    </nav>
                    
                    <div class="profile-section">
                        <div class="profile-dropdown">
                            <button class="profile-button">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <span id="userNameDisplay">Usuario de Prueba</span>
                                <svg class="dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </button>
                            <div class="profile-menu" id="profileDropdown">
                                <button class="profile-menu-item" onclick="logout()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                        <polyline points="16,17 21,12 16,7"></polyline>
                                        <line x1="21" y1="12" x2="9" y2="12"></line>
                                    </svg>
                                    Cerrar Sesión
                                </button>
                            </div>
                        </div>
                    </div>
                </header>
                
                <!-- Content Area -->
                <div class="content-wrapper">
                    <!-- Tasks Section -->
                    <section id="tasks-section" class="content-section active">
                        <div class="section-header">
                            <h1 class="section-title">Mis Tareas</h1>
                            <button class="action-button primary" id="addTaskBtn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Agregar Tarea
                            </button>
                        </div>
                        
                        <div class="task-container" id="taskList">
                            <div class="task-item" data-category="trabajo">
                                <div class="task-checkbox-container">
                                    <input type="checkbox" class="task-checkbox" id="task-1">
                                    <label for="task-1" class="checkbox-label"></label>
                                </div>
                                <span class="task-text">Completar registro de horas</span>
                                <div class="task-actions">
                                    <button class="task-action-btn star-btn" title="Marcar como importante">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                                        </svg>
                                    </button>
                                    <button class="task-action-btn delete-btn" title="Eliminar tarea">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3,6 5,6 21,6"></polyline>
                                            <path d="m19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Payments Section -->
                    <section id="payments-section" class="content-section">
                        <div class="section-header">
                            <h1 class="section-title">Comprobantes de Pago</h1>
                            <button class="action-button primary" id="uploadPaymentBtn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="7,10 12,15 17,10"></polyline>
                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                </svg>
                                Subir Comprobante
                            </button>
                        </div>

                        <!-- Payment Summary Cards -->
                        <div class="summary-grid">
                            <div class="summary-card balance">
                                <div class="card-header">
                                    <div class="card-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                            <line x1="1" y1="10" x2="23" y2="10"></line>
                                        </svg>
                                    </div>
                                    <h3 class="card-title">Pago Actual</h3>
                                </div>
                                <div class="card-amount" id="currentBalance">$15.000</div>
                                <div class="card-status" id="paymentStatus">Al día</div>
                            </div>

                            <div class="summary-card fee">
                                <div class="card-header">
                                    <div class="card-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="12" y1="1" x2="12" y2="23"></line>
                                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                        </svg>
                                    </div>
                                    <h3 class="card-title">Cuota Mensual</h3>
                                </div>
                                <div class="card-amount" id="monthlyFee">$22.000</div>
                                <div class="card-subtitle">Cuota fija mensual</div>
                            </div>

                            <div class="summary-card progress">
                                <div class="card-header">
                                    <div class="card-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                                        </svg>
                                    </div>
                                    <h3 class="card-title">Progreso de Pago</h3>
                                </div>
                                <div class="progress-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" id="progressFill" data-progress="68"></div>
                                    </div>
                                    <div class="progress-text" id="progressText">68%</div>
                                </div>
                                <div class="card-subtitle">Completado este mes</div>
                            </div>
                        </div>

                        <div id="paymentMessages" class="messages-container"></div>

                        <!-- Upload Form -->
                        <div id="upload-form" class="form-modal">
                            <div class="form-container">
                                <div class="form-header">
                                    <h2>Subir Comprobante de Pago</h2>
                                    <button class="close-btn" id="closeUploadForm">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                                </div>
                                <form id="uploadPaymentForm" enctype="multipart/form-data">
                                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                    
                                    <div class="upload-zone" id="uploadArea">
                                        <div class="upload-icon">
                                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                <polyline points="7,10 12,15 17,10"></polyline>
                                                <line x1="12" y1="15" x2="12" y2="3"></line>
                                            </svg>
                                        </div>
                                        <p class="upload-text">Arrastra y suelta tu archivo aquí</p>
                                        <p class="upload-subtext">o haz clic para seleccionar</p>
                                        <p class="upload-info">PDF, JPG, PNG - Máximo 5MB</p>
                                        <input type="file" name="payment_file" id="payment_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                    </div>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Mes de Pago *</label>
                                            <select name="payment_month" class="form-select" required>
                                                <option value="">Seleccionar mes</option>
                                                <option value="01">Enero</option>
                                                <option value="02">Febrero</option>
                                                <option value="03">Marzo</option>
                                                <option value="04">Abril</option>
                                                <option value="05">Mayo</option>
                                                <option value="06">Junio</option>
                                                <option value="07">Julio</option>
                                                <option value="08">Agosto</option>
                                                <option value="09">Septiembre</option>
                                                <option value="10">Octubre</option>
                                                <option value="11">Noviembre</option>
                                                <option value="12">Diciembre</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Año *</label>
                                            <select name="payment_year" class="form-select" required>
                                                <option value="">Seleccionar año</option>
                                                <option value="2024">2024</option>
                                                <option value="2025">2025</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Importe del Pago *</label>
                                        <div class="amount-input">
                                            <span class="amount-symbol">$</span>
                                            <input type="number" name="payment_amount" class="form-input amount" 
                                                   min="1000" max="1000000" step="1" 
                                                   placeholder="22000" required>
                                        </div>
                                        <small class="form-hint">Ingrese el monto sin puntos ni comas</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Descripción (opcional)</label>
                                        <textarea name="payment_description" class="form-textarea" 
                                                  rows="3" placeholder="Agregar notas adicionales..."></textarea>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="action-button primary">Subir Comprobante</button>
                                        <button type="button" class="action-button secondary" id="cancelUpload">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="payments-list" id="paymentsList">
                            <!-- Ejemplo de comprobante -->
                            <div class="payment-card">
                                <div class="payment-info">
                                    <h3 class="payment-title">Comprobante Septiembre 2024</h3>
                                    <div class="payment-details">
                                        <span class="payment-amount">$15.000</span>
                                        <span class="payment-date">15/09/2024 14:30</span>
                                        <span class="payment-size">1.2 MB</span>
                                    </div>
                                </div>
                                <div class="payment-actions">
                                    <span class="payment-status pending">Pendiente</span>
                                    <button class="action-button secondary small">Ver</button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Hours Section -->
                    <section id="hours-section" class="content-section">
                        <div class="section-header">
                            <h1 class="section-title">Registro de Horas</h1>
                            <button class="action-button primary" id="addHoursBtn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Registrar Horas
                            </button>
                        </div>

                        <div class="hours-summary">
                            <h3 class="summary-title">Resumen del mes actual</h3>
                            <div class="summary-stats">
                                <div class="stat">
                                    <span class="stat-label">Total de horas:</span>
                                    <span class="stat-value" id="totalHoursMonth">32 horas</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-label">Mes:</span>
                                    <span class="stat-value" id="currentMonthDisplay">Septiembre 2024</span>
                                </div>
                            </div>
                        </div>

                        <div id="hours-form" class="form-modal">
                            <div class="form-container">
                                <div class="form-header">
                                    <h2>Registrar Horas Trabajadas</h2>
                                    <button class="close-btn" id="closeHoursForm">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                                </div>
                                
                                <div id="hoursMessages" class="messages-container"></div>
                                
                                <form id="hoursForm">
                                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Fecha de Trabajo *</label>
                                            <input type="date" name="work_date" class="form-input" 
                                                   max="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Horas Trabajadas *</label>
                                            <input type="number" name="hours_worked" class="form-input" 
                                                   min="0.5" max="24" step="0.5" 
                                                   placeholder="8.0" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Descripción del Trabajo *</label>
                                        <textarea name="description" class="form-textarea" rows="4" 
                                                  placeholder="Describe las actividades realizadas durante el día..." 
                                                  required></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Tipo de Trabajo *</label>
                                        <select name="work_type" class="form-select" required>
                                            <option value="">Seleccionar tipo</option>
                                            <option value="desarrollo">Desarrollo</option>
                                            <option value="reunion">Reuniones</option>
                                            <option value="documentacion">Documentación</option>
                                            <option value="testing">Testing</option>
                                            <option value="administrativo">Administrativo</option>
                                            <option value="soporte">Soporte Técnico</option>
                                            <option value="investigacion">Investigación</option>
                                            <option value="otros">Otros</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="submit" class="action-button primary">Registrar Horas</button>
                                        <button type="button" class="action-button secondary" id="cancelHours">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="hours-list" id="hoursList">
                            <!-- Ejemplo de registro -->
                            <div class="hours-card">
                                <div class="hours-info">
                                    <h3 class="hours-title">14/09/2024 - 8 horas</h3>
                                    <div class="hours-type">Desarrollo</div>
                                    <p class="hours-description">Desarrollo de nuevas funcionalidades para el sistema de gestión cooperativa.</p>
                                    <small class="hours-date">Registrado el 14/09/2024 18:30</small>
                                </div>
                                <div class="hours-actions">
                                    <button class="action-button danger small">Eliminar</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <script src="JSS/perfil.js"></script>
</body>
</html>