<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/liaisontw
 * @since      1.0.0
 *
 * @package    wp_site_prober
 * @subpackage wp_site_prober/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<html lang="zh-Hant">
<head>
<meta charset="utf-8" />
<h1><?php esc_html_e( 'Site Prober', 'wpsp-site-prober' ); ?></h1>
<meta name="viewport" content="width=device-width,initial-scale=1" />
<!-- <title>CSS-only Tabs (Radio)</title> -->
<style>
  :root{
    --accent:#0b79d0;
    --muted:#666;
  }
  body{font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; padding:20px;}
  .tabs{max-width:900px;margin:0 auto;border:1px solid #eee;border-radius:6px;overflow:hidden}
  /* hide inputs */
  .tabs input[type="radio"]{position:absolute;left:-9999px}
  /* tab labels row */
  .tab-labels{display:flex;background:#fafafa;border-bottom:1px solid #eee}
  .tab-labels label{
    padding:12px 18px;cursor:pointer;color:var(--muted);flex:0 0 auto;
    user-select:none;transition:color .15s, border-color .15s;
  }
  .tab-labels label:hover{color:#000}
  /* selected style via :checked adjacent sibling combinator */
  #tab1:checked ~ .tab-labels label[for="tab1"],
  #tab2:checked ~ .tab-labels label[for="tab2"],
  #tab3:checked ~ .tab-labels label[for="tab3"]{
    color:var(--accent);
    border-bottom:3px solid var(--accent);
    padding-bottom:9px; /* make space for bottom border */
  }

  /* content panels */
  .tab-content{padding:20px;background:#fff}
  .panel{display:none}
  #tab1:checked ~ .tab-content #panel1,
  #tab2:checked ~ .tab-content #panel2,
  #tab3:checked ~ .tab-content #panel3{
    display:block;
  }

  /* responsive: stack labels */
  @media (max-width:520px){
    .tab-labels{flex-direction:column}
    .tab-labels label{border-bottom:1px solid #f0f0f0}
  }
</style>
</head>
<body>

<div class="tabs">
  <!-- radios -->
  <input type="radio" id="tab1" name="tab" checked>
  <input type="radio" id="tab2" name="tab">
  <input type="radio" id="tab3" name="tab">

  <!-- labels -->
  <div class="tab-labels" role="tablist" aria-label="範例分頁">
    <label for="tab1" role="tab" tabindex="0">Actions</label>
    <label for="tab2" role="tab" tabindex="0">Custom Logs</label>
    <label for="tab3" role="tab" tabindex="0">Settings</label>
  </div>

  <!-- panel container -->
  <div class="tab-content">
    <div id="panel1" class="panel" role="tabpanel" aria-labelledby="tab1">
      <!-- <h2>Overview</h2>
      <p>This is the overview panel. Works without JavaScript.</p> -->
      <?php
        $this->render_page_list_table();
      ?>
    </div>

    <div id="panel2" class="panel" role="tabpanel" aria-labelledby="tab2">
      <h2>Details</h2>
      <p>Detail content here. Use radio buttons to switch panels.</p>
    </div>

    <div id="panel3" class="panel" role="tabpanel" aria-labelledby="tab3">
      <h2>Settings</h2>
      <p>Settings content. Responsive and accessible enough for many uses.</p>
    </div>
  </div>
</div>

</body>
</html>

