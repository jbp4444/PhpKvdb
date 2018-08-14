
kvdb - a simple key-value data-store in php
===========================================

index.php - simple script to return dummy json data
	- probably should delete this in production

common.php - basic processing for all/most functions
	- set the PDO database here!
		- defaults to using 'sqlite:_kvdb.db'
	- checks for header KV-AUTH-TOKEN and sets user and permissions

kvdb.php - main user-facing code
	- get/set key-value pairs
	- key is a string
	- value is a JSON object; can be a simple string or number,
	  or full JSON object or array; note that per JSON spec,
	  simple strings must be enclosed in double-quotes
	- user sends an auth-key header which defines a "bucket"
	  (a container for a set k-v pairs) and permissions
	  (readonly,readwrite,admin,superadmin)
	- get, set/add/replace operations
	- provides a 'take' operation (atomic read-and-delete);
	  there will be performance implications when 'take' is used
		since it has to process a database transaction
	- allows a wildcard in the key for get/take operations
	- provides a 'list' operation to view keys

kvdbadm.php - main admin-facing code
	- get/set auth-key tokens
	- if user sends an auth-key header with an admin token,
	  then they can only create tokens for access into that bucket
	- if user sends a superadmin token, then they can create
	  tokens for any bucket
	- allows admins to list k-v pairs (hence superadmins can list
	  any bucket's k-v data)

kvdblink.php - IOT-device interface code
	- device registers a "device-code" (6-8 random chars)
	- device polls the system to see if the dev-code was claimed
	- user claims a dev-code with a user/permission token
	- device polls the system and gets a user/permission token
	- user or system can delete a dev-code once claimed and verified
	- to reduce info-leakage from system, the poll returns an error
	  if the dev-code is not claimed or does not exist (device cannot
	  tell the difference)

test_basicops.sh - tests kvdb.php for basic operations
	- assumes kvdb_basedata.sql has been injected into the db

test_kvdbhack.sh - tests kvdb.php for basic "user hacks" (attempts to do bad things)
	- assumes kvdb_basedata.sql has been injected into the db
	- only tests very basic "bad things" that a user might try to do

test_kvdbjson.sh - test kvdb.php for json issues
	- assumes kvdb_basedata.sql has been injected into the db
	- only tests very basic things

test_kvdbadm.sh - test kvdbadm.php for basic operations
	- assumes kvdb_basedata.sql has been injected into the db

test_kvdblink.sh - test kvdblink.php for basic operations

----------

Database Info:

CREATE TABLE MainDB ( bucket text, key text, val text, primary key(bucket,key) );
	- this holds the User-Key-Value data
	- each "bucket" is just a separate container; this could be per-app-user
		or per-group or even per-application

CREATE TABLE TokenDB ( tid text primary key, bucket text, perms text);
	- this holds the access tokens (e.g. php's uniqid() fcn)
	- a token is a (bucket,permissions) pair
	- so each bucket/container can have multiple read-only and read-write tokens

CREATE TABLE DevcodeDB ( devcode TEXT PRIMARY KEY , claimed char(1), userid TEXT, time_create integer);
	- for the IOT-ish approach
	- the device registers a unique 6-8 digit dev-code with the server
	- the server waits for a user to login and claim that dev-code
		- i.e. a user should present a Token (above) when issuing the claim operation
	- once claimed, the device can poll to determine what user claimed it
