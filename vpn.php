<?PHP
// Teamspeak config
$teamspeakInfo = array(
    'username' => 'serveradmin',
    'password' => '***',
    'host' => '127.0.0.1',
    'portQuery' => '10011',
    'portServer' => '2000',
    'displayname' => 'BOT_IP'
);

$blacklist = array('DE'); // array('BR', 'US');
$whitelist = array('DE'); // array('BR', 'US');
$ignore_groups = array('283'); //para ignorar grupos selecionados
$clientType = 2; // 1 = Todos, 2 = Ignorar Query, 3 = Ignorar Clients
$listMode = 2; // 1 = blacklist, 2 = whitelist
$punishMode = 1; // 1 = kick, 2 = ban
$punishMessage = 'Seu país não tem permissão para entrar no nosso servidor.'; // mensagem de banimento/kick


echo "|--------------------------------------|\n|      Não permitir países específicos     |\n|--------------------------------------|\n";
require_once("ts3admin.class.php");
$tsAdmin = new ts3admin($teamspeakInfo['host'], $teamspeakInfo['portQuery']);

if($tsAdmin->getElement('success', $tsAdmin->connect())) {
    echo "> Conectado com sucesso ao servidor teamspeak\n";
    $tsAdmin->login($teamspeakInfo['username'], $teamspeakInfo['password']);
    echo "> Logado com sucesso\n";
    $tsAdmin->selectServer($teamspeakInfo['portServer']);
    echo "> Servidor selecionado com sucesso ".$teamspeakInfo['portServer']."\n";
    $tsAdmin->setName($teamspeakInfo['displayname']);
    echo "> Nome alterado com sucesso para ".$teamspeakInfo['displayname']."\n";

    $connectionInfo = $tsAdmin->whoAmI()['data'];

    for(;;){
        $clientList = $tsAdmin->clientList("-country -ip -groups");
		
		foreach($clientList['data'] as $val) {
		$groups = explode(",", $val['client_servergroups'] );
		if(is_array($ignore_groups)){
			foreach($ignore_groups as $ig){
				if(in_array($ig, $groups) || ($val['client_type'] == 1)) {
					continue;	
				}
			}
		}else{
			if(in_array($ignore_groups, $groups) || ($val['client_type'] == 1)) {
				continue;
			}
		}

        foreach($clientList['data'] as $client) {
            if ($listMode == 1) {
                $invalidCountry = false;
                foreach($blacklist as $blacklistCountry){
                    if ($client['client_country'] == $blacklistCountry || $client['client_country'] == "") {
                        switch ($clientType) {
                            case '1':
                                $invalidCountry = true;
                                break;

                            case '2':
                                if ($client['client_type'] == 0) {
                                    $invalidCountry = true;
                                }
                                break;

                            case '3':
                                if ($client['client_type'] == 1) {
                                    $invalidCountry = true;
                                }
                                break;
                        }
                    }
                }
            } else if ($listMode == 2) {
                $invalidCountry = true;
                foreach($whitelist as $whitelistCountry){
                    if ($client['client_country'] == $whitelistCountry) {
                        switch ($clientType) {
                            case '1':
                                $invalidCountry = false;
                                break;

                            case '2':
                                if ($client['client_type'] == 0) {
                                    $invalidCountry = false;
                                }
                                break;

                            case '3':
                                if ($client['client_type'] == 1) {
                                    $invalidCountry = false;
                                }
                                break;
                        }
                    }
                }
            }

            if ($invalidCountry && $connectionInfo['client_id'] != $client['clid']) {
                if ($punishMode == 1) {
                    $tsAdmin->clientKick($client['clid'], "server", $punishMessage);
                    echo "> Kickado com sucesso ".$client['client_nickname']." por ".$client['client_country']." -> ".$client['connection_client_ip']."\n";
                } else if ($punishMode == 2) {
                    $tsAdmin->banClient($client['clid'], 0, $punishMessage);
                    echo "> Banido com sucesso ".$client['client_nickname']." por ".$client['client_country']."\n";
                }
            }
        }
    }
}
?>
