<?php

namespace AppScenic\Admin\Utils;

use AppScenic\Config;

class Section {

    private $template;

    public function __construct( $args ) {

        extract( $args );

        $this->template = $template;

        add_settings_section( $id, $title, [$this, 'load_template'], $page, $args );

    }

    public function load_template() {

        include Config::get( 'plugin_path' ) . "views/admin/sections/$this->template.php";

    }

}
