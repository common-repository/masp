<label for="masp_api_key"><?= __('api_key', 'masp') ?>:</label>
<input type="text" id="masp_api_key" name="masp_api_key" value="<?= $apiKey ?>" />
<a href="http://<?= $this->service_domain ?>/register" target="_blank"><?= __("do_not_have_api_key", 'masp') ?></a>