<?php
    $remove_defaults_widgets = array(
        'dashboard_incoming_links' => array(
            'page'    => 'dashboard',
            'context' => 'normal'
        ),
        'dashboard_right_now' => array(
            'page'    => 'dashboard',
            'context' => 'normal'
        ),
        'dashboard_recent_drafts' => array(
            'page'    => 'dashboard',
            'context' => 'side'
        ),
        'dashboard_quick_press' => array(
            'page'    => 'dashboard',
            'context' => 'side'
        ),
        'dashboard_plugins' => array(
            'page'    => 'dashboard',
            'context' => 'normal'
        ),
        'dashboard_primary' => array(
            'page'    => 'dashboard',
            'context' => 'side'
        ),
        'dashboard_secondary' => array(
            'page'    => 'dashboard',
            'context' => 'side'
        ),
        'dashboard_recent_comments' => array(
            'page'    => 'dashboard',
            'context' => 'normal'
        )
    );

    $custom_dashboard_widgets = array(
        'my-dashboard-widget' => array(
            'title' => 'Reuters Direct Logs',
            'callback' => 'dashboardWidgetContent'
        )
    );

    function dashboardWidgetContent() {
        $user = wp_get_current_user();
        $log = WP_PLUGIN_DIR."/reuters-direct/log.txt";
        $log_array = explode("\n", tailCustom($log));
        echo '<table style="display:block; border: 1px solid #e5e5e5; overflow:hidden;"><col width="40%"><col width="60%">';
        foreach ($log_array as &$value) {
            $log_split = explode('|', $value);
            echo '<tr style="font-size:12px;""><td style="color:#777; vertical-align: top;">'.$log_split[0].'</td><td>'.$log_split[1].'</td></tr>';

        }
        echo '</table>';
    }

    // FUNCTION TO READ LAST FEW LINES 
    function tailCustom($filepath, $lines = 20, $adaptive = true) {

        $f = @fopen($filepath, "rb");
        if ($f === false){
           return false; 
        } 

        if (!$adaptive){
           $buffer = 4096; 
        } 
        else{
            $buffer = ($lines < 2 ? 64 : ($lines < 20 ? 512 : 4096));
        } 

        fseek($f, -1, SEEK_END);
        if (fread($f, 1) != "\n"){ 
            $lines -= 1;
        }

        $output = '';
        $chunk = '';
        while (ftell($f) > 0 && $lines >= 0) {

            $seek = min(ftell($f), $buffer);
            fseek($f, -$seek, SEEK_CUR);
            $output = ($chunk = fread($f, $seek)) . $output ;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $lines -= substr_count($chunk, "\n");

        }

        while ($lines++ < 0) {
            $output = substr($output, strpos($output, "\n") + 1);
        }

        fclose($f);
        return trim($output);
    }
?>