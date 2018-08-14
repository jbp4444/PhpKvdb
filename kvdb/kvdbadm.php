<?php

include( 'common.php' );

//  //  //  //  //  //  //  //  //  //
  //  //  //  //  //  //  //  //  //
//  //  //  //  //  //  //  //  //  //

$rtn = array(
	'status' => 'error',
	'statusCode' => 400
);

// TODO: probably want some extra logging for superadmin operations
if( $perms == 'superadmin' ) {
	// superadmin can change what bucket they act on
	$bucket = $sa_bucket;
	error_log( "superadmin auth ".$sa_bucket );
} elseif( $perms == 'admin' ) {
	error_log( "admin auth ".$bucket );
} else {
	// TODO: throw a more meaningful permissions error?
	$op = 'error';
}

// limit the number of keys per list-operation
if( $num > 100 ) {
	$num = 100;
}

if( $op == 'help' ) {
	$rtn['op'] = 'help';
	$rtn['info'] = array(
		'o=help' => 'display help info',
		'o=tget&t=def' => 'get info on auth-token=def',
		'o=tset&t=def&b=abc&p=perm' => 'update auth-token=def with bucket=abc and permissions=perms',
		'o=tset&b=abc&p=perm' => 'create a new auth-token for bucket=abc with permissions=perms',
		'o=tdel&t=def' => 'delete the auth-token with token-id=def',
		'o=show' => 'show (default) 10 tokens, starting from (default) 0',
		'o=show&n=20' => 'list 20 tokens, starting from (default) 0',
		'o=show&s=123' => 'list (default) 10 tokens starting from 123rd token',
		'o=show&s=123&n=20' => 'list 20 tokens starting from 123rd token',
		'o=list' => 'list (default) 10 keys, starting from (default) 0',
		'o=list&k=abc%' => 'list (default) 10 keys matching abc* (wildcard), starting from (default) 0',
		'o=list&n=20' => 'list 20 keys, starting from (default) 0',
		'o=list&s=123' => 'list (default) 10 keys starting from 123rd key',
		'o=list&s=123&n=20' => 'list 20 keys starting from 123rd key',
		'o=list&k=abc%&s=123&n=20' => 'list 20 keys matching abc* (wildcard) starting from 123rd key'
	);
	$rtn['status'] = 'ok';
	$rtn['statusCode'] = 200;

} elseif( $op == 'tget' ) {
	$rtn['op'] = 'tget';
	$rtn['token'] = $token;
	try {
		$stmt = $dbh->prepare( 'select * from TokenDB where tid=:tid AND bucket=:bucket' );
		$stmt->bindValue( ':tid', $token, PDO::PARAM_STR );
		$stmt->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$rtn['bucket'] = $row['bucket'];
		$rtn['perms'] = $row['perms'];
		$rtn['status'] = 'ok';
		$rtn['statusCode'] = 200;
	} catch( Exception $e ) {
		error_log( 'query error='.$e );
	}

} elseif( $op == 'tset' ) {
	$rtn['op'] = 'tset';
	// TODO: test that token,bucket,etc. are present
	$rtn['token'] = $token;
	$rtn['bucket'] = $bucket;
	$rtn['perms'] = $op_perms;
	try {
		if( $token == '' ) {
			$newtoken = uniqid();
			$rtn['token'] = $newtoken;
		} else {
			$newtoken = $token;
		}
		$stmt = $dbh->prepare( 'insert or replace into TokenDB (tid,bucket,perms) values (:tid,:bucket,:perms)' );
		$stmt->bindValue( ':tid', $newtoken, PDO::PARAM_STR );
		$stmt->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
		$stmt->bindValue( ':perms', $op_perms, PDO::PARAM_STR );
		$stmt->execute();
		$rtn['status'] = 'ok';
		$rtn['statusCode'] = 200;
	} catch( Exception $e ) {
		# TODO: an error could leak info (token is already taken)
		error_log( 'query error='.$e );
	}

} elseif( $op == 'tdel' ) {
	$rtn['op'] = 'tdel';
	// TODO: test that token is present
	$rtn['token'] = $token;
	try {
		$stmt = $dbh->prepare( 'delete from TokenDB where tid=:tid AND bucket=:bucket' );
		$stmt->bindValue( ':tid', $token, PDO::PARAM_STR );
		$stmt->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
		$stmt->execute();
		$rtn['status'] = 'ok';
		$rtn['statusCode'] = 200;
	} catch( Exception $e ) {
		error_log( 'query error='.$e );
	}

} elseif( $op == 'show' ) {
	$rtn['op'] = 'show';
	try {
		$stmt = $dbh->prepare( 'select tid from TokenDB where bucket=:bucket limit :num offset :ofs' );
		$stmt->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
		$stmt->bindValue( ':num', $num, PDO::PARAM_INT );
		$stmt->bindValue( ':ofs', $start, PDO::PARAM_INT );
		$stmt->execute();
		$keylist = array();
		while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
			array_push( $keylist, $row['tid'] );
		}
		$rtn['val'] = $keylist;
		$rtn['status'] = 'ok';
		$rtn['statusCode'] = 200;
	} catch( Exception $e ) {
		error_log( 'query error='.$e );
	}

} else {
	// this includes op='error' (from admin username conflict)
	// user sent impromper request
	$rtn['op'] = 'unknown';
	$rtn['info'] = 'improper request';
	$rtn['status'] = 'error';
	$rtn['statusCode'] = 400;
}


// send the response
header('Content-type: application/json');
http_response_code( $rtn['statusCode'] );
print json_encode( $rtn ) . "\n";

?>
