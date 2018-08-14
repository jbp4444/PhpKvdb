#!/bin/tcsh

set baseurl = 'http://localhost:5000/kvdbadm.php'
#set baseurl = 'https://pormann.net/kv/kvdbadm.php'
#set baseurl = 'http://kvdb.azurewebsites.net/kvdbadm.php'

# see kvdb_basedata.sql for list of expected tokens
# user oona .. admin and r/w tokens
set ad_tk = a1b2c3d4e5f6
set rw_tk = abc123def456
# user mossy (superadmin)
set su_tk = xyzxyzxyzxyz

echo creating new readwrite and readonly tokens .. OK
curl -H "KV-AUTH-TOKEN: $ad_tk" -d o=tset -d p=readwrite $baseurl
curl -H "KV-AUTH-TOKEN: $ad_tk" -d o=tset -d p=readonly $baseurl
echo
echo

echo creating new readwrite tokens with known tid .. OK
curl -H "KV-AUTH-TOKEN: $ad_tk" -d o=tset -d p=readwrite -d t=yaddayadda $baseurl
echo
echo

echo delete token .. OK
curl -H "KV-AUTH-TOKEN: $ad_tk" -d o=tdel -d t=yaddayadda $baseurl
echo
echo

echo create new token with non-admin .. FAIL
curl -H "KV-AUTH-TOKEN: $rw_tk" -d o=tset -d p=readonly $baseurl
echo
echo

echo list all tokens .. OK
curl -H "KV-AUTH-TOKEN: $ad_tk" -d o=show $baseurl
echo
echo

echo using superadmin to create a token for another user .. OK
curl -H "KV-AUTH-TOKEN: $su_tk" -d o=tset -d u=oona -d p=readwrite $baseurl
echo
echo

echo using superadmin to list all tokens for another user .. OK
curl -H "KV-AUTH-TOKEN: $su_tk" -d o=show -d u=oona $baseurl
echo
echo
