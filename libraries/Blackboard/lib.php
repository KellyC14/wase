<?php
/*
 *  bb_ws-php - An example script accessing the Blackboard Learn 9 web services using PHP
 *  Copyright (C) 2013  Stephen P Vickers
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * Contact: stephen@spvsoftwareproducts.com
 *
 * Version history:
 *   1.0.0  10-Feb-13  Initial version
*/

// Load WSSESoap library
  require_once('soap-wsse.php');

// Global variables for SOAP username and password
  $username = 'session';
  $password = 'nosession';

// SOAP client class to inject username and password in requests
  class BbSoapClient extends SoapClient {

    function __doRequest($request, $location, $action, $version, $one_way = 0) {

      global $username, $password;

      $doc = new DOMDocument('1.0');
      $doc->loadXML($request);

      $objWSSE = new WSSESoap($doc);

      $objWSSE->addUserToken($username, $password);
      $objWSSE->addTimestamp();

      return parent::__doRequest($objWSSE->saveXML(), $location, $action, $version, $one_way);

    }

  }

?>