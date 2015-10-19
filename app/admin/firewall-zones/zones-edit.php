<script type="text/javascript">
$(document).ready(function() {
	/* bootstrap switch */
	var switch_options = {
	    onColor: 'default',
	    offColor: 'default',
	    size: "mini"
	};
	$(".input-switch").bootstrapSwitch(switch_options);
});
</script>
<?php
// firewall zone fwzones-edit.php
// add, edit and delete firewall zones

# functions
require( dirname(__FILE__) . '/../../../functions/functions.php');

# initialize classes
$Database = new Database_PDO;
$User 	  = new User ($Database);
$Admin 	  = new Admin($Database);
$Subnets  = new Subnets ($Database);
$Sections = new Sections ($Database);
$Result   = new Result ();
$Zones    = new FirewallZones($Database);

# validate session parameters
$User->check_user_session();


// validate $_POST['id'] values
if (!preg_match('/^[0-9]+$/i', $_POST['id'])) 												 { $Result->show("danger", _("Invalid ID. Do not manipulate the POST values!"), true); }
// validate $_POST['action'] values
if ($_POST['action'] != 'add' && $_POST['action'] != 'edit' && $_POST['action'] != 'delete') { $Result->show("danger", _("Invalid action. Do not manipulate the POST values!"), true); }
// validate $_POST['sectionId'] values
if (isset($_POST['sectionId'])) {
	if (!preg_match('/^[0-9]+$/i', $_POST['sectionId'])) 									 { $Result->show("danger", _("Invalid section ID. Do not manipulate the POST values!"), true); }
}

// fetch module settings
$firewallZoneSettings = json_decode($User->settings->firewallZoneSettings,true);

// fetch old zone
if ($_POST['action'] != 'add') {
	$firewallZone = $Zones->get_zone($_POST['id']);
}

// disable edit on delete
$readonly = $_POST['action']=="delete" ? "readonly" : "";


// fetch all sections
$sections = $Sections->fetch_all_sections();

// fetch all layer2 domains
$vlan_domains = $Admin->fetch_all_objects("vlanDomains", "id");
?>
<!-- header  -->
<div class="pHeader"><?php print _('Add a firewall zone'); ?></div>
<!-- content -->
<div class="pContent">
<!-- form -->
<form id="zoneEdit">
<!-- table -->
<table class="table table-noborder table-condensed">
	<!-- zone name -->
	<tr>
		<td style="width:150px;">
			<?php print _('Zone name'); ?>
		</td>

		<?php
		// transmit the action and firewall zone id
		print '<input type="hidden" name="action" value="'.$_POST['action'].'">';
		print '<input type="hidden" name="id" value="'.$firewallZone->id.'">';
		// possible zoneGenerator values:
		//		0 == autogenerated decimal name
		//		1 == autogenerated hex name
		//		2 == free text name

		if ($_POST['action'] == 'add') {
			// check if we have to autogenerate a zone name or if we have to display a text box
			if ($firewallZoneSettings['zoneGenerator'] == 2) {
				print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('Zone name (Only alphanumeric and special characters like .-_ and space.)').'" value="'.$firewallZone->zone.'" '.$readonly.'></td>';
			} else {
				print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('The zone name will be automatically generated').'" value="'.$firewallZone->zone.'" '.$readonly.' disabled></td>';
			}
		} else {
			if ($firewallZone->generator == 1) {
				print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('Zone name').'" readonly value="'.$firewallZone->zone.'"></td>';
			} elseif ($firewallZone->generator != 2) {
				print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('Zone name').'" readonly value="'.$firewallZone->zone.'"></td>';
			} else {
				print '<td><input type="text" class="form-control input-sm" name="zone" placeholder="'._('Zone name (Only alphanumeric and special characters like .-_ and space.)').'" value="'.$firewallZone->zone.'" '.$readonly.'></td>';
			}
		}
		?>
		<input type="hidden" name="generator" value="<?php print $firewallZoneSettings['zoneGenerator']; ?>">

	</tr>
	<tr>
		<!-- zone indicator -->
		<td rowspan="2">
			<?php print _('Indicator'); ?>
		</td>
		<td>
			<div class="radio" style="margin-top:5px;margin-bottom:2px;">
				<label>
					<input type="radio" name="indicator" value="0" <?php (($firewallZone->indicator == false) ? print 'checked' : print ''); ?> ><?php print '<span class="fa fa-home"  title="'._('Own zone').'"></span> '._('Own Zone'); ?>
				</label>
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<div class="radio" style="margin-top:2px;margin-bottom:2px;">
				<label>
					<input type="radio" name="indicator" value="1" <?php (($firewallZone->indicator == true) ? print 'checked' : print ''); ?> ><?php print '<span class="fa fa-group"  title="'._('Customer zone').'"></span> '._('Customer Zone'); ?>
				</label>
			</div>
		</td>
	</tr>
	<?php if($firewallZone->generator != 2 && $firewallZoneSettings['zoneGenerator'] != 2) { ?>
		<tr>
			<td>
				<?php print _('Padding'); ?>
			</td>
			<td>
				<input type="checkbox" class="input-switch" name="padding" <?php if($_POST['action'] == 'edit' && $firewallZone->padding == 1){ print 'checked';} elseif($_POST['action'] == 'edit' && $firewallZone->padding == 0) {} elseif ($firewallZoneSettings['padding'] == 'on'){print 'checked';}?>>
			</td>
		</tr>
	<?php } ?>
	<tr>
		<!-- description -->
		<td>
			<?php print _('Description'); ?>
		</td>
		<td>
			<input type="text" class="form-control input-sm" name="description" placeholder="<?php print _('Zone description'); ?>" value="<?php print $firewallZone->description; ?>">
		</td>
	</tr>
	<tr>
		<!-- section  -->
		<td>
			<?php print _('Section'); ?>
		</td>
		<td>
			<select name="sectionId" class="firewallZoneSection form-control input-sm input-w-auto input-max-200">
			<?php
			if(sizeof($sections)>1){
				print '<option value="0">'._('No section selected').'</option>';
			}
			foreach ($sections as $section) {
				// select the section if already configured
				if ($firewallZone->sectionId == $section->id) {
					print '<option value="'.$section->id.'" selected>'. $section->name.' ('.$section->description.')</option>';
				} else {
					print '<option value="'.$section->id.'">'.			$section->name.' ('.$section->description.')</option>';
				}
			}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<!-- subnet -->
		<td>
			<?php print _('Subnet'); ?>
		</td>
			<?php
			// display the subnet if already configured
			if ($firewallZone->sectionId) {
				print '<td><div class="sectionSubnets">';
				print $Subnets->print_mastersubnet_dropdown_menu($firewallZone->sectionId,$firewallZone->subnetId);
				print '</div></td>';
			} else {
				// if there is only one section, fetch the subnets of that section
				if(sizeof($sections)<=1){
					print '<td>';
					print $Subnets->print_mastersubnet_dropdown_menu($sections[0]->id,$firewallZone->subnetId);
					print '</td>';
				} else {
					// if there are more than one section, use ajax to fetch the subnets of the selected section
					print '<td><div class="sectionSubnets"></div></td>';
				}
			}
			?>
	</tr>
	<tr>
		<!-- layer2 domain -->
		<td>
			<?php _('L2 Domain'); ?>
		</td>
		<td>
			<select name="vlanDomain" class="firewallZoneVlan form-control input-sm input-w-auto input-max-200">
			<option value="0"><?php print _('No L2 domain selected'); ?></option>
			<?php
			foreach ($vlan_domains as $vlan_domain) {
				if ($firewallZone->domainId == $vlan_domain->id) {
					print '<option value="'.$vlan_domain->id.'" selected>'. $vlan_domain->name.' ('.$vlan_domain->description.')</option>';
				} else {
					print '<option value="'.$vlan_domain->id.'">'.			$vlan_domain->name.' ('.$vlan_domain->description.')</option>';
				}
			}
			?>
			</select>
		</td>
	</tr>
	<tr>
		<!-- vlan -->
		<td>
			<?php print _('VLAN'); ?>
		</td>
			<?php
			// if there is only one layer2 domain or if there is one already selected, fetch the vlans of that domain
			if($firewallZone->vlanId){
				// fetch all vlans of that particular domain
				$vlans = $Admin->fetch_multiple_objects("vlans", "domainId", $firewallZone->domainId, "number");
				print '<td><div class="domainVlans"><select name="vlanId" class="form-control input-sm input-w-auto input-max-200">';
				foreach ($vlans as $vlan) {
					if ($firewallZone->vlanId == $vlan->vlanId) {
						print '<option value="'.$vlan->vlanId.'" selected>'.$vlan->number.' ('.$vlan->description.')</option>';
					} else {
						print '<option value="'.$vlan->vlanId.'">'.			$vlan->number.' ('.$vlan->name.' - '.$vlan->description.')</option>';
					}
				}
				print '</select></div></td>';
			} else {
				// if there are more than one section, use ajax to fetch the subnets of the selected section
				print '<td><div class="domainVlans"></div></td>';
			}
			?>
	</tr>
</table>
</form>

<?php
//print delete warning
if($_POST['action'] == "delete"){
	$Result->show("warning", "<strong>"._('Warning').":</strong> "._("Removing this firewall zone will also remove all referenced mappings!"), false);
}
?>
</div>
<!-- footer -->
<div class="pFooter">
	<div class="btn-group">
		<button class="btn btn-sm btn-default hidePopups"><?php print _('Cancel'); ?></button>
		<button class="btn btn-sm btn-default <?php if($_POST['action']=="delete") { print "btn-danger"; } else { print "btn-success"; } ?>" id="editZoneSubmit"><i class="fa <?php if($_POST['action']=="add") { print "fa-plus"; } else if ($_POST['action']=="delete") { print "fa-trash-o"; } else { print "fa-check"; } ?>"></i> <?php print ucwords(_($_POST['action'])); ?></button>
	</div>
	<!-- result -->
	<div class="zones-edit-result"></div>
</div>