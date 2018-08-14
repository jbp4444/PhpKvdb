<?php

include( 'common.php' );

//  //  //  //  //  //  //  //  //  //
  //  //  //  //  //  //  //  //  //
//  //  //  //  //  //  //  //  //  //

// make the default return value an error
$rtn = array(
	'status' => 'error',
	'statusCode' => 400
);


// make admin look like readwrite
if( $perms == 'admin' ) {
	$perms = 'readwrite';
} else {
	// non-admins: limit the number of keys per list-operation
	if( $num > 25 ) {
		$num = 25;
	}
}

// which command was requested?
if( $op == 'help' ) {
	$rtn['op'] = 'help';
	$rtn['info'] = array(
		'o=help' => 'display help info',
		'o=get&k=abc' => 'get value for key=abc',
		'o=get&k=abc%' => 'get value for key=abc* (wildcard)',
		'o=set&k=abc&v=def' => 'set data for key=abc to val=def',
		'o=add&k=abc&v=def' => 'add data for key=abc, val=def, only if it key does not exist',
		'o=rep&k=abc&v=def' => 'replace data for key=abc with val=def, only if key already exists',
		'o=del&k=abc' => 'delete the data for key=abc',
		'o=take&k=abc' => 'atomic get-and-delete key=abc',
		'o=take&k=abc%' => 'atomic get-and-delete of one key=abc% (wildcard)',
		'o=move&k=abc&k2=def' => 'atomic get-and-rename key1=abc to key2=def',
		'o=move&k=abc%&k2=def%' => 'atomic get-and-rename of one key1=abc% to key2=def% (wildcard)',
		'o=list' => 'list (default) 10 keys, starting from (default) 0',
		'o=list&k=abc%' => 'list (default) 10 keys matching abc* (wildcard), starting from (default) 0',
		'o=list&n=20' => 'list 20 keys, starting from (default) 0',
		'o=list&s=123' => 'list (default) 10 keys starting from 123rd key',
		'o=list&s=123&n=20' => 'list 20 keys starting from 123rd key',
		'o=list&k=abc%&s=123&n=20' => 'list 20 keys matching abc* (wildcard) starting from 123rd key'
	);
	$rtn['status'] = 'ok';
	$rtn['statusCode'] = 200;

} elseif( $op == 'get' ) {
	$rtn['op'] = 'get';
	$rtn['bucket'] = $bucket;
	$rtn['req_key'] = $key;
	if( strpos($key,'%') > -1 ) {
		$query = 'select * from MainDB where bucket=:bucket AND key like :key';
	} else {
		$query = 'select * from MainDB where bucket=:bucket AND key=:key';
	}
	try {
		$stmt = $dbh->prepare( $query );
		$stmt->bindValue( ':key', $key, PDO::PARAM_STR );
		$stmt->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		$rtn['key'] = $row['key'];
		if( is_null($row['val']) ) {
			// no content is stored
			$rtn['val'] = $row['val'];
		} else {
			$rtn['val'] = json_decode( $row['val'] );
			if( is_null($rtn['val']) ) {
				// TODO: this assumes val is a singleton, not json object
				//error_log( 'possible json error ['. $row['val'] .']');
				$rtn['val'] = $row['val'];
			}
		}
		$rtn['status'] = 'ok';
		$rtn['statusCode'] = 200;
	} catch( Exception $e ) {
		error_log( 'query error='.$e );
	}

} elseif( ($op=='lis') or ($op=='list') ) {
	$rtn['op'] = 'list';
	$rtn['bucket'] = $bucket;
	try {
		if( strpos($key,'%') > -1 ) {
			$query = 'select key from MainDB where bucket=:bucket AND key like :key limit :num offset :ofs';
		} else {
			$query = 'select key from MainDB where bucket=:bucket limit :num offset :ofs';
		}
		$stmt = $dbh->prepare( $query );
		if( strpos($key,'%') > 0 ) {
			$stmt->bindValue( ':key', $key, PDO::PARAM_STR );
		}
		$stmt->bindValue( ':num', $num, PDO::PARAM_INT );
		$stmt->bindValue( ':ofs', $start, PDO::PARAM_INT );
		$stmt->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
		$stmt->execute();
		$keylist = array();
		while( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
			array_push( $keylist, $row['key'] );
		}
		$rtn['val'] = $keylist;
		$rtn['status'] = 'ok';
		$rtn['statusCode'] = 200;
	} catch( Exception $e ) {
		error_log( 'query error='.$e );
	}

//  //  //  //  //  //  //  //  //  //  //  //  //  //  //

// since php follows the flow of statements, we can early-exit
// here for ops that require readwrite perms
} elseif( $perms != 'readwrite' ) {
	$rtn['info'] = 'unauthorized';
	$rtn['status'] = 'error';
	$rtn['statusCode'] = 401;

//  //  //  //  //  //  //  //  //  //  //  //  //  //  //

} elseif( ($op=='set') or ($op=='add') or ($op=='rep') ) {
	$rtn['op'] = $op;
	$rtn['bucket'] = $bucket;
	$rtn['key'] = $key;
	$rtn['req_val'] = $val;
	if( strpos($key,'%') > -1 ) {
		$rtn['info'] = 'cannot wildcard a set operation';
		$rtn['status'] = 'error';
		$rtn['statusCode'] = 401;
	} else {
		$query = '';
		if( $op == 'set' ) {
			$query = 'insert or replace into MainDB values (:bucket,:key,:val)';
		} elseif( $op == 'add' ) {
			$query = 'insert into MainDB values (:bucket,:key,:val)';
		} elseif( $op == 'rep' ) {
			$query = 'update MainDB set val=:val where bucket=:bucket and key=:key';
		}
		// make sure value is valid json
		$tmp = json_decode( $val );
		if( is_null($tmp) ) {
			// fudge it into a json object (a stringified string?)
			$val = '"' . $val . '"';
		}
		try {
			$stmt = $dbh->prepare( $query );
			$stmt->bindValue( ':key', $key, PDO::PARAM_STR );
			$stmt->bindValue( ':val', $val, PDO::PARAM_STR );
			$stmt->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
			$stmt->execute();
			if( $stmt->rowCount() == 1 ) {
				$rtn['status'] = 'ok';
				$rtn['statusCode'] = 200;
			} else {
				$rtn['status'] = 'error';
				$rtn['statusCode'] = 400;
				$rtn['info'] = 'no key found';
			}
		} catch( PDOException $e ) {
			$err = $e->getCode();
			if( $err == 23000 ) {
				$rtn['info'] = 'duplicate key found';
			}
			//error_log( 'query error='.$e );
			error_log( 'query error code='.$err );
		}
	}

} elseif( $op == 'del' ) {
	$rtn['op'] = 'del';
	$rtn['bucket'] = $bucket;
	$rtn['req_key'] = $key;
	if( strpos($key,'%') > -1 ) {
		$rtn['info'] = 'cannot wildcard a delete operation';
		$rtn['status'] = 'error';
		$rtn['statusCode'] = 401;
	} else {
		try {
			$stmt = $dbh->prepare( 'delete from MainDB where bucket=:bucket AND key=:key' );
			$stmt->bindValue( ':key', $key, PDO::PARAM_STR );
			$stmt->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
			$stmt->execute();
			$rtn['status'] = 'ok';
			$rtn['statusCode'] = 200;
		} catch( Exception $e ) {
			error_log( 'query error='.$e );
		}
	}

} elseif( $op == 'take' ) {
	$rtn['op'] = 'take';
	$rtn['bucket'] = $bucket;
	$rtn['req_key'] = $key;
	if( strpos($key,'%') > -1 ) {
		$query = 'select * from MainDB where bucket=:bucket AND key like :key';
	} else {
		$query = 'select * from MainDB where bucket=:bucket AND key=:key';
	}
	try {
		$dbh->beginTransaction();
			$stmt1 = $dbh->prepare( $query );
			$stmt1->bindValue( ':key', $key, PDO::PARAM_STR );
			$stmt1->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
			$stmt1->execute();
			$row = $stmt1->fetch(PDO::FETCH_ASSOC);
			$rtn['key'] = $row['key'];
			$stmt2 = $dbh->prepare( 'delete from MainDB where bucket=:bucket AND key=:key' );
			$stmt2->bindValue( ':key', $row['key'], PDO::PARAM_STR );
			$stmt2->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
			$stmt2->execute();
		$dbh->commit();
		$ltr = substr( $row['val'], 0, 1 );
		$rtn['val'] = json_decode( $row['val'] );
		if( is_null($rtn['val']) ) {
			// TODO: this assumes val is a singleton, not json object
			$rtn['val'] = $row['val'];
		}
		$rtn['status'] = 'ok';
		$rtn['statusCode'] = 200;
	} catch( Exception $e ) {
		$dbh->rollBack();
		error_log( 'query error='.$e );
	}

} elseif( $op == 'move' ) {
	$rtn['op'] = 'move';
	$rtn['bucket'] = $bucket;
	$rtn['req_key'] = $key;
	$rtn['req_key2'] = $key2;
	if( (strpos($key,'%')<0) && (strpos($key2,'%')>-1) ) {
		// key1 can be wildcarded with or without key2 wildcard
		// but key2 wildcard must have key1 wildcarded too
		$rtn['info'] = 'key1 must be wildcarded if key2 is wildcarded';
		$rtn['status'] = 'error';
		$rtn['statusCode'] = 401;
	} else {
		if( strpos($key,'%') > 0 ) {
			$query = 'select * from MainDB where bucket=:bucket AND key like :key';
		} else {
			$query = 'select * from MainDB where bucket=:bucket AND key=:key';
		}
		try {
			$dbh->beginTransaction();
				$stmt1 = $dbh->prepare( $query );
				$stmt1->bindValue( ':key', $key, PDO::PARAM_STR );
				$stmt1->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
				$stmt1->execute();
				$row = $stmt1->fetch(PDO::FETCH_ASSOC);
				$the_key = $row['key'];
				if( strpos($key2,'%') > -1 ) {
					$i = strpos($key,'%');
					$j = strpos($key2,'%');
					$new_key = substr($key2,0,$j) . substr($the_key,$i);
				} else {
					$new_key = $key2;
				}
				//error_log( 'key='.$key.' thekey='.$the_key.' newkey='.$new_key );
				$stmt2 = $dbh->prepare( 'update MainDB set key=:newkey, val=:val where bucket=:bucket and key=:key' );
				$stmt2->bindValue( ':newkey', $new_key, PDO::PARAM_STR );
				$stmt2->bindValue( ':key', $the_key, PDO::PARAM_STR );
				$stmt2->bindValue( ':val', $val, PDO::PARAM_STR );
				$stmt2->bindValue( ':bucket', $bucket, PDO::PARAM_STR );
				$stmt2->execute();
				$rtn['key'] = $the_key;
				$rtn['new_key'] = $new_key;
			$dbh->commit();
			$ltr = substr( $row['val'], 0, 1 );
			$rtn['val'] = json_decode( $row['val'] );
			if( is_null($rtn['val']) ) {
				// TODO: this assumes val is a singleton, not json object
				$rtn['val'] = $row['val'];
			}
			$rtn['status'] = 'ok';
			$rtn['statusCode'] = 200;
		} catch( Exception $e ) {
			$dbh->rollBack();
			error_log( 'query error='.$e );
		}
	}

} else {
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