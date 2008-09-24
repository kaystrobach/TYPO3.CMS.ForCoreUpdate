This directory contains a modified version of the PHP OpenID library
(http://www.openidenabled.com/). We use only "Auth" directory from the library
and include also a copy of COPYING file to conform to the license requirements.

Current version of the library is 2.1.2.

The following modifications are made:
-	cURL calls is replaced with t3lib_div::getURL()

See also the patch to the library.
