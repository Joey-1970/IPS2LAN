<?
    // Klassendefinition
    class IPS2LANSummary extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		
		$this->RegisterPropertyBoolean("Open", false);
		
		// Profil anlegen
		$this->RegisterProfileInteger("IPS2LAN.State", "Information", "", "", 0, 3, 1);
		IPS_SetVariableProfileAssociation("IPS2LAN.State", 0, "Unbekannt", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2LAN.State", 1, "Offline", "Close", 0xFF0000);
		IPS_SetVariableProfileAssociation("IPS2LAN.State", 2, "Störung", "Alert", 0xFFFF00);
		IPS_SetVariableProfileAssociation("IPS2LAN.State", 3, "Online", "Network", 0x00FF00);
		
		
		// Status-Variablen anlegen		
		
		
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv");
		$arrayElements[] = array("type" => "Label", "label" => "Unvollendet!");

		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		$this->GetChildrensID();
		
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
 		switch ($Message) {
			case 10103:
				$this->ApplyChanges();
				break;
			
		}
    	}
	
	public function ForwardData($JSONString) 
	 {
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	$Result = -999;
	 	
		switch ($data->Function) {
			case "SetInstance":
				$this->SendDebug("ForwardData", "SetInstance", 0);
				$InstanceID = $data->InstanceID;
				$Name = $data->Name;
				$State = $data->State;
				$this->RegisterVariableInteger($InstanceID, $Name, "IPS2LAN.State", 20);
				SetValueInteger($this->GetIDForIdent($InstanceID), $State);
				break;
			case "SetState":
				$this->SendDebug("ForwardData", "SetState", 0);
				break;
			
		}
	return $Result;
	}

	    
	// Beginn der Funktionen
	private function GetChildrensID()
	{
		$InstanceIDs = IPS_GetInstanceList();
		foreach($InstanceIDs as $IID) {
    			if(IPS_GetInstance($IID)['ConnectionID'] == $this->InstanceID) {
        			$this->SendDebug("GetChildrensID", "ChildrensID: ".$IID . PHP_EOL, 0);
				
			}
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
	    

}
?>
