# bb-ws-php

These files are provided as-is to provide a simple illustration of one way in which the Blackboard Learn 9 SOAP web services can be accessed using PHP.

## System requirements

* PHP with SOAP library installed
* WS-Security library (from http://code.google.com/p/wse-php/, files included with this script)
* System admin access to Learn 9
* Context, Course and User web services should be both available and discoverable (see Administrator Panel > Web Services page); if not using SSL then also ensure the "SSL Required" option is not enabled

## Using the example script

1.  Extract the PHP files into a single directory.
2.  Edit the config.php file to add the URL of your Learn 9 server and the Proxy Tool Registration Password (see the Administrator Panel > Building Blocks > Proxy Tools > Proxy Tools Global Properties page)
3.  From a command line prompt in the directory where the files have been placed run the bb_ws.php file (e.g. "php bb_ws.php"); this will display a page showing the available options
4.  Register the tool in Learn 9: "php bb_ws.php register".
5.  Make the tool available in Learn 9 (see Administrator Panel > Building Blocks > Proxy Tools page).
6.  The other options should now work; for example:
```
       php bb_ws.php courses administrator (list of courses for a user)
       php bb_ws.php course _2_1 (course details by id, more than one id can be passed)
       php bb_ws.php course cid (course details by courseId, more than one courseId can be passed)
       php bb_ws.php user _1_1 (user details by id, more than one userId can be passed)
       php bb_ws.php user administrator (user details by username, more than one username can be passed)
       php bb_ws.php member _2_1 (membership details by courseId)
       php bb_ws.php member _2_1 _1_1 (membership details by courseId by userId, more than one userId can be passed)
```

## Version history:

* 1.0.00  10-Feb-13  Initial version
* 1.1.00  27-Apr-13
  * Added members option to retrieve course memberships
  * Allow course and user options to list multiple IDs

## Licence

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
