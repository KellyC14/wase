<?php
/**
 * SAML 1.1 remote IdP metadata for simpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://rnd.feide.no/content/idp-remote-metadata-reference
 */
$metadata['https://shibidp.cit.cornell.edu/idp/shibboleth'] = array (
  'entityid' => 'https://shibidp.cit.cornell.edu/idp/shibboleth',
  'contacts' => 
  array (
  ),
  'metadata-set' => 'shib13-idp-remote',
  'SingleSignOnService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://shibidp.cit.cornell.edu/idp/profile/SAML2/POST/SSO',
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
      'Location' => 'https://shibidp.cit.cornell.edu/idp/profile/SAML2/POST-SimpleSign/SSO',
    ),
    2 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://shibidp.cit.cornell.edu/idp/profile/SAML2/Redirect/SSO',
    ),
    3 => 
    array (
      'Binding' => 'urn:mace:shibboleth:1.0:profiles:AuthnRequest',
      'Location' => 'https://shibidp.cit.cornell.edu/idp/profile/Shibboleth/SSO',
    ),
  ),
  'ArtifactResolutionService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://shibidp.cit.cornell.edu:8443/idp/profile/SAML2/SOAP/ArtifactResolution',
      'index' => 2,
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding',
      'Location' => 'https://shibidp.cit.cornell.edu:8443/idp/profile/SAML1/SOAP/ArtifactResolution',
      'index' => 1,
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
MIIDSDCCAjCgAwIBAgIVAOZ8NfBem6sHcI7F39sYmD/JG4YDMA0GCSqGSIb3DQEB BQUAMCIxIDAeBgNVBAMTF3NoaWJpZHAuY2l0LmNvcm5lbGwuZWR1MB4XDTA5MTEy MzE4NTI0NFoXDTI5MTEyMzE4NTI0NFowIjEgMB4GA1UEAxMXc2hpYmlkcC5jaXQu Y29ybmVsbC5lZHUwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQCTURo9 90uuODo/5ju3GZThcT67K3RXW69jwlBwfn3png75Dhyw9Xa50RFv0EbdfrojH1P1 9LyfCjubfsm9Z7FYkVWSVdPSvQ0BXx7zQxdTpE9137qj740tMJr7Wi+iWdkyBQS/ bCNhuLHeNQor6NXZoBgX8HvLy4sCUb/4v7vbp90HkmP3FzJRDevzgr6PVNqWwNqp tZ0vQHSF5D3iBNbxq3csfRGQQyVi729XuWMSqEjPhhkf1UjVcJ3/cG8tWbRKw+W+ OIm71k+99kOgg7IvygndzzaGDVhDFMyiGZ4njMzEJT67sEq0pMuuwLMlLE/86mSv uGwO2Qacb1ckzjodAgMBAAGjdTBzMFIGA1UdEQRLMEmCF3NoaWJpZHAuY2l0LmNv cm5lbGwuZWR1hi5odHRwczovL3NoaWJpZHAuY2l0LmNvcm5lbGwuZWR1L2lkcC9z aGliYm9sZXRoMB0GA1UdDgQWBBSQgitoP2/rJMDepS1sFgM35xw19zANBgkqhkiG 9w0BAQUFAAOCAQEAaFrLOGqMsbX1YlseO+SM3JKfgfjBBL5TP86qqiCuq9a1J6B7 Yv+XYLmZBy04EfV0L7HjYX5aGIWLDtz9YAis4g3xTPWe1/bjdltUq5seRuksJjyb prGI2oAv/ShPBOyrkadectHzvu5K6CL7AxNTWCSXswtfdsuxcKo65tO5TRO1hWlr 7Pq2F+Oj2hOvcwC0vOOjlYNe9yRE9DjJAzv4rrZUg71R3IEKNjfOF80LYPAFD2Sp p36uB6TmSYl1nBmS5LgWF4EpEuODPSmy4sIV6jl1otuyI/An2dOcNqcgu7tYEXLX C8N6DXggDWPtPRdpk96UW45huvXudpZenrcd7A==
',
    ),
  ),
  'scope' => 
  array (
    0 => 'cornell.edu',
  ),
  'UIInfo' => 
  array (
    'DisplayName' => 
    array (
      'en' => 'Cornell University',
    ),
    'Description' => 
    array (
    ),
    'InformationURL' => 
    array (
    ),
    'PrivacyStatementURL' => 
    array (
    ),
  ),
  'name' => 
  array (
    'en' => 'Cornell University',
  ),
);
$metadata['https://shibidp-test.cit.cornell.edu/idp/shibboleth'] = array (
  'entityid' => 'https://shibidp-test.cit.cornell.edu/idp/shibboleth',
  'metadata-set' => 'shib13-idp-remote',
  'SingleSignOnService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:mace:shibboleth:1.0:profiles:AuthnRequest',
      'Location' => 'https://shibidp-test.cit.cornell.edu/idp/profile/Shibboleth/SSO',
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://shibidp-test.cit.cornell.edu/idp/profile/SAML2/POST/SSO',
    ),
    2 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
      'Location' => 'https://shibidp-test.cit.cornell.edu/idp/profile/SAML2/POST-SimpleSign/SSO',
    ),
    3 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://shibidp-test.cit.cornell.edu/idp/profile/SAML2/Redirect/SSO',
    ),
  ),
  'ArtifactResolutionService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:1.0:bindings:SOAP-binding',
      'Location' => 'https://shibidp-test.cit.cornell.edu:8443/idp/profile/SAML1/SOAP/ArtifactResolution',
      'index' => 1,
    ),
    1 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://shibidp-test.cit.cornell.edu:8443/idp/profile/SAML2/SOAP/ArtifactResolution',
      'index' => 2,
    ),
  ),
  'certFingerprint' => 
  array (
    0 => 'e1e1dad33267c41a94842082c89028484f58edef',
  ),
  'certData' => 'MIIDXDCCAkSgAwIBAgIVAMKCR8IGXIOzO/yLt6e4sd7OMLgEMA0GCSqGSIb3DQEBBQUAMCcxJTAjBgNVBAMTHHNoaWJpZHAtdGVzdC5jaXQuY29ybmVsbC5lZHUwHhcNMTIwNjA3MTg0NjIyWhcNMzIwNjA3MTg0NjIyWjAnMSUwIwYDVQQDExxzaGliaWRwLXRlc3QuY2l0LmNvcm5lbGwuZWR1MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkhlf9EP399mqnBtGmPG9Vqu79Af2NZhhsT+LTMA1uhPZYv4RX/E4VD+Iqce/EUP1ndPkGEwBnhrRT2ZegDpCmgo+EcED8cAh9AbwFTTitmBjxvErtJnS0ZBfMCLDcgOV1zM6bT5fF9SAIm0ZVSaeyQbNDwVDdwsBQHjAdg5vLd5VeYH9MI6enzdgBtPNSrEt3qZtCWl7ev8YQlWF3vZ+EoyDrWPZSOWzgR31QBs7mz13ABSveIri68FgNth9ylgFS7VNUlAp6xx6BRnMgL1QzVMZ5F4PbSRDp3UBoS6PMHd+WFenJWPPh6ShMyrInrJ4QAPfKC77tJW+GUXl4T4DqQIDAQABo38wfTBcBgNVHREEVTBTghxzaGliaWRwLXRlc3QuY2l0LmNvcm5lbGwuZWR1hjNodHRwczovL3NoaWJpZHAtdGVzdC5jaXQuY29ybmVsbC5lZHUvaWRwL3NoaWJib2xldGgwHQYDVR0OBBYEFF9RADnmBsO50hD8T+MUFqIgWAOxMA0GCSqGSIb3DQEBBQUAA4IBAQBqYpfdK4XAYE56sYmq/vUKOSBcbO2Uy3R7oTGrDKxrZI7xC1jchaaTW6BXtg6wzTSn8Jo2M0gvQrWyxZgQDrXGaL2TaPf5WjOWt/SsuJ+IShofS6ZWLkPCnrR0Ag9PwU58szw2jjUE4eJyv/dLDzhDHJ0EGastgSzRh1r3v2w8BYz1RHvjwESPB2HTgV1iuHwaIjaJxN39XyS6ZQzBj6sZ6Lem1R39zXmEvtVfCk9qgSKnbYulrrkIBzxllB34TUTKFs+Nz1j/sg2gj6Q5u9uW6mSm66mqn2E53r2CNHPTzWGwom5Mi9Z/DtOb2L/5jjxhFvCKxnEbIWm7XIe8qtqo',
  'scopes' => 
  array (
    0 => 'cornell.edu',
  ),
);
$metadata['https://idp.princeton.edu/idp/shibboleth'] = array (
  'entityid' => 'https://idp.princeton.edu/idp/shibboleth',
  'description' => 
  array (
    'en' => 'Princeton University',
  ),
  'OrganizationName' => 
  array (
    'en' => 'Princeton University',
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
    'en' => 'http://www.princeton.edu/index.shtml',
  ),
  'OrganizationURL' => 
  array (
    'en' => 'http://www.princeton.edu/index.shtml',
  ),
  'contacts' => 
  array (
    0 => 
    array (
      'contactType' => 'administrative',
      'givenName' => 'Steven Niedzwiecki',
      'emailAddress' => 
      array (
        0 => 'steven@princeton.edu',
      ),
    ),
    1 => 
    array (
      'contactType' => 'technical',
      'givenName' => 'Security and Data Protection',
      'emailAddress' => 
      array (
        0 => 'sdp@princeton.edu',
      ),
    ),
  ),
  'metadata-set' => 'shib13-idp-remote',
  'SingleSignOnService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://idp.princeton.edu/idp/profile/SAML2/Redirect/SSO',
    ),
    1 => 
    array (
      'Binding' => 'urn:mace:shibboleth:1.0:profiles:AuthnRequest',
      'Location' => 'https://idp.princeton.edu/idp/profile/Shibboleth/SSO',
    ),
    2 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://idp.princeton.edu/idp/profile/SAML2/POST/SSO',
    ),
    3 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
      'Location' => 'https://idp.princeton.edu/idp/profile/SAML2/POST-SimpleSign/SSO',
    ),
  ),
  'ArtifactResolutionService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
      'Location' => 'https://idp.princeton.edu:8443/idp/profile/SAML2/SOAP/ArtifactResolution',
      'index' => 1,
    ),
  ),
  'keys' => 
  array (
    0 => 
    array (
      'encryption' => false,
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
  'EntityAttributes' => 
  array (
    'http://macedir.org/entity-category' => 
    array (
      0 => '
http://id.incommon.org/category/registered-by-incommon
',
    ),
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
    ),
  ),
);
$metadata['urn:mace:incommon:stonybrook.edu'] = array (
  'entityid' => 'urn:mace:incommon:stonybrook.edu',
  'entityDescriptor' => 'PG1kOkVudGl0eURlc2NyaXB0b3IgeG1sbnM6bWQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDptZXRhZGF0YSIgZW50aXR5SUQ9InVybjptYWNlOmluY29tbW9uOnN0b255YnJvb2suZWR1Ij48bWQ6RXh0ZW5zaW9ucz48bWRycGk6UmVnaXN0cmF0aW9uSW5mbyB4bWxuczptZHJwaT0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6bWV0YWRhdGE6cnBpIiByZWdpc3RyYXRpb25BdXRob3JpdHk9Imh0dHBzOi8vaW5jb21tb24ub3JnIi8+PG1kYXR0cjpFbnRpdHlBdHRyaWJ1dGVzIHhtbG5zOm1kYXR0cj0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6bWV0YWRhdGE6YXR0cmlidXRlIj48c2FtbDpBdHRyaWJ1dGUgeG1sbnM6c2FtbD0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmFzc2VydGlvbiIgTmFtZT0iaHR0cDovL21hY2VkaXIub3JnL2VudGl0eS1jYXRlZ29yeS1zdXBwb3J0IiBOYW1lRm9ybWF0PSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6YXR0cm5hbWUtZm9ybWF0OnVyaSI+PHNhbWw6QXR0cmlidXRlVmFsdWUgeG1sbnM6eHNpPSJodHRwOi8vd3d3LnczLm9yZy8yMDAxL1hNTFNjaGVtYS1pbnN0YW5jZSIgeG1sbnM6c2hpYm1kPSJ1cm46bWFjZTpzaGliYm9sZXRoOm1ldGFkYXRhOjEuMCIgeG1sbnM6ZHM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvMDkveG1sZHNpZyMiIHhtbG5zOm1kcnBpPSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDptZXRhZGF0YTpycGkiPmh0dHA6Ly9pZC5pbmNvbW1vbi5vcmcvY2F0ZWdvcnkvcmVzZWFyY2gtYW5kLXNjaG9sYXJzaGlwPC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PHNhbWw6QXR0cmlidXRlIHhtbG5zOnNhbWw9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphc3NlcnRpb24iIE5hbWU9Imh0dHA6Ly9tYWNlZGlyLm9yZy9lbnRpdHktY2F0ZWdvcnkiIE5hbWVGb3JtYXQ9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDphdHRybmFtZS1mb3JtYXQ6dXJpIj48c2FtbDpBdHRyaWJ1dGVWYWx1ZSB4bWxuczp4c2k9Imh0dHA6Ly93d3cudzMub3JnLzIwMDEvWE1MU2NoZW1hLWluc3RhbmNlIiB4bWxuczpzaGlibWQ9InVybjptYWNlOnNoaWJib2xldGg6bWV0YWRhdGE6MS4wIiB4bWxuczpkcz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC8wOS94bWxkc2lnIyIgeG1sbnM6bWRycGk9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOm1ldGFkYXRhOnJwaSI+aHR0cDovL2lkLmluY29tbW9uLm9yZy9jYXRlZ29yeS9yZWdpc3RlcmVkLWJ5LWluY29tbW9uPC9zYW1sOkF0dHJpYnV0ZVZhbHVlPjwvc2FtbDpBdHRyaWJ1dGU+PC9tZGF0dHI6RW50aXR5QXR0cmlidXRlcz48L21kOkV4dGVuc2lvbnM+PG1kOklEUFNTT0Rlc2NyaXB0b3IgcHJvdG9jb2xTdXBwb3J0RW51bWVyYXRpb249InVybjptYWNlOnNoaWJib2xldGg6MS4wIHVybjpvYXNpczpuYW1lczp0YzpTQU1MOjEuMTpwcm90b2NvbCB1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDoyLjA6cHJvdG9jb2wiIGVycm9yVVJMPSJodHRwczovL3Nzby5jYy5zdG9ueWJyb29rLmVkdS9pZHAvZXJyb3IuaHRtbCI+PG1kOkV4dGVuc2lvbnM+PHNoaWJtZDpTY29wZSB4bWxuczpzaGlibWQ9InVybjptYWNlOnNoaWJib2xldGg6bWV0YWRhdGE6MS4wIiByZWdleHA9ImZhbHNlIj5zdG9ueWJyb29rLmVkdTwvc2hpYm1kOlNjb3BlPjxtZHVpOlVJSW5mbyB4bWxuczptZHVpPSJ1cm46b2FzaXM6bmFtZXM6dGM6U0FNTDptZXRhZGF0YTp1aSI+PG1kdWk6RGlzcGxheU5hbWUgeG1sOmxhbmc9ImVuIj5TdG9ueSBCcm9vayBVbml2ZXJzaXR5PC9tZHVpOkRpc3BsYXlOYW1lPjwvbWR1aTpVSUluZm8+PC9tZDpFeHRlbnNpb25zPjxtZDpLZXlEZXNjcmlwdG9yIHVzZT0ic2lnbmluZyI+PGRzOktleUluZm8geG1sbnM6ZHM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvMDkveG1sZHNpZyMiPjxkczpYNTA5RGF0YT48ZHM6WDUwOUNlcnRpZmljYXRlPgpNSUlEUURDQ0FpaWdBd0lCQWdJVkFNNnpvMVRnL0NuaTBVMVppUzlxVWpId1RiMHFNQTBHQ1NxR1NJYjNEUUVCCkJRVUFNQ0F4SGpBY0JnTlZCQU1URlhOemJ5NWpZeTV6ZEc5dWVXSnliMjlyTG1Wa2RUQWVGdzB4TWpBek16QXkKTVRJNE1UQmFGdzB6TWpBek16QXlNVEk0TVRCYU1DQXhIakFjQmdOVkJBTVRGWE56Ynk1all5NXpkRzl1ZVdKeQpiMjlyTG1Wa2RUQ0NBU0l3RFFZSktvWklodmNOQVFFQkJRQURnZ0VQQURDQ0FRb0NnZ0VCQUlaSDFZQUw4TnNiClpQN3IxWkNmVDBpWEtOZVZNVW9lczRsb3RRR25hOGxmQWt3YkJBT0lpOHo2Q2sxcEh0VHdaQm5SZjBIYWxVMnIKK0lORmQvVTJNUU1VbDYzWXJkaGpYd2tNN0xlcHhNcWoxbkJSUlM3VzFxblM0QjlOMUd4OGg0UndhWVZsVzdZUgpFRlNaanVxVlR6M2FKcXI0SVk2T2p4SGxYS0NZeTlxMHgyUUdnSm03ejAvMEswSzF3MUx5bW9MOHNtRSs3WDRUCmZvRUtJWm9oTkh4VFBvTTh0TFUzWFpodU1RTDhUQXRiVUl6MTgrZ3lxMHVnOE5mNW1Ya0JQRFNJUTkyVldUS04KQ2JGdXUxbWVHMThPWEdOUXdEc2pqM0Q2V3pvVjgvaDd3c1ZoZjF0eUJYZko2R0Zma2hkSlBKWlRDZ0p6c0dFawp3Vm1TS053VHd6a0NBd0VBQWFOeE1HOHdUZ1lEVlIwUkJFY3dSWUlWYzNOdkxtTmpMbk4wYjI1NVluSnZiMnN1ClpXUjFoaXhvZEhSd2N6b3ZMM056Ynk1all5NXpkRzl1ZVdKeWIyOXJMbVZrZFM5cFpIQXZjMmhwWW1KdmJHVjAKYURBZEJnTlZIUTRFRmdRVW9IM3ljVjNvZnRmajNXRkEzM3h0cG9hUnlVWXdEUVlKS29aSWh2Y05BUUVGQlFBRApnZ0VCQUUydmZRYkdtWlduRk1XeWxZZUxxajdsdlg1UDFTZTlpOERCSmp5M3RkQ1RJSGRIVFNSUExubnJvRkViCkF1NTVjblhVM1NlSjRqekhqM2s0dE9YUVFmRStCR0VSNDdEdFB1SjVFeTJVZzMzRENyTW9QMHlqcHdwM3VUY3kKTlJTekpUNkZpa2N2SmJHeHpzd0E2Y2hHT0hXdEd3ZTRkcSs1T20wcThRUXNRTVg1bzNUVXJrTC85ZTRjU3lIVgpiZW9aZUxNaERmNE03d2Y5NzFxeDZ0VitxVlFxcVNkRGJRT1B4K0lLS1hHdUhDd0tYd2kxVjFLam1ZRnFuT202CnZqTEpxL1pZa25la3dJZ1hEWWRMOTlkNWt3cVY2Vzd2SG01VjdqMmZ2MG8rbU51NDZzTDlZK1RWWlBBbnl3OGIKUDVrSnBObDZTa3ZVT2paNG52cjlpOUZnbUhjPQogICAgICAgICAgPC9kczpYNTA5Q2VydGlmaWNhdGU+PC9kczpYNTA5RGF0YT48L2RzOktleUluZm8+PC9tZDpLZXlEZXNjcmlwdG9yPjxtZDpTaW5nbGVTaWduT25TZXJ2aWNlIEJpbmRpbmc9InVybjptYWNlOnNoaWJib2xldGg6MS4wOnByb2ZpbGVzOkF1dGhuUmVxdWVzdCIgTG9jYXRpb249Imh0dHBzOi8vc3NvLmNjLnN0b255YnJvb2suZWR1L2lkcC9wcm9maWxlL1NoaWJib2xldGgvU1NPIi8+PG1kOlNpbmdsZVNpZ25PblNlcnZpY2UgQmluZGluZz0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmJpbmRpbmdzOkhUVFAtUE9TVCIgTG9jYXRpb249Imh0dHBzOi8vc3NvLmNjLnN0b255YnJvb2suZWR1L2lkcC9wcm9maWxlL1NBTUwyL1BPU1QvU1NPIi8+PG1kOlNpbmdsZVNpZ25PblNlcnZpY2UgQmluZGluZz0idXJuOm9hc2lzOm5hbWVzOnRjOlNBTUw6Mi4wOmJpbmRpbmdzOkhUVFAtUE9TVC1TaW1wbGVTaWduIiBMb2NhdGlvbj0iaHR0cHM6Ly9zc28uY2Muc3Rvbnlicm9vay5lZHUvaWRwL3Byb2ZpbGUvU0FNTDIvUE9TVC1TaW1wbGVTaWduL1NTTyIvPjxtZDpTaW5nbGVTaWduT25TZXJ2aWNlIEJpbmRpbmc9InVybjpvYXNpczpuYW1lczp0YzpTQU1MOjIuMDpiaW5kaW5nczpIVFRQLVJlZGlyZWN0IiBMb2NhdGlvbj0iaHR0cHM6Ly9zc28uY2Muc3Rvbnlicm9vay5lZHUvaWRwL3Byb2ZpbGUvU0FNTDIvUmVkaXJlY3QvU1NPIi8+PC9tZDpJRFBTU09EZXNjcmlwdG9yPjxtZDpPcmdhbml6YXRpb24+PG1kOk9yZ2FuaXphdGlvbk5hbWUgeG1sOmxhbmc9ImVuIj5TdG9ueSBCcm9vayBVbml2ZXJzaXR5PC9tZDpPcmdhbml6YXRpb25OYW1lPjxtZDpPcmdhbml6YXRpb25EaXNwbGF5TmFtZSB4bWw6bGFuZz0iZW4iPlN0b255IEJyb29rIFVuaXZlcnNpdHk8L21kOk9yZ2FuaXphdGlvbkRpc3BsYXlOYW1lPjxtZDpPcmdhbml6YXRpb25VUkwgeG1sOmxhbmc9ImVuIj5odHRwOi8vd3d3LnN1bnlzYi5lZHUvPC9tZDpPcmdhbml6YXRpb25VUkw+PC9tZDpPcmdhbml6YXRpb24+PG1kOkNvbnRhY3RQZXJzb24gY29udGFjdFR5cGU9ImFkbWluaXN0cmF0aXZlIj48bWQ6R2l2ZW5OYW1lPlNhbmpheSBLYXB1cjwvbWQ6R2l2ZW5OYW1lPjxtZDpFbWFpbEFkZHJlc3M+U2FuamF5LkthcHVyQHN0b255YnJvb2suZWR1PC9tZDpFbWFpbEFkZHJlc3M+PC9tZDpDb250YWN0UGVyc29uPjxtZDpDb250YWN0UGVyc29uIGNvbnRhY3RUeXBlPSJ0ZWNobmljYWwiPjxtZDpHaXZlbk5hbWU+QnJpYW4gSGVsbGVyPC9tZDpHaXZlbk5hbWU+PG1kOkVtYWlsQWRkcmVzcz5Ccmlhbi5IZWxsZXJAc3Rvbnlicm9vay5lZHU8L21kOkVtYWlsQWRkcmVzcz48L21kOkNvbnRhY3RQZXJzb24+PC9tZDpFbnRpdHlEZXNjcmlwdG9yPg==',
  'description' =>
  array (
    'en' => 'Stony Brook University',
  ),
  'OrganizationName' =>
  array (
    'en' => 'Stony Brook University',
  ),
  'name' =>
  array (
    'en' => 'Stony Brook University',
  ),
  'OrganizationDisplayName' =>
  array (
    'en' => 'Stony Brook University',
  ),
  'url' =>
  array (
    'en' => 'http://www.sunysb.edu/',
  ),
  'OrganizationURL' =>
  array (
    'en' => 'http://www.sunysb.edu/',
  ),
  'contacts' =>
  array (
    0 =>
    array (
      'contactType' => 'administrative',
      'givenName' => 'Sanjay Kapur',
      'emailAddress' =>
      array (
        0 => 'Sanjay.Kapur@stonybrook.edu',
      ),
    ),
    1 =>
    array (
      'contactType' => 'technical',
      'givenName' => 'Brian Heller',
      'emailAddress' =>
      array (
        0 => 'Brian.Heller@stonybrook.edu',
      ),
    ),
  ),
  'metadata-set' => 'shib13-idp-remote',
'SingleSignOnService' =>
  array (
    0 =>
    array (
      'Binding' => 'urn:mace:shibboleth:1.0:profiles:AuthnRequest',
      'Location' => 'https://sso.cc.stonybrook.edu/idp/profile/Shibboleth/SSO',
    ),
    1 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
      'Location' => 'https://sso.cc.stonybrook.edu/idp/profile/SAML2/POST/SSO',
    ),
    2 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
      'Location' => 'https://sso.cc.stonybrook.edu/idp/profile/SAML2/POST-SimpleSign/SSO',
    ),
    3 =>
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://sso.cc.stonybrook.edu/idp/profile/SAML2/Redirect/SSO',
    ),
  ),
  'ArtifactResolutionService' =>
  array (
  ),
  'keys' =>
  array (
    0 =>
    array (
      'encryption' => false,
      'signing' => true,
      'type' => 'X509Certificate',
      'X509Certificate' => '
MIIDQDCCAiigAwIBAgIVAM6zo1Tg/Cni0U1ZiS9qUjHwTb0qMA0GCSqGSIb3DQEB
BQUAMCAxHjAcBgNVBAMTFXNzby5jYy5zdG9ueWJyb29rLmVkdTAeFw0xMjAzMzAy
MTI4MTBaFw0zMjAzMzAyMTI4MTBaMCAxHjAcBgNVBAMTFXNzby5jYy5zdG9ueWJy
b29rLmVkdTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAIZH1YAL8Nsb
ZP7r1ZCfT0iXKNeVMUoes4lotQGna8lfAkwbBAOIi8z6Ck1pHtTwZBnRf0HalU2r
+INFd/U2MQMUl63YrdhjXwkM7LepxMqj1nBRRS7W1qnS4B9N1Gx8h4RwaYVlW7YR
EFSZjuqVTz3aJqr4IY6OjxHlXKCYy9q0x2QGgJm7z0/0K0K1w1LymoL8smE+7X4T
foEKIZohNHxTPoM8tLU3XZhuMQL8TAtbUIz18+gyq0ug8Nf5mXkBPDSIQ92VWTKN
CbFuu1meG18OXGNQwDsjj3D6WzoV8/h7wsVhf1tyBXfJ6GFfkhdJPJZTCgJzsGEk
wVmSKNwTwzkCAwEAAaNxMG8wTgYDVR0RBEcwRYIVc3NvLmNjLnN0b255YnJvb2su
ZWR1hixodHRwczovL3Nzby5jYy5zdG9ueWJyb29rLmVkdS9pZHAvc2hpYmJvbGV0
aDAdBgNVHQ4EFgQUoH3ycV3oftfj3WFA33xtpoaRyUYwDQYJKoZIhvcNAQEFBQAD
ggEBAE2vfQbGmZWnFMWylYeLqj7lvX5P1Se9i8DBJjy3tdCTIHdHTSRPLnnroFEb
Au55cnXU3SeJ4jzHj3k4tOXQQfE+BGER47DtPuJ5Ey2Ug33DCrMoP0yjpwp3uTcy
NRSzJT6FikcvJbGxzswA6chGOHWtGwe4dq+5Om0q8QQsQMX5o3TUrkL/9e4cSyHV
beoZeLMhDf4M7wf971qx6tV+qVQqqSdDbQOPx+IKKXGuHCwKXwi1V1KjmYFqnOm6
vjLJq/ZYknekwIgXDYdL99d5kwqV6W7vHm5V7j2fv0o+mNu46sL9Y+TVZPAnyw8b
P5kJpNl6SkvUOjZ4nvr9i9FgmHc=
          ',
    ),
  ),
  'scope' =>
  array (
    0 => 'stonybrook.edu',
  ),
  'EntityAttributes' =>
  array (
    'http://macedir.org/entity-category-support' =>
    array (
      0 => 'http://id.incommon.org/category/research-and-scholarship',
    ),
    'http://macedir.org/entity-category' =>
    array (
      0 => 'http://id.incommon.org/category/registered-by-incommon',
    ),
  ),
  'UIInfo' =>
  array (
    'DisplayName' =>
    array (
      'en' => 'Stony Brook University',
    ),
    'Description' =>
    array (
    ),
    'InformationURL' =>
    array (
    ),
    'PrivacyStatementURL' =>
    array (
    ),
  ),
  'tags' =>
  array (
    0 => 'all',
    1 => 'incommon',
  ),
  'authproc' =>
  array (
    51 =>
    array (
      'class' => 'core:AttributeMap',
      0 => 'oid2name',
    ),
  ),
  'redirect.sign' => true,
  'metadata.sign.enable' => true,
  'metarefresh:src' => 'http://md.incommon.org/InCommon/InCommon-metadata.xml',
);
