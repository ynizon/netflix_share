<?php
/*
Script qui permet de desactiver automatiquement les avertissements netflix
Check des derniers mails arrivés sur une boite de type outlook.fr
Si il y a un lien , alors il clique dessus
*/

$user = 'XXX@outlook.fr';
$pass = ''; 
$hostname = '{outlook.office365.com:993/imap/ssl/novalidate-cert}INBOX';

$debug = true;

if (!is_dir("log")){
	mkdir("log");
}
	
$inbox = imap_open($hostname, $user, $pass) or die('Cannot connect to '.$hostname.': ' . imap_last_error());

if ($inbox) {
	$emails = imap_search($inbox,'UNSEEN');
	if ($emails) {
		$k=1;
		foreach($emails as $mail) {
			$headerInfo = imap_headerinfo($inbox,$mail);
			
			$output = '';
			/*
			$output .= $headerInfo->subject.'<br/>';
			$output .= $headerInfo->toaddress.'<br/>';
			$output .= $headerInfo->date.'<br/>';
			$output .= $headerInfo->fromaddress.'<br/>';
			*/

			$emailStructure = imap_fetchstructure($inbox,$mail);		
			$output = imap_qprint(imap_fetchbody($inbox,$mail,2));	   
		   
			if (stripos($headerInfo->subject, "netflix") !== false){
				if ($debug) {
					echo $headerInfo->date. ' - '.$headerInfo->subject."<br/>\n";
				}
				$extractedLinks = extractLink($output);
				
				$lastLink = '';
				foreach ($extractedLinks as $link){
					if (stripos($link['href'], "update-primary-location") !== false){
						$content = get_contents($link['href']);
						$lastLink = $link['href'];
						if ($debug) {
							//echo " - <a href='".$link['href']."'>".$link['href']."</a><br/>\n";
						}
						
						if (trim($content) != ''){
							file_put_contents("log/".$k.".html", $content);
							$k++;
						}
					}
				}
				
				if ($lastLink != '') {
					echo "<a href='".$lastLink."'>CLIQUER ICI POUR ACTIVER NETFLIX</a>";
					echo '<meta http-equiv="refresh" content="2;URL='.$lastLink.'">';
				}
			}   	
		}
	}else {
		echo 'Aucun email Netflix NON LU';	
	}
} else {
	echo 'Connexion IMAP refusée';
}

imap_expunge($inbox);
imap_close($inbox);

function get_contents($url) {
	try{
		$curl_handle=curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $url);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/113.0.0.0 Safari/537.36');
		$query = curl_exec($curl_handle);
		if($query === false)
		{
			echo 'Curl error: ' . curl_error($ch);
		}
		curl_close($curl_handle);
	}catch(\Exception $e){
		echo $e->getMessage();
	}
	return $query;
}

function extractLink($html){
	$htmlDom = new DOMDocument;
	@$htmlDom->loadHTML($html);
	$links = $htmlDom->getElementsByTagName('a');

	$extractedLinks = array();

	foreach($links as $link){
		$linkText = $link->nodeValue;
		$linkHref = $link->getAttribute('href');

		if(strlen(trim($linkHref)) == 0){
			continue;
		}

		if($linkHref[0] == '#'){
			continue;
		}

		$extractedLinks[] = array(
			'text' => $linkText,
			'href' => $linkHref
		);
	}
	return $extractedLinks;
}
?>