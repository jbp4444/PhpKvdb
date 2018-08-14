<?php

include( 'common.php' );

// TODO: need a way to catch if user is logged in
//		(http-basic-auth?)
$login_user = 'foo@example.com';

//  //  //  //  //  //  //  //  //  //
  //  //  //  //  //  //  //  //  //
//  //  //  //  //  //  //  //  //  //

$rtn = array(
	'status' => 'error',
	'statusCode' => 400
);

if( $op == 'help' ) {
	$rtn['op'] = 'help';
	$rtn['info'] = array(
		'o=help' => 'display help info',
		'o=reg&c=def' => 'registers device-code=def',
		'o=claim&c=def' => 'claims device-code=def for logged-in user'
	);
	$rtn['status'] = 'ok';
	$rtn['statusCode'] = 200;

} elseif( $op == 'reg' ) {
	// NOTE: Ignore user info at this stage (should not be present anyway)
	$rtn['op'] = 'reg';
	$rtn['code'] = $code;
	try {
		$stmt = $dbh->prepare( 'insert into DevcodeDB values (:code,"N","_null",:tstamp)' );
		$stmt->bindValue( ':code', $code, PDO::PARAM_STR );
		$stmt->bindValue( ':tstamp', time(), PDO::PARAM_INT );
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$rtn['user'] = $row['user'];
		$rtn['perms'] = $row['perms'];
		$rtn['status'] = 'ok';
		$rtn['statusCode'] = 200;
	} catch( Exception $e ) {
		error_log( 'query error='.$e );
	}

} elseif( $op == 'poll' ) {
	// NOTE: Ignore user info at this stage (should not be present anyway)
	$rtn['op'] = 'poll';
	$rtn['code'] = $code;
	try {
		$stmt = $dbh->prepare( 'select * from DevcodeDB where devcode=:code AND claimed="Y"' );
		$stmt->bindValue( ':code', $code, PDO::PARAM_STR );
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if( !is_null($row['userid']) ) {
			$rtn['token'] = $row['userid'];
			// TODO: should return the permissions for this token
			$rtn['status'] = 'ok';
			$rtn['statusCode'] = 200;
		}
	} catch( Exception $e ) {
		error_log( 'query error='.$e );
	}

} elseif( $op == 'claim' ) {
	// TODO: need additional login info for user who is claiming this dev-code
	//       and then what bucket does that user belong to (or want to assign it to)
	$rtn['op'] = 'claim';
	$rtn['user'] = $login_user;
	$rtn['code'] = $code;
	try {
		$stmt = $dbh->prepare( 'update DevcodeDB set claimed="Y", userid=:user where devcode=:code' );
		$stmt->bindValue( ':code', $code, PDO::PARAM_STR );
		$stmt->bindValue( ':user', $login_user, PDO::PARAM_STR );
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		# TODO: fully check return value
		if( $stmt->rowCount() > 0 ) {
			$rtn['status'] = 'ok';
			$rtn['statusCode'] = 200;
		} else {
			$rtn['info'] = 'no device-code found';
			$rtn['status'] = 'error';
			$rtn['statusCode'] = 400;
		}
	} catch( Exception $e ) {
		error_log( 'query error='.$e );
	}

} elseif( $op == 'unreg' ) {
	$rtn['op'] = 'unreg';
	$rtn['bucket'] = $bucket;
	$rtn['code'] = $code;
	try {
		$stmt = $dbh->prepare( 'delete from DevcodeDB where devcode=:code' );
		$stmt->bindValue( ':code', $code, PDO::PARAM_STR );
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
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
