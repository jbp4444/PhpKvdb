#!/bin/tcsh

set baseurl = 'http://localhost:5000/kvdblink.php'
#set baseurl = 'https://pormann.net/kv/kvdbadm.php'
#set baseurl = 'http://kvdb.azurewebsites.net/kvdbadm.php'

# see kvdb_basedata.sql for list of expected tokens
# user oona
set tk = a1b2c3d4e5f6
set rw_tk = abc123def456
# user mossy (superadmin)
set su_tk = xyzxyzxyzxyz

echo device registers a dev-code .. OK
curl -d o=reg -d c=abc123 $baseurl
echo
echo

echo another device registers the same dev-code .. FAIL
curl -d o=reg -d c=abc123 $baseurl
echo
echo

echo device polls for claim-status .. FAIL, not claimed
curl -d o=poll -d c=abc123 $baseurl
echo
echo

echo user claims the dev-code .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=claim -d c=abc123 $baseurl
echo
echo

echo device polls for claim-status .. OK, claimed, token returned
curl -d o=poll -d c=abc123 $baseurl
echo
echo

echo user or system unregisters the dev-code .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=unreg -d c=abc123 $baseurl
echo
echo
