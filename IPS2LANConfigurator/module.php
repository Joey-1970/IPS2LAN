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
		$this->RegisterPropertyString("IP", "undefiniert"); 
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
		$arrayOptions[] = array("label" => "undefiniert", "value" => "undefiniert");
		foreach($IP AS $Network) {
			$arrayOptions[] = array("label" => $Network, "value" => $Network);
		}
		
		$arrayElements[] = array("type" => "Select", "name" => "IP", "caption" => "Netzwerk", "options" => $arrayOptions );

		
		$ArrayRowLayout = array();
		$ArrayRowLayout[] = array("type" => "Label", "label" => "Geräte-Adressbereich");
		$ArrayRowLayout[] = array("type" => "NumberSpinner", "name" => "DeviceAdressStart", "caption" => "Start", "digits" => 0);
		$ArrayRowLayout[] = array("type" => "NumberSpinner", "name" => "DeviceAdressEnd", "caption" => "Ende", "digits" => 0);
		$arrayElements[] = array("type" => "RowLayout", "items" => $ArrayRowLayout);
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arraySort = array();
		//$arraySort = array("column" => "IP", "direction" => "ascending");
		
		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "IP", "name" => "IP", "width" => "180px", "visible" => true);
		$arrayColumns[] = array("caption" => "Name", "name" => "Name", "width" => "250px", "visible" => true);
		$arrayColumns[] = array("caption" => "MAC", "name" => "MAC", "width" => "180px", "visible" => true);
		$arrayColumns[] = array("caption" => "Ping (ms)", "name" => "Duration", "width" => "100px", "visible" => true);
		$arrayColumns[] = array("caption" => "Status", "name" => "State", "width" => "auto", "visible" => false);
		
		$DeviceArray = array();
		$DeviceArray = unserialize($this->GetData());
		
		$arrayValues = array();
		foreach ($DeviceArray as $IP => $Values) {
			/*
			$arrayCreate = array();
			$arrayCreate[] = array("moduleID" => "{47286CAD-187A-6D88-89F0-BDA50CBF712F}", 
					       "configuration" => array("StationID" => $StationArray[$i]["StationsID"], "Timer_1" => 10));
			$arrayValues[] = array("Brand" => $StationArray[$i]["Brand"], "Name" => $StationArray[$i]["Name"], "Street" => $StationArray[$i]["Street"],
					       "Place" => $StationArray[$i]["Place"], "instanceID" => $StationArray[$i]["InstanceID"], 
					       "create" => $arrayCreate);
			*/
			$arrayValues[] = array("IP" => $IP, "Name" => $Values["Name"], "MAC" => $Values["MAC"],
					       "Duration" => $Values["Duration"], "State" => $Values["Ping"], "instanceID" => $Values["InstanceID"]);
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
		
		
	}
	    
	// Beginn der Funktionen
	private function GetData()
	{
		$DeviceAdressStart = $this->ReadPropertyInteger("DeviceAdressStart");
		$DeviceAdressEnd = $this->ReadPropertyInteger("DeviceAdressEnd");
		$Devices = array();
		
		$MAC = array();
		$MAC = unserialize($this->MAC());
		
		for ($i = $DeviceAdressStart; $i <= $DeviceAdressEnd; $i++) {
    			$Response = unserialize($this->Ping("192.168.178.".$i));

    			If ($Response["192.168.178.".$i]["Ping"] == true) {
        			$Devices["192.168.178.".$i]["Ping"] = true;
        			$Devices["192.168.178.".$i]["Duration"] = round($Response["192.168.178.".$i]["Duration"] * 1000, 3);
				if (array_key_exists("192.168.178.".$i, $MAC)) {
					$Devices["192.168.178.".$i]["Name"] = $MAC["192.168.178.".$i]["Name"];
					$Devices["192.168.178.".$i]["MAC"] = $MAC["192.168.178.".$i]["MAC"];
				}
				else {
					$Devices["192.168.178.".$i]["Name"] = "nicht verfügbar";
					$Devices["192.168.178.".$i]["MAC"] = "";
				}
				$Devices["192.168.178.".$i]["InstanceID"] = 0;
    			}
		}
	return serialize($Devices);
	}
	
	private function Ping($IP)
	{
    		$Start = microtime(true);
    		$Response = Sys_Ping($IP, 100); 
    		$Duration = microtime(true) - $Start;
    		$Result[$IP]["Ping"] = $Response;
    		$Result[$IP]["Duration"] = $Duration;
	return serialize($Result);
	}
	    
	private function MAC()
	{
		$arp = shell_exec('arp -a');
		$Lines = explode("\n", $arp);

		$devices = array();

		$Search = array("auf", "[ether]", "eth0", "(", ")");
		$Replace = array("", "", "", "", "");

		$Devices = array();

		foreach ($Lines as $Line) {
    			$Line = str_replace($Search, $Replace, $Line);
    			$Cols = preg_split('/\s+/', trim($Line));
    			If (Count($Cols) == 3) {
        			$Devices[$Cols[1]]["Name"] = $Cols[0];
        			$Devices[$Cols[1]]["MAC"] = $Cols[2];
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
    			$IP_Parts = explode(".", $Network["IP"]);
    			If (count($IP_Parts) == 4) {
        			$IP[] = $IP_Parts[0].".".$IP_Parts[1].".".$IP_Parts[2].".xxx";
    			}
		}
	return serialize($IP);
	}
}
?>
