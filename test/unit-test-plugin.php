<?php

class PluginTest extends TestCase
{
    public function test_plugin_installed() {
        activate_plugin( 'zume-coaching/zume-coaching.php' );

        $this->assertContains(
            'zume-coaching/zume-coaching.php',
            get_option( 'active_plugins' )
        );
    }
}
