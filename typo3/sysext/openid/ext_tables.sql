#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
	tx_openid_openid varchar(255) DEFAULT '' NOT NULL,

	UNIQUE KEY tx_openid_openid(tx_openid_openid)
);



#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_openid_openid varchar(255) DEFAULT '' NOT NULL
);