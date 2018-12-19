<div class="wrapper-cell">    
    <div id='content' class="">   
        <div class="content-inner">
            <div class="wrap">
                <form method="post" action="options.php">
                    <?php                    
                    settings_fields('display_yelp_widget');
                    do_settings_sections('display_yelp_widget');
                    submit_button();
                    ?>
                </form>
            </div>           
        </div>
    </div>
</div>