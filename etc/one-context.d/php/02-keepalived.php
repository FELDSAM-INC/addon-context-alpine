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

function get_mask_bits($if)
{
	$long = ip2long($if['MASK']);
	$base = ip2long('255.255.255.255');
	
	return 32-log(($long ^ $base)+1,2);
}

function generate_groups()
{
	$conf = 'vrrp_sync_group router {
  group {
';

	$interfaces = get_context_interfaces();
	
	foreach($interfaces as $if)
	{
		if($if['VROUTER_IP'] || $if['VROUTER_IP6'])
		{
			$conf .= '    VI_'.$if['DEV']."\n";
		}
	}

	$conf .= '  }
}
';

	return $conf;
}

function generate_instances()
{
	$conf = '';
	
	$interfaces = get_context_interfaces();
	
	$vrouter_id = (isset($_SERVER['VROUTER_KEEPALIVED_ID']) && $_SERVER['VROUTER_KEEPALIVED_ID']) ? $_SERVER['VROUTER_KEEPALIVED_ID'] : $_SERVER['VROUTER_ID'];
	
	foreach($interfaces as $if)
	{
		if($if['VROUTER_IP'] || $if['VROUTER_IP6'])
		{
			$conf .= 'vrrp_instance VI_'.$if['DEV'].' {
  state master
  priority 100
  advert_int 1
  nopreempt'."\n";

  			// Sync Interface
  			if(isset($if['VROUTER_KEEPALIVED_INF']) && $if['VROUTER_KEEPALIVED_INF'])
  			{	
  				$conf .= '  interface '.$if['VROUTER_KEEPALIVED_INF']."\n";
  			}
  			else
  			{
	  			$conf .= '  interface '.$if['DEV']."\n";
  			}
  			
  			// Router ID
  			if($vrouter_id)
  			{
  				$conf .= '  virtual_router_id '.$vrouter_id."\n";
  				$vrouter_id++;
  			}
		
  			// Auth
  			if(isset($_SERVER['VROUTER_KEEPALIVED_PASSWORD']) && $_SERVER['VROUTER_KEEPALIVED_PASSWORD'])
  			{
	  			$conf .= '  authentication {
    auth_type PASS
    auth_pass '.$_SERVER['VROUTER_KEEPALIVED_PASSWORD'].'
  }'."\n";
  			}
  			
  			// IPv4
  			if($if['VROUTER_IP'])
  			{
	  			$conf .= '  virtual_ipaddress {'."\n";
	  			$conf .= '    '.$if['VROUTER_IP'].'/'.get_mask_bits($if).' dev '.$if['DEV']."\n";
	  			$conf .= '  }'."\n";
  			}
  			
  			// IPv6
  			// Only works with conjunction with IPv4, VRRP Sync is over IPv4
  			if($if['VROUTER_IP6'])
  			{
	  			$conf .= '  virtual_ipaddress_excluded {'."\n";
	  			$conf .= '    '.$if['VROUTER_IP6'].'/64 dev '.$if['DEV']."\n";
	  			$conf .= '  }'."\n";
  			}
			
			$conf .= '}'."\n";
		}
	}
	
	return $conf;
}

function service_reload()
{
	exec('service keepalived reload');
}

$action = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : 'none';

// generate only if is VROUTER instance
if(isset($_SERVER['VROUTER_ID']))
{
	$conf = generate_groups();
	$conf .= generate_instances();
	
	exec('echo "'.$conf.'" > /etc/keepalived/keepalived.conf');
	
	if($action == 'reload') service_reload();
}