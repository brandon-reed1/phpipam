<h4><?php print _('Locations Map'); ?></h4>
<hr>

<?php if($admin && $User->settings->enableLocations=="1") { ?>
<div class="btn-group">
    <?php if($_GET['page']=="administration") { ?>
	<a href="" class='btn btn-sm btn-default editLocation' data-action='add' data-id='' style='margin-bottom:10px;'><i class='fa fa-plus'></i> <?php print _('Add location'); ?></a>
	<?php } else { ?>
	<a href="<?php print create_link("administration", "locations") ?>" class='btn btn-sm btn-default' style='margin-bottom:10px;'><i class='fa fa-pencil'></i> <?php print _('Manage'); ?></a>
	<?php } ?>
	<a href="<?php print create_link("tools", "locations") ?>" class='btn btn-sm btn-default' style='margin-bottom:10px;'> <?php print _('Locations list'); ?></a>
</div>
<br>
<?php } ?>

<?php

/**
 * Script to print locations
 ***************************/

# verify that user is logged in
$User->check_user_session();

# check that location support isenabled
if ($User->settings->enableLocations!="1") {
    $Result->show("danger", _("Locations module disabled."), false);
}
else {
    # fetch all locations
    $all_locations = $Tools->fetch_all_objects("locations", "id");

    # if none than print
    if($all_locations===false) {
        $Result->show("info","No Locations configured", false);
    }
    else {

        # sensor check
        if(isset($gmaps_api_key)) {
            $key = strlen($gmaps_api_key)>0 ? "?key=".$gmaps_api_key : "";
        }

        // get all
        foreach ($all_locations as $k=>$l) {
            // map used
            if(strlen($l->long)==0 && strlen($l->lat)==0 && strlen($l->address)==0 ) {
                // map not used
                unset($all_locations[$k]);
            }
        }

        // calculate
        if (sizeof($all_locations)>0) { ?>

            <script type="text/javascript" src="https://maps.google.com/maps/api/js<?php print $key; ?>"></script>
            <script type="text/javascript" src="js/1.2/gmaps.js"></script>
            <script type="text/javascript">
                $(document).ready(function() {
                    // init gmaps
                    var map = new GMaps({
                      div: '#gmap'
                    });

                    var bounds = [];

                    // add markers
                    <?php
                    foreach ($all_locations as $g) {
                        // address
                        if(strlen($g->address)>1) {
                            $html[] = "GMaps.geocode({";
                            $html[] = " address: '$g->address',";
                            $html[] = " callback: function(results, status) {";
                            $html[] = "     if (status == 'OK') {";
                            $html[] = "       var latlng = results[0].geometry.location;";
                            $html[] = "       bounds.push(latlng);";
                            $html[] = "       map.fitLatLngBounds(bounds);";
                            $html[] = "       map.addMarker({";
                            $html[] = "         title: '$g->name',";
                            $html[] = "         lat: latlng.lat(),";
                            $html[] = "         lng: latlng.lng(),";
                            $html[] = "         infoWindow: {";
                            $html[] = "             content: '<h5><a href=\'".create_link("tools", "locations", $g->id)."\'>$g->name</a></h5>, <span class=\'text-muted\'>$g->description</span>'";
                            $html[] = "         }";
                            $html[] = "       });";
                            $html[] = "     }";
                            $html[] = "  }";
                            $html[] = "});";
                        }
                        // latlng
                        elseif(strlen($g->lat)>0 && strlen($g->long)>0) {


                            $html[] = "GMaps.geocode({";
                            $html[] = " lat: '$g->lat',";
                            $html[] = " lng: '$g->long',";
                            $html[] = " callback: function(results, status) {";
                            $html[] = "     if (status == 'OK') {";
                            $html[] = "       var latlng = results[0].geometry.location;";
                            $html[] = "       bounds.push(latlng);";
                            $html[] = "       map.fitLatLngBounds(bounds);";
                            $html[] = "       map.addMarker({";
                            $html[] = "         title: '$g->name',";
                            $html[] = "         lat: latlng.lat(),";
                            $html[] = "         lng: latlng.lng(),";
                            $html[] = "         infoWindow: {";
                            $html[] = "             content: '<h5><a href=\'".create_link("tools", "locations", $g->id)."\'>$g->name</a></h5>, <span class=\'text-muted\'>$g->description</span>'";
                            $html[] = "         }";
                            $html[] = "       });";
                            $html[] = "     }";
                            $html[] = "  }";
                            $html[] = "});";
                        }
                    }
                    print implode("\n", $html);
                    ?>

                    function resize_map () {
                        var heights = window.innerHeight - 320;
                        $('#map_overlay').css("height", heights+"px");

                    }
                    resize_map();
                    window.onresize = function() {
                        resize_map();
                    };



                });
            </script>

            <div style="width:100%; height:1000px;" id="map_overlay">
            	<div id="gmap" style="width:100%; height:100%;"></div>
            </div>


        <?php
        # no coordinates
        }
        else {
            $Result->show("info","No Locations with coordinates configured", false);
        }
    }

    ?>
    </script>
    <?php
}
?>