CREATE TABLE MainDB ( bucket text, key text, val text, primary key(bucket,key) );
CREATE TABLE TokenDB ( tid text primary key, bucket text, perms text);
CREATE TABLE DevcodeDB ( devcode TEXT PRIMARY KEY , claimed char(1), userid TEXT, time_create integer);
