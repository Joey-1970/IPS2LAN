<?
    // Klassendefinition
    class IPS2LANDevice extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Timer_1", 0);
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterPropertyString("IP", "");
		$this->RegisterPropertyString("MAC", "");
		$this->RegisterPropertyString("Name", "");
		$this->RegisterPropertyBoolean("MultiplePing", false);
		$this->RegisterPropertyInteger("Timer_1", 10);
		$this->RegisterTimer("Timer_1", 0, 'IPS2LANDevice_GetDataUpdate($_IPS["TARGET"]);');
		
		
		// Profil anlegen
		$this->RegisterProfileInteger("IPS2LAN.State", "Information", "", "", 0, 3, 1);
		IPS_SetVariableProfileAssociation("IPS2LAN.State", 0, "Unbekannt", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2LAN.State", 1, "Offline", "Close", 0xFF0000);
		IPS_SetVariableProfileAssociation("IPS2LAN.State", 2, "Störung", "Alert", 0xFFFF00);
		IPS_SetVariableProfileAssociation("IPS2LAN.State", 3, "Online", "Network", 0x00FF00);
		
		$this->RegisterProfileFloat("IPS2LAN.ms", "Clock", "", " ms", 0, 1000, 0.001, 3);
		
		// Status-Variablen anlegen		
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		$this->RegisterVariableInteger("State", "Status", "IPS2LAN.State", 20);
		$this->RegisterVariableInteger("SuccessRate", "Erfolgsqoute", "", 30);
		$this->RegisterVariableFloat("MinDuration", "Minimale Dauer", "IPS2LAN.ms", 40);
		$this->RegisterVariableFloat("AVGDuration", "Durchschnittliche Dauer", "IPS2LAN.ms", 50);
		$this->RegisterVariableFloat("MaxDuration", "Maximale Dauer", "IPS2LAN.ms", 50);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IP", "caption" => "IP");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "MAC", "caption" => "MAC");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "Name", "caption" => "Name");
		$arrayElements[] = array("type" => "Label", "label" => "Aktualisierung");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Timer_1", "caption" => "sek");
		$arrayElements[] = array("type" => "Label", "label" => "Mehrfach-Ping nutzen");
		$arrayElements[] = array("type" => "CheckBox", "name" => "MultiplePing", "caption" => "Mehrfach-Ping"); 
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		$this->RegisterMessage($this->InstanceID, 10103);
		$this->SetStatus(102);
		
		
		
		
		$IP = $this->ReadPropertyString("IP");
		
		if (filter_var($IP, FILTER_VALIDATE_IP)) {
    			$this->SetSummary($IP);

			$this->GetDataUpdate();
			$this->SetTimerInterval("Timer_1", $this->ReadPropertyInteger("Timer_1") * 1000);
		}
		else {
			$this->SetSummary("");
			$this->SendDebug("ApplyChanges", "Keine gueltige IP verfügbar!", 0);
			$this->SetTimerInterval("Timer_1", 0);
		}
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
 		switch ($Message) {
			case 10103:
				$this->ApplyChanges();
				break;
			
		}
    	}
	    
	// Beginn der Funktionen
	public function GetDataUpdate()
	{
		$MultiplePing = $this->ReadPropertyBoolean("MultiplePing");
		If ($MultiplePing == false) {
			$Result = unserialize($this->Simple_Ping());
			If ($Result["Ping"] == true) {
				$Ping = 3;
				$SuccessRate = 100;
			}
			else {
				$Ping = 1;
				$SuccessRate = 0;
			}
			$Duration = $Result["Duration"];
		}
		else {
			$Result = unserialize($this->Multiple_Ping());
		}
		If ($Ping <> GetValueInteger($this->GetIDForIdent("State"))) {
			SetValueInteger($this->GetIDForIdent("State"), $Ping);
		}
		If ($SuccessRate <> GetValueFloat($this->GetIDForIdent("SuccessRate"))) {
			SetValueFloat($this->GetIDForIdent("SuccessRate"), $SuccessRate);
		}
		SetValueInteger($this->GetIDForIdent("LastUpdate"), time() );
		
	}
	
	private function Simple_Ping()
	{
    		$this->SendDebug("Simple_Ping", "Ausfuehrung", 0);
		$IP = $this->ReadPropertyString("IP");
		$Result = array();
		$Start = microtime(true);
    		$Response = Sys_Ping($IP, 100); 
    		$Duration = microtime(true) - $Start;
    		$Result["Ping"] = $Response;
    		$Result["Duration"] = round($Duration * 1000, 2);
		$this->SendDebug("Simple_Ping", "Dauer: ".$Duration. " Ergebnis: ".(boolval($Response)), 0);
		
	return serialize($Result);
	}    
	    
	private function Multiple_Ping()
	{
    		$this->SendDebug("Multiple_Ping", "Ausfuehrung", 0);
		$IP = $this->ReadPropertyString("IP");
		$Result = array();
		$Ping = array();
		$Duration = array();
		$Tries = 5;
		for ($i = 0; $i <= $Tries; $i++) {
			$Start = microtime(true);
			$Response = Sys_Ping($IP, 100); 
			$Duration[] = microtime(true) - $Start;
			$Ping[] = $Response;
			
		}
		// Ping-Werte berechnen
		$MinDuration = round(min($Duration) * 1000, 2);
		$AVGDuration = round((array_sum($Duration)/count($Duration)) * 1000, 2);
		$MaxDuration = round(max($Duration) * 1000, 2);
		// Erfolg auswerten
		$AVGPing = Round((array_sum($Ping)/count($Ping)) * 100, 2);
		$this->SendDebug("Multiple_Ping", "Min: ".$MinDuration."ms, Durchschnitt: ".$AVGDuration."ms, Max: ".$MaxDuration."ms, Erfolg: ".$AVGPing."%", 0);
		
	return serialize($Result);
	}    
	
	private function WakeOnLAN()
	{
    		$mac = $this->ReadPropertyString("MAC");
		if (filter_var($mac, FILTER_VALIDATE_MAC)) {
			$broadcast = "255.255.255.255";
			$mac_array = preg_split('#:#', $mac);
			$hwaddr = '';
			foreach($mac_array AS $octet)
			{
				$hwaddr .= chr(hexdec($octet));
			}
			// Create Magic Packet
			$packet = '';
			for ($i = 1; $i <= 6; $i++)
			{
				$packet .= chr(255);
			}
			for ($i = 1; $i <= 16; $i++)
			{
				$packet .= $hwaddr;
			}
			$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
			if ($sock)
			{
				$options = socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, true);
				if ($options >=0) 
				{    
					$e = socket_sendto($sock, $packet, strlen($packet), 0, $broadcast, 7);
					socket_close($sock);
				}    
			}
		}
		else {
			$this->SendDebug("WakeOnLAN", "Keine gueltige MAC verfügbar!", 0);
		}
	}    
	    
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);        
	}    
	    
	private function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 2);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 2)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	        IPS_SetVariableProfileDigits($Name, $Digits);
	}
}
?>
