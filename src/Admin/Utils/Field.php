<?php

namespace AppScenic\Admin\Utils;

use AppScenic\Config;

class Field {

    protected $args;

    public function __construct( $args ) {

        $this->args = $args;

        extract( $this->args );

        add_settings_field( $id, $title, [$this, 'load_template'], $page, $section, $args );

    }

    public function load_template() {

        extract( $this->args );

        include Config::get( 'plugin_path' ) . "views/admin/fields/$type.php";

    }

}
