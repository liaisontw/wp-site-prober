<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/liaisontw
 * @since      1.0.0
 *
 * @package    liaison_site_prober
 * @subpackage liaison_site_prober/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<html lang="zh-Hant">
<head>
<meta charset="utf-8" />
<h1><?php esc_html_e( 'Site Prober', 'liaison-site-prober' ); ?></h1>
<meta name="viewport" content="width=device-width,initial-scale=1" />
</head>
<body>
<div class="tabs">

  <!-- Tab labels -->
  <div class="tab-labels">
      <a class="<?php echo $active_tab==='log'?'active':''; ?>"
         href="<?php echo esc_html(admin_url('admin.php?page=wpsp_site_prober_log_list&tab=log')); ?>">
         Actions
      </a>

      <a class="<?php echo $active_tab==='custom'?'active':''; ?>"
         href="<?php echo esc_html(admin_url('admin.php?page=wpsp_site_prober_log_list&tab=custom')); ?>">
         Custom Logs
      </a>

      <a class="<?php echo $active_tab==='implicit'?'active':''; ?>"
         href="<?php echo esc_html(admin_url('admin.php?page=wpsp_site_prober_log_list&tab=implicit')); ?>">
         Implicit Table
      </a>
  </div>

  <!-- tab content -->
  <div class="tab-content">

      <div id="panel1"
           class="panel <?php echo $active_tab==='log'?'active':''; ?>">
          <?php $this->render_page_list_table(); ?>
      </div>

      <div id="panel2"
           class="panel <?php echo $active_tab==='custom'?'active':''; ?>">
          <?php $this->render_page_list_table_custom_log(); ?>
      </div>

      <div id="panel3"
            class="<?php echo $active_tab==='implicit'?'active':''; ?>">
      </div>
  </div>
</div>


</body>

</html>




