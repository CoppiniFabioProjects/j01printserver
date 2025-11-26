
<?php include 'includes/header.php'; ?>
<div class="content">
  <img src="img/logo.svg" alt="Logo" class="logo-img spin animate-on-load" />
  <h1 class="page-title"><?php echo _t("Modifica System"); ?></h1>
  <div class="title-separator"></div>
  <form id="source-conf-device-form" method="POST" action="?page=system">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>" />
    <table style="box-shadow: none;">
      <thead>
          <tr><th><?php echo _t("Proprietà"); ?></th><th><?php echo _t("Valore"); ?></th></tr>
        </thead>
        <tbody>
          <tr>
            <td><label for="host"><?php echo _t("Host"); ?>:</label></td>
            <td><input type="text" id="host" name="host" value="<?php echo htmlspecialchars($systemConfig->host ?? ''); ?>" /></td>
          </tr>
          <tr>
            <td><label for="username"><?php echo _t("Username"); ?>:</label></td>
            <td><input type="text" id="username" name="username" value="<?php echo htmlspecialchars($systemConfig->username ?? ''); ?>" /></td>
          </tr>
          <tr>
            <td><label for="password"><?php echo _t("Password"); ?>:</label></td>
            <td><input type="password" id="password" name="password" placeholder="<?php echo _t("Lascia vuoto per non cambiare"); ?>" /></td>
          </tr>
        </tbody>
    </table>
    <div class="form-row">
      <button type="submit" class="confirm-submit icon-button tooltip-cell" style="background:none; border:none; cursor:pointer;">
        <span class="tooltip-target"><i data-feather="save"></i></span>
        <span class="tooltip-bubble"><?php echo _t("Salva"); ?></span>
      </button>
    </div> 
  </form>
  <?php include 'includes/message.php'; ?>
  <button id="toTopBtn"><i data-feather="arrow-up-circle"></i></button>
</div>
