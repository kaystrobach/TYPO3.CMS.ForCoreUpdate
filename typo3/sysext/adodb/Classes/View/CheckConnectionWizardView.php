<?php
/**
 * Script Class checking the connection to an ADODB handled database
 *
 * @author Robert Lemke <robert@typo3.org>
 * @package TYPO3
 * @subpackage adodb
 */
class tx_adodb_checkconnectionwizard {

	/**
	 * @todo Define visibility
	 */
	public function main() {
		$content = '<html><body>';
		$conf = t3lib_div::_GP('P');
		$conf['md5ID'];
		if ($conf['table'] == 'tx_datasources_datasource') {
			$dsRecord = t3lib_beFunc::getRecord($conf['table'], intval($conf['uid']));
			if (is_array($dsRecord)) {
				$dsArr = t3lib_div::xml2array($dsRecord['configuration']);
				$dsConf = $dsArr['data']['sDEF']['lDEF'];
				$content .= ((('<p>Trying to connect with Host / DSN <strong>' . htmlspecialchars($dsConf['field_host']['vDEF'])) . '</strong> with user <strong>') . htmlspecialchars($dsConf['field_username']['vDEF'])) . '</strong> ... ';
				$dbConn =& ADONewConnection($dsConf['field_dbtype']['vDEF']);
				$dbConn->PConnect($dsConf['field_host']['vDEF'], $dsConf['field_username']['vDEF'], $dsConf['field_password']['vDEF'], $dsConf['field_dbname']['vDEF']);
				$dbConn->SetFetchMode(ADODB_FETCH_ASSOC);
				$content .= $dbConn->ErrorMsg();
				if ($dbConn->_connectionID) {
					$content .= '</p>';
					$query = 'SELECT * FROM ' . $dsConf['field_table']['vDEF'];
					$recordSet =& $dbConn->SelectLimit($query, 150);
					if (!$recordSet) {
						$content .= ('<p>Query failed (' . htmlspecialchars($query)) . '):<br />';
						$content .= $dbConn->ErrorMsg() . '</p>';
					} else {
						$content .= '<span style="color:green">successful!</span></p>';
						$counter = 0;
						$content .= '<p>Showing the first 150 entries from the result recordset:</p>';
						$content .= '<table border="1">';
						while (!$recordSet->EOF) {
							$content .= '<tr>';
							if ($counter == 0) {
								foreach (array_keys($recordSet->fields) as $key) {
									$content .= ('<th>' . htmlspecialchars($key)) . '</th>';
								}
								$content .= '</tr><tr>';
							}
							foreach (array_values($recordSet->fields) as $value) {
								$content .= ('<td>' . htmlspecialchars($value)) . '&nbsp;</td>';
							}
							$recordSet->MoveNext();
							$counter++;
							$content .= '</tr>';
						}
						$content .= '<table>';
					}
				}
			} else {
				$content .= ('<span style="color:red">failed!</span></p><p><strong>Error Message:</strong>' . $dbConn->ErrorMsg()) . '</p>';
			}
		}
		$content .= '</body></html>';
		echo $content;
	}

}

?>