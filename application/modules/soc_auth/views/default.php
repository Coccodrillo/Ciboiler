<p><? echo lang('strings.gender')?></p>

<p><?php echo $this->config->item('language'); ?></p>

<?php echo anchor($this->lang->switch_uri('en'),'Display current page in English')."<br />".anchor($this->lang->switch_uri('sl'),'Prikaži stran v Slovenščini')."<br />".anchor($this->lang->switch_uri('fr'),'Afficher la page en français'); ?>

<p><?php echo anchor($this->lang->lang()."/auth/logout", lang("strings.logout")); ?></p>
