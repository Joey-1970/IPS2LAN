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
		$this->RegisterPropertyInteger("Timer_1", 10);
		$this->RegisterTimer("Timer_1", 0, 'IPS2LANDevice_GetDataUpdate($_IPS["TARGET"]);');
		
		
		// Profil anlegen
		
		
		// Status-Variablen anlegen		
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		$this->RegisterVariableBoolean("State", "Status", "~Switch", 20);
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
		$this->Simple_Ping();
	}
	
	private function Simple_Ping()
	{
    		$this->SendDebug("Simple_Ping", "Ausfuehrung", 0);
		$IP = $this->ReadPropertyString("IP");
		$Result = array();
		$Start = microtime(true);
    		$Response = Sys_Ping($IP, 100); 
    		$Duration = microtime(true) - $Start;
    		$Result[$IP]["Ping"] = $Response;
    		$Result[$IP]["Duration"] = $Duration;
		If ($State <> GetValueBoolean($this->GetIDForIdent("State"))) {
			SetValueBoolean($this->GetIDForIdent("State"), $Response);
		}
	return serialize($Result);
	}    
	    
	private function Multiple_Ping($IP)
	{
    		$IP = $this->ReadPropertyString("IP");
		$Result = array();
		$Ping = array();
		$Duration = array();
		for ($i = 0; $i <= 5; $i++) {
			$Start = microtime(true);
			$Response = Sys_Ping($IP, 100); 
			$Duration[] = microtime(true) - $Start;
			$Ping[] = $Response;
			
		}
		
		$Result[$IP]["Ping"] = $Response;
		$Result[$IP]["Duration"] = $Duration;
	return serialize($Result);
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
