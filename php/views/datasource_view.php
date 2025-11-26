
<?php include 'includes/header.php'; ?>
<style>
    input.readonly {
        background-color: #eee; /* system grigetto non modificabile */
        color: #666;
        border: 1px solid #ccc;
    }
</style>

<div class="content">
    <img src="img/logo.svg" alt="Logo" class="logo-img spin animate-on-load" />
    <h1 class="page-title"><?php echo _t("Datasource"); ?></h1>
    <div class="title-separator"></div>
    
    <form method="POST" id="source-conf-device-form" action="?page=datasource">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>"/>
        <table style="box-shadow: none;">
        <thead>
            <tr><th><?php echo _t("Proprietà"); ?></th><th><?php echo _t("Valore"); ?></th></tr>
        </thead>
        <tbody>
            <?php foreach ($config->datasource->{'set-property'} as $setProperty): ?>
            <?php 
                $property = (string)$setProperty['property']; 
                $value = (string)$setProperty['value'];
                $readonly = ($property === 'naming');
                $isPassword = ($property === 'password');
            ?>
            <tr>
                <td>
                    <label for="<?php echo htmlspecialchars($property); ?>">
                        <?php echo _t(htmlspecialchars($property)); ?>
                    </label>
                </td>
                <td class="form-row">
                    <?php if ($readonly): ?>
                        <input type="text" id="<?php echo htmlspecialchars($property); ?>" name="<?php echo htmlspecialchars($property); ?>" value="<?php echo htmlspecialchars($value); ?>" readonly class="readonly" />
                    <?php elseif ($isPassword): ?>
                        <input type="password" id="<?php echo htmlspecialchars($property); ?>" name="<?php echo htmlspecialchars($property); ?>" value="" placeholder="<?php echo _t("Inserisci nuova password"); ?>" />
                        <small><?php echo _t("Lascia vuoto per mantenere la password attuale."); ?></small>
                    <?php else: ?>
                        <input type="text" id="<?php echo htmlspecialchars($property); ?>" name="<?php echo htmlspecialchars($property); ?>" value="<?php echo htmlspecialchars($value); ?>" />
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>

        </tbody>
        </table>
        <div class="form-row">
            <button type="submit" class="confirm-submit icon-button tooltip-cell" style="background:none; border:none; cursor:pointer;">
                <span class="tooltip-target"><i data-feather="save"></i></span>
                <span class="tooltip-bubble"><?php echo _t("Salva modifiche"); ?></span>
            </button>
        </div>
    </form>
    <?php include 'includes/message.php'; ?>
    <button id="toTopBtn"><i data-feather="arrow-up-circle"></i></button>
</div>