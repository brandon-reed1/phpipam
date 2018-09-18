<script type="text/javascript">
/* fix for ajax-loading tooltips */
$('body').tooltip({ selector: '[rel=tooltip]' });
</script>

<?php

/**
 * Script to display devices
 *
 */

# rack object
if($User->settings->enableRACK=="1") {
	$Racks      = new phpipam_rack ($Database);
}

# verify that user is logged in
$User->check_user_session();
# perm check
$User->check_module_permissions ("devices", 1, true, false);

# filter devices or fetch print all?
$devices = $Tools->fetch_all_objects("devices", "hostname");
$device_types = $Tools->fetch_all_objects ("deviceTypes", "tid");

// reindex types
if (isset($device_types)) {
	foreach($device_types as $dt) {
		$device_types_indexed[$dt->tid] = $dt;
	}
}

# strip tags - XSS
$_GET = $User->strip_input_tags ($_GET);

# get custom fields
$custom_fields = $Tools->fetch_custom_fields('devices');
# get hidden fields */
$hidden_fields = json_decode($User->settings->hiddenCustomFields, true);
$hidden_fields = is_array(@$hidden_fields['devices']) ? $hidden_fields['devices'] : array();

# title
print "<h4>"._('List of network devices')."</h4>";
print "<hr>";

# print link to manage
print "<div class='btn-group'>";
	//back button
	if(isset($_GET['sPage'])) { print "<a class='btn btn-sm btn-default' href='javascript:history.back()' style='margin-bottom:10px;'><i class='fa fa-chevron-left'></i> ". _('Back')."</a>"; }
	//administer
	elseif($User->get_module_permissions ("devices")>1) {
		print "<button class='btn btn-sm btn-default editSwitch' data-action='add'   data-switchid='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> "._('Add device')."</button>"; }
print "</div>";

# table
print '<table id="switchManagement" class="table sorted sortable table-striped table-top" data-cookie-id-table="devices_all">';

#headers
print "<thead>";
print '<tr>';
print "	<th>"._('Name')."</th>";
print "	<th>"._('IP address')."</th>";
print "	<th>"._('Description').'</th>';
if($User->settings->enableRACK=="1" && $User->get_module_permissions ("racks")>0) {
print '	<th>'._('Rack').'</th>';
$colspanCustom++;
}
if($User->settings->enableSNMP=="1" && $User->is_admin(false)) {
print "	<th>"._('SNMP info').'</th>';
$colspanCustom++;
}
print "	<th style='color:#428bca'>"._('Number of hosts').'</th>';
print "	<th class='hidden-sm'>". _('Type').'</th>';

if(sizeof(@$custom_fields) > 0) {
	foreach($custom_fields as $field) {
		if(!in_array($field['name'], $hidden_fields)) {
			print "<th class='hidden-sm hidden-xs hidden-md'>".$Tools->print_custom_field_name ($field['name'])."</th>";
			$colspanCustom++;
		}
	}
}
if($User->get_module_permissions ("devices")>1)
print '	<th class="actions"></th>';
print '</tr>';
print "</thead>";

print "<tbody>";
// no devices
if($devices===false) {
	$colspan = 8 + $colspanCustom;
	print "<tr>";
	print "	<td colspan='$colspan'>".$Result->show('info', _('No results')."!", false, false, true)."</td>";
	print "</tr>";
}
// result
else {
	$cnt_ips     = $Tools->count_all_database_objects("ipaddresses","switch");
	$cnt_subnets = $Tools->count_all_database_objects("subnets","device");

	foreach ($devices as $device) {
	//cast
	$device = (array) $device;

	//count items
	$cnt1 = isset($cnt_ips[$device['id']])     ?  $cnt_ips[$device['id']]     : 0;
	$cnt2 = isset($cnt_subnets[$device['id']]) ?  $cnt_subnets[$device['id']] : 0;
	$cnt = $cnt1 + $cnt2;

	// print details
	print '<tr>'. "\n";

	print "	<td><a class='btn btn-xs btn-default' href='".create_link("tools","devices",$device['id'])."'><i class='fa fa-desktop prefix'></i> ". $device['hostname'] .'</a></td>'. "\n";
	print "	<td>". $device['ip_addr'] .'</td>'. "\n";
	print '	<td class="description">'. $device['description'] .'</td>'. "\n";
	// rack
    if($User->settings->enableRACK=="1" && $User->get_module_permissions ("racks")>0) {
        print "<td>";
        # rack
        $rack = $Racks->fetch_rack_details ($device['rack']);
        if ($rack!==false) {
            print "<a href='".create_link("tools", "racks", $rack->id)."'>".$rack->name."</a><br>";
            print "<span class='badge badge1 badge5'>"._('Position').": $device[rack_start], "._("Size").": $device[rack_size] U</span>";
        }
        print "</td>";
    }
    // snmp
	if($User->settings->enableSNMP=="1" && $User->is_admin(false)) {
		print "<td>";
		if($User->is_admin(false)) {
		print ($device['snmp_version']==0 || strlen($device['snmp_version'])==0) ?  "<span class='text-muted'>"._("Disabled")."</span>" : _("Version").": $device[snmp_version]<br>"._("Community").": $device[snmp_community]<br>";
		}
		else {
		print ($device['snmp_version']==0 || strlen($device['snmp_version'])==0) ?  "<span class='text-muted'>"._("Disabled")."</span>" : _("Version").": $device[snmp_version]<br>"._("Community").": ********<br>";
		}
		print "</td>";
	}
	print '	<td><span class="badge badge1 badge5">'. $cnt .'</span> '._('Objects').'</td>'. "\n";
	print '	<td class="hidden-sm">'. $device_types_indexed[$device['type']]->tname .'</td>'. "\n";

	//custom
	if(sizeof(@$custom_fields) > 0) {
		foreach($custom_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				// create html links
				$device[$field['name']] = $User->create_links($device[$field['name']], $field['type']);

				print "<td class='hidden-sm hidden-xs hidden-md'>".$device[$field['name']]."</td>";
			}
		}
	}

	# actions
	if($User->get_module_permissions ("devices")>1) {
		print '	<td class="actions">'. "\n";
		print "	<div class='btn-group'>";
		print "		<button class='btn btn-xs btn-default editSwitch' data-action='edit'   data-switchid='$device[id]' rel='tooltip' title='"._('Edit')."'><i class='fa fa-pencil'></i></button>";
		if($User->settings->enableSNMP=="1" && $User->is_admin(false))
		print "		<button class='btn btn-xs btn-default editSwitchSNMP' data-action='edit' data-switchid='$device[id]' rel='tooltip' title='Manage SNMP'><i class='fa fa-cogs'></i></button>";
		if($User->get_module_permissions ("devices")>2)
		print "		<button class='btn btn-xs btn-default editSwitch' data-action='delete' data-switchid='$device[id]' rel='tooltip' title='"._('Delete')."'><i class='fa fa-times'></i></button>";
		print "	</div>";
		print '	</td>'. "\n";
	}

	print '</tr>'. "\n";

	}

	# print for unspecified
	print '<tr class="unspecified">'. "\n";

    // count empty
	$cnt1 = (isset($cnt_ips[""]) ? $cnt_ips[""] : 0)         + (isset($cnt_ips[0]) ? $cnt_ips[0] : 0);
	$cnt2 = (isset($cnt_subnets[""]) ? $cnt_subnets[""] : 0) + (isset($cnt_subnets[0]) ? $cnt_subnets[0] : 0);
	$cnt = $cnt1 + $cnt2;


	print '	<td>'._('Device not specified').'</td>'. "\n";
	print '	<td></td>'. "\n";
	print '	<td></td>'. "\n";
	if($User->settings->enableRACK=="1" && $User->get_module_permissions ("racks")>0) {
	print '	<td></td>'. "\n";
	}
	if($User->settings->enableSNMP=="1" && $User->is_admin(false)) {
	print '	<td></td>'. "\n";
	}
	print '	<td><span class="badge badge1 badge5">'. $cnt .'</span> '._('Objects').'</td>'. "\n";
	print '	<td class="hidden-sm"></td>'. "\n";

	//custom
	if(sizeof(@$custom_fields) > 0) {
		foreach($custom_fields as $field) {
			if(!in_array($field['name'], $hidden_fields)) {
				print "<td class='hidden-sm hidden-xs hidden-md'></td>";
			}
		}
	}
	if($User->get_module_permissions ("devices")>1)
	print '	<td class="actions"></td>';
	print '</tr>'. "\n";
}
print "</tbody>";
print '</table>';