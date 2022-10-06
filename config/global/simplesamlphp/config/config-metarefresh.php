<?php

$config = array(

    /*
     * Global blacklist: entityIDs that should be excluded from ALL sets.
     */
    #'blacklist' = array(
    #	'http://my.own.uni/idp'
    #),

    /*
     * Conditional GET requests
     * Efficient downloading so polling can be done more frequently.
     * Works for sources that send 'Last-Modified' or 'Etag' headers.
     * Note that the 'data' directory needs to be writable for this to work.
     */
    #'conditionalGET'	=> TRUE,

    'sets' => array(

        'incommon' => array(
            'cron' => array('frequent', 'daily', 'hourly'),
            'sources' => array(
                array(
                    // See: https://spaces.internet2.edu/display/InCCollaborate/Phase+1+Implementation+Plan
                    // Changed by visser@terena.org on 19 Dec 2013
                    // Changed by Serge Goldstein, May 14, 2019
                    // 'src'   => 'http://md.incommon.org/InCommon/InCommon-metadata.xml',
                    'src' => 'http://md.incommon.org/InCommon/InCommon-metadata-idp-only.xml',
                    // See: https://spaces.internet2.edu/display/InCFederation/Metadata+Signing+Certificate
                    'validateFingerprint' => '7D:B4:BB:28:D3:D5:C8:52:E0:80:B3:62:43:2A:AF:34:B2:A6:0E:DD',
                    'template' => array(
                        'tags' => array('all', 'incommon'),
                        'authproc' => array(
                            51 => array('class' => 'core:AttributeMap', 'oid2name'),
                        ),
                        'redirect.sign' => TRUE,
                        'metadata.sign.enable' => TRUE,
                    ),
                    // We already have a ProtectNetwork entry in the guest options
                    'blacklist' => array(
                        'urn:mace:incommon:idp.protectnetwork.org',
                    ),
                ),
            ),
            'outputDir' => '/usr/local/www/wase/config/global/simplesamlphp/metadata/metarefresh/incommon/',
            'outputFormat' => 'flatfile',
        ),
    ),
);



