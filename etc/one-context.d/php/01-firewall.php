<?php

function get_interface_mac_addresses()
{
    exec('ip link show | awk \'/^[0-9]+: [A-Za-z0-9]+:/ { device=$2; gsub(/:/, "",device)} /link\/ether/ { print device " " $2 }\'', $result);

    $interfaces = array();
    foreach($result as $line)
    {
        $parts                 = explode(' ', $line);
        $interfaces[$parts[1]] = $parts[0];
    }

    return $interfaces;
}

function get_context_interfaces()
{
    $mac_addresses = get_interface_mac_addresses();
    $interfaces    = array();

    foreach($_SERVER as $key => $val)
    {
        if(preg_match('/^(ETH[0-9])_(.+)$/', $key, $matches))
        {
            if($matches[2] == 'MAC')
            {
                $interfaces[$matches[1]]['DEV'] = $mac_addresses[$val];
            }

            $interfaces[$matches[1]][$matches[2]] = $val;
        }
    }

    return $interfaces;
}

function get_filter_rules()
{
	$interfaces = get_context_interfaces();
	
	$rules = '*filter
:INPUT ACCEPT [0:0]
:FORWARD DROP [0:0]
:OUTPUT ACCEPT [0:0]
';
	
	foreach($interfaces as $interface)
	{
		foreach($interfaces as $destination)
		{
			if($interface['DEV'] == $destination['DEV']) continue;
			
			$rules .= '-A FORWARD -i '.$interface['DEV'].' -o '.$destination['DEV'].' -j ACCEPT'."\n";
		}
		
		// close non management interfaces
		if($interface['VROUTER_MANAGEMENT'] != 'YES')
		{
			$rules .= '-A INPUT -i '.$interface['DEV'].' -p tcp --dport 443 -j DROP'."\n";
			$rules .= '-A OUTPUT -o '.$interface['DEV'].' -p tcp --sport 443 -j DROP'."\n";
			$rules .= '-A INPUT -i '.$interface['DEV'].' -p tcp --dport 22 -j DROP'."\n";
			$rules .= '-A OUTPUT -o '.$interface['DEV'].' -p tcp --sport 22 -j DROP'."\n";
		}
	}

	$rules .= 'COMMIT'."\n";
	
	return $rules;
}

function get_nat_rules()
{
	$interfaces = get_context_interfaces();
	
	$rules = '*nat
:PREROUTING ACCEPT [0:0]
:INPUT ACCEPT [0:0]
:OUTPUT ACCEPT [0:0]
:POSTROUTING ACCEPT [0:0]
';

	foreach($interfaces as $interface)
	{
		if(isset($interface['VROUTER_GATEWAY']) && $interface['VROUTER_GATEWAY'] == 'YES')
		{
			$rules .= '-A POSTROUTING -o '.$interface['DEV'].' -j MASQUERADE'."\n";
		}	
	}

	$rules .= 'COMMIT'."\n";

	return $rules;
}

function service_reload()
{
	exec('service iptables reload');
	exec('service ip6tables reload');
}

$action = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 'none';

// generate only if is VROUTER instance
if(isset($_SERVER['VROUTER_ID']))
{
	$rules = get_filter_rules();
	$rules .= get_nat_rules();
	
	exec('echo "'.$rules.'" > /etc/iptables/rules-save');
	exec('echo "'.$rules.'" > /etc/iptables/rules6-save');
	
	if($action == 'reload') service_reload();
}