<?php

defined( 'ABSPATH' ) || exit;

$options = get_option( $option_name );

?>

<fieldset>

    <legend class="screen-reader-text"><?php echo esc_html( $title ); ?></legend>

    <label for="<?php echo esc_attr( $id ); ?>">

        <input type="checkbox"
               id="<?php echo esc_attr( $id ); ?>"
               name="<?php echo esc_attr( $option_name . '[' . $id . ']' ); ?>"
               value="1"
		       <?php echo $options && isset( $options[$id] ) ? ' checked' : ''; ?>>

	    <?php echo esc_html( $description ); ?>

    </label>

</fieldset>
