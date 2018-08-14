<?php

error_reporting(E_ALL);

// connect to the database
$dbh = new PDO('sqlite:../_kvdb.db' );
$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

// really simple header/token-based authentication
// NOTE: the client can send 'KV-AUTH-TOKEN', but php converts it to HTTP_KV_AUTH_TOKEN
$perms = 'readonly';
$bucket = 'guest';
if( array_key_exists('HTTP_KV_AUTH_TOKEN',$_SERVER) ) {
	$authtoken = $_SERVER['HTTP_KV_AUTH_TOKEN'];
	//error_log( "token=" . $authtoken );
	try {
		$stmt = $dbh->prepare( 'select * from TokenDB where tid=:token' );
		$stmt->bindValue( ':token', $authtoken, PDO::PARAM_STR );
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if( (!is_null($row['bucket'])) && (!empty($row['perms'])) ) {
			$bucket = $row['bucket'];
			$perms = $row['perms'];
		}
	} catch( Exception $e ) {
		error_log( "auth lookup error=".$e );
	}
}

//  //  //  //  //  //  //  //  //  //

// NOTE: this is HORRIBLY INSECURE and susceptible to CSRF attacks!
// TODO: need to move params out to form (_POST instead of _REQUEST)

// prepare the inputs
// NOTE: we'll clean up the inputs later, before we send them into SQL query statements
$op = 'help';
if( array_key_exists('o',$_REQUEST) ) {
	$op = $_REQUEST['o'];
}
$key = '';
if( array_key_exists('k',$_REQUEST) ) {
	$key = $_REQUEST['k'];
}
$key2 = '';
if( array_key_exists('k2',$_REQUEST) ) {
	$key2 = $_REQUEST['k2'];
}
$val = '';
if( array_key_exists('v',$_REQUEST) ) {
	$val = $_REQUEST['v'];
}
$start = 0;
if( array_key_exists('s',$_REQUEST) ) {
	$start = (int)($_REQUEST['s']);
}
$num = 10;
if( array_key_exists('n',$_REQUEST) ) {
	$num = (int)($_REQUEST['n']);
}

// just for admin, but easier to put them here (maybe??)
$code = '';
if( array_key_exists('c',$_REQUEST) ) {
	$code = $_REQUEST['c'];
}
$token = '';
if( array_key_exists('t',$_REQUEST) ) {
	$token = $_REQUEST['t'];
}
$sa_bucket = 'guest';
if( array_key_exists('b',$_REQUEST) ) {
	$sa_bucket = $_REQUEST['u'];
}
$op_perms = 'readonly';
if( array_key_exists('p',$_REQUEST) ) {
	$op_perms = $_REQUEST['p'];
	// we explicitly do not allow superadmin privs to be created
	if( ! in_array($op_perms,['readonly','readwrite','admin']) ) {
		$op_perms = 'readonly';
	}
}

//error_log( 'token=' . $token . ' user=' . $username . ' perms=' . $perms );

?>