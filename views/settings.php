<div class="wrapper-cell">    
    <div id='content' class="">   
        <div class="content-inner">
            <div class="wrap">
                <form method="post" action="options.php">
                    <?php
//                    settings_fields("yelp_widget_general_section");                    
//                    do_settings_sections(YELP_WIDGET_SLUG . '-settings');                
//                    submit_button();  
                    
                    settings_fields('yelp_widget');
                    do_settings_sections('yelp_widget');
                    submit_button();
                    ?>
                </form>
            </div>           
        </div>
    </div>
</div>