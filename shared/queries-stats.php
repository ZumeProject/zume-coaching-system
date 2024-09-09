<?php

class Zume_Queries_Stats {
    public static function v5_ready_languages() {
        $languages = zume_languages();
        $five_ready = 0;
        foreach($languages as $language ) {
            if ( $language['enable_flags']['version_5_ready'] ) {
                $five_ready++;
            }
        }
        return $five_ready;
    }
}
