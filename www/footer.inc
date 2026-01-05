
            <?php

            //If we're looking at a test result, include the inner test-result div
            if (!strcasecmp('Test Result', $tab) && (!isset($nosubheader) || !@$nosubheader)) {
                echo '</div></div>';
            }
            ?>





    <?php
    if (!isset($site_js_loaded) || !$site_js_loaded) {
        echo "<script src=\"{$GLOBALS['cdnPath']}/assets/js/vendor/jquery-1.7.1.js\"></script>\n";
        echo "<script src=\"{$GLOBALS['cdnPath']}/assets/js/site.js?v=" . VER_JS . "\"></script>\n";
        $hasJquery = true;
    }
    if (
        !array_key_exists('HTTP_CACHE_CONTROL', $_SERVER) &&    // skip manual browser refreshes
        is_file('./assets/js/experiment.js')
    ) {
        $headers = $_SERVER;
        preg_match_all(
            '%(?P<browser>Camino|Kindle(\ Fire\ Build)?|Firefox|Safari|MSIE|Chrome|IEMobile|Opera|Silk|Lynx|Version|Wget|curl|NintendoBrowser|PLAYSTATION|AppleWebKit\ \d+)(?:;?)(?:(?:[/ ])(?P<version>[0-9]+)|/(?:[A-Z]*))%x',
            $_SERVER['HTTP_USER_AGENT'],
            $result,
            PREG_PATTERN_ORDER
        );
        $browser = "{$result['browser'][0]} {$result['version'][0]}";
        echo "<script>\n";
        echo "wptJSVer = '" . VER_JS . "';\n";
        echo "wptBrowser = '$browser';\n";
        include('./assets/js/experiment.js');
        echo "</script>\n";
        //echo "<script src=\"{$GLOBALS['cdnPath']}/assets/js/experiment.js?v=" . VER_JS . "\"></script>\n";
    }

    ?>
    <?php
    if (file_exists('./settings/server/customfooter.inc.php')) {
        include('./settings/server/customfooter.inc.php');
    } elseif (file_exists('./settings/common/customfooter.inc.php')) {
        include('./settings/common/customfooter.inc.php');
    } elseif (file_exists('./settings/customfooter.inc.php')) {
        include('./settings/customfooter.inc.php');
    }
    ?>

    <div id="experiment-timing" style="color: #77A;"></div>


