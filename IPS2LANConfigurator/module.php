<?
    // Klassendefinition
    class IPS2LANConfigurator extends IPSModule 
    {
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterPropertyInteger("DeviceAdressStart", 1);  
		$this->RegisterPropertyInteger("DeviceAdressEnd", 254); 
		$this->RegisterPropertyString("BasicIP", "undefiniert"); 
		$this->RegisterPropertyString("OwnIP", "undefiniert"); 
		$this->RegisterPropertyInteger("Category", 0);
		$this->RegisterPropertyBoolean("MultiplePing", false);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayOptions = array();
		$IP = unserialize($this->IP());
		$arrayValues[] = array("name" => "BasicIP", "value" => "undefiniert");
		$arrayValues[] = array("name" => "OwnIP", "value" => "undefiniert");
		$arrayOptions[] = array("caption" => "undefiniert", "value" => $arrayValues);
		foreach($IP AS $Network) {
			$arrayValues = array();
			$IP_Parts = explode(".", $Network);
			$BasicIP = $IP_Parts[0].".".$IP_Parts[1].".".$IP_Parts[2].".xxx";
			$arrayValues[] = array("name" => "BasicIP", "value" => $BasicIP);
			$arrayValues[] = array("name" => "OwnIP", "value" => $Network);
			$arrayOptions[] = array("caption" => $BasicIP, "value" => $arrayValues);
		}
		
		$arrayElements[] = array("type" => "Select", "name" => "IP", "caption" => "Netzwerk", "options" => $arrayOptions );

		
		$ArrayRowLayout = array();
		$ArrayRowLayout[] = array("type" => "Label", "label" => "Geräte-Adressbereich    ");
		$ArrayRowLayout[] = array("type" => "NumberSpinner", "name" => "DeviceAdressStart", "caption" => "Start", "digits" => 0);
		$ArrayRowLayout[] = array("type" => "NumberSpinner", "name" => "DeviceAdressEnd", "caption" => "Ende", "digits" => 0);
		
		$arrayElements[] = array("type" => "RowLayout", "items" => $ArrayRowLayout);
		$arrayElements[] = array("type" => "Label", "label" => "Mehrfach-Ping nutzen");
		$arrayElements[] = array("type" => "CheckBox", "name" => "MultiplePing", "caption" => "Mehrfach-Ping"); 
		$arrayElements[] = array("type" => "SelectCategory", "name" => "Category", "caption" => "Zielkategorie beim Erstellen");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arraySort = array();
		//$arraySort = array("column" => "IP", "direction" => "ascending");
		
		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "IP", "name" => "IP", "width" => "180px", "visible" => true);
		$arrayColumns[] = array("caption" => "Name", "name" => "Name", "width" => "250px", "visible" => true);
		$arrayColumns[] = array("caption" => "MAC", "name" => "MAC", "width" => "180px", "visible" => true);
		$arrayColumns[] = array("caption" => "Ping (ms)", "name" => "Duration", "width" => "100px", "visible" => true);
		$arrayColumns[] = array("caption" => "Status", "name" => "State", "width" => "auto", "visible" => false);
		
		$Category = $this->ReadPropertyInteger("Category");
		$RootNames = [];
		$RootId = $Category;
		while ($RootId != 0) {
		    	if ($RootId != 0) {
				$RootNames[] = IPS_GetName($RootId);
		    	}
		    	$RootId = IPS_GetParent($RootId);
			}
		$RootNames = array_reverse($RootNames);
		
		
		$DeviceArray = array();
		$DeviceArray = unserialize($this->GetData());
		
		$arrayValues = array();
		foreach ($DeviceArray as $IP => $Values) {
			
			$arrayCreate = array();
			$arrayCreate[] = array("moduleID" => "{D43B786D-F3F7-53A2-1434-79C975AA4408}", "location" => $RootNames, 
					       "configuration" => array("IP" => $IP, "MAC" => $Values["MAC"], "Name" => $Values["Name"], "Timer_1" => 60));
			$arrayValues[] = array("IP" => $IP, "Name" => $Values["Name"], "MAC" => $Values["MAC"],
					       "Duration" => $Values["Duration"], "State" => $Values["Ping"], "name" => $Values["Name"], "instanceID" => $Values["InstanceID"],
					       "create" => $arrayCreate);
		}
		
		$arrayElements[] = array("type" => "Configurator", "name" => "Network", "caption" => "Netzwerk", "rowCount" => 20, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		$BasicIP = $this->ReadPropertyString("BasicIP");
		$OwnIP = $this->ReadPropertyString("OwnIP");
		$this->SendDebug("ApplyChanges", "Basis IP: ".$BasicIP." Eigene IP: ".$OwnIP, 0);
	}
	    
	// Beginn der Funktionen
	private function GetData()
	{
		$DeviceAdressStart = $this->ReadPropertyInteger("DeviceAdressStart");
		$DeviceAdressEnd = $this->ReadPropertyInteger("DeviceAdressEnd");
		$BasicIP = $this->ReadPropertyString("BasicIP");
		$OwnIP = $this->ReadPropertyString("OwnIP");
		$MultiplePing = $this->ReadPropertyBoolean("MultiplePing");
		$Devices = array();
		$MAC = array();
		
		If (($BasicIP <> "undefinert") AND ($DeviceAdressStart <= $DeviceAdressEnd)) {
			// Basic IP auflösen
			$IP_Parts = explode(".", $BasicIP);
    			If (count($IP_Parts) == 4) {
        			$IP = $IP_Parts[0].".".$IP_Parts[1].".".$IP_Parts[2].".";
    			
				// erweiterte Daten über ARP laden
				$MAC = unserialize($this->MAC());

				for ($i = $DeviceAdressStart; $i <= $DeviceAdressEnd; $i++) {
					
					If ($MultiplePing == false) {
						$Response = unserialize($this->Ping($IP.$i));
					}
					else {
						$Response = unserialize($this->Multiple_Ping($IP.$i));
					}

					If ($Response[$IP.$i]["Ping"] == true) {
						$Devices[$IP.$i]["Ping"] = true;
						$Devices[$IP.$i]["Duration"] = round($Response[$IP.$i]["Duration"] * 1000, 3);
						if (array_key_exists($IP.$i, $MAC)) {
							$Devices[$IP.$i]["Name"] = $MAC[$IP.$i]["Name"];
							$Devices[$IP.$i]["MAC"] = $MAC[$IP.$i]["MAC"];
						}
						elseif ($IP.$i == $OwnIP) {
							$Devices[$IP.$i]["Name"] = "dieses Gerät";
							$Devices[$IP.$i]["MAC"] = $this->OwnMAC($OwnIP);
						}
						else {
							$Devices[$IP.$i]["Name"] = "nicht verfügbar";
							$Devices[$IP.$i]["MAC"] = "";
						}
						$Devices[$IP.$i]["InstanceID"] = $this->GetDeviceInstanceID($IP.$i);
					}
				}
			}
		}
	return serialize($Devices);
	}
	
	private function Ping($IP)
	{
    		$Result = array();
		$Start = microtime(true);
    		$Response = Sys_Ping($IP, 50); 
    		$Duration = microtime(true) - $Start;
    		$Result[$IP]["Ping"] = $Response;
    		$Result[$IP]["Duration"] = $Duration;
	return serialize($Result);
	}
	    
	private function Multiple_Ping($IP)
	{
		$Tries = 5;
		$Result = array();
		$Ping = array();
		$Duration = array();
		
		for ($i = 0; $i < $Tries; $i++) {
			$Start = microtime(true);
			$Response = Sys_Ping($IP, 50); 
			$Duration[] = microtime(true) - $Start;
			$Ping[] = $Response;
		}
		// Ping-Werte berechnen
		$AvgDuration = array_sum($Duration)/count($Duration);
		// Erfolg auswerten
		$SuccessRate = Round((array_sum($Ping)/count($Ping)) * 100, 2);
		If ($SuccessRate > 0) {
			$Result[$IP]["Ping"] = true;
		}
		else {
			$Result[$IP]["Ping"] = false;
		}
		$Result[$IP]["Duration"] = $AvgDuration;
	return serialize($Result);
	} 
	    
	private function MAC()
	{
		$arp = shell_exec('arp -a');
		$Lines = explode("\n", $arp);

		$devices = array();

		$Search = array(" auf ", "[ether]", "eth0", "(", ")", " at ", " on ");
		$Replace = array(" ", " ", " ", " ", " ", " ", " ");
		
		$Devices = array();

		foreach ($Lines as $Line) {
    			$Line = str_replace($Search, $Replace, $Line);
    			$Cols = preg_split('/\s+/', trim($Line));
    			If (Count($Cols) == 3) {
        			$Devices[$Cols[1]]["Name"] = $Cols[0];
        			if (filter_var($Cols[2], FILTER_VALIDATE_MAC)) {
					$Devices[$Cols[1]]["MAC"] = $Cols[2];
				}
				else {
					$Devices[$Cols[1]]["MAC"] = "nicht verfügbar";
				}
    			}
		}
	return serialize($Devices);
	}
	    
	private function IP()
	{
		$IPArray = array();
		$IPArray = Sys_GetNetworkInfo();
		$IP = array();
		foreach ($IPArray as $Network) {
			if (filter_var($Network["IP"], FILTER_VALIDATE_IP)) {
				$IP[] = $Network["IP"];
    			}
		}
	return serialize($IP);
	}
	
	private function OwnMAC($OwnIP)
	{
	    	$IPArray = array();
		$IPArray = Sys_GetNetworkInfo();
		$MAC = "nicht verfügbar";
		foreach ($IPArray as $Network) {
    			If ($Network["IP"] == $OwnIP) {
        			$MAC = $Network["MAC"];
    			}
		}
	return $MAC;
	}
	    
	function GetDeviceInstanceID(string $IP)
	{
		$guid = "{D43B786D-F3F7-53A2-1434-79C975AA4408}";
	    	$Result = 0;
	    	// Modulinstanzen suchen
	    	$InstanceArray = array();
	    	$InstanceArray = @(IPS_GetInstanceListByModuleID($guid));
	    	If (is_array($InstanceArray)) {
			foreach($InstanceArray as $Module) {
				If (strtolower(IPS_GetProperty($Module, "IP")) == strtolower($IP)) {
					$this->SendDebug("GetStationInstanceID", "Gefundene Instanz: ".$Module, 0);
					$Result = $Module;
					break;
				}
				else {
					$Result = 0;
				}
			}
		}
	return $Result;
	}
}
?>
