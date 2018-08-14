#!/bin/tcsh

set baseurl = 'http://localhost:5000/kvdb.php'
#set baseurl = 'https://pormann.net/kv/kvdb.php'
#set baseurl = 'http://kvdb.azurewebsites.net/kvdb.php'

# see kvdb_basedata.sql for list of expected tokens

# bucket oona
set rw_tk1 = abc123def456
set ro_tk1 = abc123ghi789
set ad_tk1 = a1b2c3d4e5f6
# bucket baba
set rw_tk2 = xabc123def456
set ro_tk2 = xabc123ghi789

echo set up some data
curl -H "KV-AUTH-TOKEN: $rw_tk1" -d o=set -d k=foo -d "v=foofoo-oona" $baseurl
curl -H "KV-AUTH-TOKEN: $rw_tk1" -d o=set -d k=bar -d "v=barbar-oona" $baseurl
curl -H "KV-AUTH-TOKEN: $rw_tk2" -d o=set -d k=foo -d "v=foofoo-baba" $baseurl
curl -H "KV-AUTH-TOKEN: $rw_tk2" -d o=set -d k=bar -d "v=barbar-baba" $baseurl
echo
echo

echo write data with readonly token .. FAIL
curl -H "KV-AUTH-TOKEN: $ro_tk1" -d o=set -d k=foo -d "v=fail-oona" $baseurl
curl -H "KV-AUTH-TOKEN: $ro_tk1" -d o=get -d k=foo $baseurl
curl -H "KV-AUTH-TOKEN: $ro_tk2" -d o=set -d k=foo -d "v=fail-baba" $baseurl
curl -H "KV-AUTH-TOKEN: $ro_tk2" -d o=get -d k=foo $baseurl
echo
echo

echo read data of another user .. FAIL .. b=bucket is ignored
curl -H "KV-AUTH-TOKEN: $ro_tk1" -d o=get -d k=foo -d b=baba $baseurl
curl -H "KV-AUTH-TOKEN: $ro_tk2" -d o=get -d k=foo -d b=oona $baseurl
echo
echo
