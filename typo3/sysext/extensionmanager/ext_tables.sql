#
# Table structure for table 'extensions'
#
CREATE TABLE tx_extensionmanager_extension (
	uid int(11) NOT NULL auto_increment,

	extension_key varchar(60) NOT NULL default '',
	version varchar(10) NOT NULL default '',
	title varchar(150) NOT NULL default '',
	description mediumtext,
	state int(4) NOT NULL default '0',
	category int(4) NOT NULL default '0',
	last_updated int(11) unsigned NOT NULL default '0',
	updatecomment mediumtext,
	authorname varchar(100) NOT NULL default '',
	authoremail varchar(100) NOT NULL default '',
	PRIMARY KEY (uid)
);
