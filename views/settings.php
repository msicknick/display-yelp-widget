<div class="wrapper-cell">    
    <div id='content' class="">   
        <h3><?php _e('Settings', YELP_WIDGET_SLUG); ?></h3>
        <div class="content-inner">
            <div class="wrap">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('yelp_plugin_group');
                    do_settings_sections('yelp_plugin_group');
                    submit_button();
                    ?>
                </form>
            </div>           
        </div>
    </div>
</div>