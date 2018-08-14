#!/bin/tcsh

set baseurl = 'http://localhost:5000/kvdb.php'
#set baseurl = 'https://pormann.net/kv/kvdb.php'
#set baseurl = 'http://kvdb.azurewebsites.net/kvdb.php'

# see kvdb_basedata.sql for list of expected tokens

set tk = abc123def456

echo get non-existant data .. OK/EMPTY:
curl -H "KV-AUTH-TOKEN: $tk" -d o=get -d k=foo $baseurl
echo
echo

echo create data .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=set -d k=foo -d "v=foofoo-$tk" $baseurl
echo
echo

echo get existant data .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=get -d k=foo $baseurl
echo
echo

echo get existant data with wildcard .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=get -d 'k=f%' $baseurl
echo
echo

echo add data where no key exists .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=add -d k=addme -d "v=addmetoo" $baseurl
echo
echo

echo verify added data .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=get -d k=addme $baseurl
echo
echo

echo add data where key exists .. FAIL
curl -H "KV-AUTH-TOKEN: $tk" -d o=add -d k=addme -d v=addmetoo $baseurl
echo
echo

echo replace data where no key exists .. FAIL
curl -H "KV-AUTH-TOKEN: $tk" -d o=rep -d k=replme -d v=replmetoo $baseurl
echo
echo

echo replace data where key exists .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=rep -d k=addme -d v=replmetoo $baseurl
echo
echo

echo verify replaced data .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=get -d k=addme $baseurl
echo
echo

echo delete data .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=del -d k=addme $baseurl
echo
echo

echo verify deleted data .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=get -d k=addme $baseurl
echo
echo

echo create data for take operation .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=set -d k=takeme1 -d v=takedata1 $baseurl
curl -H "KV-AUTH-TOKEN: $tk" -d o=set -d k=takeme2 -d v=takedata2 $baseurl
curl -H "KV-AUTH-TOKEN: $tk" -d o=set -d k=takeme3 -d v=takedata3 $baseurl
echo take data .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=take -d k=takeme1 $baseurl
echo take data with wildcard .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=take -d k=takeme% $baseurl
echo list data with wildcard .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=list -d k=takeme% $baseurl
echo
echo

echo create data for move operation .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=set -d k=moveme1 -d v=movedata1 $baseurl
curl -H "KV-AUTH-TOKEN: $tk" -d o=set -d k=moveme2 -d v=movedata2 $baseurl
curl -H "KV-AUTH-TOKEN: $tk" -d o=set -d k=moveme3 -d v=movedata3 $baseurl
curl -H "KV-AUTH-TOKEN: $tk" -d o=set -d k=moveme4 -d v=movedata4 $baseurl
echo move data .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=move -d k=moveme1 -d k2=done1 $baseurl
echo move data with wildcard .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=move -d k=moveme% -d k2=done2 $baseurl
echo move data with 2 wildcards .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=move -d k=moveme% -d k2=alldone% $baseurl
echo list data with wildcard .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=list -d k=moveme% $baseurl
curl -H "KV-AUTH-TOKEN: $tk" -d o=list -d k=done% $baseurl
curl -H "KV-AUTH-TOKEN: $tk" -d o=list -d k=alldone% $baseurl
echo
echo

echo create several data items .. OK
curl -H "KV-AUTH-TOKEN: $tk" -d o=set -d k=foo -d "v=foofoo-$tk" $baseurl
curl -H "KV-AUTH-TOKEN: $tk" -d o=set -d k=bar -d "v=barbar-$tk" $baseurl
curl -H "KV-AUTH-TOKEN: $tk" -d o=set -d k=baz -d "v=bazbaz-$tk" $baseurl
echo
echo

echo listing data as user .. OK
curl -H 'KV-AUTH-TOKEN: abc123def456' -d o=list $baseurl
echo
echo
