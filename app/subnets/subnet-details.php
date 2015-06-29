<?php

/**
 * Main script to display master subnet details if subnet has slaves
 ***********************************************************************/

# set rowspan
$rowSpan = 10 + sizeof($custom_fields);
?>

<!-- subnet details upper table -->
<h4><?php print _('Subnet details'); ?></h4>
<hr>

<table class="ipaddress_subnet table-condensed table-full">
	<tr>
		<th><?php print _('Subnet details'); ?></th>
		<td><?php print "<b>$subnet[ip]/$subnet[mask]</b> ($subnet_detailed[netmask])"; ?></td>
	</tr>
	<tr>
		<th><?php print _('Hierarchy'); ?></th>
		<td>
			<?php print_breadcrumbs($Sections, $Subnets, $_GET); ?>
		</td>
	</tr>
	<tr>
		<th><?php print _('Subnet description'); ?></th>
		<td><?php print html_entity_decode($subnet['description']); ?></td>
	</tr>
	<tr>
		<th><?php print _('Permission'); ?></th>
		<td><?php print $Subnets->parse_permissions($subnet_permission); ?></td>
	</tr>
	<?php if(!$slaves) { ?>
	<tr>
		<th><?php print _('Subnet Usage'); ?></th>
		<td>
			<?php
				  print ''._('Used').':  '. $Subnets->reformat_number ($subnet_usage['used']) .' |
						 '._('Free').':  '. $Subnets->reformat_number ($subnet_usage['freehosts']) .' ('. $subnet_usage['freehosts_percent']  .'%) |
						 '._('Total').': '. $Subnets->reformat_number ($subnet_usage['maxhosts']);
			?>
		</td>
	</tr>

	<!-- gateway -->
	<?php
	$gateway = $Subnets->find_gateway($subnet['id']);
	if($gateway !==false) { ?>
	<tr>
		<th><?php print _('Gateway'); ?></th>
		<td><strong><?php print $Subnets->transform_to_dotted($gateway->ip_addr);?></strong></td>
	</tr>
	<?php } ?>

	<?php } ?>
	<tr>
		<th><?php print _('VLAN'); ?></th>
		<td>
		<?php
		if(empty($vlan['number']) || $vlan['number'] == 0) { $vlan['number'] = "<span class='text-muted'>/</span>"; }	//Display fix for emprt VLAN
		print $vlan['number'];

		if(!empty($vlan['name'])) 		 { print ' - '.$vlan['name']; }					//Print name if provided
		if(!empty($vlan['description'])) { print ' ['. $vlan['description'] .']'; }		//Print description if provided
		?>
		</td>
	</tr>

	<?php
	# VRF
	if(!empty($subnet['vrfId']) && $User->settings->enableVRF==1) {
		# get vrf details
		$vrf = (array) $Tools->fetch_vrf(null,$subnet['vrfId']);
		# set text
		$vrfText = $vrf['name'];
		if(!empty($vrf['description'])) { $vrfText .= " [$vrf[description]]";}

		print "<tr>";
		print "	<th>"._('VRF')."</th>";
		print "	<td>$vrfText</td>";
		print "</tr>";
	}

	if(!$slaves) {
		# divider
		print "<tr>";
		print "	<th><hr></th>";
		print "	<td></td>";
		print "</tr>";

		# Are IP requests allowed?
		if ($User->settings->enableIPrequests==1) {
			print "<tr>";
			print "	<th>"._('IP requests')."</th>";
			if($subnet['allowRequests'] == 1) 		{ print "	<td>"._('enabled')."</td>"; }		# yes
			else 									{ print "	<td class='info2'>"._('disabled')."</td>";}		# no
			print "</tr>";
		}
		# ping-check hosts inside subnet
		print "<tr>";
		print "	<th>"._('Hosts check')."</th>";
		if($subnet['pingSubnet'] == 1) 				{ print "	<td>"._('enabled')."</td>"; }		# yes
		else 										{ print "	<td class='info2'>"._('disabled')."</td>";}		# no
		print "</tr>";
		# scan subnet for new hosts *
		print "<tr>";
		print "	<th>"._('Discover new hosts')."</th>";
		if($subnet['discoverSubnet'] == 1) 			{ print "	<td>"._('enabled')."</td>"; }		# yes
		else 										{ print "	<td class='info2'>"._('disabled')."</td>";}		# no
		print "</tr>";
	}
	?>

	<?php
	# custom subnet fields
	if(sizeof($custom_fields) > 0) {
		foreach($custom_fields as $key=>$field) {
			if(strlen($subnet[$key])>0) {
				$subnet[$key] = str_replace(array("\n", "\r\n"), "<br>",$subnet[$key]);
				$html_custom[] = "<tr>";
				$html_custom[] = "	<th>$key</th>";
				$html_custom[] = "	<td>";
				#booleans
				if($field['type']=="tinyint(1)")	{
					if($subnet[$key] == "0")		{ $html_custom[] = _("No"); }
					elseif($subnet[$key] == "1")	{ $html_custom[] = _("Yes"); }
				}
				else {
					$html_custom[] = create_links($subnet[$key]);
				}
				$html_custom[] = "	</td>";
				$html_custom[] = "</tr>";
			}
		}

		# any?
		if(isset($html_custom)) {
			# divider
			print "<tr>";
			print "	<th><hr></th>";
			print "	<td></td>";
			print "</tr>";

			print implode("\n", $html_custom);
		}
	}

	# check for temporary shares!
	if($User->settings->tempShare==1) {
		foreach(json_decode($User->settings->tempAccess) as $s) {
			if($s->type=="subnets" && $s->id==$subnet['id']) {
				if(time()<$s->validity) {
					$active_shares[] = $s;
				}
				else {
					$expired_shares[] = $s;
				}
			}
		}
		if(sizeof(@$active_shares)>0) {
			# divider
			print "<tr>";
			print "	<th><hr></th>";
			print "	<td></td>";
			print "</tr>";
			# print
			print "<tr>";
			print "<th>"._("Active subnet shares").":</th>";
			print "<td>";
			$m=1;
			foreach($active_shares as $s) {
				print "<button class='btn btn-xs btn-default removeSharedTemp' data-code='$s->code' ><i class='fa fa-times'></i></button> <a href='".create_link("temp_share",$s->code)."'>Share $m</a> ("._("Expires")." ".date("Y-m-d H:i:s", $s->validity).")<br>";
				$m++;
			}
			print "<td>";
			print "</tr>";
		}
		if(sizeof(@$expired_shares)>0) {
			# divider
			print "<tr>";
			print "	<th><hr></th>";
			print "	<td></td>";
			print "</tr>";
			# print
			print "<tr>";
			print "<th>"._("Expired subnet shares").":</th>";
			print "<td>";
			$m=1;
			foreach($expired_shares as $s) {
				print "<button class='btn btn-xs btn-danger removeSharedTemp' data-code='$s->code' ><i class='fa fa-times'></i></button> <a href='".create_link("temp_share",$s->code)."'>Share $m</a> ("._("Expired")." ".date("Y-m-d H:i:s", $s->validity).")<br>";
				$m++;
			}
			print "<td>";
			print "</tr>";
		}
	}

	print "<tr>";
	print "<td colspan=2><hr></td>";
	print "</tr>";

	# action button groups
	print "<tr>";
	print "	<th>"._('Actions')."</th>";
	print "	<td class='actions'>";

	print "	<div class='btn-toolbar'>";

	# set values for permissions
	if($subnet_permission == 1) {
		$sp['editsubnet']= false;		//edit subnet
		$sp['editperm']  = false;		//edit permissions

		$sp['addip'] 	 = false;		//add ip address
		$sp['scan']		 = false;		//scan subnet
		$sp['changelog'] = false;		//changelog view
		$sp['import'] 	 = false;		//import
	}
	else if ($subnet_permission == 2) {
		$sp['editsubnet']= false;		//edit subnet
		$sp['editperm']  = false;		//edit permissions

		$sp['addip'] 	 = true;		//add ip address
		$sp['scan']		 = true;		//scan subnet
		$sp['changelog'] = true;		//changelog view
		$sp['import'] 	 = true;		//import
	}
	else if ($subnet_permission == 3) {
		$sp['editsubnet']= true;		//edit subnet
		$sp['editperm']  = true;		//edit permissions

		$sp['addip'] 	 = true;		//add ip address
		$sp['scan']		 = true;		//scan subnet
		$sp['changelog'] = true;		//changelog view
		$sp['import'] 	 = true;		//import
	}

	# edit / permissions / nested
	print "<div class='btn-group'>";
		# warning
		if($subnet_permission == 1)
		print "<button class='btn btn-xs btn-default btn-danger' 	data-container='body' rel='tooltip' title='"._('You do not have permissions to edit subnet or IP addresses')."'>													<i class='fa fa-lock'></i></button> ";
		# edit subnet
		if($sp['editsubnet'])
		print "<a class='edit_subnet btn btn-xs btn-default' 	href='' data-container='body' rel='tooltip' title='"._('Edit subnet properties')."'	data-subnetId='$subnet[id]' data-sectionId='$subnet[sectionId]' data-action='edit'>	<i class='fa fa-pencil'></i></a>";
		else
		print "<a class='btn btn-xs btn-default disabled' 		href='' data-container='body' rel='tooltip' title='"._('Edit subnet properties')."'>																					<i class='fa fa-pencil'></i></a>";
		# permissions
		if($sp['editperm'])
		print "<a class='showSubnetPerm btn btn-xs btn-default' href='' data-container='body' rel='tooltip' title='"._('Manage subnet permissions')."'	data-subnetId='$subnet[id]' data-sectionId='$subnet[sectionId]' data-action='show'>	<i class='fa fa-tasks'></i></a>";
		else
		print "<a class='btn btn-xs btn-default disabled' 		href='' data-container='body' rel='tooltip' title='"._('Manage subnet permissions')."'>																						<i class='fa fa-tasks'></i></a>";
		# add nested subnet
		if($section_permission == 3) {
		print "<a class='edit_subnet btn btn-xs btn-default '	href='' data-container='body' rel='tooltip' title='"._('Add new nested subnet')."' 		data-subnetId='$subnet[id]' data-action='add' data-id='' data-sectionId='$subnet[sectionId]'> <i class='fa fa-plus-circle'></i></a> ";
		} else {
		print "<a class='btn btn-xs btn-default disabled' 		href=''> 																																											  <i class='fa fa-plus-circle'></i></a> ";
		}
	print "</div>";

	# favourites / changelog
	print "<div class='btn-group'>";
		# favourite
		if($User->is_subnet_favourite($subnet['id']))
		print "<a class='btn btn-xs btn-default btn-info editFavourite favourite-$subnet[id]' href='' data-container='body' rel='tooltip' title='"._('Click to remove from favourites')."' data-subnetId='$subnet[id]' data-action='remove'>			<i class='fa fa-star'></i></a> ";
		else
		print "<a class='btn btn-xs btn-default editFavourite favourite-$subnet[id]' 		 href='' data-container='body' rel='tooltip' title='"._('Click to add to favourites')."' data-subnetId='$subnet[id]' data-action='add'>						<i class='fa fa-star fa-star-o' ></i></a> ";
		# changelog
		if($User->settings->enableChangelog==1) {
		if($sp['changelog'])
		print "<a class='sChangelog btn btn-xs btn-default'     									 href='".create_link("subnets",$subnet['sectionId'],$subnet['id'],"changelog")."' data-container='body' rel='tooltip' title='"._('Changelog')."'>	<i class='fa fa-clock-o'></i></a>";
		else
		print "<a class='btn btn-xs btn-default disabled'     									 	 href='' 																data-container='body' rel='tooltip' title='"._('Changelog')."'>				<i class='fa fa-clock-o'></i></a>";
		}
	print "</div>";

	# add / requests / scan
	if(!$slaves) {
	print "<div class='btn-group'>";
		// if full prevent new
		if($Subnets->reformat_number($subnet_usage['freehosts'])=="0" || !$sp['addip'])
		print "<a class='btn btn-xs btn-default btn-success disabled' 	href='' data-container='body' rel='tooltip' title='"._('Add new IP address')."'>															<i class='fa fa-plus'></i></a> ";
		else
		print "<a class='modIPaddr btn btn-xs btn-default btn-success' 	href='' data-container='body' rel='tooltip' title='"._('Add new IP address')."' data-subnetId='$subnet[id]' data-action='add' data-id=''>	<i class='fa fa-plus'></i></a> ";
		//requests
		if($subnet['allowRequests'] == 1  && $subnet_permission<3)  {
		print "<a class='request_ipaddress btn btn-xs btn-default btn-success' 	href='' data-container='body' rel='tooltip' title='"._('Request new IP address')."' data-subnetId='$subnet[id]'>					<i class='fa fa-plus-circle'>  </i></a>";
		}
		// subnet scan
		if($sp['scan'])
		print "<a class='scan_subnet btn btn-xs btn-default'			href='' data-container='body' rel='tooltip' title='"._('Scan subnet for new hosts')."' 	data-subnetId='$subnet[id]'> 						<i class='fa fa-cogs'></i></a> ";
		else
		print "<a class='btn btn-xs btn-default disabled'				href='' data-container='body' rel='tooltip' title='"._('Scan subnet for new hosts')."'> 													<i class='fa fa-cogs'></i></a> ";
	print "</div>";

	# export / import
	print "<div class='btn-group'>";
		//import
		if($sp['import'])
		print "<a class='csvImport btn btn-xs btn-default'  href='' data-container='body' rel='tooltip' title='"._('Import IP addresses')."' data-subnetId='$subnet[id]'>		<i class='fa fa-upload'></i></a>";
		else
		print "<a class='btn btn-xs btn-default disabled'  	href='' data-container='body' rel='tooltip' title='"._('Import IP addresses')."'>											<i class='fa fa-upload'></i></a>";
		//export
		print "<a class='csvExport btn btn-xs btn-default'  href='' data-container='body' rel='tooltip' title='"._('Export IP addresses')."' data-subnetId='$subnet[id]'>		<i class='fa fa-download'></i></a>";
		//share
		if($subnet_permission>1 && $User->settings->tempShare==1) {
		print "<a class='shareTemp btn btn-xs btn-default'  href='' data-container='body' rel='tooltip' title='"._('Temporary share subnet')."' data-id='$subnet[id]' data-type='subnets'>		<i class='fa fa-share-alt'></i></a>";
		}
	print "</div>";
	}

	print "	</div>";
	print "	</td>";
	print "</tr>";
	?>

</table>