<?php

defined( 'ABSPATH' ) || exit;

use AppScenic\Config;

?>

<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>?page=<?php echo esc_attr( Config::get( 'plugin_slug' ) ); ?>" method="POST" target="_blank">

	<?php wp_nonce_field( 'appscenic_auth' ); ?>

	<button type="submit" class="appscenic-body-box appscenic-body-button">

		<?php esc_html_e( 'Connect this Store to AppScenic', 'appscenic' ); ?>

	</button>

</form>
