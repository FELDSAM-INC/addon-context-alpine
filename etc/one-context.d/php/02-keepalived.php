<?php

$_SERVER['CHARSET'] = 'UTF-8';
$_SERVER['DISK_ID'] = '1';
$_SERVER['ETH0_CONTEXT_FORCE_IPV4'] = 'yes';
$_SERVER['ETH0_DNS'] = '8.8.8.8 8.8.4.4 2001:4860:4860::8888 2001:4860:4860::8844';
$_SERVER['ETH0_GATEWAY'] = '185.174.168.254';
$_SERVER['ETH0_GATEWAY6'] = '2a0b:a900::ffff';
$_SERVER['ETH0_IP'] = '185.174.168.82';
$_SERVER['ETH0_IP6'] = '2a0b:a900::400:b9ff:feae:a852';
$_SERVER['ETH0_IP6_ULA'] = '';
$_SERVER['ETH0_MAC'] = '02:00:b9:ae:a8:52';
$_SERVER['ETH0_MASK'] = '255.255.255.0';
$_SERVER['ETH0_MTU'] = '';
$_SERVER['ETH0_NETWORK'] = '185.174.168.0';
$_SERVER['ETH0_SEARCH_DOMAIN'] = '';
$_SERVER['ETH0_VLAN_ID'] = '101';
$_SERVER['ETH0_VROUTER_IP'] = '185.174.168.100';
$_SERVER['ETH0_VROUTER_IP6'] = '2a0b:a900::400:b9ff:feae:a864';
$_SERVER['ETH0_VROUTER_KEEPALIVED_INF'] = 'eth1';
$_SERVER['ETH0_VROUTER_MANAGEMENT'] = '';
$_SERVER['ETH1_CONTEXT_FORCE_IPV4'] = '';
$_SERVER['ETH1_DNS'] = '8.8.8.8';
$_SERVER['ETH1_GATEWAY'] = '192.168.4.109';
$_SERVER['ETH1_GATEWAY6'] = '';
$_SERVER['ETH1_IP'] = '192.168.4.16';
$_SERVER['ETH1_IP6'] = '';
$_SERVER['ETH1_IP6_ULA'] = '';
$_SERVER['ETH1_MAC'] = '02:00:c0:a8:04:10';
$_SERVER['ETH1_MASK'] = '255.255.255.0';
$_SERVER['ETH1_MTU'] = '';
$_SERVER['ETH1_NETWORK'] = '192.168.4.0';
$_SERVER['ETH1_SEARCH_DOMAIN'] = '';
$_SERVER['ETH1_VLAN_ID'] = '';
$_SERVER['ETH1_VROUTER_IP'] = '192.168.4.100';
$_SERVER['ETH1_VROUTER_IP6'] = '';
$_SERVER['ETH1_VROUTER_KEEPALIVED_INF'] = '';
$_SERVER['ETH1_VROUTER_MANAGEMENT'] = 'YES';
$_SERVER['HOME'] = '/root';
$_SERVER['LOGNAME'] = 'root';
$_SERVER['MAIL'] = '/var/mail/root';
$_SERVER['NETWORK'] = 'YES';
$_SERVER['OLDPWD'] = '/etc';
$_SERVER['PAGER'] = 'less';
$_SERVER['PATH'] = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
$_SERVER['PATH_TRANSLATED'] = 'test.php';
$_SERVER['PHP_SELF'] = 'test.php';
$_SERVER['PS1'] = '\h:\w\$';
$_SERVER['PWD'] = '/etc/one-context.d';
$_SERVER['REQUEST_TIME'] = '1485109883';
$_SERVER['REQUEST_TIME_FLOAT'] = '1485109883.7706';
$_SERVER['SCRIPT_FILENAME'] = 'test.php';
$_SERVER['SCRIPT_NAME'] = 'test.php';
$_SERVER['SHELL'] = '/bin/ash';
$_SERVER['SHLVL'] = '3';
$_SERVER['SSH_CLIENT'] = '185.47.222.69 51171 22';
$_SERVER['SSH_CONNECTION'] = '185.47.222.69 51171 185.174.168.82 22';
$_SERVER['SSH_PUBLIC_KEY'] = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQDl5AXN8O4c/5d5UNws4crB3iO6e0alTbZ2R0dVeAUuTNbVzyMZ9B2KTRrpmahTVC49TT/0zxBtffq6+lURcVgbcZ5KOxE2Qnnpl+FYUEUeom2xZtgtN7KQPjHI5LGG86ELtIhYFbglRvPOqVDmaKtI8/6jtJyiGZ+g1Ut0Mo6hxpQylAaj1Rn134BQIHLWap/qf9Ouxef/zaSuDRYqqIQJaDiE8ub9ZDUseB+M89N7hx4c078NUkJXCQRxe42rfbZWaPMAnR1tLORECrVPZJeNLvaXArmPRz2bj2JhtZ4NxmCPH9Sc86HiazPFupqVR/byhOrw+LN3H7kYKD4mbDwn tio@Kristians-MacBook-Pro.local';
$_SERVER['SSH_TTY'] = '/dev/pts/0';
$_SERVER['TARGET'] = 'hda';
$_SERVER['TERM'] = 'xterm-256color';
$_SERVER['USER'] = 'root';
$_SERVER['VROUTER_ID'] = '25';
$_SERVER['VROUTER_KEEPALIVED_ID'] = '21';
$_SERVER['VROUTER_KEEPALIVED_PASSWORD'] = 'randompass';
$_SERVER['argc'] = '1';

function get_interface_mac_addresses()
{
    //exec('ip link show | awk \'/^[0-9]+: [A-Za-z0-9]+:/ { device=$2; gsub(/:/, "",device)} /link\/ether/ { print device " " $2 }\'', $result);

	$result = array(
		'eth0 02:00:b9:ae:a8:52',
		'eth1 02:00:c0:a8:04:10',	
	);

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
		
  			// Virtaul addresses
  			$conf .= '  virtual_ipaddress {'."\n";
  			
  			// IPv4
  			if($if['VROUTER_IP'])
  			{
	  			$conf .= '    '.$if['VROUTER_IP'].'/'.get_mask_bits($if).' dev '.$if['DEV']."\n";
  			}
  			
  			// IPv6
  			if($if['VROUTER_IP6'])
  			{
	  			$conf .= '    '.$if['VROUTER_IP6'].'/64 dev '.$if['DEV']."\n";
  			}
			
			$conf .= '  }
}
';
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
if(isset($_SERVER['ETH0_VROUTER_IP']))
{
	$conf = generate_groups();
	$conf .= generate_instances();
	
	exec('echo "'.$conf.'" > /etc/keepalived/keepalived.conf');
	
	if($action == 'reload') service_reload();
}