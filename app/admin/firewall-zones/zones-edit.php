<?php
// firewall zone fwzones-edit.php
// add, edit and delete firewall zones

// functions 
require( dirname(__FILE__) . '/../../../functions/functions.php');

// initialize classes
$Database = new Database_PDO;
$User = new User ($Database);
$Subnets = new Subnets ($Database);
$Sections = new Sections ($Database);
$Result = new Result ();
$Zones = new FirewallZones($Database);

// validate session parameters
$User->check_user_session();

// DEBUG
print 'DEBUG<br><pre>';
print_r($_POST);
print '<br>';
//var_dump($sectionIds);
print '</pre>';
// !DEBUG

// validate $_POST['id'] values
if (!preg_match('/^[0-9]+$/i', $_POST['id'])) {
	$Result->show("danger", _("Invalid ID. Do not manipulate the POST values!"), true);
}
// validate $_POST['action'] values
if ($_POST['action'] != 'add' && $_POST['action'] != 'edit' && $_POST['action'] != 'delete') {
	$Result->show("danger", _("Invalid action. Do not manipulate the POST values!"), true);
}
// validate $_POST['sectionId'] values
if (isset($_POST['sectionId'])) {
	if (!preg_match('/^[0-9]+$/i', $_POST['sectionId'])) {
		$Result->show("danger", _("Invalid section ID. Do not manipulate the POST values!"), true);
	}	
}

// fetch module settings
$firewallZoneSettings = json_decode($User->settings->firewallZoneSettings,true);

if ($_POST['action'] != 'add') {
	$FirewallZones = $Zones->get_zone($_POST['id']);
}
// disable edit on delete
$readonly = $_POST['action']=="delete" ? "readonly" : "";

// $subnets = $Subnets->fetch_all_subnets_search();
$subnets = $Database->getObjectsQuery('SELECT id,subnet,mask,description FROM subnets ORDER BY subnet * 1 ASC;');
if(sizeof($subnets)>0) {
	foreach($subnets as $subnet) {
		// add decimal format
		$subnet->subnet = $Subnets->transform_to_dotted ($subnet->subnet);
		// save to subnets
		$subnets[$subnet->id] = (object) $subnet;
	}
}








?>


<?php


// header 
print '<div class="pHeader">'._('Add a firewall zone').'</div>';

// content
print '<div class="pContent">';
// form
print '<form id="zoneEdit">';
// table
print '<table class="table table-noborder table-condensed">';

print '<tr>';
print '<td style="width:150px;">'._('Zone name').'</td>';
// possible zoneGenerator values:
//		0 == autogenerated decimal name
//		1 == autogenerated hex name
//		2 == free text name
//
// check if we have to autogenerate a zone name or if we have to display a text box
if ($firewallZoneSettings[zoneGenerator] != 0 && $firewallZoneSettings[zoneGenerator] != 1) {
	print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('Zone name').'" value="'.$FirewallZones->zone.'" '.$readonly.'></td>';
} else {
	print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('The zone name will be automatically generated').'" value="'.$FirewallZones->zone.'" '.$readonly.' disabled></td>';
}
print '</tr><tr>';
print '<td rowspan="2">'._('Indicator').'</td>';
print '<td><input type="radio" name="indicator" value="0" '.(($FirewallZones->indicator == false) ? 'checked':'').'> '._('Own zone').'</td>';
print '</tr><tr>';
print '<td><input type="radio" name="indicator" value="1" '.(($FirewallZones->indicator == true) ? 'checked':'').'> '._('Customer zone').'</td>';

print '</tr><tr>';
print '<td>'._('Description').'</td>';
print '<td><input type="text" class="form-control input-sm" name="description" placeholder="'._('Zone description').'" value="'.$FirewallZones->description.'"></td>';

print '</tr><tr>';
print '<td>'._('Section').'</td>';
print '<td><select name="sectionId" class="firewallZoneSection form-control input-sm input-w-auto input-max-200">';
print $Subnets->print_simple_subnet_dropdown_menu();
print '</td>';
//<input type="text" class="searchSubnet form-control input-sm" name="subnetId" placeholder="'._('Select a subnet').'" value="'.$FirewallZones->subnetId.'"></td>';



print '</tr><tr>';
print '<td>'._('VLAN').'</td>';
print '<td><input type="text" class="form-control input-sm" name="vlanId" placeholder="'._('Select a VLAN').'" value="'.$FirewallZones->vlanId.'"></td>';

?>


	</table>
	</form>

	<?php
	//print delete warning
	if($_POST['action'] == "delete")	{ $Result->show("warning", "<strong>"._('Warning').":</strong> "._("removing Domain will also remove all referenced entries!"), false);}
	?>
</div>


<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editDomainSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="domain-edit-result"></div>
</div>