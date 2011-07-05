<?php

/**
 * @license Code licensed under the GNU General Public License v2.0
 * @author 
 * @copyright (C) Alex Kozeka 2011
 */
class MobileBrowserDetectType extends eZWorkflowEventType {

    const WORKFLOW_TYPE_STRING = 'mobilebrowserdetect';

    function __construct() {
        parent::__construct( 
            self::WORKFLOW_TYPE_STRING,
            'Mobile browser detect'
        );
    }

    function execute( $process, $event ) {
        $siteIni = eZINI::instance();

        $siteMobileUrl = $siteIni->variable( 'SiteSettings', 'SiteMobileURL' );
        if ( $siteMobileUrl === false ) {
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        $httpTool = eZHttpTool::instance();
        if ( $httpTool->hasGetVariable( 'full_view_on_mobile' ) ) {
            $cookieTimeoutInDays = (int) $siteIni->variable(
                'SiteSettings',
                'FullViewOnMobileCookieTimeout'
            );
            if ( $cookieTimeoutInDays == 0 ) {
                $cookieTimeoutInDays = 365;
            }
            setcookie(
                'full_view_on_mobile',
                1,
                time() + 86400 * $cookieTimeoutInDays,
                '/'
            );

            $process->RedirectUrl = '/';
            return eZWorkflowType::STATUS_REDIRECT;
        }

        if ( isset( $_COOKIE['full_view_on_mobile'] ) ) {
            return eZWorkflowType::STATUS_ACCEPTED;
        }

        if ( $this->isBrowserMobile() ) {
            $process->RedirectUrl = $siteMobileUrl;
            return eZWorkflowType::STATUS_REDIRECT;
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }

    function isBrowserMobile() {
        $ini = eZINI::instance( 'mobilebrowserdetect.ini' );

        $httpUserAgent = $_SERVER['HTTP_USER_AGENT'];
        $httpAccept = $_SERVER['HTTP_ACCEPT'];

        $httpUserAgentRegexps = $ini->variable( 'SiteSettings', 'MobileBrowserUserAgentRegexps' );
        foreach ( $httpUserAgentRegexps as $regexp ) {
            if ( preg_match( $regexp, $httpUserAgent ) ) {
                return true;
            }
        }

        if ( 
            ( strpos( $httpAccept, 'text/vnd.wap.wml' ) > 0 ) ||
            ( strpos( $httpAccept, 'application/vnd.wap.xhtml+xml' ) > 0 ) ||
            isset( $_SERVER['HTTP_X_WAP_PROFILE'] ) ||
            isset( $_SERVER['HTTP_PROFILE'] )
        ) {
            return true;
        }

        $httpUserAgentCodes = explode(
            '|',
            $ini->variable( 'SiteSettings', 'MobileBrowserUserAgentCodes' )
        );
        if ( in_array( strtolower( substr( $httpUserAgent, 0, 4 ) ), $httpUserAgentCodes ) ) {
            return true;
        }

        return false;
    }

}

eZWorkflowEventType::registerEventType(
    MobileBrowserDetectType::WORKFLOW_TYPE_STRING,
    'MobileBrowserDetectType'
);

?>