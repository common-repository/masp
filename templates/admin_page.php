<div class="wrap">
    <?= screen_icon(); ?>
    <h2>MASP</h2>

    <?= $msg ?>

    <form action="" method="POST" id="masp_admin_options_form">
        <div style="margin: 20px 0;">
            <?php do_meta_boxes($this->options_page, 'normal', $this->options); ?>
        </div>

        <p class="submit">
            <input type="submit" name="submit" value="<?= __('save_changes', 'masp'); ?>" class="button-primary" maxlength="32" size="32" />
        </p>

        <?php wp_nonce_field('masp_options_update', 'masp_admin_nonce'); ?>
    </form>
</div>
