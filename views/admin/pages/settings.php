<?php

defined( 'ABSPATH' ) || exit;

use AppScenic\Config;

$is_connected = $this->is_connected();

?>
<div class="appscenic">

    <div class="appscenic-header">

        <div class="appscenic-header-logo">

            <img src="<?php echo esc_url( Config::get( 'plugin_url' ) ); ?>assets/dist/svg/logo.svg" alt="AppScenic">

        </div>

        <nav class="appscenic-header-nav">

            <ul>
                <li>
                    <a href="#settings">
                        <?php esc_html_e( 'Settings', 'appscenic' ); ?>
                    </a>
                </li>
                <li>
                    <a href="https://helpdesk.appscenic.com/support/solutions" target="_blank">
                        <?php esc_html_e( 'Knowledge Base', 'appscenic' ); ?>
                    </a>
                </li>
                <li>
                    <a href="mailto:support@appscenic.com">
                        <?php esc_html_e( 'Contact Us', 'appscenic' ); ?>
                    </a>
                </li>
            </ul>

        </nav>

    </div>

    <div class="appscenic-body">

	    <?php do_action( 'appscenic_admin_notices' ); ?>

        <div class="appscenic-body-top">

            <?php

            if ( $is_connected ) {
	            include Config::get( 'plugin_path' ) . '/views/admin/parts/store-connected.php';
            } else {
	            include Config::get( 'plugin_path' ) . '/views/admin/parts/store-disconnected.php';
            }

            ?>

        </div>

        <div class="appscenic-body-bottom">

            <h2><?php esc_html_e( 'Getting Started', 'appscenic' ); ?></h2>

            <a href="https://www.youtube.com/watch?v=j3XCkstF4xU" target="_blank" id="play">

                <img src="<?php echo Config::get( 'plugin_url' ); ?>assets/img/thumb.png" alt="<?php esc_attr_e( 'Getting Started', 'appscenic' ); ?>">

            </a>

            <div class="appscenic-body-video" id="video">

                <div>

                    <iframe id="iframe" src="about:blank" data-src="https://www.youtube-nocookie.com/embed/j3XCkstF4xU" allow="autoplay; encrypted-media;" allowfullscreen></iframe>

                </div>

            </div>

        </div>

        <div class="appscenic-body-bottom" id="settings">

            <form action="<?php echo esc_url( admin_url() ); ?>options.php" method="post">

		        <?php

		        settings_fields( Config::get( 'plugin_slug' ) . '_options' );
		        do_settings_sections( Config::get( 'plugin_slug' ) );
		        submit_button();

		        ?>

            </form>

        </div>

    </div>

</div>
