<?php
/**
 * SAML 2.0 remote IdP metadata for simpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://rnd.feide.no/content/idp-remote-metadata-reference
 */
/*
Princeton
*/
$metadata['https://idp.princeton.edu/idp/shibboleth'] = array (
  'entityid' => 'https://idp.princeton.edu/idp/shibboleth',
  'description' => 
  array (
    'en' => 'princeton.edu',
  ),
  'OrganizationName' => 
  array (
    'en' => 'princeton.edu',
  ),
  'name' => 
  array (
    'en' => 'Princeton University',
  ),
  'OrganizationDisplayName' => 
  array (
    'en' => 'Princeton University',
  ),
  'url' => 
  array (
    'en' => 'http://www.princeton.edu/',
  ),
  'OrganizationURL' => 
  array (
    'en' => 'http://www.princeton.edu/',
  ),
  'contacts' => 
  array (
    0 => 
    array (
      'contactType' => 'administrative',
      'givenName' => 'Identity and Access Management',
      'emailAddress' => 
      array (
        0 => 'mailto:iam@princeton.edu',
      ),
    ),
    1 => 
    array (
      'contactType' => 'support',
      'givenName' => 'OIT Support and Operations Center',
      'emailAddress' => 
      array (
        0 => 'mailto:helpdesk@princeton.edu',
      ),
    ),
    2 => 
    array (
      'contactType' => 'technical',
      'givenName' => 'Identity and Access Management',
      'emailAddress' => 
      array (
        0 => 'mailto:iam@princeton.edu',
      ),
    ),
    3 => 
    array (
      'contactType' => 'other',
      'givenName' => 'Information Security Office',
      'emailAddress' => 
      array (
        0 => 'mailto:infosec@princeton.edu',
      ),
    ),
  ),
  'metadata-set' => 'shib13-idp-remote',
  'SingleSignOnService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:mace:shibboleth:1.0:profiles:AuthnRequest',
      'Location' => 'https://idp.princeton.edu/idp/profile/Shibboleth/SSO',
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://idp.princeton.edu/idp/profile/SAML2/POST/SSO',
    ),
    2 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
      'Location' => 'https://idp.princeton.edu/idp/profile/SAML2/POST-SimpleSign/SSO',
    ),
    3 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idp.princeton.edu/idp/profile/SAML2/Redirect/SSO',
    ),
  ),
  'ArtifactResolutionService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding',
      'Location' => 'https://idp.princeton.edu:8443/idp/profile/SAML1/SOAP/ArtifactResolution',
      'index' => 1,
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://idp.princeton.edu:8443/idp/profile/SAML2/SOAP/ArtifactResolution',
      'index' => 2,
    ),
  ),
  'keys' => 
  array (
    0 => 
    array (
      'encryption' => true,
      'signing' => true,
      'type' => 'X509Certificate',
      'X509Certificate' => '
MIIDLzCCAhegAwIBAgIUMfmaP7flCY4+d5Gnju4bntgM57wwDQYJKoZIhvcNAQEF BQAwHDEaMBgGA1UEAxMRaWRwLnByaW5jZXRvbi5lZHUwHhcNMDkxMDMwMTI1MTM5 WhcNMjkxMDMwMTI1MTM5WjAcMRowGAYDVQQDExFpZHAucHJpbmNldG9uLmVkdTCC ASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAJE0moHwKJGyg9z94dvN0HCB klHGPvRdIB0nvzAJxo9KaF81zFaqHpvprwNEoB3Pfy18hIbtnDSv/sV/j6pnKnBw XXsf7QJOOF81klkGHZh4C9VnjUL5ok9Ahx1zPgaLcCgvZeGRG7DiRPnOgxVzuwoB WFnEWBCoLaqcZUl2njnawRB+LXt8mO+HPhsMO8c7ASJ50hF/l9cGaCs3ucEcwp9d FoxSiVy2TMyatszHTHZknaqVyqR+WNCxE/Jpcwfi1oq6k3V5T372GE8WTKclgvpg IYV8ISROBHpVlYz9v3N0nnpOn+Io6zuUOS3YNmuX52vaSciaYNoPcmBxYMEG2jcC AwEAAaNpMGcwRgYDVR0RBD8wPYIRaWRwLnByaW5jZXRvbi5lZHWGKGh0dHBzOi8v aWRwLnByaW5jZXRvbi5lZHUvaWRwL3NoaWJib2xldGgwHQYDVR0OBBYEFPQsLxgr W14zmCfogqfOscaIPOtoMA0GCSqGSIb3DQEBBQUAA4IBAQAqkhYwrjjwi31OYkDK jeKyss835BrdLVTqqEfssT3lvYW/SGyRMLCr2hS21p9zbt8dJO67C9RYEjJ/05p2 Keo+ZQj3ehOP80/phxk0r+Je/fNdpO/HbQG9/DfcYp5sLUXk9koYrXrOHq6KnkVh rmikDRb9izfU9nDttB8hWGLiX0WhIvk9xkIAW5ueyL5QxcQmRYNcaT3BUpjkGiBu FEsLXa42F1nmdBDGrI2woHNEr2diujL5EOxqIsunquUXuu2dFuNtqA0HRK6wj32b fsZT9KwTEdLv/oAbgQ8zlVXErx54GJFg1ksGAJY3lsGY+XdvcUaKuVD2IWOrbCtP qZfT
',
    ),
  ),
  'scope' => 
  array (
    0 => 'princeton.edu',
  ),
  'UIInfo' => 
  array (
    'DisplayName' => 
    array (
      'en' => 'Princeton University',
    ),
    'Description' => 
    array (
    ),
    'InformationURL' => 
    array (
    ),
    'PrivacyStatementURL' => 
    array (
      'en' => 'http://www.princeton.edu/pub/rrr/',
    ),
    'Logo' => 
    array (
      0 => 
      array (
        'url' => 'https://www.princeton.edu/favicon.ico',
        'height' => 49,
        'width' => 49,
        'lang' => 'en',
      ),
    ),
  ),
);
/* 
Farmingda;e
*/
$metadata['http://auth.farmingdale.edu/adfs/services/trust'] = array (
  'entityid' => 'http://auth.farmingdale.edu/adfs/services/trust',
  'description' => 
  array (
    'en-US' => 'Farmingdale State College',
  ),
  'OrganizationName' => 
  array (
    'en-US' => 'Farmingdale State College',
  ),
  'name' => 
  array (
    'en-US' => 'Farmingdale State College',
  ),
  'OrganizationDisplayName' => 
  array (
    'en-US' => 'Farmingdale State College',
  ),
  'url' => 
  array (
    'en-US' => 'http://farmingdale.edu/',
  ),
  'OrganizationURL' => 
  array (
    'en-US' => 'http://farmingdale.edu/',
  ),
  'contacts' => 
  array (
    0 => 
    array (
      'contactType' => 'support',
      'givenName' => 'Farmingdale',
      'surName' => 'Helpdesk',
      'emailAddress' => 
      array (
        0 => 'SSO.Help@farmingdale.edu',
      ),
      'telephoneNumber' => 
      array (
        0 => '631-420-2754',
      ),
    ),
  ),
  'metadata-set' => 'saml20-idp-remote',
  'SingleSignOnService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://auth.farmingdale.edu/adfs/ls/',
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://auth.farmingdale.edu/adfs/ls/',
    ),
  ),
  'SingleLogoutService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://auth.farmingdale.edu/adfs/ls/',
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://auth.farmingdale.edu/adfs/ls/',
    ),
  ),
  'ArtifactResolutionService' => 
  array (
  ),
  'NameIDFormats' => 
  array (
    0 => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
    1 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
    2 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
  ),
  'keys' => 
  array (
    0 => 
    array (
      'encryption' => true,
      'signing' => false,
      'type' => 'X509Certificate',
      'X509Certificate' => 'MIIGoDCCBYigAwIBAgIQBMb5ZvZ0LWjHNosg0lhkITANBgkqhkiG9w0BAQsFADBEMQswCQYDVQQGEwJVUzEWMBQGA1UEChMNR2VvVHJ1c3QgSW5jLjEdMBsGA1UEAxMUR2VvVHJ1c3QgU1NMIENBIC0gRzMwHhcNMTcwMjE3MDAwMDAwWhcNMTkwMzE4MjM1OTU5WjCBmjELMAkGA1UEBhMCVVMxETAPBgNVBAgMCE5ldyBZb3JrMRQwEgYDVQQHDAtGYXJtaW5nZGFsZTElMCMGA1UECgwcU3RhdGUgVW5pdmVyc2l0eSBvZiBOZXcgWW9yazEfMB0GA1UECwwWSW5mb3JtYXRpb24gVGVjaG5vbG9neTEaMBgGA1UEAwwRKi5mYXJtaW5nZGFsZS5lZHUwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCmVeVByQMrbmq6nrajtAzBRbcESI3X5s1FrFRSjuRh/ZQjdOIp1DWvhyhrQG5ZC7h21qmlPkyIAUx5YxrK8orfStruLjYIAyB1HAVt+DnRXczYYMDCdcbre+O8r9eJkfxarhP+4vOHQNHYJHEoAvyqr+5p4ctG6WwXL9FFLCWvA921n3+rjqtpy4oYLdQY5jbw7zUsWCMJtc1/0MXgYxDf4VUlfhhQ4El1/Ni3veYIxbYCqRyjNPKzVdWl5kmxUL2yGwEPOHec0yFs+0nvfEtNsTk7p2+89ScCQkfx64t53a2DYIdOQqx9DP6tt/ABED0xc+ZFVeP3CBvuseDfFXMzAgMBAAGjggM1MIIDMTAtBgNVHREEJjAkghEqLmZhcm1pbmdkYWxlLmVkdYIPZmFybWluZ2RhbGUuZWR1MAkGA1UdEwQCMAAwDgYDVR0PAQH/BAQDAgWgMCsGA1UdHwQkMCIwIKAeoByGGmh0dHA6Ly9nbi5zeW1jYi5jb20vZ24uY3JsMIGdBgNVHSAEgZUwgZIwgY8GBmeBDAECAjCBhDA/BggrBgEFBQcCARYzaHR0cHM6Ly93d3cuZ2VvdHJ1c3QuY29tL3Jlc291cmNlcy9yZXBvc2l0b3J5L2xlZ2FsMEEGCCsGAQUFBwICMDUMM2h0dHBzOi8vd3d3Lmdlb3RydXN0LmNvbS9yZXNvdXJjZXMvcmVwb3NpdG9yeS9sZWdhbDAdBgNVHSUEFjAUBggrBgEFBQcDAQYIKwYBBQUHAwIwHwYDVR0jBBgwFoAU0m/3lvSFP3I8MH0j2oV4m6N8WnwwVwYIKwYBBQUHAQEESzBJMB8GCCsGAQUFBzABhhNodHRwOi8vZ24uc3ltY2QuY29tMCYGCCsGAQUFBzAChhpodHRwOi8vZ24uc3ltY2IuY29tL2duLmNydDCCAX0GCisGAQQB1nkCBAIEggFtBIIBaQFnAHYA3esdK3oNT6Ygi4GtgWhwfi6OnQHVXIiNPRHEzbbsvswAAAFaTbIcVAAABAMARzBFAiEAvvBdyHk8RczOZYznYb+zJpuOfeiWLzQYkoh37TwD0TgCIF/kSvk3Vesit030Oc3rprOyoAIDEvnlpfsgVqBN1yMlAHYApLkJkLQYWBSHuxOizGdwCjw1mAT5G9+443fNDsgN3BAAAAFaTbIdtgAABAMARzBFAiEAknG8ZWoJVUBHRLVcu+Qcxka4Bjcx9euhDTJDA+XJj2wCIAUILiU7XDsIrvioCYkwn+JSrp66HHk0BKAQ73eV0tiYAHUA7ku9t3XOYLrhQmkfq+GeZqMPfl+wctiDAMR7iXqo/csAAAFaTbIcogAABAMARjBEAiAaHz1YWS7xQR4ksT96cXSQEtPL6zoiqFaDIZG+N2PpHwIgZjFGT+1Zbw11MwrUIUZDxe8abhFOoLyPo54IynTak2UwDQYJKoZIhvcNAQELBQADggEBAFOPtjFrVQsgEAKnVB0N+o14hLih1Cs3MMy2prJPSjMvK9Ywl8rlJh5oO8K/xf20Y0t9RMDkpmbnF1j1fdUXgXLF2cJYGOQ7sD3+mm9ZyIuPjKMeTkWBJ1xA/vW+rz2OA+WCC08WUd5Z9hzQOP0PCyXqKXzvYCV6mYHob76B/xfZXUdIU6T/Ia4I/jiaofqw+5YyUFc+8JLiv/vzgyL3JFDJfCT9/M8HFJ5P3cvx3E3iSLJvxhpyIL9dne/rmX/sQhTTPncoW9g8P204ObxFt0/KzcmeK0E3XJbc3Ft8kJIbRRnYy+WNJV848MyqPnD/au+g/ekxEd8R8pY5hUrwkzw=',
    ),
    1 => 
    array (
      'encryption' => false,
      'signing' => true,
      'type' => 'X509Certificate',
      'X509Certificate' => 'MIIE6TCCA9GgAwIBAgIBIDANBgkqhkiG9w0BAQsFADBmMQswCQYDVQQGEwJVUzEWMBQGA1UEChMNR2VvVHJ1c3QgSW5jLjEdMBsGA1UECxMURG9tYWluIFZhbGlkYXRlZCBTU0wxIDAeBgNVBAMTF0dlb1RydXN0IERWIFNTTCBDQSAtIEc0MB4XDTE0MDkxNDIwMTIzNVoXDTE4MTExNTA0MTkzN1owgaAxEzARBgNVBAsTCkdUOTg2ODcwNjcxMTAvBgNVBAsTKFNlZSB3d3cuZ2VvdHJ1c3QuY29tL3Jlc291cmNlcy9jcHMgKGMpMTQxNzA1BgNVBAsTLkRvbWFpbiBDb250cm9sIFZhbGlkYXRlZCAtIFF1aWNrU1NMKFIpIFByZW1pdW0xHTAbBgNVBAMTFGF1dGguZmFybWluZ2RhbGUuZWR1MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtVpZ5wlqjNEzAwGbLo0OzMzgUux/TtrNUG1xasAkWGZM0j6v5HFA+d2DDQqC4uj+bdUqiwZSzbVmFw/l8NgODfmjV0U3TtRGzN9P7IXVsY+yn2JZiTyxgork4mzHe+204B4+/M8QcCaE/CP0Z9P5OepYAl7lI+MAjgkYWQRkSt4BUPsyfLZ4A92/+EJQq1b8bBaTdnT8VJJs57tzF9NTB3Oazbid9m8V2qB0KK1U12ZaFOUjsAGoqq+yEka+um8jej8WsxnwM5OYGyHbhqAOt0YN36DUDiPJHO/WtqhTooOBV3QYFGuNkiMiEsCmIcHm2pOlyj1GKtY65q2JSGySFQIDAQABo4IBZTCCAWEwHwYDVR0jBBgwFoAUC1Dsd+8qm//sA6EK/63G5CoYxz4wVwYIKwYBBQUHAQEESzBJMB8GCCsGAQUFBzABhhNodHRwOi8vZ3Uuc3ltY2QuY29tMCYGCCsGAQUFBzAChhpodHRwOi8vZ3Uuc3ltY2IuY29tL2d1LmNydDAOBgNVHQ8BAf8EBAMCBaAwHQYDVR0lBBYwFAYIKwYBBQUHAwEGCCsGAQUFBwMCMB8GA1UdEQQYMBaCFGF1dGguZmFybWluZ2RhbGUuZWR1MCsGA1UdHwQkMCIwIKAeoByGGmh0dHA6Ly9ndS5zeW1jYi5jb20vZ3UuY3JsMAwGA1UdEwEB/wQCMAAwWgYDVR0gBFMwUTBPBgpghkgBhvhFAQc2MEEwPwYIKwYBBQUHAgEWM2h0dHBzOi8vd3d3Lmdlb3RydXN0LmNvbS9yZXNvdXJjZXMvcmVwb3NpdG9yeS9sZWdhbDANBgkqhkiG9w0BAQsFAAOCAQEAkAVwe+971LiYRQSoNMi3e8jB7xJsbyFHttiLabbg9IU1yhLxPqEKjbkma065MAYfBxveYzX3SthSj6znzlyD+G1FuYL4uf8MqMnDcyC2Pv2eKKrNV80fFeXubeXSNEEBnY6Aw5SDfPGxecyoChnStg25elglA8l79Qm5UWPnSl/Gw9TODP0vohMOyhkgGaWkdSG8DqZAbptpJrgabnjqr4aUW+JeE3dIZ/QxTplpvb6yzfkjBZEb10j/Dz60nxugMtaw4VKKLPDYsu5h3tVD+ZKfdfq0o+IoXiKNa8PK47vStQBYmc/kI5OQR30HsrJO/IsWzH0UAccZMFaqP/DoRg==',
    ),
    2 => 
    array (
      'encryption' => false,
      'signing' => true,
      'type' => 'X509Certificate',
      'X509Certificate' => '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',
    ),
  ),
);
