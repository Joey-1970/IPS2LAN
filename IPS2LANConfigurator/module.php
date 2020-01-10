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
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$ArrayRowLayout = array();
		$ArrayRowLayout[] = array("type" => "Label", "label" => "Geräte-Adressbereich");
		$ArrayRowLayout[] = array("type" => "NumberSpinner", "name" => "DeviceAdressStart", "caption" => "Start", "digits" => 0);
		$ArrayRowLayout[] = array("type" => "NumberSpinner", "name" => "DeviceAdressEnd", "caption" => "Ende", "digits" => 0);
		$arrayElements[] = array("type" => "RowLayout", "items" => $ArrayRowLayout);
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arraySort = array();
		$arraySort = array("column" => "Brand", "direction" => "ascending");
		
		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "IP", "name" => "IP", "width" => "100px", "visible" => true);
		$arrayColumns[] = array("caption" => "Name", "name" => "Name", "width" => "250px", "visible" => true);
		$arrayColumns[] = array("caption" => "MAC", "name" => "MAC", "width" => "200px", "visible" => true);
		$arrayColumns[] = array("caption" => "Status", "name" => "State", "width" => "auto", "visible" => true);
		
		$StationArray = array();
		If ($this->HasActiveParent() == true) {
			$StationArray = unserialize($this->GetData());
		}
		$arrayValues = array();
		for ($i = 0; $i < Count($StationArray); $i++) {
			$arrayCreate = array();
			$arrayCreate[] = array("moduleID" => "{47286CAD-187A-6D88-89F0-BDA50CBF712F}", 
					       "configuration" => array("StationID" => $StationArray[$i]["StationsID"], "Timer_1" => 10));
			$arrayValues[] = array("Brand" => $StationArray[$i]["Brand"], "Name" => $StationArray[$i]["Name"], "Street" => $StationArray[$i]["Street"],
					       "Place" => $StationArray[$i]["Place"], "instanceID" => $StationArray[$i]["InstanceID"], 
					       "create" => $arrayCreate);
		}
		
		$arrayElements[] = array("type" => "Configurator", "name" => "Network", "caption" => "Netzwerk", "rowCount" => 20, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Button", "caption" => "Tankerkönig-API", "onClick" => "echo 'https://creativecommons.tankerkoenig.de/';");
		
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
		
	return;
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
}
?>
